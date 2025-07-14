<?php

require_once 'sql-parser/autoload.php';

use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Components\Condition;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;
use PhpMyAdmin\SqlParser\Statements\InsertStatement;
use PhpMyAdmin\SqlParser\Statements\UpdateStatement;
use PhpMyAdmin\SqlParser\Statements\DeleteStatement;
use PhpMyAdmin\SqlParser\Utils\Formatter;

class CRM_Utils_SqlParser {

  private $query;
  private $allowlist;
  private $parser;
  private $errors = [];
  private $isValid = true;

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
      $this->isValid = false;
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
      return null;
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
        } else {
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
          } elseif (!empty($join->expr->alias)) {
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
    if (!in_array($value, $this->allowlist[$type], true)) {
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
    $this->validateStatementType($statementType);

    if ($statement instanceof SelectStatement) {
      $this->validateSelectStatement($statement);
    } else {
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
        } else {
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

      if (!in_array($tableName, $allowedTables, true)) {
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

      if (!in_array($fieldName, $allowedFields, true)) {
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

    if (!empty($expression->column) && $context == 'select') {
      $this->validateField($expression->column);
    }

    if (!empty($expression->table)) {
      $this->validateTable($expression->table);
    }

    if (!empty($expression->expr) && $expression->subquery) {
      $this->validateSubquery($expression->expr);
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

    if (!empty($condition->expr)) {
      foreach ($condition->expr as $expression) {
        if ($expression instanceof Expression) {
          $this->validateExpression($expression, 'condition');
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

    if (strpos($identifier, ' AS ') !== false) {
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
    
    // Remove operators by cutting at first space
    if (strpos($identifier, ' ') !== false) {
      $identifier = trim(substr($identifier, 0, strpos($identifier, ' ')));
    }
    
    if (strpos($identifier, '.') !== false) {
      $parts = explode('.', $identifier, 2);
      return ['table' => trim($parts[0]), 'field' => trim($parts[1])];
    }
    
    return ['table' => null, 'field' => $identifier];
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
      return true;
    }
    
    // Check for numbers
    if (is_numeric($value)) {
      return true;
    }
    
    // Check for date/timestamp literals
    if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
      return true;
    }
    
    return false;
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
    } elseif ($statement instanceof InsertStatement) {
      return 'INSERT';
    } elseif ($statement instanceof UpdateStatement) {
      return 'UPDATE';
    } elseif ($statement instanceof DeleteStatement) {
      return 'DELETE';
    } else {
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

      if (!in_array(strtoupper($statementType), $allowedStatements, true)) {
        $this->addError("Statement type '{$statementType}' is not in the allowlist");
      }
    }
  }

  /**
   * Add validation error
   *
   * @param string $message
   */
  private function addError(string $message): void {
    $this->errors[] = $message;
    $this->isValid = false;
  }
}