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

require_once 'api/v2/utils.php';

/**
 * class to parse contact csv files
 */
class CRM_Import_Parser_Contact extends CRM_Import_Parser {
  public $_mapperRelatedContactLocType;
  public $_mapperRelatedContactPhoneType;
  public $_contactSubType;
  public $_emailIndex;
  /**
   * @var mixed[]
   */
  public $_dedupeRuleFieldsLabel;
  public $_dateFormats;
  protected $_mapperKeys;
  protected $_mapperLocType;
  protected $_mapperPhoneType;
  protected $_mapperImProvider;
  protected $_mapperWebsiteType;
  protected $_mapperRelated;
  protected $_mapperRelatedContactType;
  protected $_mapperRelatedContactDetails;
  protected $_mapperRelatedContactEmailType;
  protected $_mapperRelatedContactImProvider;
  protected $_mapperRelatedContactWebsiteType;
  protected $_relationships;

  protected $_allEmails;

  protected $_phoneIndex;
  protected $_updateWithId;
  protected $_retCode;

  protected $_externalIdentifierIndex;
  protected $_allExternalIdentifiers;
  protected $_internalIdentifierIndex;
  protected $_allInternalIdentifiers;
  protected $_parseStreetAddress;
  protected $_lastImportContactId;

  /**
   * Array of succesfully imported contact id's
   *
   * @array
   */
  protected $_newContacts;

  /**
   * line count id
   *
   * @var int
   */
  protected $_lineCount;

  /**
   * Array of succesfully imported related contact id's
   *
   * @array
   */
  protected $_newRelatedContacts;

  /**
   * array of all the contacts whose street addresses are not parsed
   * of this import process
   * @var array
   */
  protected $_unparsedStreetAddressContacts;

  protected $_requiredFields;

  /**
   * class constructor
   */
  function __construct(&$mapperKeys, $mapperLocType = NULL, $mapperPhoneType = NULL,
    $mapperImProvider = NULL, $mapperRelated = NULL, $mapperRelatedContactType = NULL,
    $mapperRelatedContactDetails = NULL, $mapperRelatedContactLocType = NULL,
    $mapperRelatedContactPhoneType = NULL, $mapperRelatedContactImProvider = NULL,
    $mapperWebsiteType = NULL, $mapperRelatedContactWebsiteType = NULL
  ) {
    parent::__construct();
    $this->_mapperKeys = &$mapperKeys;
    $this->_mapperLocType = &$mapperLocType;
    $this->_mapperPhoneType = &$mapperPhoneType;
    $this->_mapperWebsiteType = $mapperWebsiteType;
    // get IM service provider type id for contact
    $this->_mapperImProvider = &$mapperImProvider;
    $this->_mapperRelated = &$mapperRelated;
    $this->_mapperRelatedContactType = &$mapperRelatedContactType;
    $this->_mapperRelatedContactDetails = &$mapperRelatedContactDetails;
    $this->_mapperRelatedContactLocType = &$mapperRelatedContactLocType;
    $this->_mapperRelatedContactPhoneType = &$mapperRelatedContactPhoneType;
    $this->_mapperRelatedContactWebsiteType = $mapperRelatedContactWebsiteType;
    // get IM service provider type id for related contact
    $this->_mapperRelatedContactImProvider = &$mapperRelatedContactImProvider;

  }

