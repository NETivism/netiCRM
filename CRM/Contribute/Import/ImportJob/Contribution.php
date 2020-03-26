<?php
class CRM_Contribute_Import_ImportJob_Contribution extends CRM_Import_ImportJob {

  protected $_mapperSoftCredit;
  protected $_mapperPCP;

  public function __construct($tableName = NULL, $createSql = NULL, $createTable = FALSE) {
    parent::__construct($tableName);

    //initialize the properties.
    $properties = array(
      'mapperSoftCredit',
      'mapperPCP',
      'mapperLocTypes',
      'mapperPhoneTypes',
      'mapperImProviders',
      'mapperWebsiteTypes',
    );
    foreach ($properties as $property) {
      $this->{"_$property"} = array();
    }
  }

  public function runImport(&$form) {
    $mapper = $this->_mapper;
    $mapperFields = array();
    $mapperSoftCredit = array();
    $mapperPCP = array();
    foreach ($mapper as $key => $value) {
      $mapperFields[$key] = $mapper[$key][0];
      if (isset($mapper[$key][0]) && $mapper[$key][0] == 'soft_credit') {
        $mapperSoftCredit[$key] = $mapper[$key][1];
      }
      elseif (isset($mapper[$key][0]) && $mapper[$key][0] == 'pcp_creator') {
        $mapperPCP[$key] = $mapper[$key][1];
      }
      else {
        $mapperSoftCredit[$key] = NULL;
        $mapperPCP[$key] = NULL;
      }
    }

    $this->_parser = new CRM_Contribute_Import_Parser_Contribution($mapperFields, $mapperSoftCredit, $this->_mapperLocType, $this->_mapperPhoneType, $this->_mapperWebsiteType, $this->_mapperImProvider, $mapperPCP);
    if (!empty($this->_dedupeRuleGroupId)) {
      $this->_parser->_dedupeRuleGroupId = $this->_dedupeRuleGroupId;
    }

    $this->_parser->_job = $this;
    $this->_parser->run(
      $this->_tableName,
      $mapperFields,
      CRM_Contribute_Import_Parser::MODE_IMPORT,
      $this->_contactType,
      $this->_primaryKeyName,
      $this->_statusFieldName,
      $this->_onDuplicate,
      NULL, 
      NULL,
      $this->_createContactOption,
      $this->_dedupeRuleGroupId
    );
    $this->_parser->set($form, CRM_Contribute_Import_Parser::MODE_IMPORT);
  }
}