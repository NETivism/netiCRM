<?php

require_once 'sql-parser/autoload.php';

use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Components\Condition;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;
use PhpMyAdmin\SqlParser\Statements\InsertStatement;
use PhpMyAdmin\SqlParser\Statements\UpdateStatement;
use PhpMyAdmin\SqlParser\Statements\DeleteStatement;
use PhpMyAdmin\SqlParser\Statements\WithStatement;
use PhpMyAdmin\SqlParser\Utils\Formatter;

class CRM_Utils_SqlParser {

  private $query;
  private $allowlist;
  private $parser;
  private $errors = [];
  private $isValid = TRUE;

  /**
   * Parse and validate query
   *
   * Validate all touched tables, fields and statements are in allowed list
   *
   * @param string $query SQL query to validate
   * @param array $allowlist Array with 'table', 'field', and 'statement' keys containing allowed values
   */
  public function __construct(string $query, array $allowlist = []) {
    $this->query = $query;
    $this->allowlist = $allowlist;
    if (empty($this->allowlist['statement'])) {
      $this->allowlist['statement'] = 'SELECT';
    }
    $this->parser = new Parser($query);

    if (!empty($this->parser->errors)) {
      $this->isValid = FALSE;
      $this->errors = array_merge($this->errors, $this->parser->errors);
      return;
    }

    $this->collectAliases();
    $this->validateQuery();
  }

  /**
   * Check if the query is valid according to allowlist
   *
   * @return bool
   */
  public function isValid(): bool {
    return $this->isValid;
  }

  /**
   * Get validation errors
   *
   * @return array
   */
  public function getErrors(): array {
    return $this->errors;
  }

  /**
   * Get rebuilt query from parsed statements
   *
   * @param bool $format
   *
   * @return string|null
   */
  public function getQuery($format = FALSE): ?string {
    if (empty($this->parser->statements)) {
      return NULL;
    }

    $sql = $this->parser->statements[0]->build();
    if ($format) {
      return Formatter::format($sql, ['type' => 'text']);
    }
    return $sql;
  }

  /**
   * Collect all aliases from the query and add to allowlist
   */
  private function collectAliases(): void {
    if (empty($this->parser->statements)) {
      return;
    }

    foreach ($this->parser->statements as $statement) {
      if ($statement instanceof SelectStatement) {
        $this->collectSelectAliases($statement);
        $this->collectAllSubqueryAliases($statement);
      }
      elseif ($statement instanceof WithStatement) {
        $this->collectWithAliases($statement);
      }
    }
  }

  /**
   * Recursively collect all aliases from subqueries before validation
   *
   * @param SelectStatement $statement
   */
  private function collectAllSubqueryAliases(SelectStatement $statement): void {
    if ($statement->from) {
      foreach ($statement->from as $fromClause) {
        if ($fromClause instanceof Expression && !empty($fromClause->expr) && $fromClause->subquery) {
          $this->collectSubqueryAliases($fromClause->expr);
        }
      }
    }

    if ($statement->expr) {
      foreach ($statement->expr as $expression) {
        if (!empty($expression->expr) && $expression->subquery) {
          $this->collectSubqueryAliases($expression->expr);
        }
      }
    }

    $this->collectConditionSubqueryAliases($statement->where);
    $this->collectConditionSubqueryAliases($statement->having);

    // Also collect aliases from UNION statements
    if (!empty($statement->union)) {
      foreach ($statement->union as $unionTuple) {
        if (is_array($unionTuple) && isset($unionTuple[1]) && $unionTuple[1] instanceof SelectStatement) {
          $this->collectSelectAliases($unionTuple[1]);
          $this->collectConditionSubqueryAliases($unionTuple[1]->where);
          $this->collectConditionSubqueryAliases($unionTuple[1]->having);
        }
      }
    }
  }

