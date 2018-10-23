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

require_once 'api/v2/Membership.php';

/**
 * class to parse membership csv files
 */
class CRM_Member_Import_Parser_Membership extends CRM_Member_Import_Parser {

  protected $_mapperKeys;

  private $_contactIdIndex;
  private $_totalAmountIndex;
  private $_membershipTypeIndex;
  private $_membershipStatusIndex;

  /**
   * Array of succesfully imported membership id's
   *
   * @array
   */
  protected $_newMemberships;

  protected $_importableContactFields;
  protected $_parserContact;

  /**
   * class constructor
   */
  function __construct(&$mapperKeys, $mapperLocType = NULL, $mapperPhoneType = NULL, $mapperWebsiteType = NULL, $mapperImProvider = NULL) {
    parent::__construct();
    $this->_mapperKeys = &$mapperKeys;
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
    $fields = &CRM_Member_BAO_Membership::importableFields($this->_contactType, FALSE);
    $this->_importableContactFields = $fields;

    foreach ($fields as $name => $field) {
      $this->addField($name, $field['title'], $field['type'], $field['headerPattern'], $field['dataPattern'], $field['hasLocationType']);
    }

    $this->_newMemberships = array();

    $this->setActiveFields($this->_mapperKeys);
    $this->setActiveFieldLocationTypes($this->_mapperLocType);
    $this->setActiveFieldPhoneTypes($this->_mapperPhoneType);
    $this->setActiveFieldWebsiteTypes($this->_mapperWebsiteType);
    $this->setActiveFieldImProviders($this->_mapperImProvider);

    // FIXME: we should do this in one place together with Form/MapField.php
    $this->_contactIdIndex = -1;
    $this->_membershipTypeIndex = -1;
    $this->_membershipStatusIndex = -1;

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
      elseif($key == 'membership_contact_id'){
        $this->_contactIdIndex = $index;
      }
      elseif($key == 'membership_type_id'){
        $this->_membershipTypeIndex = $index;
      }
      elseif($key == 'status_id'){
        $this->_membershipStatusIndex = $index;
      }
      $index++;
    }

    // create parser object for contact import, #18222, #23651
    $this->_parserContact = new CRM_Import_Parser_Contact(
      $this->_mapperKeys,
      $this->_mapperLocType,
      $this->_mapperPhoneType,
      $this->_mapperImProvider
    );
    $this->_parserContact->_onDuplicate = CRM_Import_Parser::DUPLICATE_SKIP;
    $this->_parserContact->_contactType = $this->_contactType;
    $this->_parserContact->_dedupeRuleGroupId = $this->_dedupeRuleGroupId;
    $this->_parserContact->_contactLog = ts('Import Contact').' '.ts('From').' '.ts('Import Membership');
    $this->_parserContact->init();
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
    return CRM_Member_Import_Parser::VALID;
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
    require_once 'CRM/Import/Parser/Contact.php';
    $errorMessage = NULL;

    $errorRequired = FALSE;

    if ($this->_membershipTypeIndex < 0) {
      $errorRequired = TRUE;
    }
    else {
      $errorRequired = !CRM_Utils_Array::value($this->_membershipTypeIndex, $values);
    }

    if ($errorRequired) {
      array_unshift($values, ts('Missing required fields'));
      return CRM_Member_Import_Parser::ERROR;
    }

    $params = &$this->getActiveFieldParams();

    $errorMessage = NULL;


    //To check whether start date or join date is provided
    if (!$params['membership_start_date'] && !$params['join_date']) {
      $errorMessage = "Membership Start Date is required to create a memberships.";
      CRM_Import_Parser_Contact::addToErrorMsg('Start Date', $errorMessage);
    }
    //end

