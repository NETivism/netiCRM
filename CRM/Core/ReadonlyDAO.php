<?php

/**
 * ReadonlyDAO - Setup and connect to MySQL/MariaDB read-only views.
 *
 * Accepts a view definition (tables + fields), creates MERGE views for each,
 * and grants SELECT on those views to a pre-existing readonly database user.
 *
 * Prerequisites (must be handled by a DBA outside this class):
 *   - CIVICRM_DSN_READONLY must be defined (e.g. in civicrm.settings.php):
 *       define('CIVICRM_DSN_READONLY', 'mysql://crm_readonly:@localhost/dbname');
 *   - The readonly user referenced in CIVICRM_DSN_READONLY must already exist.
 *   - The CiviCRM DB user must have CREATE VIEW, SELECT on source tables,
 *     and GRANT OPTION on the database.
 *
 * Usage:
 *   $dao = new CRM_Core_ReadonlyDAO($viewDefinitions);
 *   $dao->setup();                 // create views + grant SELECT (idempotent)
 *   $pdo = $dao->connectReadonly(); // PDO as readonly user, view-scoped only
 */
class CRM_Core_ReadonlyDAO {

  /**
   * Admin PDO connection (used only during setup).
   *
   * @var PDO
   */
  private $pdo;

  /**
   * Current database name, resolved at construction time.
   *
   * @var string
   */
  private $dbName;

  /**
   * View definitions: viewName => ['source' => tableName, 'fields' => [...]]
   *
   * @var array
   */
  private $viewDefinitions;

  /**
   * Parsed components of CIVICRM_DSN_READONLY.
   * Keys: host, port, dbname, username, password.
   *
   * @var array
   */
  private $readonlyDsn;

  /**
   * @param array $viewDefinitions
   *   Associative array keyed by view name.
   *   Each entry must have:
   *     'source' (string) – the source table name
   *     'fields' (array)  – list of column names to expose
   *
   * @throws RuntimeException  When CIVICRM_DSN / CIVICRM_DSN_READONLY is
   *                           unavailable, unsupported, or cannot connect.
   */
  public function __construct(array $viewDefinitions) {
    $this->viewDefinitions = $viewDefinitions;
    $this->readonlyDsn = self::parseReadonlyDsn();
    $this->pdo = $this->createAdminConnection();
    $this->dbName = $this->pdo->query('SELECT DATABASE()')->fetchColumn();
  }

  // ---------------------------------------------------------------------------
  // Public API
  // ---------------------------------------------------------------------------

  /**
   * Idempotent setup: create views and grant SELECT to the readonly user.
   *
   * All steps are attempted independently so a single failure does not abort
   * the remaining operations. Every action (success, skip, or error) is written
   * to the CiviCRM log via CRM_Core_Error::debug_log_message.
   *
   * Note: CREATE USER and FLUSH PRIVILEGES require DBA-level privileges and
   * must be performed outside this class.
   *
   * @return bool  TRUE if every step succeeded, FALSE if any step failed.
   */
  public function setup() {
    $success = TRUE;
    $prefix = 'ReadonlyDAO';

    foreach ($this->viewDefinitions as $viewName => $def) {
      try {
        $msg = $this->createViewIfNotExists($viewName, $def);
        if (!empty($msg)) {
          CRM_Core_Error::debug_log_message("[{$prefix}] {$msg}");
        }
      }
      catch (Exception $e) {
        CRM_Core_Error::debug_log_message("[{$prefix}][ERROR] Failed to create view `{$viewName}`: " . $e->getMessage());
        $success = FALSE;
      }
    }

    foreach (array_keys($this->viewDefinitions) as $viewName) {
      try {
        $msg = $this->grantSelectOnView($viewName);
      }
      catch (Exception $e) {
        CRM_Core_Error::debug_log_message("[{$prefix}][ERROR] Failed to grant SELECT on `{$viewName}`: " . $e->getMessage());
        $success = FALSE;
      }
    }

    return $success;
  }