  /**
   * Collect subquery aliases from condition arrays (WHERE, HAVING)
   *
   * @param array|null $conditions
   */
  private function collectConditionSubqueryAliases(?array $conditions): void {
    if (empty($conditions)) {
      return;
    }

    foreach ($conditions as $condition) {
      if ($condition instanceof Condition && !$condition->isOperator) {
        if (is_string($condition->expr)) {
          $subquery = $this->extractSubquery($condition->expr);
          if ($subquery !== NULL) {
            $this->collectSubqueryAliases($subquery);
          }
        }
      }
    }
  }

  /**
   * Collect table names and field aliases from a WITH (CTE) statement.
   *
   * Each CTE name is added to the table allowlist so the final SELECT and
   * other CTEs can reference it.  Field aliases produced inside each CTE
   * body are also collected so they can appear in the outer SELECT.
   *
   * @param WithStatement $statement
   */
  private function collectWithAliases(WithStatement $statement): void {
    // Add every CTE name to the table allowlist
    foreach ($statement->withers as $name => $wither) {
      $this->addToAllowlist('table', $name);
      // Collect field aliases produced by each CTE body
      if (!empty($wither->statement->statements)) {
        foreach ($wither->statement->statements as $cteStmt) {
          if ($cteStmt instanceof SelectStatement) {
            $this->collectSelectAliases($cteStmt);
            $this->collectAllSubqueryAliases($cteStmt);
          }
        }
      }
    }
    // Collect aliases from the main query that follows the CTEs
    if (!empty($statement->cteStatementParser->statements)) {
      foreach ($statement->cteStatementParser->statements as $mainStmt) {
        if ($mainStmt instanceof SelectStatement) {
          $this->collectSelectAliases($mainStmt);
          $this->collectAllSubqueryAliases($mainStmt);
        }
      }
    }
  }

  /**
   * Validate a WITH (CTE) statement.
   *
   * Validates each CTE body as an independent SELECT, then validates the
   * final SELECT that consumes the CTEs.  CTE names are pre-added to the
   * table allowlist so cross-references between CTEs and the main query
   * are accepted.
   *
   * @param WithStatement $statement
   */
  private function validateWithStatement(WithStatement $statement): void {
    // Validate each CTE body
    foreach ($statement->withers as $name => $wither) {
      if (!empty($wither->statement->statements)) {
        foreach ($wither->statement->statements as $cteStmt) {
          if ($cteStmt instanceof SelectStatement) {
            $this->validateSelectStatement($cteStmt);
          }
        }
      }
    }
    // Validate the final SELECT / statement
    if (!empty($statement->cteStatementParser->statements)) {
      foreach ($statement->cteStatementParser->statements as $mainStmt) {
        if ($mainStmt instanceof SelectStatement) {
          $this->validateSelectStatement($mainStmt);
        }
        else {
          $type = $this->getStatementType($mainStmt);
          $this->addError("CTE final statement type '{$type}' is not in the allowlist");
        }
      }
    }
  }

  /**
   * Collect aliases from SELECT statement
   *
   * @param SelectStatement $statement
   */
  private function collectSelectAliases(SelectStatement $statement): void {
    if ($statement->from) {
      foreach ($statement->from as $fromClause) {
        if ($fromClause instanceof Expression) {
          if (!empty($fromClause->alias)) {
            $this->addToAllowlist('table', $fromClause->alias);
          }

          if (!empty($fromClause->expr) && $fromClause->subquery) {
            $this->collectSubqueryAliases($fromClause->expr);
          }
        }
        else {
          // Collect table alias (like 'cc' from 'civicrm_contribution cc')
          if (!empty($fromClause->alias)) {
            $this->addToAllowlist('table', $fromClause->alias);
          }
        }
      }
    }

    if ($statement->expr) {
      foreach ($statement->expr as $expression) {
        if (!empty($expression->alias)) {
          $this->addToAllowlist('field', $expression->alias);
        }
      }
    }

    if ($statement->join) {
      foreach ($statement->join as $join) {
        // Collect JOIN table alias (like 'p' from 'civicrm_participant_payment p')
        if (!empty($join->expr)) {
          if ($join->expr instanceof Expression && !empty($join->expr->alias)) {
            $this->addToAllowlist('table', $join->expr->alias);
          }
          elseif (!empty($join->expr->alias)) {
            $this->addToAllowlist('table', $join->expr->alias);
          }
        }
      }
    }
  }

