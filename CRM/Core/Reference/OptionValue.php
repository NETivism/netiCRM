<?php

/**
 * Description of a one-way link between an option-value and an entity
 */
class CRM_Core_Reference_OptionValue extends CRM_Core_Reference_Basic {
  /**
   * @var string option-group-name
   */
  protected $targetOptionGroupName;

  /**
   * @var int|NULL null if not yet loaded
   */
  protected $targetOptionGroupId;

  /**
   * Class constructor.
   *
   * @param string $refTable Table containing the foreign key.
   * @param string $refKey Column containing the foreign key.
   * @param string|null $targetTable Table containing the referenced record.
   * @param string $targetKey Column containing the referenced record's ID.
   * @param string $optionGroupName Name of the option group.
   */
  public function __construct($refTable, $refKey, $targetTable = NULL, $targetKey = 'id', $optionGroupName = '') {
    parent::__construct($refTable, $refKey, $targetTable, $targetKey, NULL);
    $this->targetOptionGroupName = $optionGroupName;
  }

  /**
   * Create a query to find references to a particular record.
   *
   * @param CRM_Core_DAO $targetDao The instance for which we want references.
   *
   * @return CRM_Core_DAO|null A query-handle.
   * @throws CRM_Core_Exception
   */
  public function findReferences($targetDao) {
    if (!($targetDao instanceof CRM_Core_DAO_OptionValue)) {
      throw new CRM_Core_Exception("Mismatched reference: expected OptionValue but received " . get_class($targetDao));
    }
    if ($targetDao->option_group_id == $this->getTargetOptionGroupId()) {
      return parent::findReferences($targetDao);
    }
    else {
      return NULL;
    }
  }

  /**
   * Create a query to find the number of references to a particular record.
   *
   * @param CRM_Core_DAO $targetDao The instance for which we want references.
   *
   * @return array|null Describing the reference count.
   * @throws CRM_Core_Exception
   */
  public function getReferenceCount($targetDao) {
    if (!($targetDao instanceof CRM_Core_DAO_OptionValue)) {
      throw new CRM_Core_Exception("Mismatched reference: expected OptionValue but received " . get_class($targetDao));
    }
    if ($targetDao->option_group_id == $this->getTargetOptionGroupId()) {
      return parent::getReferenceCount($targetDao);
    }
    else {
      return NULL;
    }
  }

  /**
   * Get the ID of the target option group.
   *
   * @return int|null
   */
  public function getTargetOptionGroupId() {
    if ($this->targetOptionGroupId === NULL) {
      $this->targetOptionGroupId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', $this->targetOptionGroupName, 'id', 'name');
    }
    return $this->targetOptionGroupId;
  }

}
