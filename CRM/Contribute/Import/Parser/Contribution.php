<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2010                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id$
 *
 */

require_once 'api/v2/Contribution.php';

/**
 * class to parse contribution csv files
 */
class CRM_Contribute_Import_Parser_Contribution extends CRM_Contribute_Import_Parser {

  protected $_mapperKeys;
  protected $_mapperLocType;
  protected $_mapperPhoneType;
  protected $_mapperImProvider;
  protected $_mapperWebsiteType;

  private $_contactIdIndex;
  private $_totalAmountIndex;
  private $_contributionTypeIndex;

  protected $_mapperSoftCredit;
  protected $_mapperPCP;
  //protected $_mapperPhoneType;

  /**
   * Array of succesfully imported contribution id's
   *
   * @array
   */
  protected $_newContributions;

  protected $_importableContactFields;

  /**
   * Contact importer parser
   *
   * @var CRM_Import_Parser_Contact
   */
  protected $_parserContact;

  /**
   * class constructor
   */
  function __construct(&$mapperKeys, $mapperSoftCredit = NULL, $mapperLocType = NULL, $mapperPhoneType = NULL, $mapperWebsiteType = NULL, $mapperImProvider = NULL, $mapperPCP = NULL) {
    parent::__construct();
    $this->_mapperKeys = &$mapperKeys;
    $this->_mapperSoftCredit = &$mapperSoftCredit;
    $this->_mapperPCP = &$mapperPCP;
    $this->_mapperLocType = &$mapperLocType;
    $this->_mapperPhoneType = &$mapperPhoneType;
    $this->_mapperWebsiteType = $mapperWebsiteType;
    $this->_mapperImProvider = &$mapperImProvider;

  }