  /**
   * Collect aliases from subquery
   *
   * @param string $subquery
   */
  private function collectSubqueryAliases(string $subquery): void {
    if (empty($subquery)) {
      return;
    }

    $subParser = new Parser($subquery);
    if (!empty($subParser->statements)) {
      foreach ($subParser->statements as $statement) {
        if ($statement instanceof SelectStatement) {
          $this->collectSelectAliases($statement);
        }
      }
    }
  }

  /**
   * Add item to allowlist if not already present
   *
   * @param string $type
   * @param string $value
   */
  private function addToAllowlist(string $type, string $value): void {
    if (!isset($this->allowlist[$type])) {
      $this->allowlist[$type] = [];
    }

    if (!is_array($this->allowlist[$type])) {
      $this->allowlist[$type] = [$this->allowlist[$type]];
    }

    $value = $this->normalizeIdentifier($value);
    if (!in_array($value, $this->allowlist[$type], TRUE)) {
      $this->allowlist[$type][] = $value;
    }
  }

  /**
   * Validate the parsed query
   */
  private function validateQuery(): void {
    if (empty($this->parser->statements)) {
      $this->addError('No valid SQL statements found');
      return;
    }

    foreach ($this->parser->statements as $statement) {
      $this->validateStatement($statement);
    }
  }

  /**
   * Validate a single SQL statement
   *
   * @param mixed $statement
   */
  private function validateStatement($statement): void {
    $statementType = $this->getStatementType($statement);

    if ($statement instanceof WithStatement) {
      // WITH (CTE) is permitted when SELECT is in the statement allowlist,
      // since the final query is always a SELECT.
      $this->validateStatementType('SELECT');
      $this->validateWithStatement($statement);
      return;
    }

    $this->validateStatementType($statementType);

    if ($statement instanceof SelectStatement) {
      $this->validateSelectStatement($statement);
    }
    else {
      $this->addError("Statement type '{$statementType}' is not in the allowlist");
    }
  }

  /**
   * Validate SELECT statement
   *
   * @param SelectStatement $statement
   */
  private function validateSelectStatement(SelectStatement $statement): void {
    if ($statement->from) {
      foreach ($statement->from as $fromClause) {
        if ($fromClause instanceof Expression) {
          $this->validateExpression($fromClause, 'select');
        }
        else {
          $this->validateTable($fromClause->table);
        }
      }
    }

    if ($statement->expr) {
      foreach ($statement->expr as $expression) {
        if ($expression instanceof Expression) {
          $this->validateExpression($expression, 'select');
        }
      }
    }

    if ($statement->where) {
      foreach ($statement->where as $condition) {
        $this->validateCondition($condition);
      }
    }

    if ($statement->join) {
      foreach ($statement->join as $join) {
        $this->validateTable($join->expr->table);
        if ($join->on) {
          foreach ($join->on as $condition) {
            $this->validateCondition($condition);
          }
        }
      }
    }

    if ($statement->having) {
      foreach ($statement->having as $condition) {
        $this->validateCondition($condition);
      }
    }

    if (!empty($statement->union)) {
      // Each union element is a tuple: [string $type, SelectStatement $stmt]
      foreach ($statement->union as $unionTuple) {
        if (is_array($unionTuple) && isset($unionTuple[1]) && $unionTuple[1] instanceof SelectStatement) {
          $this->validateSelectStatement($unionTuple[1]);
        }
      }
    }
  }

  /**
   * Validate table name against allowlist
   *
   * @param string $tableName
   */
  private function validateTable(?string $tableName): void {
    if (empty($tableName)) {
      return;
    }

    $tableName = $this->normalizeIdentifier($tableName);

    if (isset($this->allowlist['table'])) {
      $allowedTables = is_array($this->allowlist['table'])
        ? $this->allowlist['table']
        : [$this->allowlist['table']];

      if (!in_array($tableName, $allowedTables, TRUE)) {
        $this->addError("Table '{$tableName}' is not in the allowlist");
      }
    }
  }