  /**
   * the initializer code, called before the processing
   *
   * @return void
   * @access public
   */
  function init() {


    $contactFields = CRM_Contact_BAO_Contact::importableFields($this->_contactType);
    // exclude the address options disabled in the Address Settings
    $fields = CRM_Core_BAO_Address::validateAddressOptions($contactFields);

    //CRM-5125
    //supporting import for contact subtypes
    if (!empty($this->_contactSubType)) {
      //custom fields for sub type
      $subTypeFields = CRM_Core_BAO_CustomField::getFieldsForImport($this->_contactSubType);

      if (!empty($subTypeFields)) {
        foreach ($subTypeFields as $customSubTypeField => $details) {
          $fields[$customSubTypeField] = $details;
        }
      }
    }

    //Relationship importables
    $this->_relationships = $relations = CRM_Contact_BAO_Relationship::getContactRelationshipType(NULL, NULL, NULL, $this->_contactType,
      FALSE, 'label', TRUE, $this->_contactSubType
    );
    asort($relations);

    foreach ($relations as $key => $var) {
      list($type) = explode('_', $key);
      $relationshipType[$key]['title'] = $var;
      $relationshipType[$key]['headerPattern'] = '/' . preg_quote($var, '/') . '/';
      $relationshipType[$key]['import'] = TRUE;
      $relationshipType[$key]['relationship_type_id'] = $type;
      $relationshipType[$key]['related'] = TRUE;
    }

    if (!empty($relationshipType)) {
      $fields = array_merge($fields,
        ['related' => ['title' => ts('- related contact info -')]],
        $relationshipType
      );
    }

    foreach ($fields as $name => $field) {
      $this->addField($name,
        $field['title'],
        CRM_Utils_Array::value('type', $field),
        CRM_Utils_Array::value('headerPattern', $field),
        CRM_Utils_Array::value('dataPattern', $field),
        CRM_Utils_Array::value('hasLocationType', $field)
      );
    }

    $this->_newContacts = [];

    $this->setActiveFields($this->_mapperKeys);
    $this->setActiveFieldLocationTypes($this->_mapperLocType);
    $this->setActiveFieldPhoneTypes($this->_mapperPhoneType);
    $this->setActiveFieldWebsiteTypes($this->_mapperWebsiteType);
    //set active fields of IM provider of contact
    $this->setActiveFieldImProviders($this->_mapperImProvider);

    //related info
    $this->setActiveFieldRelated($this->_mapperRelated);
    $this->setActiveFieldRelatedContactType($this->_mapperRelatedContactType);
    $this->setActiveFieldRelatedContactDetails($this->_mapperRelatedContactDetails);
    $this->setActiveFieldRelatedContactLocType($this->_mapperRelatedContactLocType);
    $this->setActiveFieldRelatedContactPhoneType($this->_mapperRelatedContactPhoneType);
    $this->setActiveFieldRelatedContactWebsiteType($this->_mapperRelatedContactWebsiteType);
    //set active fields of IM provider of related contact
    $this->setActiveFieldRelatedContactImProvider($this->_mapperRelatedContactImProvider);

    $this->_emailIndex = $this->_phoneIndex = $this->_externalIdentifierIndex = $this->_internalIdentifierIndex = -1;

    $index = 0;
    foreach ($this->_mapperKeys as $key) {
      if (substr($key, 0, 5) == 'email' && substr($key, 0, 14) != 'email_greeting') {
        $this->_emailIndex = $index;
      }
      if (substr($key, 0, 5) == 'phone') {
        $this->_phoneIndex = $index;
      }

      if ($key == 'external_identifier') {
        $this->_externalIdentifierIndex = $index;
        $this->_allExternalIdentifiers = [];
      }
      if ($key == 'id') {
        $this->_internalIdentifierIndex = $index;
        $this->_allInternalIdentifiers = [];
      }

      $index++;
    }

    $this->_updateWithId = FALSE;
    if ($this->_internalIdentifierIndex >= 0 || ( $this->_externalIdentifierIndex >= 0 && in_array($this->_onDuplicate, [CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::DUPLICATE_FILL, CRM_Import_Parser::DUPLICATE_SKIP]) )
    ) {
      $this->_updateWithId = TRUE;
    }


    $this->_parseStreetAddress = CRM_Utils_Array::value('street_address_parsing',
      CRM_Core_BAO_Preferences::valueOptions('address_options'),
      FALSE
    );

    if (!empty($this->_dedupeRuleGroupId)) {
      $ruleParams = ['id' => $this->_dedupeRuleGroupId];
      $this->_requiredFields = CRM_Dedupe_BAO_Rule::dedupeRuleFieldsMapping($ruleParams);
      // correct sort_name / display_name problem
      $hasSortName = array_search('sort_name', $this->_requiredFields);
      $hasDisplayName = array_search('display_name', $this->_requiredFields);
      if ($hasSortName !== FALSE) {
        unset($this->_requiredFields[$hasSortName]);
      }
      if ($hasDisplayName !== FALSE) {
        unset($this->_requiredFields[$hasDisplayName]);
      }
      if ($hasSortName !== FALSE || $hasDisplayName !== FALSE) {
        $this->_requiredFields[] = 'last_name';
        $this->_requiredFields[] = 'first_name';
      }
      $supportedFields = CRM_Dedupe_BAO_RuleGroup::supportedFields($this->_contactType);
      foreach($supportedFields as $array) {
        foreach($array as $name => $label){
          if (in_array($name, $this->_requiredFields)) {
            $this->_dedupeRuleFieldsLabel[$name] = $label;
          } 
        }
      }
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
    return CRM_Import_Parser::VALID;
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
    $response = $this->setActiveFieldValues($values);
    $this->_lineCount++;

    $errorMessage = NULL;
    $errorRequired = FALSE;
    $missingNames = [];

    if (!empty($this->_requiredFields)) {
      foreach($this->_requiredFields as $fieldName) {
        if ($fieldName == 'email' && $this->_emailIndex < 0) {
          $errorRequired = TRUE;
          $missingNames[] = ts('Email Address');
        }
        else {
          if ($fieldName == 'state_province_id') {
            if (!in_array('state_province', $this->_mapperKeys)) {
              $missingNames[] = $this->_dedupeRuleFieldsLabel[$fieldName];
            }
          }
          elseif (!in_array($fieldName, $this->_mapperKeys)) {
            $errorRequired = TRUE;
            $missingNames[] = $this->_dedupeRuleFieldsLabel[$fieldName];
          }
        }
      }
    }

    $statusFieldName = $this->_statusFieldName;

    // missing required dedupe fields, fail
    if ($errorRequired && !$this->_updateWithId) {
      $errorMessage = ts('Missing required fields:') . CRM_Utils_Array::implode(' '.ts('and').' ', $missingNames);
      array_unshift($values, $errorMessage);
      $importRecordParams = [$statusFieldName => CRM_Import_Parser::ERROR, "{$statusFieldName}Msg" => $errorMessage];
      $this->updateImportStatus($values[count($values) - 1], $importRecordParams);

      return CRM_Import_Parser::ERROR;
    }

    // If the email address isn't valid, fail
    if ($this->_emailIndex >= 0) {
      $email = CRM_Utils_Array::value($this->_emailIndex, $values);
      if (!empty($email)) {
        if (!CRM_Utils_Rule::email($email)) {
          $errorMessage = ts('Invalid Email address');
          array_unshift($values, $errorMessage);
          $importRecordParams = [$statusFieldName => CRM_Import_Parser::ERROR, "{$statusFieldName}Msg" => $errorMessage];
          $this->updateImportStatus($values[count($values) - 1], $importRecordParams);

          return CRM_Import_Parser::ERROR;
        }
      }
    }

    //check for duplicate external Identifier
    $externalID = CRM_Utils_Array::value($this->_externalIdentifierIndex, $values);
    if ($externalID) {
      /* If it's a dupe,external Identifier  */
      if ($externalDupe = CRM_Utils_Array::value($externalID, $this->_allExternalIdentifiers)) {
        $errorMessage = ts('External Identifier conflicts with record %1', [1 => $externalDupe]);
        array_unshift($values, $errorMessage);
        $importRecordParams = [$statusFieldName => CRM_Import_Parser::CONFLICT, "{$statusFieldName}Msg" => $errorMessage];
        $this->updateImportStatus($values[count($values) - 1], $importRecordParams);
        return CRM_Import_Parser::CONFLICT;
      }
      //otherwise, count it and move on
      $this->_allExternalIdentifiers[$externalID] = $this->_lineCount;
    }
    
    //check for duplicate internal Identifier
    $internalID = CRM_Utils_Array::value($this->_internalIdentifierIndex, $values);
    if ($internalID) {
      /* If it's a dupe, internalIdentifier  */
      if ($internalDupe = CRM_Utils_Array::value($internalID, $this->_allInternalIdentifiers)) {
        $errorMessage = ts('Contact ID conflicts with record %1', [1 => $internalDupe]);
        array_unshift($values, $errorMessage);
        $importRecordParams = [$statusFieldName => CRM_Import_Parser::CONFLICT, "{$statusFieldName}Msg" => $errorMessage];
        $this->updateImportStatus($values[count($values) - 1], $importRecordParams);
        return CRM_Import_Parser::CONFLICT;
      }
      else {
        $errorRequired = FALSE;
        $isDeleted = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $internalID, 'is_deleted', 'id');
        if ($isDeleted) {
          self::addToErrorMsg(ts('Deleted Contact(s): %1', [1 => ts('Contact ID').'-'.$internalID]), $errorMessage);
          $errorRequired = TRUE;
        }
        else {
          $contactExists = self::checkContactById(['id' => $internalID], 'id');
          if (!$contactExists) {
            self::addToErrorMsg(ts('Could not find contact by %1', [1 => ts('Contact ID').'-'.$internalID]), $errorMessage);
            $errorRequired = TRUE;
          }
        }
        if ($errorRequired) {
          array_unshift($values, $errorMessage);
          $importRecordParams = [$statusFieldName => CRM_Import_Parser::ERROR, "{$statusFieldName}Msg" => $errorMessage];
          $this->updateImportStatus($values[count($values) - 1], $importRecordParams);
          return CRM_Import_Parser::ERROR;
        }
      }

      //otherwise, count it and move on
      $this->_allInternalIdentifiers[$internalID] = $this->_lineCount;
    }

    //Checking error in custom data
    $params = &$this->getActiveFieldParams();
    $params['contact_type'] = $this->_contactType;
    //date-format part ends

    $errorMessage = NULL;

    //checking error in custom data

    $this->isErrorInCustomData($params, $errorMessage);

    //checking error in core data
    $this->isErrorInCoreData($params, $errorMessage);
    if ($errorMessage) {
      $tempMsg = ts("Invalid value for field(s)").":".$errorMessage;
      // put the error message in the import record in the DB
      $importRecordParams = [$statusFieldName => CRM_Import_Parser::ERROR, "{$statusFieldName}Msg" => $tempMsg];
      $this->updateImportStatus($values[count($values) - 1], $importRecordParams);
      array_unshift($values, $tempMsg);
      $errorMessage = NULL;
      return CRM_Import_Parser::ERROR;
    }

    //if user correcting errors by walking back
    //need to reset status ERROR msg to null
    //now currently we are having valid data.
    $importRecordParams = [$statusFieldName => CRM_Import_Parser::PENDING];
    $this->updateImportStatus($values[count($values) - 1], $importRecordParams);

    return CRM_Import_Parser::VALID;
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
  function import($onDuplicate, &$values, $doGeocodeAddress = FALSE) {
    $this->_lastImportContactId = 0;
    $config = &CRM_Core_Config::singleton();
    $this->_unparsedStreetAddressContacts = [];
    if (!$doGeocodeAddress) {
      // CRM-5854, reset the geocode method to null to prevent geocoding
      $config->geocodeMethod = NULL;
    }

    // first make sure this is a valid line
    //$this->_updateWithId = false;
    $response = $this->summary($values);

    $statusFieldName = $this->_statusFieldName;

    if ($response != CRM_Import_Parser::VALID) {
      // summary already update status to database table
      return $response;
    }

    $params = &$this->getActiveFieldParams();
    $groupNames = $tagNames = [];
    if (!empty($params['group_name'])) {
      $params['group_name'] = str_replace('|', ',', $params['group_name']);
      $groupNames = explode(',', $params['group_name']);
      unset($params['group_name']);
    }
    if (!empty($params['tag_name'])) {
      $params['tag_name'] = str_replace('|', ',', $params['tag_name']);
      $tagNames = explode(',', $params['tag_name']);
      unset($params['tag_name']);
    }
    $formatted = ['contact_type' => $this->_contactType];

    static $contactFields = NULL;
    if ($contactFields == NULL) {

      $contactFields = &CRM_Contact_DAO_Contact::import();
    }

    //check if external identifier exists in database
    if (CRM_Utils_Array::value('external_identifier', $params) &&
      (CRM_Utils_Array::value('id', $params) ||
        in_array($onDuplicate, [CRM_Import_Parser::DUPLICATE_SKIP, CRM_Import_Parser::DUPLICATE_NOCHECK])
      )
    ) {


      if ($internalCid = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
          $params['external_identifier'],
          'id',
          'external_identifier'
        )) {
        if ($internalCid != CRM_Utils_Array::value('id', $params)) {

          $errorMessage = ts('External Identifier already exists in database.');
          array_unshift($values, $errorMessage);
          $importRecordParams = [$statusFieldName => CRM_Import_Parser::ERROR, "{$statusFieldName}Msg" => $errorMessage];
          $this->updateImportStatus($values[count($values) - 1], $importRecordParams);
          return CRM_Import_Parser::ERROR;
        }
      }
    }

    if (!empty($this->_contactSubType)) {
      $params['contact_sub_type'] = $this->_contactSubType;
    }

    if ($subType = CRM_Utils_Array::value('contact_sub_type', $params)) {
      if (CRM_Contact_BAO_ContactType::isExtendsContactType($subType, $this->_contactType, FALSE, 'label')) {
        $subTypes = CRM_Contact_BAO_ContactType::subTypePairs($this->_contactType, FALSE, NULL);
        $params['contact_sub_type'] = array_search($subType, $subTypes);
      }
      elseif (!CRM_Contact_BAO_ContactType::isExtendsContactType($subType, $this->_contactType)) {
        $message = "Mismatched or Invalid Contact SubType.";
        array_unshift($values, $message);
        return CRM_Import_Parser::NO_MATCH;
      }
    }

    //get contact id to format common data in update/fill mode,
    //if external identifier is present, CRM-4423
    if ($this->_updateWithId &&
      !CRM_Utils_Array::value('id', $params) &&
      CRM_Utils_Array::value('external_identifier', $params)
    ) {
      if ($cid = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
          $params['external_identifier'], 'id',
          'external_identifier'
        )) {
        $formatted['id'] = $cid;
      }
    }

    //format common data, CRM-4062
    $this->formatCommonData($params, $formatted, $contactFields);
    $formatted['check_permission'] = FALSE; // remove acl join