  /**
   * the initializer code, called before the processing
   *
   * @return void
   * @access public
   */
  function init() {

    $fields = &CRM_Contribute_BAO_Contribution::importableFields($this->_contactType, FALSE);
    $this->_importableContactFields = $fields;

    $fields = array_merge($fields,
      array(
        'soft_credit' => array(
          'title' => ts('Soft Credit'),
          'softCredit' => TRUE,
          'headerPattern' => '/Soft Credit/i',
        ),
        'pcp_id' => array(
          'title' => ts('Personal Campaign Page ID'),
          'softCredit' => TRUE,
          'headerPattern' => '/Personal Campaign Page ID/i',
        ),
        'pcp_page' => array(
          'title' => ts('Personal Campaign Page Title'),
          'softCredit' => TRUE,
          'headerPattern' => '/Personal Campaign Page Title/i',
        ),
        'pcp_creator' => array(
          'title' => ts('Personal Campaign Page Creator'),
          'softCredit' => TRUE,
          'headerPattern' => '/Personal Campaign Page Creator/i',
        ),
        'pcp_display_in_roll' => array(
          'title' => ts('Pcp Display In Roll'),
          'softCredit' => TRUE,
          'headerPattern' => '/Pcp Display In Roll/i',
        ),
        'pcp_roll_nickname' => array(
          'title' => ts('Pcp Roll Nickname'),
          'softCredit' => TRUE,
          'headerPattern' => '/Pcp Roll Nickname/i',
        ),
        'pcp_personal_note' => array(
          'title' => ts('Pcp Personal Note'),
          'softCredit' => TRUE,
          'headerPattern' => '/Personal Campaign Page/i',
        ),
      )
    );

    // add pledge fields only if its is enabled
    if (CRM_Core_Permission::access('CiviPledge')) {
      $pledgeFields = array('pledge_payment' => array('title' => ts('Pledge Payment'),
          'headerPattern' => '/Pledge Payment/i',
        ),
        'pledge_id' => array('title' => ts('Pledge ID'),
          'headerPattern' => '/Pledge ID/i',
        ),
      );

      $fields = array_merge($fields, $pledgeFields);
    }
    foreach ($fields as $name => $field) {
      $this->addField($name, $field['title'], $field['type'], $field['headerPattern'], $field['dataPattern'], $field['hasLocationType']);
    }

    $this->_newContributions = array();

    $this->setActiveFields($this->_mapperKeys);
    $this->setActiveFieldSoftCredit($this->_mapperSoftCredit);
    $this->setActiveFieldLocationTypes($this->_mapperLocType);
    $this->setActiveFieldPhoneTypes($this->_mapperPhoneType);
    $this->setActiveFieldWebsiteTypes($this->_mapperWebsiteType);
    $this->setActiveFieldImProviders($this->_mapperImProvider);
    $this->setActiveFieldPCP($this->_mapperPCP);

    // FIXME: we should do this in one place together with Form/MapField.php
    $index = 0;
    foreach ($this->_mapperKeys as $key) {
      switch ($key) {
      }
      $index++;
    }

    $this->_contactIdIndex = -1;
    $this->_totalAmountIndex = -1;
    $this->_contributionTypeIndex = -1;

    $this->_phoneIndex = -1;
    $this->_emailIndex = -1;
    $this->_firstNameIndex = -1;
    $this->_lastNameIndex = -1;
    $this->_householdNameIndex = -1;
    $this->_organizationNameIndex = -1;
    $this->_externalIdentifierIndex = -1;

    $index = 0;
    foreach ($this->_mapperKeys as $key) {
      if (preg_match('/^contact_email/', $key) && !strstr($key, 'email_greeting')) {
        $this->_emailIndex = $index;
        $this->_allEmails = array();
      }
      elseif (preg_match('/^contact__phone/', $key)) {
        $this->_phoneIndex = $index;
      }
      elseif ($key == 'contact__first_name') {
        $this->_firstNameIndex = $index;
      }
      elseif ($key == 'contact__last_name') {
        $this->_lastNameIndex = $index;
      }
      elseif ($key == 'contact__household_name') {
        $this->_householdNameIndex = $index;
      }
      elseif ($key == 'contact__organization_name') {
        $this->_organizationNameIndex = $index;
      }
      elseif ($key == 'contact__external_identifier') {
        $this->_externalIdentifierIndex = $index;
        $this->_allExternalIdentifiers = array();
      }
      elseif($key == 'contribution_contact_id'){
        $this->_contactIdIndex = $index;
      }
      elseif($key == 'total_amount'){
        $this->_totalAmountIndex = $index;
      }
      elseif($key == 'contribution_type') {
        $this->_contributionTypeIndex = $index;
      }

      $index++;
    }

    // create parser object for contact import, #18222
    $this->_parserContact = new CRM_Import_Parser_Contact(
      $this->_mapperKeys,
      $this->_mapperLocType,
      $this->_mapperPhoneType,
      $this->_mapperImProvider
    );
    $this->_parserContact->_onDuplicate = CRM_Import_Parser::DUPLICATE_SKIP;
    $this->_parserContact->_contactType = $this->_contactType;
    $this->_parserContact->_dedupeRuleGroupId = $this->_dedupeRuleGroupId;
    $this->_parserContact->_contactLog = ts('Import Contact').' '.ts('From').' '.ts('Import Contribution');
    $this->_parserContact->init();

    // create dedupe fields mapping to prevent each loop query
    if (!empty($this->_dedupeRuleGroupId)) {
      $ruleParams = array(
        'id' => $this->_dedupeRuleGroupId,
      );
    }
    else {
      $ruleParams = array(
        'contact_type' => $this->_contactType,
        'level' => 'Strict',
      );
    }
    $this->_dedupeRuleFields = CRM_Dedupe_BAO_Rule::dedupeRuleFieldsMapping($ruleParams);
    $hasSortName = array_search('sort_name', $this->_dedupeRuleFields);
    $hasDisplayName = array_search('display_name', $this->_dedupeRuleFields);
    if ($hasSortName !== FALSE) {
      unset($this->_dedupeRuleFields[$hasSortName]);
    }
    if ($hasDisplayName !== FALSE) {
      unset($this->_dedupeRuleFields[$hasDisplayName]);
    }
    if ($hasSortName !== FALSE || $hasDisplayName !== FALSE) {
      $this->_dedupeRuleFields[] = 'last_name';
      $this->_dedupeRuleFields[] = 'first_name';
    }
  }

  /**
   * handle the values in mapField mode
   *
   * @param array $values the array of values belonging to this line
   *
   * @return boolean
   * @access public
   */
  function mapField(&$values) {
    return CRM_Contribute_Import_Parser::VALID;
  }

  /**
   * handle the values in preview mode
   *
   * @param array $values the array of values belonging to this line
   *
   * @return boolean      the result of this processing
   * @access public
   */
  function preview(&$values) {
    return $this->summary($values);
  }