  /**
   * Validate field name against allowlist
   *
   * @param string $fieldName
   */
  private function validateField(?string $fieldName): void {
    if (empty($fieldName)) {
      return;
    }
    if ($fieldName === '*') {
      $this->addError("Field '{$fieldName}' is not in the allowlist");
    }

    $fieldName = $this->normalizeIdentifier($fieldName);

    if (isset($this->allowlist['field'])) {
      $allowedFields = is_array($this->allowlist['field'])
        ? $this->allowlist['field']
        : [$this->allowlist['field']];

      if (!in_array($fieldName, $allowedFields, TRUE)) {
        $this->addError("Field '{$fieldName}' is not in the allowlist");
      }
    }
  }

  /**
   * Validate expression (handles fields, functions, subqueries)
   *
   * @param Expression $expression
   */
  private function validateExpression(?Expression $expression, ?string $context): void {
    if (!$expression) {
      return;
    }

    if ($context === 'select') {
      if (!empty($expression->function) &&
          (!$expression->subquery || $this->isSqlFunction($expression->expr))) {
        // Function call (bare or window). Check function first because in lower MySQL
        // contexts (< 8.0) window function names (NTILE, ROW_NUMBER, …) are not
        // recognised as function keywords, so the parser also sets expression->column
        // to the function name — we must not treat that as a field reference.
        // Use expr (e.g. "NTILE(4) OVER (...)") so validateSqlFunction can extract
        // the function name via the "(" character.
        //
        // Note: when OVER (PARTITION BY ...) is present, PhpMyAdmin sets
        // expression->subquery = 'PARTITION' (because PARTITION is in its statement
        // parser map). We detect real subqueries by checking that expr does NOT start
        // with a function call pattern.
        $this->validateSqlFunction($expression->expr);
        // Validate fields referenced inside OVER (...) for window functions
        if (preg_match('/\bOVER\s*\(/i', $expression->expr)) {
          $this->validateWindowOverClause($expression->expr);
        }
      }
      elseif (!empty($expression->column)) {
        // Skip string / numeric literals used as SELECT values (e.g. 'label' AS alias).
        // The raw token (including quotes) is stored in expression->expr.
        if (!empty($expression->expr) && $this->isLiteral($expression->expr)) {
          // Literal value — not a field reference, no validation needed
        }
        elseif ($this->isSqlFunction($expression->column)) {
          // Column holds a function name without parentheses (edge case)
          $this->validateSqlFunction($expression->column);
        }
        else {
          $this->validateField($expression->column);
        }
      }
      elseif (!$expression->subquery && !empty($expression->expr) && trim($expression->expr) === '*') {
        // Wildcard SELECT * — always reject
        $this->validateField('*');
      }
    }

    if (!empty($expression->table)) {
      $this->validateTable($expression->table);
    }

    // Only validate subqueries if expr is not a SQL function call
    // This prevents treating function arguments (like "INTERVAL 1 DAY") as subqueries
    if (!empty($expression->expr) && $expression->subquery) {
      if (!$this->isSqlFunction($expression->expr)) {
        $this->validateSubquery($expression->expr);
      }
    }
  }

  /**
   * Validate condition (handles WHERE and JOIN conditions)
   *
   * @param Condition $condition
   */
  private function validateCondition(?Condition $condition): void {
    if (!$condition) {
      return;
    }

    // Check if condition contains a subquery (e.g., IN (SELECT ...))
    if (!empty($condition->expr)) {
      if (is_string($condition->expr)) {
        $subquery = $this->extractSubquery($condition->expr);
        if ($subquery !== NULL) {
          // Validate left operand against outer query allowlist.
          // Skip when the entire leftOperand IS the EXISTS/NOT EXISTS expression
          // (i.e. there is no real left-hand column — EXISTS is the whole condition).
          if (!empty($condition->leftOperand) && !preg_match('/^(NOT\s+)?EXISTS\s*\(/i', trim($condition->leftOperand))) {
            $this->validateOperand($condition->leftOperand);
          }
          // Validate subquery independently
          $this->validateSubquery($subquery);
          return;
        }
      }

      if (is_array($condition->expr)) {
        foreach ($condition->expr as $expression) {
          if ($expression instanceof Expression) {
            $this->validateExpression($expression, 'condition');
          }
        }
      }
    }

    // Validate left operand (e.g., "p.contribution_id")
    if (!empty($condition->leftOperand)) {
      $this->validateOperand($condition->leftOperand);
    }

    // Validate right operand (e.g., "cc.id")
    if (!empty($condition->rightOperand)) {
      $this->validateOperand($condition->rightOperand);
    }
  }

