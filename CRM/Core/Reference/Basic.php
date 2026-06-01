<?php

/**
 * Description of a one-way link between two entities
 *
 * This is a basic SQL foreign key.
 */
class CRM_Core_Reference_Basic implements CRM_Core_Reference_Interface {
  protected $refTable;
  protected $refKey;
  protected $refTypeColumn;
  protected $targetTable;
  protected $targetKey;

  /**
   * Class constructor.
   *
   * @param string $refTable Table containing the foreign key.
   * @param string $refKey Column containing the foreign key.
   * @param string|null $targetTable Table containing the referenced record.
   * @param string $targetKey Column containing the referenced record's ID.
   * @param string|null $refTypeColumn Column containing the type of reference (for dynamic references).
   */
  public function __construct($refTable, $refKey, $targetTable = NULL, $targetKey = 'id', $refTypeColumn = NULL) {
    $this->refTable = $refTable;
    $this->refKey = $refKey;
    $this->targetTable = $targetTable;
    $this->targetKey = $targetKey;
    $this->refTypeColumn = $refTypeColumn;
  }

  /**
   * Get the table containing the foreign key.
   *
   * @return string
   */
  public function getReferenceTable() {
    return $this->refTable;
  }

  /**
   * Get the column containing the foreign key.
   *
   * @return string
   */
  public function getReferenceKey() {
    return $this->refKey;
  }

  /**
   * Get the column containing the type of reference.
   *
   * @return string|null
   */
  public function getTypeColumn() {
    return $this->refTypeColumn;
  }

  /**
   * Get the table containing the referenced record.
   *
   * @return string|null
   */
  public function getTargetTable() {
    return $this->targetTable;
  }

  /**
   * Get the column containing the referenced record's ID.
   *
   * @return string
   */
  public function getTargetKey() {
    return $this->targetKey;
  }

  /**
   * Check if a given table is the target of this reference.
   *
   * @param string $tableName Table name to check.
   *
   * @return bool
   */
  public function matchesTargetTable($tableName) {
    return ($this->getTargetTable() === $tableName);
  }

  /**
   * Create a query to find references to a particular record.
   *
   * @param CRM_Core_DAO $targetDao The instance for which we want references.
   *
   * @return CRM_Core_DAO A query-handle.
   */
  public function findReferences($targetDao) {
    $targetColumn = $this->getTargetKey();
    $select = 'id';
    $params = [
      1 => [$targetDao->$targetColumn, 'String'],
    ];
    $sql = <<<EOS
    SELECT {$select}
    FROM {$this->getReferenceTable()}
    WHERE {$this->getReferenceKey()} = %1
    EOS;

    $daoName = CRM_Core_DAO_AllCoreTables::getClassForTable($this->getReferenceTable());
    $result = CRM_Core_DAO::executeQuery($sql, $params, TRUE, $daoName);
    return $result;
  }

  /**
   * Create a query to find the number of references to a particular record.
   *
   * @param CRM_Core_DAO $targetDao The instance for which we want references.
   *
   * @return array Describing the reference count.
   */
  public function getReferenceCount($targetDao) {
    $targetColumn = $this->getTargetKey();
    $params = [
      1 => [$targetDao->$targetColumn, 'String'],
    ];
    $sql = <<<EOS
    SELECT count(*)
    FROM {$this->getReferenceTable()}
    WHERE {$this->getReferenceKey()} = %1
    EOS;

    return [
      'name' => CRM_Utils_Array::implode(':', ['sql', $this->getReferenceTable(), $this->getReferenceKey()]),
      'type' => get_class($this),
      'table' => $this->getReferenceTable(),
      'key' => $this->getReferenceKey(),
      'count' => CRM_Core_DAO::singleValueQuery($sql, $params),
    ];
  }

}