  /**
   * handle the values in summary mode
   *
   * @param array $values the array of values belonging to this line
   *
   * @return boolean      the result of this processing
   * @access public
   */
  function summary(&$values) {
    $erroneousField = NULL;
    $response = $this->setActiveFieldValues($values, $erroneousField);

    $params = &$this->getActiveFieldParams();

    $errorMessage = NULL;
    $statusFieldName = $this->_statusFieldName;

    //for date-Formats
    $dateType = $this->_dateFormats;
    $addedError = NULL;
    foreach ($params as $key => $val) {
      $contactExists = NULL;
      $isDeleted = NULL;
      if ($val) {
        switch ($key) {
          case 'contribution_page_id':
            if (!isset($this->_contributionPages[$val])) {
              CRM_Import_Parser_Contact::addToErrorMsg(ts('Contribution Page ID'), $errorMessage);
            }
            break;

          case 'receive_date':
            if (CRM_Utils_Date::convertToDefaultDate($params, $dateType, $key)) {
              if (!CRM_Utils_Rule::dateTime($params[$key])) {
                CRM_Import_Parser_Contact::addToErrorMsg(ts('Receive Date'), $errorMessage);
              }
            }
            else {
              CRM_Import_Parser_Contact::addToErrorMsg(ts('Receive Date'), $errorMessage);
            }
            break;

          case 'cancel_date':
            if (CRM_Utils_Date::convertToDefaultDate($params, $dateType, $key)) {
              if (!CRM_Utils_Rule::dateTime($params[$key])) {
                CRM_Import_Parser_Contact::addToErrorMsg(ts('Cancel Date'), $errorMessage);
              }
            }
            else {
              CRM_Import_Parser_Contact::addToErrorMsg(ts('Cancel Date'), $errorMessage);
            }
            break;

          case 'receipt_date':
            if (CRM_Utils_Date::convertToDefaultDate($params, $dateType, $key)) {
              if (!CRM_Utils_Rule::dateTime($params[$key])) {
                CRM_Import_Parser_Contact::addToErrorMsg(ts('Receipt Date'), $errorMessage);
              }
            }
            else {
              CRM_Import_Parser_Contact::addToErrorMsg(ts('Receipt Date'), $errorMessage);
            }
            break;

          case 'thankyou_date':
            if (CRM_Utils_Date::convertToDefaultDate($params, $dateType, $key)) {
              if (!CRM_Utils_Rule::dateTime($params[$key])) {
                CRM_Import_Parser_Contact::addToErrorMsg(ts('Thank You Date'), $errorMessage);
              }
            }
            else {
              CRM_Import_Parser_Contact::addToErrorMsg(ts('Thank You Date'), $errorMessage);
            }
            break;
          case 'contribution_contact_id':
            if ($this->_createContactOption == self::CONTACT_NOIDCREATE) {
							$contactExists = CRM_Import_Parser_Contact::checkContactById(array('contribution_contact_id' => $params['contribution_contact_id']), 'contribution_contact_id');
							if (!$contactExists) {
                CRM_Import_Parser_Contact::addToErrorMsg(ts('Could not find contact by %1', array(1 => ts('Contact ID').'-'.$val)), $errorMessage);
              }
            }
            $isDeleted = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $val, 'is_deleted', 'id');
            if ($isDeleted) {
              CRM_Import_Parser_Contact::addToErrorMsg(ts('Deleted Contact(s): %1', array(1 => ts('Contact ID').'-'.$val)), $errorMessage);
            }
            break;
          case 'external_identifier':
            if ($this->_createContactOption == self::CONTACT_NOIDCREATE) {
              $contactExists = CRM_Import_Parser_Contact::checkContactById(array('external_identifier' => $params['external_identifier']), 'contribution_contact_id');
              if (!$contactExists) {
                CRM_Import_Parser_Contact::addToErrorMsg(ts('Could not find contact by %1', array(1 => ts('External Identifier').'-'.$val)), $errorMessage);
              }
            }
            $isDeleted = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $val, 'is_deleted', 'external_identifier');
            if ($isDeleted) {
              CRM_Import_Parser_Contact::addToErrorMsg(ts('Deleted Contact(s): %1', array(1 => ts('External Identifier').'-'.$val)), $errorMessage);
            }
            break;
          case 'soft_credit':
            if ((!empty($params['soft_credit']['external_identifier']) || !empty($params['soft_credit']['contact_id'])) && !empty($val)) {
              $contactExists = CRM_Import_Parser_Contact::checkContactById($params['soft_credit'], 'contact_id');
              if (!$contactExists) {
                CRM_Import_Parser_Contact::addToErrorMsg(ts('Could not find contact by %1', array(1 => ts('Soft Credit').' - '.ts('Contact ID'))), $errorMessage);
              }
            }
            break;
          case 'pcp_creator':
            if ((!empty($params['pcp_creator']['pcp_external_identifier']) || !empty($params['pcp_creator']['pcp_contact_id'])) && !empty($val)) {
              $contactExists = CRM_Import_Parser_Contact::checkContactById(array(
                'external_identifier' => $params['pcp_creator']['pcp_external_identifier'],
                'contact_id' => $params['pcp_creator']['pcp_contact_id'],
              ), 'contact_id');
              if (!$contactExists) {
                CRM_Import_Parser_Contact::addToErrorMsg(ts('Could not find contact by %1', array(1 => ts('Personal Campaign Page Creator'))), $errorMessage);
              }
            }
            break;
          case 'pcp_display_in_roll':
          case 'pcp_roll_nickname':
          case 'pcp_personal_note':
            if (!CRM_Utils_Array::arrayKeyExists('pcp_id', $params) && !CRM_Utils_Array::arrayKeyExists('pcp_page', $params) && !CRM_Utils_Array::arrayKeyExists('pcp_creator', $params) && !$addedError) {
              $addedError = TRUE;
              CRM_Import_Parser_Contact::addToErrorMsg(ts('PCP related field needs PCP page title or id or user'), $errorMessage);
            }
            break;
        }
      }
    }

    //date-Format part ends

    $params['contact_type'] = 'Contribution';

    //checking error in custom data
    $contactParams = array();
    $contactParams['contact_type'] = $this->_contactType;
    if (!empty($this->_contactSubType)) {
      $csType = $this->_contactSubType;
    }
    if (CRM_Utils_Array::value('contact_sub_type', $params)) {
      $csType = CRM_Utils_Array::value('contact_sub_type', $params);
    }
    $contactFields = CRM_Core_BAO_CustomField::getFields($this->_contactType, FALSE, FALSE, $csType);
    if(!empty($contactFields)){
      foreach(array_keys($contactFields) as $customKey) {
        if (isset($params['custom_'.$customKey])) {
          $contactParams['custom_'.$customKey] = $params['custom_'.$customKey];
        }
      }
      CRM_Import_Parser_Contact::isErrorInCustomData($contactParams, $errorMessage);
    }

    if ($errorMessage) {
      $tempMsg = ts('Invalid value for field(s)').': '. $errorMessage;
      $importRecordParams = array($statusFieldName => CRM_Import_Parser::ERROR, "${statusFieldName}Msg" => $tempMsg);
      $this->updateImportStatus($values[count($values) - 1], $importRecordParams);
      array_unshift($values, $tempMsg);
      $errorMessage = NULL;
      return CRM_Contribute_Import_Parser::ERROR;
    }
    else {
      $importRecordParams = array($statusFieldName => CRM_Import_Parser::PENDING);
      $this->updateImportStatus($values[count($values) - 1], $importRecordParams);
      return CRM_Contribute_Import_Parser::VALID;
    }
  }

  /**
   * handle the values in import mode
   *
   * @param int $onDuplicate the code for what action to take on duplicates
   * @param array $values the array of values belonging to this line
   *
   * @return boolean      the result of this processing
   * @access public
   */
  function import($onDuplicate, &$values) {
    $contactValues = $values;
    $statusFieldName = $this->_statusFieldName;
    // first make sure this is a valid line
    $response = $this->summary($values);
    if ($response != CRM_Contribute_Import_Parser::VALID) {
      // summary already update status to database table
      return $response;
    }

    $params = &$this->getActiveFieldParams();

    $formatted = array();

    // don't add to recent items, CRM-4399
    $formatted['skipRecentView'] = TRUE;

    //for date-Formats
    $dateType = $this->_dateFormats;
    $customFields = CRM_Core_BAO_CustomField::getFields(CRM_Utils_Array::value('contact_type', $params));

    foreach ($params as $key => $val) {
      if ($val) {
        switch ($key) {
          case 'receive_date':
            CRM_Utils_Date::convertToDefaultDate($params, $dateType, $key);
            break;

          case 'cancel_date':
            CRM_Utils_Date::convertToDefaultDate($params, $dateType, $key);
            break;

          case 'receipt_date':
            CRM_Utils_Date::convertToDefaultDate($params, $dateType, $key);
            break;

          case 'thankyou_date':
            CRM_Utils_Date::convertToDefaultDate($params, $dateType, $key);
            break;

          case 'pledge_payment':
            $params[$key] = CRM_Utils_String::strtobool($val);
            break;
        }
        if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($key)) {
          if ($customFields[$customFieldID]['data_type'] == 'Date') {
            CRM_Import_Parser_Contact::formatCustomDate($params, $formatted, $dateType, $key);
            unset($params[$key]);
          }
          elseif ($customFields[$customFieldID]['data_type'] == 'Boolean') {
            $params[$key] = CRM_Utils_String::strtoboolstr($val);
          }
        }
      }
    }
    //date-Format part ends

    static $indieFields = NULL;
    if ($indieFields == NULL) {

      $tempIndieFields = &CRM_Contribute_DAO_Contribution::import();
      $indieFields = $tempIndieFields;
    }

    $paramValues = array();
    foreach ($params as $key => $field) {
      if ($field == NULL || $field === '') {
        continue;
      }
      $paramValues[$key] = $field;
    }

    //import contribution record according to select contact type
    if ($onDuplicate == CRM_Contribute_Import_Parser::DUPLICATE_SKIP &&
      ($paramValues['contribution_contact_id'] || $paramValues['external_identifier'])
    ) {
      $paramValues['contact_type'] = $this->_contactType;
    }
    elseif ($onDuplicate == CRM_Contribute_Import_Parser::DUPLICATE_UPDATE &&
      ($paramValues['contribution_id'] || $values['trxn_id'] || $paramValues['invoice_id'])
    ) {
      $paramValues['contact_type'] = $this->_contactType;
    }
    elseif (!empty($params['soft_credit'])) {
      $paramValues['contact_type'] = $this->_contactType;
    }
    elseif (CRM_Utils_Array::value('pledge_payment', $paramValues)) {
      $paramValues['contact_type'] = $this->_contactType;
    }

    //need to pass $onDuplicate to check import mode.
    if (CRM_Utils_Array::value('pledge_payment', $paramValues)) {
      $paramValues['onDuplicate'] = $onDuplicate;
    }

    $formatError = _civicrm_contribute_formatted_param($paramValues, $formatted, TRUE);

    if (civicrm_error($formatError)) {
      $errorMsg = $formatError['error_message'];
      if (CRM_Utils_Array::value('error_data', $formatError) == 'soft_credit') {
        $importRecordParams = array($statusFieldName => CRM_Contribute_Import_Parser::SOFT_CREDIT_ERROR, "${statusFieldName}Msg" => $errorMsg);
        $this->updateImportStatus($values[count($values) - 1], $importRecordParams);
        array_unshift($values, $importRecordParams[$statusFieldName.'Msg']);
        return CRM_Contribute_Import_Parser::SOFT_CREDIT_ERROR;
      }
      elseif (CRM_Utils_Array::value('error_data', $formatError) == 'pledge_payment') {
        $importRecordParams = array($statusFieldName => CRM_Contribute_Import_Parser::PLEDGE_PAYMENT_ERROR, "${statusFieldName}Msg" => $errorMsg);
        $this->updateImportStatus($values[count($values) - 1], $importRecordParams);
        array_unshift($values, $importRecordParams[$statusFieldName.'Msg']);
        return CRM_Contribute_Import_Parser::PLEDGE_PAYMENT_ERROR;
      }
      elseif (CRM_Utils_Array::value('error_data', $formatError) == 'pcp_creator') {
        $importRecordParams = array($statusFieldName => CRM_Contribute_Import_Parser::PCP_ERROR, "${statusFieldName}Msg" => $errorMsg);
        $this->updateImportStatus($values[count($values) - 1], $importRecordParams);
        array_unshift($values, $importRecordParams[$statusFieldName.'Msg']);
        return CRM_Contribute_Import_Parser::PCP_ERROR;
      }
      else {
        $importRecordParams = array($statusFieldName => CRM_Contribute_Import_Parser::ERROR, "${statusFieldName}Msg" => $errorMsg);
        $this->updateImportStatus($values[count($values) - 1], $importRecordParams);
        array_unshift($values, $importRecordParams[$statusFieldName.'Msg']);
        return CRM_Contribute_Import_Parser::ERROR;
      }
    }

    if ($onDuplicate != CRM_Contribute_Import_Parser::DUPLICATE_UPDATE) {
      $formatted['custom'] = CRM_Core_BAO_CustomField::postProcess($formatted, CRM_Core_DAO::$_nullObject, NULL, 'Contribution');
    }
    else {
      //fix for CRM-2219 - Update Contribution
      // onDuplicate == CRM_Contribute_Import_Parser::DUPLICATE_UPDATE
      $dupeIds = array(
        'id' => CRM_Utils_Array::value('contribution_id', $paramValues),
        'trxn_id' => CRM_Utils_Array::value('trxn_id', $paramValues),
        'invoice_id' => CRM_Utils_Array::value('invoice_id', $paramValues),
      );

      if ($paramValues['invoice_id'] || $paramValues['trxn_id'] || $paramValues['contribution_id']) {
        $ids['contribution'] = CRM_Contribute_BAO_Contribution::checkDuplicateIds($dupeIds);
        if ($ids['contribution']) {
          $formatted['id'] = $ids['contribution'];
          $formatted['custom'] = CRM_Core_BAO_CustomField::postProcess($formatted, CRM_Core_DAO::$_nullObject, $formatted['id'], 'Contribution');

          //process note
          if ($paramValues['note']) {
            $noteID = array();
            $contactID = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $ids['contribution'], 'contact_id');

            $daoNote = new CRM_Core_BAO_Note();
            $daoNote->entity_table = 'civicrm_contribution';
            $daoNote->entity_id = $ids['contribution'];
            if ($daoNote->find(TRUE)) {
              $noteID['id'] = $daoNote->id;
            }

            $noteParams = array(
              'entity_table' => 'civicrm_contribution',
              'note' => $paramValues['note'],
              'entity_id' => $ids['contribution'],
              'contact_id' => $contactID,
            );
            CRM_Core_BAO_Note::add($noteParams, $noteID);
            unset($formatted['note']);
          }

          //need to check existing soft credit contribution, CRM-3968
          if (CRM_Utils_Array::value('soft_credit_to', $formatted)) {
            $dupeSoftCredit = array('contact_id' => $formatted['soft_credit_to'],
              'contribution_id' => $ids['contribution'],
            );
            $existingSoftCredit = CRM_Contribute_BAO_Contribution::getSoftContribution($dupeSoftCredit);
            if (CRM_Utils_Array::value('soft_credit_id', $existingSoftCredit)) {
              $formatted['softID'] = $existingSoftCredit['soft_credit_id'];
            }
          }

          $newContribution = &CRM_Contribute_BAO_Contribution::create($formatted, $ids);
          $this->_newContributions[] = $newContribution->id;

          //return soft valid since we need to show how soft credits were added
          if (CRM_Utils_Array::value('soft_credit_to', $formatted)) {
            $importRecordParams = array($statusFieldName => CRM_Contribute_Import_Parser::SOFT_CREDIT, "${statusFieldName}Msg" => '');
            $this->updateImportStatus($values[count($values) - 1], $importRecordParams);
            return CRM_Contribute_Import_Parser::SOFT_CREDIT;
          }

          if (CRM_Utils_Array::value('pcp_creator_id', $formatted)) {
            $importRecordParams = array($statusFieldName => CRM_Contribute_Import_Parser::PCP, "${statusFieldName}Msg" => '');
            array_unshift($values, $importRecordParams[$statusFieldName.'Msg']);
            return CRM_Contribute_Import_Parser::PCP;
          }

          // process pledge payment assoc w/ the contribution
          $importRecordParams = array($statusFieldName => CRM_Contribute_Import_Parser::VALID, "${statusFieldName}Msg" => '');
          $this->updateImportStatus($values[count($values) - 1], $importRecordParams);
          return $this->processPledgePayments($formatted);
        }
      }

      // cache all error when CRM_Contribute_Import_Parser::DUPLICATE_UPDATE
      $labels = array(
        'id' => 'Contribution ID',
        'trxn_id' => 'Transaction ID',
        'invoice_id' => 'Invoice ID',
      );
      foreach ($dupeIds as $k => $v) {
        if ($v) {
          $errorMsg[] = "$labels[$k] $v";
        }
      }
      $errorMsg = CRM_Utils_Array::implode(' AND ', $errorMsg);
      $importRecordParams = array($statusFieldName => CRM_Contribute_Import_Parser::ERROR, "${statusFieldName}Msg" => "Matching Contribution record not found for " . $errorMsg . ". Row was skipped.");
      $this->updateImportStatus($values[count($values) - 1], $importRecordParams);
      array_unshift($values, $importRecordParams[$statusFieldName.'Msg']);
      return CRM_Contribute_Import_Parser::ERROR;
    }

    $doCreateContact = FALSE;
    $checkContactId = CRM_Import_Parser_Contact::checkContactById($paramValues, 'contribution_contact_id');
    $errDisp = "";

    if ($this->_createContactOption == self::CONTACT_DONTCREATE) {
      $doCreateContact = FALSE;
    }
    elseif ($this->_createContactOption == self::CONTACT_NOIDCREATE) {
      if (!empty($paramValues['external_identifier']) || !empty($paramValues['contribution_contact_id'])) {
        $doCreateContact = FALSE;
      }
      else {
        $doCreateContact = TRUE;
      }
    }
    elseif ($this->_createContactOption == self::CONTACT_AUTOCREATE) {
      $doCreateContact = TRUE;
    }

    // using duplicate rule when we don't have contact id and external identifier
    if (empty($checkContactId)) {
      // set the contact type if its not set
      if (!isset($paramValues['contact_type'])) {
        $paramValues['contact_type'] = $this->_contactType;
      }
      //retrieve contact id using contact dedupe rule
      $found = civicrm_check_contact_dedupe($paramValues, $this->_dedupeRuleGroupId);
      if (civicrm_duplicate($found)) {
        $matchedIDs = explode(',', $found['error_message']['params'][0]);
        if (count($matchedIDs) > 1) {
          $importRecordParams = array($statusFieldName => CRM_Contribute_Import_Parser::ERROR, "${statusFieldName}Msg" => ts('Record duplicates multiple contacts'));
          $this->updateImportStatus($values[count($values) - 1], $importRecordParams);
          array_unshift($values, $importRecordParams[$statusFieldName.'Msg']);
          return CRM_Contribute_Import_Parser::ERROR;
        }
        else {
          $checkContactId = $matchedIDs[0];
          $doCreateContact = FALSE;
        }
      }
      else {
        // Using new Dedupe rule for error message handling
        $fieldsArray = $this->_dedupeRuleFields;

        $dispArray = array();
        $noValueFields = array();
        foreach ($fieldsArray as $dupeFieldName) {
          if ($doCreateContact) {
            if (!CRM_Utils_Array::arrayKeyExists(trim($dupeFieldName), $params)) {
              $dispArray[] = $this->_importableContactFields[$dupeFieldName]['title'];
            }
            elseif (!is_array($params[$dupeFieldName]) && empty(trim($params[$dupeFieldName]))) {
              $noValueFields[$dupeFieldName] = $this->_importableContactFields[$dupeFieldName]['title'];
            }
            elseif (is_array($params[$dupeFieldName])) {
              $hasValue = FALSE;
              foreach($params[$dupeFieldName] as $email) {
                if ($dupeFieldName === 'email' && !empty($email['email'])) {
                  $hasValue = TRUE;
                }
              }
              if (!$hasValue) {
                $noValueFields[$dupeFieldName] = $this->_importableContactFields[$dupeFieldName]['title'];
              }
            }
          }
          elseif (CRM_Utils_Array::arrayKeyExists(trim($dupeFieldName), $params)) {
            $paramValue = $params[trim($dupeFieldName)];
            if (is_array($paramValue)) {
              $dispArray[] = $params[trim($dupeFieldName)][0][trim($dupeFieldName)];
            }
            else {
              $dispArray[] = $params[trim($dupeFieldName)];
            }
          }
        }

        if ($doCreateContact && count($noValueFields) >= count($fieldsArray)) {
          foreach($noValueFields as $fieldTitle) {
            $dispArray[] = $fieldTitle;
          }
        }

        if (CRM_Utils_Array::value('external_identifier', $params) && !$doCreateContact) {
          $dispArray[] = $params['external_identifier'];
        }
        if (!empty($dispArray)) {
          if ($doCreateContact) {
            $errDisp = ts('Missing required contact matching fields.')." - ".CRM_Utils_Array::implode('|', $dispArray);
          }
          else {
            $errDisp = ts("No matching results for "). ":" . CRM_Utils_Array::implode('|', $dispArray) . "";
          }
          $doCreateContact = FALSE;
        }
      }
    }

    if ($doCreateContact && empty($checkContactId)) {
      // trying to create new contact base on exists contact related params
      $doGeocodeAddress = FALSE;
      $this->_parserContact->_dateFormats = $this->_dateFormats;
      $contactImportResult = $this->_parserContact->import(CRM_Import_Parser::DUPLICATE_FILL, $contactValues, $doGeocodeAddress);
      $contactID = $this->_parserContact->getLastImportContactId();
      if (!empty($contactID) && $contactImportResult == CRM_Import_Parser::VALID) {
        $formatted['contact_id'] = $contactID;
        return $this->importContribution($formatted, $values);
      }
      else {
        $errDisp = "Contact Import Error: ".$contactValues[0];
        $importRecordParams = array($statusFieldName => $contactImportResult, "${statusFieldName}Msg" => $errDisp);
        $this->updateImportStatus($values[count($values) - 1], $importRecordParams);
        array_unshift($values, $importRecordParams[$statusFieldName.'Msg']);
        return $contactImportResult;
      }
    }
    else {
      if ($checkContactId) {
        $formatted['contact_id'] = $checkContactId;
        unset($formatted['contribution_contact_id']);
        return $this->importContribution($formatted, $values);
      }
      elseif ($checkContactId === FALSE) {
        $errDisp = "Mismatch of External identifier :" . $paramValues['external_identifier'] . " and Contact Id:" . $formatted['contact_id'];
      }
    }

    // catach all for CRM_Contribute_Import_Parser::DUPLICATE_SKIP
    $importRecordParams = array($statusFieldName => CRM_Contribute_Import_Parser::ERROR, "${statusFieldName}Msg" => $errDisp);
    $this->updateImportStatus($values[count($values) - 1], $importRecordParams);
    array_unshift($values, $importRecordParams[$statusFieldName.'Msg']);
    return CRM_Contribute_Import_Parser::ERROR;
  }

  /**
   *  Function to process pledge payments
   */
  function processPledgePayments(&$formatted) {
    $statusFieldName = $this->_statusFieldName;
    if (CRM_Utils_Array::value('pledge_payment_id', $formatted) &&
      CRM_Utils_Array::value('pledge_id', $formatted)
    ) {
      //get completed status
      $completeStatusID = CRM_Core_OptionGroup::getValue('contribution_status', 'Completed', 'name');

      //need to update payment record to map contribution_id
      CRM_Core_DAO::setFieldValue('CRM_Pledge_DAO_Payment', $formatted['pledge_payment_id'],
        'contribution_id', $formatted['contribution_id']
      );


      CRM_Pledge_BAO_Payment::updatePledgePaymentStatus($formatted['pledge_id'], array($formatted['pledge_payment_id']), $completeStatusID, NULL, $formatted['total_amount']);

      $importRecordParams = array($statusFieldName => CRM_Contribute_Import_Parser::PLEDGE_PAYMENT, "${statusFieldName}Msg" => '');
      $this->updateImportStatus($values[count($values) - 1], $importRecordParams);
      return CRM_Contribute_Import_Parser::PLEDGE_PAYMENT;
    }
    return CRM_Contribute_Import_Parser::VALID;
  }

  /**
   * Get the array of succesfully imported contribution id's
   *
   * @return array
   * @access public
   */
  function &getImportedContributions() {
    return $this->_newContributions;
  }

  /**
   * import contribution wrapper
   *
   * @return array
   * @access public
   */
  function importContribution($formatted, &$values) {
    $newContribution = civicrm_contribution_format_create($formatted);
    $statusFieldName = $this->_statusFieldName;
    if (civicrm_error($newContribution)) {
      if (is_array($newContribution['error_message'])) {
        if ($newContribution['error_message']['params'][0]) {
          // original message will be "Duplicate error - existing contribution record(s) have a matching Transaction ID or Invoice ID." or
          // "Duplicate error - existing contribution record(s) have a matching Receipt ID."
          $duplicateField = strstr($newContribution['error_message']['message'], 'Transaction ID') ? ts('Transaction ID').'/'.ts('Invoice ID') : ts('Receipt ID');
          $importRecordParams = array($statusFieldName => CRM_Contribute_Import_Parser::DUPLICATE, "${statusFieldName}Msg" => $duplicateField.'-'.ts('On duplicate entries').":".ts("Contribution ID").$newContribution['error_message']['params'][0]);
          $this->updateImportStatus($values[count($values) - 1], $importRecordParams);
          array_unshift($values, $importRecordParams[$statusFieldName.'Msg']);
          return CRM_Contribute_Import_Parser::DUPLICATE;
        }
      }
      else {
        $importRecordParams = array($statusFieldName => CRM_Contribute_Import_Parser::ERROR, "${statusFieldName}Msg" => $newContribution['error_message']);
        $this->updateImportStatus($values[count($values) - 1], $importRecordParams);
        array_unshift($values, $newContribution['error_message']);
        return CRM_Contribute_Import_Parser::ERROR;
      }
    }

    $this->_newContributions[] = $newContribution['id'];

    //return soft valid since we need to show how soft credits were added
    if (CRM_Utils_Array::value('soft_credit_to', $formatted)) {
      $importRecordParams = array($statusFieldName => CRM_Contribute_Import_Parser::SOFT_CREDIT, "${statusFieldName}Msg" => '');
      $this->updateImportStatus($values[count($values) - 1], $importRecordParams);
      return CRM_Contribute_Import_Parser::SOFT_CREDIT;
    }

    $importRecordParams = array($statusFieldName => CRM_Contribute_Import_Parser::VALID, "${statusFieldName}Msg" => '');
    $this->updateImportStatus($values[count($values) - 1], $importRecordParams);

    // process pledge payment assoc w/ the contribution
    return $this->processPledgePayments($formatted);
  }


  /**
   * the initializer code, called before the processing
   *
   * @return void
   * @access public
   */
  function fini() {}
}