    //for date-Formats
    $session = CRM_Core_Session::singleton();
    $dateType = $session->get("dateTypes");
    foreach ($params as $key => $val) {
      $is_deleted = NULL;
      if ($val) {
        switch ($key) {
          case 'join_date':
            if (CRM_Utils_Date::convertToDefaultDate($params, $dateType, $key)) {
              if (!CRM_Utils_Rule::date($params[$key])) {
                CRM_Import_Parser_Contact::addToErrorMsg('Join Date', $errorMessage);
              }
            }
            else {
              CRM_Import_Parser_Contact::addToErrorMsg('Join Date', $errorMessage);
            }
            break;

          case 'membership_start_date':
            if (CRM_Utils_Date::convertToDefaultDate($params, $dateType, $key)) {
              if (!CRM_Utils_Rule::date($params[$key])) {
                CRM_Import_Parser_Contact::addToErrorMsg('Start Date', $errorMessage);
              }
            }
            else {
              CRM_Import_Parser_Contact::addToErrorMsg('Start Date', $errorMessage);
            }
            break;

          case 'membership_end_date':
            if (CRM_Utils_Date::convertToDefaultDate($params, $dateType, $key)) {
              if (!CRM_Utils_Rule::date($params[$key])) {
                CRM_Import_Parser_Contact::addToErrorMsg('End date', $errorMessage);
              }
            }
            else {
              CRM_Import_Parser_Contact::addToErrorMsg('End date', $errorMessage);
            }
            break;

          case 'membership_type_id':
            if (!CRM_Utils_Array::crmInArray($val, CRM_Member_PseudoConstant::membershipType())) {
              CRM_Import_Parser_Contact::addToErrorMsg('Membership Type', $errorMessage);
            }
            break;

          case 'status_id':
            $statuses = CRM_Member_PseudoConstant::membershipStatus(NULL, NULL, 'name');
            $statusesLabel = CRM_Member_PseudoConstant::membershipStatus(NULL, NULL, 'label');
            if (is_numeric($val) && !array_key_exists($val, $statuses)) {
              CRM_Import_Parser_Contact::addToErrorMsg('Membership Status', $errorMessage);
            }
            elseif (!CRM_Utils_Array::crmInArray($val, $statues) && !CRM_Utils_Array::crmInArray($val, $statusesLabel)) {
              CRM_Import_Parser_Contact::addToErrorMsg('Membership Status', $errorMessage);
            }
            break;
          case 'contribution_contact_id':
            $is_deleted = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $val, 'is_deleted', 'id');
            if ($is_deleted) {
              CRM_Import_Parser_Contact::addToErrorMsg(ts('Deleted Contact(s): %1', array(1 => ts('Contact ID').'-'.$val)), $errorMessage);
            }
            break;
          case 'external_identifier':
            $is_deleted = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $val, 'is_deleted', 'external_identifier');
            if ($is_deleted) {
              CRM_Import_Parser_Contact::addToErrorMsg(ts('Deleted Contact(s): %1', array(1 => ts('External Identifier').'-'.$val)), $errorMessage);
            }
            break;
        }
      }
    }
    //date-Format part ends

    $params['contact_type'] = 'Membership';

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
      array_unshift($values, $tempMsg);
      $errorMessage = NULL;
      return CRM_Member_Import_Parser::ERROR;
    }

    return CRM_Member_Import_Parser::VALID;
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
    // first make sure this is a valid line
    $response = $this->summary($values);
    if ($response != CRM_Member_Import_Parser::VALID) {
      return $response;
    }

    $params = &$this->getActiveFieldParams();

    $session = CRM_Core_Session::singleton();
    $dateType = $session->get("dateTypes");
    $formatted = array();
    $customFields = CRM_Core_BAO_CustomField::getFields(CRM_Utils_Array::value('contact_type', $params));

    // don't add to recent items, CRM-4399
    $formatted['skipRecentView'] = TRUE;

    foreach ($params as $key => $val) {
      if ($val) {
        switch ($key) {
          case 'join_date':
            if (CRM_Utils_Date::convertToDefaultDate($params, $dateType, $key)) {
              if (!CRM_Utils_Rule::date($params[$key])) {
                CRM_Import_Parser_Contact::addToErrorMsg('Join Date', $errorMessage);
              }
            }
            else {
              CRM_Import_Parser_Contact::addToErrorMsg('Join Date', $errorMessage);
            }
            break;

          case 'membership_start_date':
            if (CRM_Utils_Date::convertToDefaultDate($params, $dateType, $key)) {
              if (!CRM_Utils_Rule::date($params[$key])) {
                CRM_Import_Parser_Contact::addToErrorMsg('Start Date', $errorMessage);
              }
            }
            else {
              CRM_Import_Parser_Contact::addToErrorMsg('Start Date', $errorMessage);
            }
            break;

          case 'membership_end_date':
            if (CRM_Utils_Date::convertToDefaultDate($params, $dateType, $key)) {
              if (!CRM_Utils_Rule::date($params[$key])) {
                CRM_Import_Parser_Contact::addToErrorMsg('End Date', $errorMessage);
              }
            }
            else {
              CRM_Import_Parser_Contact::addToErrorMsg('End Date', $errorMessage);
            }
            break;

          case 'is_override':
            $params[$key] = CRM_Utils_String::strtobool($val);
            break;
        }
        if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($key)) {
          if ($customFields[$customFieldID][2] == 'Date') {
            CRM_Import_Parser_Contact::formatCustomDate($params, $formatted, $dateType, $key);
            unset($params[$key]);
          }
          elseif ($customFields[$customFieldID][2] == 'Boolean') {
            $params[$key] = CRM_Utils_String::strtoboolstr($val);
          }
        }
      }
    }
    //date-Format part ends

    static $indieFields = NULL;
    if ($indieFields == NULL) {
      $tempIndieFields = &CRM_Member_DAO_Membership::import();
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
    if ($this->_createContactOption !== self::CONTACT_DONTCREATE &&
      ($paramValues['membership_contact_id'] || $paramValues['external_identifier'])
    ) {
      $paramValues['contact_type'] = $this->_contactType;
    }
    elseif ($onDuplicate == CRM_Member_Import_Parser::DUPLICATE_UPDATE && $paramValues[$this->_dataReferenceField]
    ) {
      $paramValues['contact_type'] = $this->_contactType;
    }

    $formatError = _civicrm_membership_formatted_param($paramValues, $formatted, TRUE);

    if ($formatError) {
      array_unshift($values, $formatError['error_message']);
      return CRM_Member_Import_Parser::ERROR;
    }

    if ($onDuplicate != CRM_Member_Import_Parser::DUPLICATE_UPDATE) {
      $formatted['custom'] = CRM_Core_BAO_CustomField::postProcess($formatted,
        CRM_Core_DAO::$_nullObject,
        NULL,
        'Membership'
      );
    }
    else {
      //fix for CRM-2219 Update Membership
      // onDuplicate == CRM_Member_Import_Parser::DUPLICATE_UPDATE
      if (CRM_Utils_Array::value('is_override', $formatted) &&
        !CRM_Utils_Array::value('status_id', $formatted)
      ) {
        array_unshift($values, "Required parameter missing: Status");
        return CRM_Member_Import_Parser::ERROR;
      }
      if($this->_dataReferenceField == 'membership_id' && $paramValues['membership_id']){
        $membership_id = $paramValues['membership_id'];
      }
      else if(preg_match('/^custom_/', $this->_dataReferenceField) && !empty($paramValues[$this->_dataReferenceField])){
        $field_id = str_replace('custom_', '', $this->_dataReferenceField);
        list($custom_table, $custom_field, $ignore) = CRM_Core_BAO_CustomField::getTableColumnGroup($field_id);
        $sql = "SELECT entity_id FROM $custom_table WHERE $custom_field = %1";
        $queryParams = array(1 => array($paramValues[$this->_dataReferenceField], 'String'));
        $membership_id = CRM_Core_DAO::singleValueQuery($sql, $queryParams);
      }

      if ($membership_id) {
        $dao = new CRM_Member_BAO_Membership();
        $dao->id = $membership_id;
        if ($dao->find(TRUE)) {
          $dates = array('join_date', 'start_date', 'end_date');
          foreach ($dates as $v) {
            if (empty($formatted[$v]) && !empty($dao->$v)) {
              $formatted[$v] = $dao->$v;
            }
          }
          $formatted['custom'] = CRM_Core_BAO_CustomField::postProcess($formatted,
            CRM_Core_DAO::$_nullObject,
            $membership_id,
            'Membership'
          );

          $ids = array(
            'membership' => $membership_id,
            'userId' => $session->get('userID'),
          );
          $newMembership = &CRM_Member_BAO_Membership::create($formatted, $ids, TRUE, 'Membership Renewal');
          // Workaround: why $formatted have reminder_date value 'null'
          // reference: CRM_Member_BAO_Membership::add(&$params, &$ids)
          if($formatted['reminder_date'] == 'null'){
            unset($formatted['reminder_date']);
          }
          $this->_newMemberships[] = $newMembership->id;
          if(empty($paramValues['membership_contact_id'])){
            $paramValues['membership_contact_id'] = $newMembership->contact_id;
          }

          if (civicrm_error($newMembership)) {
            if($this->_dataReferenceField == 'membership_id'){
              array_unshift($values, $newMembership['is_error'] . " for Membership ID " . $paramValues['membership_id'] . ". Row was skipped.");
            }
            else{
              array_unshift($values, $newMembership['is_error'] . " for Custom field and ID: " . $this->_dataReferenceField . ": " . $paramValues[$this->_dataReferenceField] . ". Row was skipped.");
            }
            return CRM_Member_Import_Parser::ERROR;
          }
          else{
            $this->_newMemberships[] = $newMembership->id;
            return CRM_Member_Import_Parser::VALID;
          }
        }
        else {
          array_unshift($values, "Matching Membership record not found for Membership ID " . $paramValues['membership_id'] . ". Row was skipped.");
          return CRM_Member_Import_Parser::ERROR;
        }
      }
    }

    //Format dates
    //assign join date equal to start date if join date is not provided
    if (!$params['join_date'] && $params['membership_start_date']) {
      $params['join_date'] = $params['membership_start_date'];
      $formatted['join_date'] = $params['join_date'];
    }

    $startDate = CRM_Utils_Date::customFormat($formatted['start_date'], '%Y-%m-%d');
    $endDate = CRM_Utils_Date::customFormat($formatted['end_date'], '%Y-%m-%d');
    $joinDate = CRM_Utils_Date::customFormat($formatted['join_date'], '%Y-%m-%d');

    $checkContactId = $this->checkContactById($paramValues);
    $errDisp = "";

    if ($this->_createContactOption == self::CONTACT_DONTCREATE) {
      $doCreateContact = FALSE;
    }
    elseif ($this->_createContactOption == self::CONTACT_NOIDCREATE) {
      if (!empty($paramValues['external_identifier']) || !empty($params['membership_contact_id'])) {
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
      //retrieve contact id using contact dedupe rule
      $paramValues['contact_type'] = $this->_contactType;
      $found = civicrm_check_contact_dedupe($paramValues);

      if (civicrm_duplicate($found)) {
        $matchedIDs = explode(',', $found['error_message']['params'][0]);
        if (count($matchedIDs) > 1) {
          array_unshift($values, "Multiple matching contact records detected for this row. The membership was not imported");
          return CRM_Member_Import_Parser::ERROR;
        }
        else {
          $doCreateContact = FALSE;
          $checkContactId = $matchedIDs[0];
        }
      }
      else {
        // Using new Dedupe rule for error message handling
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
        $fieldsArray = CRM_Dedupe_BAO_Rule::dedupeRuleFields($ruleParams);

        $dispArray = array();
        // workaround for #23859
        $this->_importableContactFields['sort_name']['title'] = ts('Sort Name');
        foreach ($fieldsArray as $value) {
          if ($doCreateContact) {
            if (!array_key_exists(trim($value), $params)) {
              $dispArray[] = $this->_importableContactFields[$value]['title'];
            }
          }
          elseif (array_key_exists(trim($value), $params)) {
            $paramValue = $params[trim($value)];
            if (is_array($paramValue)) {
              $dispArray[] = $params[trim($value)][0][trim($value)];
            }
            else {
              $dispArray[] = $params[trim($value)];
            }
          }
        }

        if (CRM_Utils_Array::value('external_identifier', $params) && !$doCreateContact) {
          $dispArray[] = $params['external_identifier'];
        }
        if (!empty($dispArray)) {
          if ($doCreateContact) {
            $errDisp = ts('Missing required contact matching fields.')." - ".implode('|', $dispArray);
          }
          else {
            $errDisp = "No matching Contact found for (" . implode('|', $dispArray) . ")";
          }
          $doCreateContact = FALSE;
        }
      }
    }

    if ($doCreateContact && empty($checkContactId)) {
      // trying to create new contact base on exists contact related params
      $doGeocodeAddress = FALSE;
      $contactImportResult = $this->_parserContact->import(CRM_Import_Parser::DUPLICATE_FILL, $contactValues, $doGeocodeAddress);
      $contactID = $this->_parserContact->getLastImportContactId();
      if (!empty($contactID) && $contactImportResult == CRM_Import_Parser::VALID) {
        $formatted['contact_id'] = $contactID;
        return $this->importMembership($formatted, $values);
      }
      else {
        $errDisp = $contactValues[0];
      }
    }
    else {
      if ($checkContactId) {
        $formatted['contact_id'] = $checkContactId;
        unset($formatted['membership_contact_id']);
        return $this->importMembership($formatted, $values);
      }
      elseif ($checkContactId === FALSE) {
        $errDisp = "Mismatch of External identifier :" . $paramValues['external_identifier'] . " and Contact Id:" . $formatted['contact_id'];
      }
    }

    // cache all for CRM_Contribute_Import_Parser::DUPLICATE_SKIP
    array_unshift($values, $errDisp);
    return CRM_Member_Import_Parser::ERROR;
  }

  /**
   * Get the array of succesfully imported membership id's
   *
   * @return array
   * @access public
   */
  function &getImportedMemberships() {
    return $this->_newMemberships;
  }

  /**
   * the initializer code, called before the processing
   *
   * @return void
   * @access public
   */
  function fini() {}

  /**
   *  to calculate join, start and end dates
   *
   *  @param Array $calcDates array of dates returned by getDatesForMembershipType()
   *
   *  @return Array formatted containing date values
   *
   *  @access public
   */
  function formattedDates($calcDates, &$formatted) {
    $dates = array('join_date',
      'start_date',
      'end_date',
      'reminder_date',
    );

    foreach ($dates as $d) {
      if (isset($formatted[$d]) &&
        !CRM_Utils_System::isNull($formatted[$d])
      ) {
        $formatted[$d] = CRM_Utils_Date::isoToMysql($formatted[$d]);
      }
      elseif (isset($calcDates[$d])) {
        $formatted[$d] = CRM_Utils_Date::isoToMysql($calcDates[$d]);
      }
    }
  }

  function importMembership($formatted, &$values) {
    $startDate = CRM_Utils_Date::customFormat($formatted['start_date'], '%Y-%m-%d');
    $endDate = CRM_Utils_Date::customFormat($formatted['end_date'], '%Y-%m-%d');
    $joinDate = CRM_Utils_Date::customFormat($formatted['join_date'], '%Y-%m-%d');
    //to calculate dates
    $calcDates = CRM_Member_BAO_MembershipType::getDatesForMembershipType($formatted['membership_type_id'],
      $joinDate,
      $startDate,
      $endDate
    );
    self::formattedDates($calcDates, $formatted);
    //end of date calculation part

    //fix for CRM-3570, exclude the statuses those having is_admin = 1
    //now user can import is_admin if is override is true.
    $excludeIsAdmin = FALSE;
    if (!CRM_Utils_Array::value('is_override', $formatted)) {
      $formatted['exclude_is_admin'] = $excludeIsAdmin = TRUE;
    }
    $calcStatus = CRM_Member_BAO_MembershipStatus::getMembershipStatusByDate($startDate,
      $endDate,
      $joinDate,
      'today',
      $excludeIsAdmin
    );
    if (!$formatted['status_id']) {
      $formatted['status_id'] = $calcStatus['id'];
    }
    elseif (!CRM_Utils_Array::value('is_override', $formatted)) {
      if (empty($calcStatus)) {
        array_unshift($values, "Status in import row (" . $paramValues['status_id'] . ") does not match calculated status based on your configured Membership Status Rules. Record was not imported.");
        return CRM_Member_Import_Parser::ERROR;
      }
      elseif ($formatted['status_id'] != $calcStatus['id']) {
        //Status Hold" is either NOT mapped or is FALSE
        array_unshift($values, "Status in import row (" . $paramValues['status_id'] . ") does not match calculated status based on your configured Membership Status Rules (" . $calcStatus['name'] . "). Record was not imported.");
        return CRM_Member_Import_Parser::ERROR;
      }
    }
    $newMembership = civicrm_contact_membership_create($formatted);
    if (civicrm_error($newMembership)) {
      array_unshift($values, $newMembership['error_message']);
      return CRM_Member_Import_Parser::ERROR;
    }

    $this->_newMemberships[] = $newMembership['id'];
    return CRM_Member_Import_Parser::VALID;
  }


  function checkContactById($params) {
    $pass = $contactID = 0;
    $checkCid = new CRM_Contact_DAO_Contact();
    if (!empty($params['external_identifier'])) {
      $checkCid->external_identifier = $params['external_identifier'];
      $checkCid->is_deleted = 0;
      if($checkCid->find(TRUE)){
        $contactID = $checkCid->id;
      }
    }

    if (!empty($params['membership_contact_id'])) {
      if (!empty($contactID)) {
        if ($contactID != $params['membership_contact_id'] ){
          $pass = FALSE;
        }
        else {
          $pass = $contactID;
        }
      }
      else {
        $checkCid->id = $params['membership_contact_id'];
        $checkCid->is_deleted = 0;
        if($checkCid->find(TRUE)){
          $contactID = $checkCid->id;
          $pass = $contactID;
        }
      }
    }
    elseif(!empty($contactID)) {
      $pass = $contactID;
    }
    $checkCid->free();

    return $pass;
  }
}