  /**
   * Validate an operand (left or right side of a condition)
   *
   * @param string $operand
   */
  private function validateOperand(string $operand): void {
    if ($this->isLiteral($operand)) {
      return;
    }

    // If it's a SQL function, validate the function and skip field validation
    if ($this->isSqlFunction($operand)) {
      $this->validateSqlFunction($operand);
      $this->validateFunctionArgFields($operand);
      return;
    }

    $parsed = $this->parseIdentifier($operand);
    if ($parsed['table']) {
      $this->validateTable($parsed['table']);
    }
    $this->validateField($parsed['field']);
  }

  /**
   * Validate subquery recursively
   *
   * @param string $subquery SQL string from expression->expr
   */
  private function validateSubquery(?string $subquery): void {
    if (empty($subquery)) {
      return;
    }

    // Pass the current allowlist (which now includes all collected aliases) to subquery
    $subParser = new self($subquery, $this->allowlist);
    if (!$subParser->isValid()) {
      foreach ($subParser->getErrors() as $error) {
        $this->addError("Subquery error: {$error}");
      }
    }
  }

  /**
   * Extract subquery SQL from a condition expression string
   *
   * Detects patterns like: IN (SELECT ...), EXISTS (SELECT ...), etc.
   *
   * @param string $expr
   * @return string|null The subquery SQL without outer parentheses, or null
   */
  private function extractSubquery(string $expr): ?string {
    $pos = stripos($expr, 'SELECT');
    if ($pos === FALSE) {
      return NULL;
    }

    // Find the opening parenthesis before SELECT
    $parenStart = strrpos(substr($expr, 0, $pos), '(');
    if ($parenStart === FALSE) {
      return NULL;
    }

    // Match balanced parentheses from parenStart
    $depth = 0;
    $len = strlen($expr);
    for ($i = $parenStart; $i < $len; $i++) {
      if ($expr[$i] === '(') {
        $depth++;
      }
      elseif ($expr[$i] === ')') {
        $depth--;
        if ($depth === 0) {
          return substr($expr, $parenStart + 1, $i - $parenStart - 1);
        }
      }
    }

    return NULL;
  }

  /**
   * Normalize identifier (remove quotes, handle aliases)
   *
   * @param string $identifier
   * @return string
   */
  private function normalizeIdentifier(string $identifier): string {
    $identifier = trim($identifier);

    if (preg_match('/^[`"\'](.+)[`"\']$/', $identifier, $matches)) {
      $identifier = $matches[1];
    }

    if (strpos($identifier, ' AS ') !== FALSE) {
      $parts = explode(' AS ', $identifier, 2);
      $identifier = trim($parts[0]);
    }

    return $identifier;
  }

  /**
   * Extract table and field from identifier
   *
   * @param string $identifier
   * @return array ['table' => string|null, 'field' => string]
   */
  private function parseIdentifier(string $identifier): array {
    $identifier = $this->normalizeIdentifier($identifier);

    // Strip leading parentheses (can appear when grouped WHERE conditions like
    // "(table.field = val OR ...)" are parsed and the paren ends up in the operand)
    $identifier = ltrim($identifier, '(');

    // Remove operators by cutting at first space
    if (strpos($identifier, ' ') !== FALSE) {
      $identifier = trim(substr($identifier, 0, strpos($identifier, ' ')));
    }

    if (strpos($identifier, '.') !== FALSE) {
      $parts = explode('.', $identifier, 2);
      return ['table' => trim($parts[0]), 'field' => trim($parts[1])];
    }

    return ['table' => NULL, 'field' => $identifier];
  }