  /**
   * Return a PDO connection authenticated as the readonly user.
   *
   * Connects using credentials from CIVICRM_DSN_READONLY. Unlike the old
   * initReadonly() which reused admin credentials with SET SESSION TRANSACTION
   * READ ONLY, this authenticates as the dedicated readonly user whose grants
   * are limited to the configured views.
   *
   * @return PDO|false  PDO instance, or FALSE on failure.
   */
  public function connectReadonly() {
    try {
      $r = $this->readonlyDsn;
      $pdoDsn = "mysql:host={$r['host']};port={$r['port']};dbname={$r['dbname']};charset=utf8mb4";
      $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_PERSISTENT         => FALSE,
      ];
      return new PDO($pdoDsn, $r['username'], $r['password'], $options);
    }
    catch (Exception $e) {
      return FALSE;
    }
  }

  /**
   * Verify the current state of views and grants.
   *
   * @return array  Structured result with keys 'views' and 'grants'.
   */
  public function verify() {
    $result = [];

    foreach (array_keys($this->viewDefinitions) as $viewName) {
      $result['views'][$viewName] = $this->viewExists($viewName);
    }

    $stmt = $this->pdo->prepare(
      "SELECT TABLE_NAME, PRIVILEGE_TYPE
       FROM information_schema.TABLE_PRIVILEGES
       WHERE GRANTEE LIKE :grantee AND TABLE_SCHEMA = :db"
    );
    $stmt->execute([
      ':grantee' => "%'" . $this->readonlyDsn['username'] . "'@'" . $this->readonlyDsn['host'] . "'%",
      ':db'      => $this->dbName,
    ]);
    $result['grants'] = $stmt->fetchAll();

    return $result;
  }

  /**
   * Return the configured view definitions.
   *
   * @return array
   */
  public function getViewDefinitions() {
    return $this->viewDefinitions;
  }

  // ---------------------------------------------------------------------------
  // View management
  // ---------------------------------------------------------------------------

  /**
   * @return string  Log message.
   */
  private function createViewIfNotExists($viewName, array $def) {
    if ($this->viewExists($viewName)) {
      return "";
    }

    $fields = implode(', ', array_map(function ($f) { return "`{$f}`"; }, $def['fields']));
    $source = $def['source'];

    // ALGORITHM=MERGE: MariaDB/MySQL will inline the view into the outer query,
    // meaning the optimizer sees the base table directly – no performance penalty.
    $sql = "CREATE ALGORITHM=MERGE VIEW `{$viewName}` AS "
         . "SELECT {$fields} FROM `{$source}`";

    $this->pdo->exec($sql);
    return "[CREATED] View `{$viewName}` on `{$source}`.";
  }

  /**
   * @return bool
   */
  private function viewExists($viewName) {
    $stmt = $this->pdo->prepare(
      "SELECT COUNT(*) FROM information_schema.VIEWS
       WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :view"
    );
    $stmt->execute([':db' => $this->dbName, ':view' => $viewName]);
    return (int) $stmt->fetchColumn() > 0;
  }

  // ---------------------------------------------------------------------------
  // Grants
  // ---------------------------------------------------------------------------

  /**
   * GRANT is idempotent; safe to call repeatedly.
   *
   * @return string  Log message.
   */
  private function grantSelectOnView($viewName) {
    $grantee = "'" . $this->readonlyDsn['username'] . "'@'" . $this->readonlyDsn['host'] . "'";
    $this->pdo->exec(
      "GRANT SELECT ON `{$this->dbName}`.`{$viewName}` TO {$grantee}"
    );
    return "[GRANT] SELECT on `{$viewName}` to {$grantee}.";
  }

  // ---------------------------------------------------------------------------
  // DSN helpers
  // ---------------------------------------------------------------------------

  /**
   * Create a PDO connection using the CiviCRM admin DSN (CIVICRM_DSN).
   *
   * @return PDO
   * @throws RuntimeException
   */
  private function createAdminConnection() {
    if (!defined('CIVICRM_DSN')) {
      throw new RuntimeException('CRM_Core_ReadonlyDAO: CIVICRM_DSN is not defined.');
    }

    $parsed = self::parseDsn(CIVICRM_DSN);
    if ($parsed === FALSE) {
      throw new RuntimeException('CRM_Core_ReadonlyDAO: CIVICRM_DSN is unsupported or malformed.');
    }

    $pdoDsn = "mysql:host={$parsed['host']};port={$parsed['port']};dbname={$parsed['dbname']};charset=utf8mb4";
    return new PDO($pdoDsn, $parsed['username'], $parsed['password'], [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
  }

  /**
   * Parse CIVICRM_DSN_READONLY into its components.
   *
   * The constant must be defined in civicrm.settings.php, e.g.:
   *   define('CIVICRM_DSN_READONLY', 'mysql://crm_readonly:@localhost/dbname');
   *
   * @return array  Keys: host, port, dbname, username, password.
   * @throws RuntimeException  When the constant is missing or malformed.
   */
  private static function parseReadonlyDsn() {
    if (!defined('CIVICRM_DSN_READONLY')) {
      throw new RuntimeException(
        'CRM_Core_ReadonlyDAO: CIVICRM_DSN_READONLY is not defined. '
        . "Add define('CIVICRM_DSN_READONLY', 'mysql://user:pass@host/dbname') to civicrm.settings.php."
      );
    }

    $parsed = self::parseDsn(CIVICRM_DSN_READONLY);
    if ($parsed === FALSE) {
      throw new RuntimeException('CRM_Core_ReadonlyDAO: CIVICRM_DSN_READONLY is unsupported or malformed.');
    }

    return $parsed;
  }

  /**
   * Parse a mysql:// or mysqli:// DSN string into components.
   *
   * @param string $dsn
   * @return array|false  Keys: host, port, dbname, username, password; or FALSE.
   */
  private static function parseDsn($dsn) {
    $parsed = parse_url($dsn);
    if (!$parsed || !isset($parsed['scheme'])) {
      return FALSE;
    }

    switch ($parsed['scheme']) {
      case 'mysqli':
      case 'mysql':
        break;
      default:
        return FALSE;
    }

    return [
      'host'     => $parsed['host'] ?? 'localhost',
      'port'     => $parsed['port'] ?? 3306,
      'dbname'   => isset($parsed['path']) ? ltrim($parsed['path'], '/') : '',
      'username' => $parsed['user'] ?? '',
      'password' => $parsed['pass'] ?? '',
    ];
  }

}