    $relationship = FALSE;
    $createNewContact = TRUE;
    // Support Match and Update Via Contact ID
    if ($this->_updateWithId) {
      $createNewContact = FALSE;
      if (!CRM_Utils_Array::value('id', $params) && CRM_Utils_Array::value('external_identifier', $params)) {
        if ($cid) {
          $params['id'] = $cid;
        }
        else {
          //update contact if dedupe found contact id, CRM-4148
          $dedupeParams = $formatted;

          //special case to check dedupe if external id present.
          //if we send external id dedupe will stop.
          unset($dedupeParams['external_identifier']);

          $checkDedupe = _civicrm_duplicate_formatted_contact($dedupeParams);
          if (civicrm_duplicate($checkDedupe)) {
            $matchingContactIds = explode(',', $checkDedupe['error_message']['params'][0]);
            if (count($matchingContactIds) == 1) {
              $params['id'] = array_pop($matchingContactIds);
            }
            else {
              $errorMessage = ts('Record duplicates multiple contacts');
              $importRecordParams = [$statusFieldName => CRM_Import_Parser::NO_MATCH, "{$statusFieldName}Msg" => $errorMessage];
              array_unshift($values, $message);
              $this->updateImportStatus($values[count($values) - 1], $importRecordParams);
              return CRM_Import_Parser::NO_MATCH;
            }
          }
          else {
            $createNewContact = TRUE;
          }
        }
      }

      $error = _civicrm_duplicate_formatted_contact($formatted);
      if (civicrm_duplicate($error)) {
        $matchedIDs = explode(',', $error['error_message']['params'][0]);
        if (count($matchedIDs) >= 1) {
          $updateflag = TRUE;
          foreach ($matchedIDs as $contactId) {
            if ($params['id'] == $contactId) {
              $contactType = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
                $params['id'],
                'contact_type'
              );

              if ($formatted['contact_type'] == $contactType) {

                //validation of subtype for update mode
                //CRM-5125
                $contactSubType = NULL;
                if (CRM_Utils_Array::value('contact_sub_type', $params)) {
                  $contactSubType = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
                    $params['id'],
                    'contact_sub_type'
                  );
                }

                if (!empty($contactSubType) &&
                  (!CRM_Contact_BAO_ContactType::isAllowEdit($params['id'], $contactSubType) &&
                    $contactSubType != CRM_Utils_Array::value('contact_sub_type', $formatted)
                  )
                ) {

                  $message = "Mismatched contact SubTypes :";
                  array_unshift($values, $message);
                  $updateflag = FALSE;
                  $this->_retCode = CRM_Import_Parser::NO_MATCH;
                }
                else {

                  $newContact = $this->createContact($formatted, $contactFields,
                    $onDuplicate, $contactId, FALSE
                  );
                  $updateflag = FALSE;
                  $this->_retCode = CRM_Import_Parser::VALID;
                }
              }
              else {
                $message = "Mismatched contact Types :";
                array_unshift($values, $message);
                $updateflag = FALSE;
                $this->_retCode = CRM_Import_Parser::NO_MATCH;
              }
            }
          }
          if ($updateflag) {
            $message = "Mismatched contact IDs OR Mismatched contact Types :";
            array_unshift($values, $message);
            $this->_retCode = CRM_Import_Parser::NO_MATCH;
          }
        }
      }
      else {
        $contactType = NULL;
        if (CRM_Utils_Array::value('id', $params)) {
          $contactType = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
            $params['id'],
            'contact_type'
          );
          if ($contactType) {
            if ($formatted['contact_type'] == $contactType) {
              //validation of subtype for update mode
              //CRM-5125
              $contactSubType = NULL;
              if (CRM_Utils_Array::value('contact_sub_type', $params)) {
                $contactSubType = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
                  $params['id'],
                  'contact_sub_type'
                );
              }

              if (!empty($contactSubType) &&
                (!CRM_Contact_BAO_ContactType::isAllowEdit($params['id'], $contactSubType) &&
                  $contactSubType != CRM_Utils_Array::value('contact_sub_type', $formatted)
                )
              ) {

                $message = "Mismatched contact SubTypes :";
                array_unshift($values, $message);
                $this->_retCode = CRM_Import_Parser::NO_MATCH;
              }
              else {
                $newContact = $this->createContact($formatted, $contactFields,
                  $onDuplicate, $params['id'], FALSE
                );

                $this->_retCode = CRM_Import_Parser::VALID;
              }
            }
            else {
              $message = "Mismatched contact Types :";
              array_unshift($values, $message);
              $this->_retCode = CRM_Import_Parser::NO_MATCH;
            }
          }
          else {
            // we should avoid multiple errors for single record
            // since we have already retCode and we trying to force again.
            if ($this->_retCode != CRM_Import_Parser::NO_MATCH) {
              $message = "No contact found for this contact ID:" . $params['id'];
              array_unshift($values, $message);
              $this->_retCode = CRM_Import_Parser::NO_MATCH;
            }
          }
        }
        else {
          //CRM-4148
          //now we want to create new contact on update/fill also.
          $createNewContact = TRUE;
        }
      }

      if (is_a($newContact, 'CRM_Contact_BAO_Contact')) {
        $relationship = TRUE;
      }
      elseif (is_a($error, 'CRM_Core_Error')) {
        $newContact = $error;
        $relationship = TRUE;
      }
    }

    //fixed CRM-4148
    //now we create new contact in update/fill mode also.
    if ($createNewContact) {

      //CRM-4430, don't carry if not submitted.
      foreach (['prefix', 'suffix', 'gender'] as $name) {
        if (CRM_Utils_Array::arrayKeyExists($name, $formatted)) {
          if (in_array($name, ['prefix', 'suffix'])) {
            $formattedName = "individual_{$name}";
            $formatted[$formattedName] = CRM_Core_OptionGroup::getValue($formattedName, (string)$formatted[$name]);
          }
          else {
            $formatted[$name] = CRM_Core_OptionGroup::getValue($name, (string)$formatted[$name]);
          }
        }
      }
      $newContact = $this->createContact($formatted, $contactFields, $onDuplicate);
    }

    if (is_object($newContact) || ($newContact instanceof CRM_Contact_BAO_Contact)) {
      $relationship = TRUE;
      $newContact = clone($newContact);
      $contactID = $newContact->id;
      $this->_newContacts[] = $newContact->id;

      //get return code if we create new contact in update mode, CRM-4148
      if ($this->_updateWithId) {
        $this->_retCode = CRM_Import_Parser::VALID;
      }
    }
    elseif (civicrm_duplicate($newContact)) {
      // if duplicate, no need of further processing
      if ($onDuplicate == CRM_Import_Parser::DUPLICATE_SKIP) {
        $errorMessage = ts("On duplicate entries").":".ts("Skip");
        array_unshift($values, $errorMessage);
        $importRecordParams = [$statusFieldName => CRM_Import_Parser::DUPLICATE, "{$statusFieldName}Msg" => $errorMessage];
        $this->updateImportStatus($values[count($values) - 1], $importRecordParams);
        return CRM_Import_Parser::DUPLICATE;
      }

      $relationship = TRUE;
      $contactID = $newContact['error_message']['params'][0];
      if (!in_array($contactID, $this->_newContacts)) {
        $this->_newContacts[] = $contactID;
      }
    }

    #file_put_contents('/tmp/imp', $contactID."\n", FILE_APPEND);

    if ($contactID) {
      // call import hook

      $currentImportID = end($values);
      $this->_lastImportContactId = $contactID;

      $hookParams = ['contactID' => $contactID,
        'importID' => $currentImportID,
        'importTempTable' => $this->_tableName,
        'fieldHeaders' => $this->_mapperKeys,
        'fields' => $this->_activeFields,
      ];

      CRM_Utils_Hook::import('Contact',
        'process',
        $this,
        $hookParams
      );

      // import group and tag when each contact
      $addIds = [$contactID];
      if ($this->_job->_newGroupName || !empty($this->_job->_groups)) {
        $this->_job->addImportedContactsToNewGroup($addIds, $this->_job->_newGroupName, $this->_job->_newGroupDesc);
      }
      if ($this->_job->_newTagName || !empty($this->_job->_tag)) {
        $this->_job->tagImportedContactsWithNewTag($addIds, $this->_job->_newTagName, $this->_job->_newTagDesc);
      }
      if (!empty($groupNames) || !empty($tagNames)) {
        $this->_job->addContactToGroupTag($contactID, $groupNames, $tagNames);
      }
    }

    if ($relationship) {
      $primaryContactId = NULL;
      if (civicrm_duplicate($newContact)) {
        if (CRM_Utils_Rule::integer($newContact['error_message']['params'][0])) {
          $primaryContactId = $newContact['error_message']['params'][0];
        }
      }
      else {
        $primaryContactId = $newContact->id;
      }

      if ((civicrm_duplicate($newContact) || is_a($newContact, 'CRM_Contact_BAO_Contact'))
        && $primaryContactId
      ) {

        //relationship contact insert
        foreach ($params as $key => $field) {
          list($id, $first, $second) = CRM_Utils_System::explode('_', $key, 3);
          if (!($first == 'a' && $second == 'b') && !($first == 'b' && $second == 'a')) {
            continue;
          }

          $relationType = new CRM_Contact_DAO_RelationshipType();
          $relationType->id = $id;
          $relationType->find(TRUE);
          $direction = "contact_sub_type_$second";

          $formatting = ['contact_type' => $params[$key]['contact_type']];

          //set subtype for related contact CRM-5125
          if (isset($relationType->$direction)) {
            //validation of related contact subtype for update mode
            if ($relCsType = CRM_Utils_Array::value('contact_sub_type', $params[$key])
              && $relCsType != $relationType->$direction
            ) {
              $errorMessage = ts("Mismatched or Invalid contact subtype found for this related contact");
              array_unshift($values, $errorMessage);
              return CRM_Import_Parser::NO_MATCH;
            }
            else {
              $formatting['contact_sub_type'] = $relationType->$direction;
            }
          }
          $relationType->free();

          $contactFields = NULL;
          $contactFields = CRM_Contact_DAO_Contact::import();

          //Relation on the basis of External Identifier.
          if (!CRM_Utils_Array::value('id', $params[$key]) && !empty($params[$key]['external_identifier'])) {
            $params[$key]['id'] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
              $params[$key]['external_identifier'], 'id',
              'external_identifier'
            );
          }
          // check for valid related contact id in update/fill mode, CRM-4424
          if (in_array($onDuplicate, [CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::DUPLICATE_FILL]) && CRM_Utils_Array::value('id', $params[$key])) {
            $relatedContactType = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
              $params[$key]['id'],
              'contact_type'
            );
            if (!$relatedContactType) {
              $errorMessage = ts("No contact found for this related contact ID: %1", [1 => $params[$key]['id']]);
              array_unshift($values, $errorMessage);
              return CRM_Import_Parser::NO_MATCH;
            }
            else {
              //validation of related contact subtype for update mode
              //CRM-5125
              $relatedCsType = NULL;
              if (CRM_Utils_Array::value('contact_sub_type', $formatting)) {
                $relatedCsType = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
                  $params[$key]['id'],
                  'contact_sub_type'
                );
              }

              if (!empty($relatedCsType) &&
                (!CRM_Contact_BAO_ContactType::isAllowEdit($params[$key]['id'], $relatedCsType) &&
                  $relatedCsType != CRM_Utils_Array::value('contact_sub_type', $formatting)
                )
              ) {
                $errorMessage = ts("Mismatched or Invalid contact subtype found for this related contact ID: %1", [1 => $params[$key]['id']]);
                array_unshift($values, $errorMessage);
                return CRM_Import_Parser::NO_MATCH;
              }
              else {
                // get related contact id to format data in update/fill mode,
                //if external identifier is present, CRM-4423
                $formatting['id'] = $params[$key]['id'];
              }
            }
          }

          //format common data, CRM-4062
          $this->formatCommonData($field, $formatting, $contactFields);

          //do we have enough fields to create related contact.
          $allowToCreate = $this->checkRelatedContactFields($key, $formatting);

          if (!$allowToCreate) {
            $errorMessage = ts('Related contact required fields are missing.');
            array_unshift($values, $errorMessage);
            return CRM_Import_Parser::NO_MATCH;
          }

          //fixed for CRM-4148
          if ($params[$key]['id']) {
            $contact = ['contact_id' => $params[$key]['id']];
            $defaults = [];
            $relatedNewContact = CRM_Contact_BAO_Contact::retrieve($contact, $defaults);
          }
          else {
            $formatting['check_permission'] = FALSE; // remove acl join
            $relatedNewContact = $this->createContact($formatting, $contactFields,
              $onDuplicate, NULL, FALSE
            );
          }

          if (is_object($relatedNewContact) || ($relatedNewContact instanceof CRM_Contact_BAO_Contact)) {
            $relatedNewContact = clone($relatedNewContact);
          }

          $matchedIDs = [];
          // To update/fill contact, get the matching contact Ids if duplicate contact found
          // otherwise get contact Id from object of related contact
          if (is_array($relatedNewContact) && civicrm_error($relatedNewContact)) {
            if (civicrm_duplicate($relatedNewContact)) {
              $matchedIDs = explode(',', $relatedNewContact['error_message']['params'][0]);
            }
            else {
              $errorMessage = $relatedNewContact['error_message'];
              array_unshift($values, $errorMessage);
              $importRecordParams = [$statusFieldName => CRM_Import_Parser::ERROR, "{$statusFieldName}Msg" => $errorMessage];
              $this->updateImportStatus($values[count($values) - 1], $importRecordParams);
              return CRM_Import_Parser::ERROR;
            }
          }
          else {
            $matchedIDs[] = $relatedNewContact->id;
          }
          // update/fill related contact after getting matching Contact Ids, CRM-4424
          if (in_array($onDuplicate, [CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::DUPLICATE_FILL])) {
            //validation of related contact subtype for update mode
            //CRM-5125
            $relatedCsType = NULL;
            if (CRM_Utils_Array::value('contact_sub_type', $formatting)) {
              $relatedCsType = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
                $matchedIDs[0],
                'contact_sub_type'
              );
            }

            if (!empty($relatedCsType) &&
              (!CRM_Contact_BAO_ContactType::isAllowEdit($matchedIDs[0], $relatedCsType) &&
                $relatedCsType != CRM_Utils_Array::value('contact_sub_type', $formatting)
              )
            ) {
              $errorMessage = ts("Mismatched or Invalid contact subtype found for this related contact.");
              array_unshift($values, $errorMessage);
              return CRM_Import_Parser::NO_MATCH;
            }
            else {
              $updatedContact = $this->createContact($formatting, $contactFields, $onDuplicate, $matchedIDs[0]);
            }
          }
          static $relativeContact = [];
          if (civicrm_duplicate($relatedNewContact)) {
            if (count($matchedIDs) >= 1) {
              $relContactId = $matchedIDs[0];
              //add relative contact to count during update & fill mode.
              //logic to make count distinct by contact id.
              if ($this->_newRelatedContacts || !empty($relativeContact)) {
                $reContact = array_keys($relativeContact, $relContactId);

                if (empty($reContact)) {
                  $this->_newRelatedContacts[] = $relativeContact[] = $relContactId;
                }
              }
              else {
                $this->_newRelatedContacts[] = $relativeContact[] = $relContactId;
              }
            }
          }
          else {
            $relContactId = $relatedNewContact->id;
            $this->_newRelatedContacts[] = $relativeContact[] = $relContactId;
          }

          if (civicrm_duplicate($relatedNewContact) ||
            ($relatedNewContact instanceof CRM_Contact_BAO_Contact)
          ) {
            //fix for CRM-1993.Checks for duplicate related contacts
            if (count($matchedIDs) >= 1) {
              //if more than one duplicate contact
              //found, create relationship with first contact
              // now create the relationship record
              $relationParams = [];
              $relationParams = ['relationship_type_id' => $key,
                'contact_check' => [$relContactId => 1],
                'is_active' => 1,
                'skipRecentView' => TRUE,
              ];

              // we only handle related contact success, we ignore failures for now
              // at some point wold be nice to have related counts as separate
              $relationIds = ['contact' => $primaryContactId];

              list($valid, $invalid, $duplicate, $saved, $relationshipIds) = CRM_Contact_BAO_Relationship::create($relationParams, $relationIds);

              if ($valid || $duplicate) {
                $relationIds['contactTarget'] = $relContactId;
                $action = ($duplicate) ? CRM_Core_Action::UPDATE : CRM_Core_Action::ADD;
                CRM_Contact_BAO_Relationship::relatedMemberships($primaryContactId,
                  $relationParams,
                  $relationIds,
                  $action
                );
              }

              //handle current employer, CRM-3532
              if ($valid) {

                $allRelationships = CRM_Core_PseudoConstant::relationshipType('name');
                $relationshipTypeId = str_replace(['_a_b', '_b_a'], ['', ''], $key);
                $relationshipType = str_replace($relationshipTypeId . '_', '', $key);
                $orgId = $individualId = NULL;
                if ($allRelationships[$relationshipTypeId]["name_{$relationshipType}"] == 'Employee of') {
                  $orgId = $relContactId;
                  $individualId = $primaryContactId;
                }
                elseif ($allRelationships[$relationshipTypeId]["name_{$relationshipType}"] == 'Employer of') {
                  $orgId = $primaryContactId;
                  $individualId = $relContactId;
                }
                if ($orgId && $individualId) {
                  $currentEmpParams[$individualId] = $orgId;

                  CRM_Contact_BAO_Contact_Utils::setCurrentEmployer($currentEmpParams);
                }
              }
            }
          }
        }
      }
    }
    if ($this->_updateWithId) {
      //return warning if street address is unparsed, CRM-5886
      return $this->processMessage($values, $statusFieldName, $this->_retCode);
    }
    //dupe checking
    if (is_array($newContact) && civicrm_error($newContact)) {
      $code = NULL;

      if (($code = CRM_Utils_Array::value('code', $newContact['error_message'])) &&
        ($code == CRM_Core_Error::DUPLICATE_CONTACT)
      ) {
        $urls = [];
        // need to fix at some stage and decide if the error will return an
        // array or string, crude hack for now
        if (is_array($newContact['error_message']['params'][0])) {
          $cids = $newContact['error_message']['params'][0];
        }
        else {
          $cids = explode(',', $newContact['error_message']['params'][0]);
        }

        foreach ($cids as $cid) {
          $urls[] = CRM_Utils_System::url('civicrm/contact/view',
            'reset=1&cid=' . $cid, TRUE
          );
        }

        $url_string = CRM_Utils_Array::implode("\n", $urls);

        // If we duplicate more than one record, skip no matter what
        if (count($cids) > 1) {
          $errorMessage = ts('Record duplicates multiple contacts');
          $importRecordParams = [$statusFieldName => CRM_Import_Parser::NO_MATCH, "{$statusFieldName}Msg" => $errorMessage];

          //combine error msg to avoid mismatch between error file columns.
          $errorMessage .= "\n" . $url_string;
          array_unshift($values, $errorMessage);
          $this->updateImportStatus($values[count($values) - 1], $importRecordParams);
          return CRM_Import_Parser::NO_MATCH;
        }

        // Params only had one id, so shift it out
        $contactId = array_shift($cids);
        $cid = NULL;

        $vals = ['contact_id' => $contactId];

        if ($onDuplicate == CRM_Import_Parser::DUPLICATE_REPLACE) {
          $result = civicrm_replace_contact_formatted($contactId, $formatted, $contactFields);
          $cid = $result['result'];
        }
        elseif ($onDuplicate == CRM_Import_Parser::DUPLICATE_UPDATE) {
          $newContact = $this->createContact($formatted, $contactFields, $onDuplicate, $contactId);
        }
        elseif ($onDuplicate == CRM_Import_Parser::DUPLICATE_FILL) {
          $newContact = $this->createContact($formatted, $contactFields, $onDuplicate, $contactId);
        }
        // else skip does nothing and just returns an error code.

        if ($cid) {
          $contact = ['contact_id' => $cid];
          $defaults = [];
          $newContact = CRM_Contact_BAO_Contact::retrieve($contact, $defaults);
        }

        if (civicrm_error($newContact)) {
          $contactID = $newContact['error_message']['params'][0];
          if (!in_array($contactID, $this->_newContacts)) {
            $this->_newContacts[] = $contactID;
          }
        }
        //CRM-262 No Duplicate Checking
        if ($onDuplicate == CRM_Import_Parser::DUPLICATE_SKIP) {
          array_unshift($values, $url_string);
          $importRecordParams = [$statusFieldName => CRM_Import_Parser::DUPLICATE, "{$statusFieldName}Msg" => "Skipping duplicate record"];
          $this->updateImportStatus($values[count($values) - 1], $importRecordParams);
          return CRM_Import_Parser::DUPLICATE;
        }

        $importRecordParams = [$statusFieldName => CRM_Import_Parser::VALID];
        $this->updateImportStatus($values[count($values) - 1], $importRecordParams);
        //return warning if street address is not parsed, CRM-5886
        return $this->processMessage($values, $statusFieldName, CRM_Import_Parser::VALID);
      }
      else {
        // Not a dupe, so we had an error
        $errorMessage = $newContact['error_message'];
        array_unshift($values, $errorMessage);
        $importRecordParams = [$statusFieldName => CRM_Import_Parser::ERROR, "{$statusFieldName}Msg" => $errorMessage];
        $this->updateImportStatus($values[count($values) - 1], $importRecordParams);
        return CRM_Import_Parser::ERROR;
      }
    }
    // sleep(3);
    return $this->processMessage($values, $statusFieldName, CRM_Import_Parser::VALID);
  }

  /**
   * Get the array of succesfully imported contact id's
   *
   * @return array
   * @access public
   */
  function &getImportedContacts() {
    return $this->_newContacts;
  }

  /**
   * Get the array of succesfully imported related contact id's
   *
   * @return array
   * @access public
   */
  function &getRelatedImportedContacts() {
    return $this->_newRelatedContacts;
  }

  /**
   * the initializer code, called before the processing
   *
   * @return void
   * @access public
   */
  function fini() {}

  /**
   *  function to check if an error in custom data
   *
   *  @param String   $errorMessage   A string containing all the error-fields.
   *
   *  @access public
   */
  static function isErrorInCustomData($params, &$errorMessage) {
    //CRM-5125
    //add custom fields for contact sub type
    if (isset($this)) {
      $dateType = $this->_dateFormats;
      if (!empty($this->_contactSubType)) {
        $csType = $this->_contactSubType;
      }
    }

    if (CRM_Utils_Array::value('contact_sub_type', $params)) {
      $csType = CRM_Utils_Array::value('contact_sub_type', $params);
    }

    $customFields = CRM_Core_BAO_CustomField::getFields($params['contact_type'], FALSE, FALSE, $csType);

    foreach ($params as $key => $value) {
      if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($key)) {
        /* check if it's a valid custom field id */

        if (!CRM_Utils_Array::arrayKeyExists($customFieldID, $customFields)) {
          self::addToErrorMsg(ts('field ID'), $errorMessage);
        }
        /* validate the data against the CF type */


        if ($value) {
          if ($customFields[$customFieldID]['data_type'] == 'Date') {
            if (CRM_Utils_Date::convertToDefaultDate($params, $dateType, $key)) {
              $value = $params[$key];
            }
            else {
              self::addToErrorMsg($customFields[$customFieldID]['label'], $errorMessage);
            }
          }
          elseif ($customFields[$customFieldID]['data_type'] == 'Boolean') {
            if (CRM_Utils_String::strtoboolstr($value) === FALSE) {
              self::addToErrorMsg($customFields[$customFieldID]['label'], $errorMessage);
            }
          }
          // need not check for label filed import
          $htmlType = ['CheckBox', 'Multi-Select', 'AdvMulti-Select', 'Select', 'Radio', 'Multi-Select State/Province', 'Multi-Select Country', 'File', 'Select Date'];
          if (!in_array($customFields[$customFieldID]['html_type'], $htmlType) ||
            $customFields[$customFieldID]['data_type'] == 'ContactReference'
          ) {
            $valid = CRM_Core_BAO_CustomValue::typecheck($customFields[$customFieldID]['data_type'], $value);
            if (!$valid) {
              self::addToErrorMsg($customFields[$customFieldID]['label'], $errorMessage);
            }
          }

          // check values for File type
          if ($customFields[$customFieldID]['html_type'] == 'File') {
            $valid = CRM_Core_BAO_CustomValue::typecheck($customFields[$customFieldID]['data_type'], $value);
            if (!$valid) {
              self::addToErrorMsg(ts("File %1 does not exist or is not readable", [1 => $value]), $errorMessage);
            }
          }

          // check for values for custom fields for checkboxes and multiselect
          if ($customFields[$customFieldID]['html_type'] == 'CheckBox' ||
            $customFields[$customFieldID]['html_type'] == 'AdvMulti-Select' ||
            $customFields[$customFieldID]['html_type'] == 'Multi-Select'
          ) {
            $value = trim($value);
            $value = str_replace('|', ',', $value);
            $mulValues = explode(',', $value);
            $customOption = CRM_Core_BAO_CustomOption::getCustomOption($customFieldID, TRUE);
            foreach ($mulValues as $v1) {
              if (strlen($v1) == 0) {
                continue;
              }

              $flag = FALSE;
              foreach ($customOption as $v2) {
                if ((strtolower(trim($v2['label'])) == strtolower(trim($v1))) ||
                  (strtolower(trim($v2['value'])) == strtolower(trim($v1)))
                ) {
                  $flag = TRUE;
                }
              }

              if (!$flag) {
                self::addToErrorMsg($customFields[$customFieldID]['label'], $errorMessage);
              }
            }
          }
          elseif ($customFields[$customFieldID]['html_type'] == 'Select' ||
            ($customFields[$customFieldID]['html_type'] == 'Radio' &&
              $customFields[$customFieldID]['data_type'] != 'Boolean'
            )
          ) {
            $customOption = CRM_Core_BAO_CustomOption::getCustomOption($customFieldID, TRUE);
            $flag = FALSE;
            foreach ($customOption as $v2) {
              if ((strtolower(trim($v2['label'])) == strtolower(trim($value))) ||
                (strtolower(trim($v2['value'])) == strtolower(trim($value)))
              ) {
                $flag = TRUE;
              }
            }
            if (!$flag) {
              self::addToErrorMsg($customFields[$customFieldID]['label'], $errorMessage);
            }
          }
          elseif ($customFields[$customFieldID]['html_type'] == 'Multi-Select State/Province') {
            $mulValues = explode(',', $value);
            foreach ($mulValues as $stateValue) {
              if ($stateValue) {
                if (self::in_value(trim($stateValue), CRM_Core_PseudoConstant::stateProvinceAbbreviation())
                  || self::in_value(trim($stateValue), CRM_Core_PseudoConstant::stateProvince())
                ) {
                  continue;
                }
                else {
                  self::addToErrorMsg($customFields[$customFieldID]['label'], $errorMessage);
                }
              }
            }
          }
          elseif ($customFields[$customFieldID]['html_type'] == 'Multi-Select Country') {
            $mulValues = explode(',', $value);
            foreach ($mulValues as $countryValue) {
              if ($countryValue) {

                CRM_Core_PseudoConstant::populate($countryNames, 'CRM_Core_DAO_Country',
                  TRUE, 'name', 'is_active'
                );
                $countryTranslateNames = array_map('ts', $countryNames);
                CRM_Core_PseudoConstant::populate($countryIsoCodes,
                  'CRM_Core_DAO_Country', TRUE,
                  'iso_code'
                );

                $config = CRM_Core_Config::singleton();
                $limitCodes = $config->countryLimit();

                $error = TRUE;
                foreach ([$countryNames, $countryTranslateNames, $countryIsoCodes, $limitCodes] as $values) {
                  if (in_array(trim($countryValue), $values)) {
                    $error = FALSE;
                    break;
                  }
                }

                if ($error) {
                  self::addToErrorMsg($customFields[$customFieldID]['label'], $errorMessage);
                }
              }
            }
          }
        }
      }
      elseif (is_array($params[$key]) &&
        isset($params[$key]["contact_type"])
      ) {
        //CRM-5125
        //supporting custom data of related contact subtypes
        if (isset($this)) {
          if (CRM_Utils_Array::arrayKeyExists($key, $this->_relationships)) {
            $relation = $key;
          }
          elseif (CRM_Utils_Array::key($key, $this->_relationships)) {
            $relation = CRM_Utils_Array::key($key, $this->_relationships);
          }
        }
        if (!empty($relation)) {
          list($id, $first, $second) = CRM_Utils_System::explode('_', $relation, 3);
          $direction = "contact_sub_type_$second";

          $relationshipType = new CRM_Contact_BAO_RelationshipType();
          $relationshipType->id = $id;
          if ($relationshipType->find(TRUE)) {
            if (isset($relationshipType->$direction)) {
              $params[$key]['contact_sub_type'] = $relationshipType->$direction;
            }
          }
          $relationshipType->free();
        }

        self::isErrorInCustomData($params[$key], $errorMessage);
      }
    }
  }

  /**
   *  Check if value present in all genders or
   *  as a substring of any gender value, if yes than return corresponding gender.
   *  eg value might be  m/M, ma/MA, mal/MAL, male return 'Male'
   *  but if value is 'maleabc' than return false
   *
   *  @param string $gender check this value across gender values.
   *
   *  retunr gender value / false
   *  @access public
   */
  public function checkGender($gender) {
    $gender = trim($gender, '.');
    if (!$gender) {
      return FALSE;
    }

    $allGenders = CRM_Core_PseudoConstant::gender();
    foreach ($allGenders as $key => $value) {
      if (strlen($gender) > strlen($value)) {
        continue;
      }
      if ($gender == $value) {
        return $value;
      }
      if (substr_compare($value, $gender, 0, strlen($gender), TRUE) === 0) {
        return $value;
      }
    }

    return FALSE;
  }

  public function checkLanguage($lang) {
    $lang = trim($lang);
    if (empty($lang)) {
      return FALSE;
    }
    $allLanguages = CRM_Core_I18n_PseudoConstant::languages();
    if ($langKey = array_search($lang, $allLanguages)) {
      return $langKey;
    }
    if (isset($allLanguages[$lang])) {
      return $lang;
    }
    if (strstr($lang, '_')) {
      list($pre, $post) = explode('_', $lang);
      $lang = strtolower($pre). '_' . strtoupper($post);
      if (isset($allLanguages[$lang])) {
        return $lang;
      }
    }
    return FALSE;
  }

  /**
   * function to check if an error in Core( non-custom fields ) field
   *
   * @param String   $errorMessage   A string containing all the error-fields.
   *
   * @access public
   */
  function isErrorInCoreData($params, &$errorMessage) {

    foreach ($params as $key => $value) {
      if ($value) {
        $dateType = $this->_dateFormats;

        switch ($key) {
          case 'birth_date':
            if (CRM_Utils_Date::convertToDefaultDate($params, $dateType, $key)) {
              if (!CRM_Utils_Rule::date($params[$key])) {
                self::addToErrorMsg(ts('Birth Date'), $errorMessage);
              }
            }
            else {
              self::addToErrorMsg(ts('Birth Date'), $errorMessage);
            }
            break;

          case 'deceased_date':
            if (CRM_Utils_Date::convertToDefaultDate($params, $dateType, $key)) {
              if (!CRM_Utils_Rule::date($params[$key])) {
                self::addToErrorMsg(ts('Deceased Date'), $errorMessage);
              }
            }
            else {
              self::addToErrorMsg(ts('Deceased Date'), $errorMessage);
            }
            break;

          case 'is_deceased':
            if (CRM_Utils_String::strtoboolstr($value) === FALSE) {
              self::addToErrorMsg(ts('Is Deceased'), $errorMessage);
            }
            break;

          case 'preferred_language':
            if (!self::checkLanguage($value)) {
              self::addToErrorMsg(ts('Preferred Language'), $errorMessage);
            }
            break;

          case 'gender':
            if (!self::checkGender($value)) {
              self::addToErrorMsg(ts('Gender'), $errorMessage);
            }
            break;

          case 'preferred_communication_method':
            $preffComm = [];
            $preffComm = explode(',', $value);
            foreach ($preffComm as $v) {
              if (!self::in_value(trim($v), CRM_Core_PseudoConstant::pcm())) {
                self::addToErrorMsg(ts('Preferred Communication Method'), $errorMessage);
              }
            }
            break;

          case 'preferred_mail_format':
            if (!CRM_Utils_Array::arrayKeyExists(strtolower($value), array_change_key_case(CRM_Core_SelectValues::pmf(), CASE_LOWER))) {
              self::addToErrorMsg(ts('Preferred Mail Format'), $errorMessage);
            }
            break;

          case 'individual_prefix':
            if (!self::in_value($value, CRM_Core_PseudoConstant::individualPrefix())) {
              self::addToErrorMsg(ts('Individual Prefix'), $errorMessage);
            }
            break;

          case 'individual_suffix':
            if (!self::in_value($value, CRM_Core_PseudoConstant::individualSuffix())) {
              self::addToErrorMsg(ts('Individual Suffix'), $errorMessage);
            }
            break;

          case 'state_province':
            if (!empty($value)) {
              foreach ($value as $stateValue) {
                if ($stateValue['state_province']) {
                  if (self::in_value($stateValue['state_province'], CRM_Core_PseudoConstant::stateProvinceAbbreviation())
                    || self::in_value($stateValue['state_province'], CRM_Core_PseudoConstant::stateProvince())
                  ) {
                    continue;
                  }
                  else {
                    self::addToErrorMsg(ts('State / Province'), $errorMessage);
                  }
                }
              }
            }
            break;

          case 'country':
            if (!empty($value)) {
              foreach ($value as $stateValue) {
                if ($stateValue['country']) {
                  CRM_Core_PseudoConstant::populate($countryNames, 'CRM_Core_DAO_Country',
                    TRUE, 'name', 'is_active'
                  );
                  CRM_Core_PseudoConstant::populate($countryIsoCodes,
                    'CRM_Core_DAO_Country', TRUE,
                    'iso_code'
                  );
                  $config = CRM_Core_Config::singleton();
                  $limitCodes = $config->countryLimit();
                  //If no country is selected in
                  //localization then take all countries
                  if (empty($limitCodes)) {
                    $limitCodes = $countryIsoCodes;
                  }

                  if (self::in_value($stateValue['country'], $limitCodes) || self::in_value($stateValue['country'], CRM_Core_PseudoConstant::country()) || self::in_value($stateValue['country'], $countryNames)) {
                    continue;
                  }
                  else {
                    if (self::in_value($stateValue['country'], $countryIsoCodes) || self::in_value($stateValue['country'], $countryNames)) {
                      self::addToErrorMsg(ts('Country input value is in table but not "available": "This Country is valid but is NOT in the list of Available Countries currently configured for your site. This can be viewed and modifed from Global Settings >> Localization." '), $errorMessage);
                    }
                    else {
                      self::addToErrorMsg(ts('Country input value not in country table: "The Country value appears to be invalid. It does not match any value in CiviCRM table of countries."'), $errorMessage);
                    }
                  }
                }
              }
            }
            break;

          case 'county':
            if (!empty($value)) {
              $countyNames = CRM_Core_PseudoConstant::county();
              foreach ($value as $county) {
                if (!in_array($county['county'], $countyNames)) {
                  self::addToErrorMsg(ts('County input value not in county table: The County value appears to be invalid. It does not match any value in CiviCRM table of counties.'), $errorMessage);
                }
              }
            }
            break;

          case 'geo_code_1':
            if (!empty($value)) {
              foreach ($value as $codeValue) {
                if ($codeValue['geo_code_1']) {
                  if (CRM_Utils_Rule::numeric($codeValue['geo_code_1'])) {
                    continue;
                  }
                  else {
                    self::addToErrorMsg(ts('Geo code 1'), $errorMessage);
                  }
                }
              }
            }
            break;

          case 'geo_code_2':
            if (!empty($value)) {
              foreach ($value as $codeValue) {
                if ($codeValue['geo_code_2']) {
                  if (CRM_Utils_Rule::numeric($codeValue['geo_code_2'])) {
                    continue;
                  }
                  else {
                    self::addToErrorMsg(ts('Geo code 2'), $errorMessage);
                  }
                }
              }
            }
            break;
          //check for any error in email/postal greeting, addressee,
          //custom email/postal greeting, custom addressee, CRM-4575

          case 'email_greeting':
            $emailGreetingFilter = ['contact_type' => $params['contact_type'],
              'greeting_type' => 'email_greeting',
            ];
            if (!self::in_value($value, CRM_Core_PseudoConstant::greeting($emailGreetingFilter))) {
              self::addToErrorMsg(ts('Email Greeting must be one of the configured format options. Check Administer >> Option Lists >> Email Greetings for valid values'), $errorMessage);
            }
            break;

          case 'postal_greeting':
            $postalGreetingFilter = ['contact_type' => $params['contact_type'],
              'greeting_type' => 'postal_greeting',
            ];
            if (!self::in_value($value, CRM_Core_PseudoConstant::greeting($postalGreetingFilter))) {
              self::addToErrorMsg(ts('Postal Greeting must be one of the configured format options. Check Administer >> Option Lists >> Postal Greetings for valid values'), $errorMessage);
            }
            break;

          case 'addressee':
            $addresseeFilter = ['contact_type' => $params['contact_type'],
              'greeting_type' => 'addressee',
            ];
            if (!self::in_value($value, CRM_Core_PseudoConstant::greeting($addresseeFilter))) {
              self::addToErrorMsg(ts('Addressee must be one of the configured format options. Check Administer >> Option Lists >> Addressee for valid values'), $errorMessage);
            }
            break;

          case 'email_greeting_custom':
            if (CRM_Utils_Array::arrayKeyExists('email_greeting', $params)) {
              $emailGreetingLabel = key(CRM_Core_OptionGroup::values('email_greeting', TRUE, NULL,
                  NULL, 'AND v.name = "Customized"'
                ));
              if (CRM_Utils_Array::value('email_greeting', $params) != $emailGreetingLabel) {
                self::addToErrorMsg(ts('Email Greeting - Custom'), $errorMessage);
              }
            }
            break;

          case 'postal_greeting_custom':
            if (CRM_Utils_Array::arrayKeyExists('postal_greeting', $params)) {
              $postalGreetingLabel = key(CRM_Core_OptionGroup::values('postal_greeting', TRUE,
                  NULL, NULL, 'AND v.name = "Customized"'
                ));
              if (CRM_Utils_Array::value('postal_greeting', $params) != $postalGreetingLabel) {
                self::addToErrorMsg(ts('Postal Greeting - Custom'), $errorMessage);
              }
            }
            break;

          case 'addressee_custom':
            if (CRM_Utils_Array::arrayKeyExists('addressee', $params)) {
              $addresseeLabel = key(CRM_Core_OptionGroup::values('addressee', TRUE, NULL, NULL,
                  'AND v.name = "Customized"'
                ));
              if (CRM_Utils_Array::value('addressee', $params) != $addresseeLabel) {
                self::addToErrorMsg(ts('Addressee - Custom'), $errorMessage);
              }
            }
            break;

          case 'url':
            if (is_array($value)) {
              foreach ($value as $values) {
                if (CRM_Utils_Array::value('url', $values) &&
                  !CRM_Utils_Rule::url($values['url'])
                ) {
                  self::addToErrorMsg(ts('Website'), $errorMessage);
                  break;
                }
              }
            }
            break;

          case 'do_not_email':
          case 'do_not_phone':
          case 'do_not_mail':
          case 'do_not_sms':
          case 'do_not_trade':
            if (CRM_Utils_Rule::boolean($value) == FALSE) {
              $key = ucwords(str_replace("_", " ", $key));
              self::addToErrorMsg($key, $errorMessage);
            }
            break;

          case 'email':
            if (is_array($value)) {
              foreach ($value as $values) {
                if (CRM_Utils_Array::value('email', $values) &&
                  !CRM_Utils_Rule::email($values['email'])
                ) {
                  self::addToErrorMsg($key, $errorMessage);
                  break;
                }
              }
            }
            break;

          default:
            if (is_array($params[$key]) &&
              isset($params[$key]["contact_type"])
            ) {
              //check for any relationship data ,FIX ME
              self::isErrorInCoreData($params[$key], $errorMessage);
            }
        }
      }
    }
  }

  /**
   * function to ckeck a value present or not in a array
   *
   * @return ture if value present in array or retun false
   *
   * @access public
   */
  public static function in_value($value, $valueArray) {
    foreach ($valueArray as $key => $v) {
      //fix for CRM-1514
      if (strtolower(trim($v, ".")) == strtolower(trim($value, "."))) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * function to build error-message containing error-fields
   *
   * @param String   $errorName      A string containing error-field name.
   * @param String   $errorMessage   A string containing all the error-fields, where the new errorName is concatenated.
   *
   * @static
   * @access public
   */
  static function addToErrorMsg($errorName, &$errorMessage) {
    if ($errorMessage) {
      $errorMessage .= "; $errorName";
    }
    else {
      $errorMessage = $errorName;
    }
  }

  /**
   * method for creating contact
   *
   *
   */
  function createContact(&$formatted, &$contactFields, $onDuplicate, $contactId = NULL, $requiredCheck = TRUE) {
    $dupeCheck = FALSE;

    $newContact = NULL;

    if (is_null($contactId) && ($onDuplicate != CRM_Import_Parser::DUPLICATE_NOCHECK)) {
      $dupeCheck = (bool)($onDuplicate);
    }

    //get the prefix id etc if exists
    CRM_Contact_BAO_Contact::resolveDefaults($formatted, TRUE);

    require_once 'api/v2/Contact.php';

    // setting required check to false, CRM-2839
    // plus we do our own required check in import
    $dedupeRuleGroupId = $this->_dedupeRuleGroupId ? $this->_dedupeRuleGroupId : NULL;
    $error = civicrm_contact_check_params($formatted, $dupeCheck, TRUE, FALSE, $dedupeRuleGroupId);

    if ((is_null($error)) &&
      (civicrm_error(_civicrm_validate_formatted_contact($formatted)))
    ) {
      $error = _civicrm_validate_formatted_contact($formatted);
    }

    $newContact = $error;

    if (is_null($error)) {
      if ($contactId) {
        $this->formatParams($formatted, $onDuplicate, (int)$contactId);
      }

      // pass doNotResetCache flag since resetting and rebuilding cache could be expensive.
      $config = &CRM_Core_Config::singleton();
      $config->doNotResetCache = 1;
      $formatted['log_data'] = !empty($this->_contactLog) ? $this->_contactLog : ts('Import Contact');
      $cid = CRM_Contact_BAO_Contact::createProfileContact($formatted, $contactFields,
        $contactId, NULL, NULL,
        $formatted['contact_type']
      );
      $config->doNotResetCache = 0;

      $contact = ['contact_id' => $cid];

      $defaults = [];
      $newContact = CRM_Contact_BAO_Contact::retrieve($contact, $defaults);
    }

    //get the id of the contact whose street address is not parsable, CRM-5886
    if ($this->_parseStreetAddress && $newContact->address) {
      foreach ($newContact->address as $address) {
        if ($address['street_address'] &&
          (!CRM_Utils_Array::value('street_number', $address) ||
            !CRM_Utils_Array::value('street_name', $address)
          )
        ) {
          $this->_unparsedStreetAddressContacts[] = ['id' => $newContact->id,
            'streetAddress' => $address['street_address'],
          ];
        }
      }
    }
    return $newContact;
  }

  /**
   * format params for update and fill mode
   *
   * @param $params       array  referance to an array containg all the
   *                             values for import
   * @param $onDuplicate  int
   * @param $cid          int    contact id
   */
  function formatParams(&$params, $onDuplicate, $cid) {
    if ($onDuplicate == CRM_Import_Parser::DUPLICATE_SKIP) {
      return;
    }

    $contactParams = ['contact_id' => $cid];

    $defaults = [];
    $contactObj = CRM_Contact_BAO_Contact::retrieve($contactParams, $defaults);

    $modeUpdate = $modeFill = FALSE;

    if ($onDuplicate == CRM_Import_Parser::DUPLICATE_UPDATE) {
      $modeUpdate = TRUE;
    }

    if ($onDuplicate == CRM_Import_Parser::DUPLICATE_FILL) {
      $modeFill = TRUE;
    }


    $groupTree = CRM_Core_BAO_CustomGroup::getTree($params['contact_type'], CRM_Core_DAO::$_nullObject,
      $cid, 0, NULL
    );
    CRM_Core_BAO_CustomGroup::setDefaults($groupTree, $defaults, FALSE, FALSE);

    $locationFields = ['email' => 'email',
      'phone' => 'phone',
      'im' => 'name',
      'website' => 'website',
      'address' => 'address',
    ];

    $contact = get_object_vars($contactObj);

    foreach ($params as $key => $value) {
      if ($key == 'id' || $key == 'contact_type') {
        continue;
      }

      if (CRM_Utils_Array::arrayKeyExists($key, $locationFields)) {
        continue;
      }
      elseif (in_array($key, ['email_greeting', 'postal_greeting', 'addressee'])) {
        // CRM-4575, need to null custom
        if ($params["{$key}_id"] != 4) {
          $params["{$key}_custom"] = 'null';
        }
        unset($params[$key]);
      }
      elseif ($customFieldId = CRM_Core_BAO_CustomField::getKeyID($key)) {
        $custom = TRUE;
      }
      else {
        $getValue = CRM_Utils_Array::retrieveValueRecursive($contact, $key);

        if ($key == 'contact_source') {
          $params['source'] = $params[$key];
          unset($params[$key]);
        }

        if ($modeFill && isset($getValue)) {
          unset($params[$key]);
        }
      }
    }

    foreach ($locationFields as $locKeys) {
      if (is_array($params[$locKeys])) {
        foreach ($params[$locKeys] as $key => $value) {
          if ($modeFill) {
            $getValue = CRM_Utils_Array::retrieveValueRecursive($contact, $locKeys);

            if (isset($getValue)) {
              foreach ($getValue as $cnt => $values) {
                if ($locKeys == 'website') {
                  if (($getValue[$cnt]['website_type_id'] ==
                      $params[$locKeys][$key]['website_type_id']
                    )) {
                    unset($params[$locKeys][$key]);
                  }
                }
                else {
                  if ($getValue[$cnt]['location_type_id']
                    == $params[$locKeys][$key]['location_type_id']
                  ) {
                    unset($params[$locKeys][$key]);
                  }
                }
              }
            }
          }
        }
        if (count($params[$locKeys])== 0) {
          unset($params[$locKeys]);
        }
      }
    }
  }

  /**
   * convert any given date string to default date array.
   *
   * @param array  $params     has given date-format
   * @param array  $formatted  store formatted date in this array
   * @param int    $dateType   type of date
   * @param string $dateParam  index of params
   * @static
   */
  public static function formatCustomDate(&$params, &$formatted, $dateType, $dateParam) {
    //fix for CRM-2687
    CRM_Utils_Date::convertToDefaultDate($params, $dateType, $dateParam);

    if ($dateType == 1) {
      if (strstr($params[$dateParam], '-')) {
        $formatted[$dateParam] = CRM_Utils_Date::processDate($params[$dateParam]);
      }
      else {
        $formatted[$dateParam] = CRM_Utils_Date::processDate($params[$dateParam]);
      }
    }
    else {
      $formatted[$dateParam] = CRM_Utils_Date::processDate($params[$dateParam]);
    }
  }

  /**
   * format common params data to proper format to store.
   *
   * @param array  $params        contain record values.
   * @param array  $formatted     array of formatted data.
   * @param array  $contactFields contact DAO fields.
   * @static
   */
  function formatCommonData($params, &$formatted, &$contactFields) {
    $csType = [CRM_Utils_Array::value('contact_type', $formatted)];

    //CRM-5125
    //add custom fields for contact sub type
    if (!empty($this->_contactSubType)) {
      $csType = $this->_contactSubType;
    }

    if ($relCsType = CRM_Utils_Array::value('contact_sub_type', $formatted)) {
      $csType = $relCsType;
    }

    $customFields = CRM_Core_BAO_CustomField::getFields($formatted['contact_type'], FALSE, FALSE, $csType);

    //if a Custom Email Greeting, Custom Postal Greeting or Custom Addressee is mapped, and no "Greeting / Addressee Type ID" is provided, then automatically set the type = Customized, CRM-4575
    $elements = ['email_greeting_custom' => 'email_greeting',
      'postal_greeting_custom' => 'postal_greeting',
      'addressee_custom' => 'addressee',
    ];
    foreach ($elements as $k => $v) {
      if (CRM_Utils_Array::arrayKeyExists($k, $params) && !(CRM_Utils_Array::arrayKeyExists($v, $params))) {
        $label = key(CRM_Core_OptionGroup::values($v, TRUE, NULL, NULL, 'AND v.name = "Customized"'));
        $params[$v] = $label;
      }
    }

    //format date first
    $dateType = $this->_dateFormats;
    foreach ($params as $key => $val) {
      if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($key)) {
        //we should not update Date to null, CRM-4062
        if ($val && ($customFields[$customFieldID]['data_type'] == 'Date')) {
          self::formatCustomDate($params, $formatted, $dateType, $key);
          unset($params[$key]);
        }
        elseif ($customFields[$customFieldID]['data_type'] == 'Boolean') {
          $params[$key] = CRM_Utils_String::strtoboolstr($val);
        }
      }

      if ($key == 'birth_date' && $val) {
        CRM_Utils_Date::convertToDefaultDate($params, $dateType, $key);
      }
      elseif ($key == 'deceased_date' && $val) {
        CRM_Utils_Date::convertToDefaultDate($params, $dateType, $key);
      }
      elseif ($key == 'is_deceased' && $val) {
        $params[$key] = CRM_Utils_String::strtoboolstr($val);
      }
      elseif ($key == 'gender') {
        //CRM-4360
        $params[$key] = $this->checkGender($val);
      }
      elseif ($key == 'is_opt_out') {
        $params[$key] = CRM_Utils_String::strtoboolstr($val);
      }
      elseif ($key == 'preferred_language' && !empty($val)) {
        $params[$key] = $this->checkLanguage($val);
      }
    }

    //now format custom data.
    foreach ($params as $key => $field) {
      if ($field == NULL || $field === '') {
        continue;
      }

      if (is_array($field)) {
        foreach ($field as $value) {
          $break = FALSE;
          if (is_array($value)) {
            foreach ($value as $name => $testForEmpty) {
              // check if $value does not contain IM provider or phoneType
              if (($name !== 'phone_type_id' || $name !== 'provider_id')
                && ($testForEmpty === '' || $testForEmpty == NULL)
              ) {
                $break = TRUE;
                break;
              }
            }
          }
          else {
            $break = TRUE;
          }

          if (!$break) {
            _civicrm_add_formatted_param($value, $formatted);
          }
        }
        continue;
      }

      $formatValues = [$key => $field];

      if (($key !== 'preferred_communication_method') &&
        (CRM_Utils_Array::arrayKeyExists($key, $contactFields))
      ) {
        // due to merging of individual table and
        // contact table, we need to avoid
        // preferred_communication_method forcefully
        $formatValues['contact_type'] = $formatted['contact_type'];
      }
      if ($key == 'id' && isset($field)) {
        $formatted[$key] = $field;
      }
      if ($key == 'is_opt_out' && isset($field)) {
        $formatted[$key] = $field;
      }

      _civicrm_add_formatted_param($formatValues, $formatted);

      //Handling Custom Data
      if (($customFieldID = CRM_Core_BAO_CustomField::getKeyID($key))
        && CRM_Utils_Array::arrayKeyExists($customFieldID, $customFields)
      ) {

        //get the html type.
        $type = $customFields[$customFieldID]['html_type'];

        switch ($type) {
          case 'CheckBox':
          case 'AdvMulti-Select':
          case 'Multi-Select':

            $mulValues = explode(',', $field);
            $customOption = CRM_Core_BAO_CustomOption::getCustomOption($customFieldID, TRUE);
            $formatted[$key] = [];

            foreach ($mulValues as $v1) {
              foreach ($customOption as $v2) {
                if ((strtolower($v2['label']) == strtolower(trim($v1))) ||
                  (strtolower($v2['value']) == strtolower(trim($v1)))
                ) {

                  if ($type == 'CheckBox') {
                    $formatted[$key][$v2['value']] = 1;
                  }
                  else {
                    $formatted[$key][] = $v2['value'];
                  }
                }
              }
            }
            break;

          case 'Select':
          case 'Radio':

            $customOption = CRM_Core_BAO_CustomOption::getCustomOption($customFieldID, TRUE);
            foreach ($customOption as $v2) {
              if ((strtolower($v2['label']) == strtolower(trim($field))) ||
                (strtolower($v2['value']) == strtolower(trim($field)))
              ) {
                $formatted[$key] = $v2['value'];
              }
            }
            break;

          case 'Multi-Select State/Province':

            $mulValues = explode(',', $field);
            $stateAbbr = CRM_Core_PseudoConstant::stateProvinceAbbreviation();
            $stateName = CRM_Core_PseudoConstant::stateProvince();
            $formatted[$key] = $stateValues = [];

            foreach ($mulValues as $values) {
              if ($val = CRM_Utils_Array::key($values, $stateAbbr)) {
                $formatted[$key][] = $val;
              }
              elseif ($val = CRM_Utils_Array::key($values, $stateName)) {
                $formatted[$key][] = $val;
              }
            }
            break;

          case 'Multi-Select Country':

            $config = CRM_Core_Config::singleton();
            $limitCodes = $config->countryLimit();
            $mulValues = explode(',', $field);
            $formatted[$key] = [];

            CRM_Core_PseudoConstant::populate($countryNames, 'CRM_Core_DAO_Country',
              TRUE, 'name', 'is_active'
            );
            $countryTranslateNames = array_map('ts', $countryNames);
            CRM_Core_PseudoConstant::populate($countryIsoCodes,
              'CRM_Core_DAO_Country', TRUE,
              'iso_code'
            );

            foreach ($mulValues as $values) {
              if ($val = CRM_Utils_Array::key($values, $countryNames)) {
                $formatted[$key][] = $val;
              }
              elseif ($val = CRM_Utils_Array::key($values, $countryIsoCodes)) {
                $formatted[$key][] = $val;
              }
              elseif ($val = CRM_Utils_Array::key($values, $countryTranslateNames)) {
                $formatted[$key][] = $val;
              }
              elseif ($val = CRM_Utils_Array::key($values, $limitCodes)) {
                $formatted[$key][] = $val;
              }
            }
            break;
        }
      }
    }

    // check for primary location type, whether it is already present for the contact or not, CRM-4423
    if (CRM_Utils_Array::value('id', $formatted) && isset($formatted['location'])) {
      $primaryLocationTypeId = CRM_Contact_BAO_Contact::getPrimaryLocationType($formatted['id'], TRUE);
      if (isset($primaryLocationTypeId)) {
        foreach ($formatted['location'] as $loc => $details) {
          if ($primaryLocationTypeId == CRM_Utils_Array::value('location_type_id', $details)) {
            $formatted['location'][$loc]['is_primary'] = 1;
            break;
          }
          else {
            $formatted['location'][$loc]['is_primary'] = 0;
          }
        }
      }
    }

    // parse street address, CRM-5450
    if ($this->_parseStreetAddress) {

      if (CRM_Utils_Array::arrayKeyExists('address', $formatted) && is_array($formatted['address'])) {
        foreach ($formatted['address'] as $instance => & $address) {
          $streetAddress = CRM_Utils_Array::value('street_address', $address);
          if (empty($streetAddress)) {
            continue;
          }

          // parse address field.
          $parsedFields = CRM_Core_BAO_Address::parseStreetAddress($streetAddress);

          //street address consider to be parsed properly,
          //If we get street_name and street_number.
          if (!CRM_Utils_Array::value('street_name', $parsedFields) ||
            !CRM_Utils_Array::value('street_number', $parsedFields)
          ) {
            $parsedFields = array_fill_keys(array_keys($parsedFields), '');
          }

          // merge parse address w/ main address block.
          $address = array_merge($address, $parsedFields);
        }
      }
    }
  }

  /**
   * Function to generate status and error message for unparsed street address records.
   *
   * @param array  $values           the array of values belonging to each row
   * @param array  $statusFieldName  store formatted date in this array

   * @access public
   */
  function processMessage(&$values, $statusFieldName, $returnCode) {
    if (empty($this->_unparsedStreetAddressContacts)) {
      $importRecordParams = [$statusFieldName => CRM_Import_Parser::VALID];
    }
    else {
      $errorMessage = ts("Record imported successfully but unable to parse the street address: ");
      foreach ($this->_unparsedStreetAddressContacts as $contactInfo => $contactValue) {
        $contactUrl = CRM_Utils_System::url('civicrm/contact/add', 'reset=1&action=update&cid=' . $contactValue['id'], TRUE, NULL, FALSE);
        $errorMessage .= "\n Contact ID:" . $contactValue['id'] . " <a href=\"$contactUrl\"> " . $contactValue['streetAddress'] . "</a>";
      }
      array_unshift($values, $errorMessage);
      $importRecordParams = [$statusFieldName => CRM_Import_Parser::UNPARSED_ADDRESS_WARNING, "{$statusFieldName}Msg" => $errorMessage];
      $returnCode = CRM_Import_Parser::UNPARSED_ADDRESS_WARNING;
    }
    $this->updateImportStatus($values[count($values) - 1], $importRecordParams);
    return $returnCode;
  }

  function checkRelatedContactFields($relKey, $params) {
    //avoid blank contact creation.
    $allowToCreate = FALSE;

    //build the mapper field array.
    static $relatedContactFields = [];
    if (!isset($relatedContactFields[$relKey])) {
      foreach ($this->_mapperRelated as $key => $name) {
        if (!$name) {
          continue;
        }
        if (!is_array($relatedContactFields[$name])) {
          $relatedContactFields[$name] = [];
        }
        $fldName = CRM_Utils_Array::value($key, $this->_mapperRelatedContactDetails);
        if ($fldName == 'url') {
          $fldName = 'website';
        }
        if ($fldName) {
          $relatedContactFields[$name][] = $fldName;
        }
      }
    }

    //validate for passed data.
    if (is_array($relatedContactFields[$relKey])) {
      foreach ($relatedContactFields[$relKey] as $fld) {
        if (CRM_Utils_Array::value($fld, $params)) {
          $allowToCreate = TRUE;
          break;
        }
      }
    }

    return $allowToCreate;
  }

  function getLastImportContactId() {
    return $this->_lastImportContactId;
  }

  static public function checkContactById($params, $cidField) {
    $pass = $contactID = 0;
    $checkCid = new CRM_Contact_DAO_Contact();
    if (!empty($params['external_identifier'])) {
      $checkCid->external_identifier = $params['external_identifier'];
      $checkCid->is_deleted = 0;
      if($checkCid->find(TRUE)){
        $contactID = $checkCid->id;
      }
    }

    if (!empty($params[$cidField])) {
      if (!empty($contactID)) {
        if ($contactID != $params[$cidField] ){
          $pass = FALSE;
        }
        else {
          $pass = $contactID;
        }
      }
      else {
        $checkCid->id = $params[$cidField];
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