  /**
   * Check if string looks like a SQL function call
   *
   * @param string $value
   * @return bool True if it matches function pattern (regardless of whether it's allowed)
   */
  private function isSqlFunction(string $value): bool {
    $value = trim($value);
    // Check if it matches function call pattern: NAME(
    return preg_match('/^[A-Za-z_][A-Za-z0-9_]*\s*\(/', $value) === 1;
  }

  /**
   * Validate SQL function against whitelist/blacklist
   *
   * Only allow safe, read-only SQL functions that cannot be used for:
   * - System information disclosure
   * - File operations
   * - Data modification
   * - Code execution
   *
   * @param string $value
   * @return void Adds error if function is not allowed
   */
  private function validateSqlFunction(string $value): void {
    $value = trim($value);

    // Extract function name from the value
    if (!preg_match('/^([A-Za-z_][A-Za-z0-9_]*)\s*\(/', $value, $matches)) {
      return;
    }

    $functionName = strtoupper($matches[1]);

    // Whitelist of allowed SQL functions (read-only, safe functions)
    $allowedFunctions = [
      // Aggregate functions
      'COUNT', 'SUM', 'AVG', 'MAX', 'MIN', 'GROUP_CONCAT',

      // Date and time functions
      'NOW', 'CURDATE', 'CURTIME', 'DATE', 'TIME', 'YEAR', 'MONTH', 'DAY',
      'HOUR', 'MINUTE', 'SECOND', 'DAYOFWEEK', 'DAYOFMONTH', 'DAYOFYEAR',
      'WEEK', 'WEEKDAY', 'YEARWEEK', 'QUARTER', 'DATE_ADD', 'DATE_SUB',
      'DATEDIFF', 'TIMESTAMPDIFF', 'DATE_FORMAT', 'STR_TO_DATE', 'UNIX_TIMESTAMP',
      'FROM_UNIXTIME', 'LAST_DAY', 'MAKEDATE',

      // String functions
      'CONCAT', 'CONCAT_WS', 'SUBSTRING', 'SUBSTR', 'LEFT', 'RIGHT', 'LENGTH',
      'CHAR_LENGTH', 'LOWER', 'UPPER', 'TRIM', 'LTRIM', 'RTRIM', 'REPLACE',
      'REVERSE', 'REPEAT', 'SPACE', 'LPAD', 'RPAD', 'LOCATE', 'POSITION',
      'INSTR', 'STRCMP', 'FIELD', 'FIND_IN_SET', 'ASCII', 'CHAR',

      // Numeric functions
      'ABS', 'CEIL', 'CEILING', 'FLOOR', 'ROUND', 'TRUNCATE', 'MOD', 'POW',
      'POWER', 'SQRT', 'EXP', 'LN', 'LOG', 'LOG2', 'LOG10', 'PI', 'RAND',
      'SIGN', 'DEGREES', 'RADIANS', 'SIN', 'COS', 'TAN', 'ASIN', 'ACOS', 'ATAN',

      // Conditional functions
      'IF', 'IFNULL', 'NULLIF', 'COALESCE', 'CASE',

      // Conversion functions
      'CAST', 'CONVERT',

      // Other safe functions
      'ISNULL', 'GREATEST', 'LEAST',

      // Window functions (used with OVER clause)
      'ROW_NUMBER', 'RANK', 'DENSE_RANK', 'PERCENT_RANK', 'CUME_DIST',
      'NTILE', 'LAG', 'LEAD', 'FIRST_VALUE', 'LAST_VALUE', 'NTH_VALUE',
    ];

    // Blocked dangerous functions (explicitly blocked, even if somehow added to allowlist)
    $blockedFunctions = [
      // System/Database information functions (potential information disclosure)
      'DATABASE', 'USER', 'CURRENT_USER', 'SESSION_USER', 'SYSTEM_USER', 'VERSION',
      'CONNECTION_ID', 'SCHEMA', 'BENCHMARK',

      // File operations (dangerous)
      'LOAD_FILE', 'INTO_OUTFILE', 'INTO_DUMPFILE',

      // Encryption/Hashing (could be used for attacks)
      'ENCRYPT', 'PASSWORD', 'MD5', 'SHA', 'SHA1', 'SHA2',

      // System execution (dangerous)
      'SLEEP', 'GET_LOCK', 'RELEASE_LOCK',
    ];

    // First check if it's in the blocked list
    if (in_array($functionName, $blockedFunctions, TRUE)) {
      $this->addError("SQL function '{$functionName}' is not allowed (blocked for security)");
      return;
    }

    // Then check if it's in the allowed list
    if (!in_array($functionName, $allowedFunctions, TRUE)) {
      $this->addError("SQL function '{$functionName}' is not in the allowed function list");
      return;
    }
  }

  /**
   * Check if string is a literal value (number, string, date)
   *
   * @param string $value
   * @return bool
   */
  private function isLiteral(string $value): bool {
    $value = trim($value);

    // Check for quoted strings
    if (preg_match('/^["\'].*["\']$/', $value)) {
      return TRUE;
    }

    // Check for numbers
    if (is_numeric($value)) {
      return TRUE;
    }

    // Check for date/timestamp literals
    if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Get statement type as string
   *
   * @param mixed $statement
   * @return string
   */
  private function getStatementType($statement): string {
    if ($statement instanceof SelectStatement) {
      return 'SELECT';
    }
    elseif ($statement instanceof InsertStatement) {
      return 'INSERT';
    }
    elseif ($statement instanceof UpdateStatement) {
      return 'UPDATE';
    }
    elseif ($statement instanceof DeleteStatement) {
      return 'DELETE';
    }
    elseif ($statement instanceof WithStatement) {
      return 'WITH';
    }
    else {
      $className = get_class($statement);
      $parts = explode('\\', $className);
      $shortName = end($parts);
      return strtoupper(str_replace('Statement', '', $shortName));
    }
  }

  /**
   * Validate statement type against allowlist
   *
   * @param string $statementType
   */
  private function validateStatementType(string $statementType): void {
    if (isset($this->allowlist['statement'])) {
      $allowedStatements = is_array($this->allowlist['statement'])
        ? $this->allowlist['statement']
        : [$this->allowlist['statement']];

      $allowedStatements = array_map('strtoupper', $allowedStatements);

      if (!in_array(strtoupper($statementType), $allowedStatements, TRUE)) {
        $this->addError("Statement type '{$statementType}' is not in the allowlist");
      }
    }
  }

  /**
   * Validate fields referenced inside a SQL function's arguments.
   *
   * Recursively handles nested function calls (e.g. ROUND(COUNT(x), 1)).
   *
   * @param string $funcExpr Full function expression, e.g. "SUM(cc.total_amount)"
   */
  private function validateFunctionArgFields(string $funcExpr): void {
    $inner = $this->extractFunctionInner($funcExpr);
    if ($inner === NULL) {
      return;
    }

    // Strip leading DISTINCT keyword
    $inner = preg_replace('/^\s*DISTINCT\s+/i', '', $inner);

    foreach ($this->splitByComma($inner) as $arg) {
      $arg = trim($arg);
      if ($arg === '' || $this->isLiteral($arg)) {
        continue;
      }
      // Skip MySQL INTERVAL expressions (e.g. "INTERVAL 6 MONTH", "INTERVAL 1 DAY")
      if (preg_match('/^INTERVAL\s+/i', $arg)) {
        continue;
      }
      if ($this->isSqlFunction($arg)) {
        $this->validateSqlFunction($arg);
        $this->validateFunctionArgFields($arg);
        continue;
      }
      // Skip pure numeric/operator tokens (e.g. "100.0", "* 100.0 /")
      if (preg_match('/^[\d\s\+\-\*\/\%\.]+$/', $arg)) {
        continue;
      }
      $parsed = $this->parseIdentifier($arg);
      if (!empty($parsed['table'])) {
        $this->validateTable($parsed['table']);
      }
      if (!empty($parsed['field']) && $parsed['field'] !== '*') {
        $this->validateField($parsed['field']);
      }
    }
  }

  /**
   * Extract the content between the first opening parenthesis and its
   * matching closing parenthesis in a function expression.
   *
   * @param string $funcExpr
   * @return string|null Inner content, or NULL if no balanced parens found.
   */
  private function extractFunctionInner(string $funcExpr): ?string {
    $open = strpos($funcExpr, '(');
    if ($open === FALSE) {
      return NULL;
    }
    $depth = 0;
    $len = strlen($funcExpr);
    for ($i = $open; $i < $len; $i++) {
      if ($funcExpr[$i] === '(') {
        $depth++;
      }
      elseif ($funcExpr[$i] === ')') {
        $depth--;
        if ($depth === 0) {
          return substr($funcExpr, $open + 1, $i - $open - 1);
        }
      }
    }
    return NULL;
  }

  /**
   * Validate field references inside an OVER (...) window clause.
   *
   * Handles PARTITION BY and ORDER BY fields, e.g.:
   *   NTILE(4) OVER (PARTITION BY region ORDER BY total_donated ASC)
   *
   * @param string $funcExpr Full window function expression
   */
  private function validateWindowOverClause(string $funcExpr): void {
    // Locate "OVER (" in the expression
    if (!preg_match('/\bOVER\s*\(/i', $funcExpr, $match, PREG_OFFSET_CAPTURE)) {
      return;
    }

    // Find the opening paren of the OVER clause
    $overStart = $match[0][1] + strlen($match[0][0]) - 1;
    $depth = 0;
    $len = strlen($funcExpr);
    $inner = '';

    for ($i = $overStart; $i < $len; $i++) {
      if ($funcExpr[$i] === '(') {
        $depth++;
        if ($depth === 1) {
          continue; // skip the opening paren itself
        }
      }
      elseif ($funcExpr[$i] === ')') {
        $depth--;
        if ($depth === 0) {
          break;
        }
      }
      if ($depth > 0) {
        $inner .= $funcExpr[$i];
      }
    }

    if (empty($inner)) {
      return;
    }

    // Strip clause keywords to leave only field tokens
    $inner = preg_replace('/\b(PARTITION\s+BY|ORDER\s+BY|ROWS\s+BETWEEN|RANGE\s+BETWEEN|UNBOUNDED\s+PRECEDING|UNBOUNDED\s+FOLLOWING|CURRENT\s+ROW|AND)\b/i', ',', $inner);
    $inner = preg_replace('/\b(ASC|DESC|NULLS\s+FIRST|NULLS\s+LAST)\b/i', '', $inner);

    foreach ($this->splitByComma($inner) as $field) {
      $field = trim($field);
      if ($field === '' || $this->isLiteral($field)) {
        continue;
      }
      $parsed = $this->parseIdentifier($field);
      if (!empty($parsed['table'])) {
        $this->validateTable($parsed['table']);
      }
      if (!empty($parsed['field']) && $parsed['field'] !== '*') {
        $this->validateField($parsed['field']);
      }
    }
  }

  /**
   * Split an expression string by top-level commas (ignoring commas inside
   * nested parentheses).
   *
   * @param string $expr
   * @return string[]
   */
  private function splitByComma(string $expr): array {
    $result = [];
    $depth = 0;
    $current = '';
    $len = strlen($expr);
    for ($i = 0; $i < $len; $i++) {
      $c = $expr[$i];
      if ($c === '(') {
        $depth++;
      }
      elseif ($c === ')') {
        $depth--;
      }
      elseif ($c === ',' && $depth === 0) {
        $result[] = $current;
        $current = '';
        continue;
      }
      $current .= $c;
    }
    if ($current !== '') {
      $result[] = $current;
    }
    return $result;
  }

  /**
   * Add validation error
   *
   * @param string $message
   */
  private function addError(string $message): void {
    $this->errors[] = $message;
    $this->isValid = FALSE;
  }
}
