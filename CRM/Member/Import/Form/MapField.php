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

require_once 'CRM/Core/Form.php';

require_once 'CRM/Core/DAO/Mapping.php';
require_once 'CRM/Core/DAO/MappingField.php';

require_once 'CRM/Member/Import/Parser/Membership.php';

/**
 * This class gets the name of the file to upload
 */
class CRM_Member_Import_Form_MapField extends CRM_Core_Form {

  /**
   * cache of preview data values
   *
   * @var array
   * @access protected
   */
  protected $_dataValues;

  /**
   * mapper fields
   *
   * @var array
   * @access protected
   */
  protected $_mapperFields;

  /**
   * loaded mapping ID
   *
   * @var int
   * @access protected
   */
  protected $_loadedMappingId;

  /**
   * number of columns in import file
   *
   * @var int
   * @access protected
   */
  protected $_columnCount;

  /**
   * column headers, if we have them
   *
   * @var array
   * @access protected
   */
  protected $_columnHeaders;

  /**
   * an array of booleans to keep track of whether a field has been used in
   * form building already.
   *
   * @var array
   * @access protected
   */
  protected $_fieldUsed;

  /**
   * to store contactType
   *
   * @var int
   * @static
   */
  static $_contactType = NULL;

  /**
   * Attempt to resolve the header with our mapper fields
   *
   * @param header
   * @param mapperFields
   *
   * @return string
   * @access public
   */
  public function defaultFromHeader($columnName, &$patterns) {
    if (!preg_match('/^[0-9a-z]$/i', $columnName)) {
      $columnMatch = trim(preg_replace('/\(.*\)/', '', $columnName));
      $matches = preg_grep('/^'.$columnMatch.'/iu', $this->_mapperFields);
      if (count($matches)) {
        $columnKey = key($matches);
        $this->_fieldUsed[$columnKey] = TRUE;
        return $columnKey;
      }
    }
    return '';
  }

  /**
   * Guess at the field names given the data and patterns from the schema
   *
   * @param patterns
   * @param index
   *
   * @return string
   * @access public
   */
  public function defaultFromData(&$patterns, $index) {
    $best = '';
    $bestHits = 0;
    $n = count($this->_dataValues);

    foreach ($patterns as $key => $re) {
      if (empty($re)) {
        continue;
      }

      /* Take a vote over the preview data set */

      $hits = 0;
      for ($i = 0; $i < $n; $i++) {
        if (preg_match($re, $this->_dataValues[$i][$index])) {
          $hits++;
        }
      }

      if ($hits > $bestHits) {
        $bestHits = $hits;
        $best = $key;
      }
    }

    if ($best != '') {
      $this->_fieldUsed[$best] = TRUE;
    }
    return $best;
  }

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    $this->_mapperFields = $this->get('fields');

    $this->_columnCount = $this->get('columnCount');
    $this->assign('columnCount', $this->_columnCount);
    $this->_dataValues = $this->get('dataValues');
    $this->assign('dataValues', $this->_dataValues);

    $skipColumnHeader = $this->controller->exportValue('UploadFile', 'skipColumnHeader');
    $this->_onDuplicate = $this->get('onDuplicate', isset($onDuplicate) ? $onDuplicate : "");

    $highlightedFields = array();
    if ($skipColumnHeader) {
      $this->assign('skipColumnHeader', $skipColumnHeader);
      $this->assign('rowDisplayCount', 3);
      /* if we had a column header to skip, stash it for later */

      $this->_columnHeaders = $this->_dataValues[0];
    }
    else {
      $this->assign('rowDisplayCount', 2);
    }

    //CRM-2219 removing other required fields since for updation only
    //membership id is required.
    if ($this->_onDuplicate == CRM_Member_Import_Parser::DUPLICATE_UPDATE) {
      $remove = array('membership_contact_id', 'email', 'first_name', 'last_name', 'external_identifier');
      foreach ($remove as $value) {
        unset($this->_mapperFields[$value]);
      }
      $highlightedFieldsArray = array('membership_start_date', 'membership_type_id');
      $highlightedFieldsArray[] = $this->controller->exportValue('UploadFile', 'dataReferenceField');
      foreach ($highlightedFieldsArray as $name) {
        $highlightedFields[] = $name;
      }
    }
    elseif ($this->_onDuplicate == CRM_Member_Import_Parser::DUPLICATE_SKIP) {
      unset($this->_mapperFields['membership_id']);
      $highlightedFieldsArray = array('membership_contact_id', 'external_identifier', 'membership_start_date', 'membership_type_id');
      foreach ($highlightedFieldsArray as $name) {
        $highlightedFields[] = $name;
      }
      $dedupeRuleGroup = $this->get('dedupeRuleGroup');
      if(!empty($dedupeRuleGroup)) {
        $ruleParams = array('id' => $dedupeRuleGroup);
      }
      else{
        // default rule group
        $ruleParams = array(
          'contact_type' => $contactType,
          'level' => 'Strict',
        );
      }
      $dedupeFields = CRM_Dedupe_BAO_Rule::dedupeRuleFields($ruleParams);
      $dedupeFields = array_merge($dedupeFields, array('membership_contact_id', 'external_identifier'));
      foreach ($dedupeFields as $fieldName) {
        $this->_mapperFields[$fieldName] .= ' '. ts('(match to contact)');
        $highlightedFields[] = $fieldName;
      }
    }

    // modify field title
    $this->_mapperFields['status_id'] = ts('Membership Status');
    $this->_mapperFields['membership_type_id'] = ts('Membership Type');

    self::$_contactType = $this->get('contactType');
    $this->assign('highlightedFields', $highlightedFields);
  }

  /**
   * Function to actually build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    require_once "CRM/Core/BAO/Mapping.php";
    require_once "CRM/Core/OptionGroup.php";
    //to save the current mappings
    if (!$this->get('savedMapping')) {
      $saveDetailsName = ts('Save this field mapping');
      $this->applyFilter('saveMappingName', 'trim');
      $this->add('text', 'saveMappingName', ts('Name'));
      $this->add('text', 'saveMappingDesc', ts('Description'));
      $this->_locationFields = array_merge(
        CRM_Core_DAO_Address::import(),
        CRM_Core_DAO_Phone::import(),
        CRM_Core_DAO_Email::import(),
        CRM_Core_DAO_IM::import(TRUE),
        CRM_Core_DAO_OpenID::import()
      );
    }
    else {
      $savedMapping = $this->get('savedMapping');
      list($mappingName, $mappingContactType, $mappingLocation, $mappingPhoneType, $mappingImProvider, $mappingRelation, $mappingOperator, $mappingValue, $mappingWebsiteType) = CRM_Core_BAO_Mapping::getMappingFields($savedMapping);

      $mappingName = $mappingName[1];
      $mappingContactType = $mappingContactType[1];
      $mappingLocation = CRM_Utils_Array::value(1, $mappingLocation);
      $mappingPhoneType = CRM_Utils_Array::value(1, $mappingPhoneType);
      $mappingImProvider = CRM_Utils_Array::value(1, $mappingImProvider);
      $mappingRelation = CRM_Utils_Array::value(1, $mappingRelation);
      $mappingWebsiteType = CRM_Utils_Array::value(1, $mappingWebsiteType);

      //mapping is to be loaded from database

      $params = array('id' => $savedMapping);
      $temp = array();
      $mappingDetails = CRM_Core_BAO_Mapping::retrieve($params, $temp);

      $this->assign('loadedMapping', $mappingDetails->name);
      $this->set('loadedMapping', $savedMapping);

      $getMappingName = new CRM_Core_DAO_Mapping();
      $getMappingName->id = $savedMapping;
      $getMappingName->mapping_type = 'Import Memberships';
      $getMappingName->find();
      while ($getMappingName->fetch()) {
        $mapperName = $getMappingName->name;
      }

      $this->assign('savedName', $mapperName);

      $this->add('hidden', 'mappingId', $savedMapping);

      $this->addElement('checkbox', 'updateMapping', ts('Update this field mapping'), NULL);
      $saveDetailsName = ts('Save as a new field mapping');
      $this->add('text', 'saveMappingName', ts('Name'));
      $this->add('text', 'saveMappingDesc', ts('Description'));
    }

    $this->addElement('checkbox', 'saveMapping', $saveDetailsName, NULL, array('onclick' => "showSaveDetails(this)"));

    $this->addFormRule(array('CRM_Member_Import_Form_MapField', 'formRule'), $this);

    //-------- end of saved mapping stuff ---------

    $defaults = array();
    $mapperKeys = array_keys($this->_mapperFields);
    $hasHeaders = !empty($this->_columnHeaders);
    $headerPatterns = $this->get('headerPatterns');
    $dataPatterns = $this->get('dataPatterns');
    $hasLocationTypes = $this->get('fieldTypes');


    /* Initialize all field usages to false */

    $this->_location_types = &CRM_Core_PseudoConstant::locationType();
    $defaultLocationType = &CRM_Core_BAO_LocationType::getDefault();

    /* FIXME: dirty hack to make the default option show up first.  This
         * avoids a mozilla browser bug with defaults on dynamically constructed
         * selector widgets. */
    if ($defaultLocationType) {
      $defaultLocation = $this->_location_types[$defaultLocationType->id];
      unset($this->_location_types[$defaultLocationType->id]);
      $this->_location_types = array($defaultLocationType->id => $defaultLocation) + $this->_location_types;
    }

    $sel1 = $this->_mapperFields;
    $sel2[''] = NULL;

    $phoneTypes = CRM_Core_PseudoConstant::phoneType();
    $imProviders = CRM_Core_PseudoConstant::IMProvider();
    $websiteTypes = CRM_Core_PseudoConstant::websiteType();

    foreach ($this->_location_types as $key => $value) {
      $sel3['phone'][$key] = &$phoneTypes;
      //build array for IM service provider type for contact
      $sel3['im'][$key] = &$imProviders;
    }

    foreach ($mapperKeys as $key) {
      $this->_fieldUsed[$key] = FALSE;
      $options = NULL;
      if ($hasLocationTypes[$key]) {
        $options = $this->_location_types;
      }
      elseif ($key == 'url') {
        $options = $websiteTypes;
      }
      $sel2[$key] = $options;
    }
    if (!$this->get('onDuplicate')) {
      unset($sel1['id']);
      unset($sel1['membership_id']);
    }

    $sel2[''] = NULL;

    $js = "<script type='text/javascript'>\n";
    $formName = 'document.forms.' . $this->_name;

    //used to warn for mismatch column count or mismatch mapping
    $warning = 0;

    for ($i = 0; $i < $this->_columnCount; $i++) {
      $this->add('hidden', "weight[$i]");
      $defaults["weight[$i]"] = $i;
      $sel = &$this->addElement('hierselect', "mapper[$i]", ts('Mapper for Field %1', array(1 => $i)), NULL);
      $jsSet = FALSE;
      if ($this->get('savedMapping')) {
        if (isset($mappingName[$i])) {
          if ($mappingName[$i] != ts('- do not import -')) {
            $mapName = $mappingName[$i];
            $patterns = array();
            $mappingHeader = $this->defaultFromHeader($mapName, $patterns);

            $websiteTypeId = isset($mappingWebsiteType[$i]) ? $mappingWebsiteType[$i] : NULL;
            $locationId = isset($mappingLocation[$i]) ? $mappingLocation[$i] : 0;
            $phoneType = isset($mappingPhoneType[$i]) ? $mappingPhoneType[$i] : NULL;
            $imProvider = isset($mappingImProvider[$i]) ? $mappingImProvider[$i] : NULL;
            $softField = isset($mappingContactType[$i]) ? $mappingContactType[$i] : 0;

            if ($softField) {
              $defaults["mapper[$i]"] = array($mappingHeader, $softField);
            }
            elseif ($websiteTypeId) {
              if (!$websiteTypeId) {
                $js .= "{$formName}['mapper[$i][1]'].style.display = 'none';\n";
              }
              $defaults["mapper[$i]"] = array($mappingHeader, $websiteTypeId);
            }
            else {
              if (!$locationId) {
                $js .= "{$formName}['mapper[$i][1]'].style.display = 'none';\n";
              }
              //default for IM/phone without related contact
              $typeId = NULL;
              if (isset($phoneType)) {
                $typeId = $phoneType;
              }
              elseif (isset($imProvider)) {
                $typeId = $imProvider;
              }
              $defaults["mapper[$i]"] = array($mappingHeader, $locationId, $typeId);
            }

            if ((!$phoneType) && (!$imProvider)) {
              $js .= "{$formName}['mapper[$i][2]'].style.display = 'none';\n";
            }
            $js .= "{$formName}['mapper[$i][3]'].style.display = 'none';\n";

            $jsSet = TRUE;
          }
          else {
            $defaults["mapper[$i]"] = array();
          }
          if (!$jsSet) {
            for ($k = 1; $k < 4; $k++) {
              $js .= "{$formName}['mapper[$i][$k]'].style.display = 'none';\n";
            }
          }
        }
        //end of load mapping
      }
      else {
        // $js .= "swapOptions($formName, 'mapper[$i]', 0, 3, 'hs_mapper_" . $i . "_');\n";
        $mappedHeaderKey = '';
        $mappedTypeId = $mappedLocationId = 0;
        if ($hasHeaders) {
          // Infer the default from the skipped headers if we have them
          $mappedHeaderKey = $this->defaultFromHeader(CRM_Utils_Array::value($i, $this->_columnHeaders), $headerPatterns);
          $mappedLocationId = !empty($mappedHeaderKey) && !empty($this->_locationFields[$mappedHeaderKey]) ? $defaultLocationType->id : 0;
        }
        else {
          // Otherwise guess the default from the form of the data
          $mappedHeaderKey = $this->defaultFromData($dataPatterns, $i);
          $mappedLocationId = !empty($mappedHeaderKey) && !empty($this->_locationFields[$mappedHeaderKey]) ? $defaultLocationType->id : 0;
        }
        if ($mappedLocationId) {
          switch($mappedHeaderKey){
            case 'im':
              $mappedTypeId = key($imProviders);
              break;
            case 'phone':
              $mappedTypeId = key($phoneTypes);
              break;
            default:
              $mappedTypeId = 0;
              break;
          }
        }
        if ($mappedHeaderKey == 'website') {
          $mappedLocationId = key($websiteTypes); 
        }
        if (!empty($mappedHeaderKey)) {
          $defaults["mapper[$i]"] = array($mappedHeaderKey, $mappedLocationId, $mappedTypeId);
          if ((!$mappedLocationId)) {
            $js .= "{$formName}['mapper[$i][1]'].style.display = 'none';\n";
          }
          if ((!$mappedTypeId)) {
            $js .= "{$formName}['mapper[$i][2]'].style.display = 'none';\n";
          }
        }
        else {
          $js .= "{$formName}['mapper[$i][1]'].style.display = 'none';\n";
          $js .= "{$formName}['mapper[$i][2]'].style.display = 'none';\n";
        }
        $js .= "{$formName}['mapper[$i][3]'].style.display = 'none';\n";
      }
      $sel->setOptions(array($sel1, $sel2, (isset($sel3)) ? $sel3 : "", (isset($sel4)) ? $sel4 : ""));
    }
    $js .= "</script>\n";
    $this->assign('initHideBoxes', $js);

    //set warning if mismatch in more than
    if (isset($mappingName)) {
      if (($this->_columnCount != count($mappingName))) {
        $warning++;
      }
    }
    if ($warning != 0 && $this->get('savedMapping')) {
      $session = CRM_Core_Session::singleton();
      $session->setStatus(ts('The data columns in this import file appear to be different from the saved mapping. Please verify that you have selected the correct saved mapping before continuing.'));
    }
    else {
      $session = CRM_Core_Session::singleton();
      $session->setStatus(NULL);
    }

    $this->setDefaults($defaults);

    $this->addButtons(array(
        array('type' => 'back',
          'name' => ts('<< Previous'),
        ),
        array('type' => 'next',
          'name' => ts('Continue >>'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ),
        array('type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );
  }

  /**
   * global validation rules for the form
   *
   * @param array $fields posted values of the form
   *
   * @return array list of errors to be posted back to the form
   * @static
   * @access public
   */
  static function formRule($fields, $files, $self) {
    $errors = array();
    $fieldMessage = NULL;

    if (!array_key_exists('savedMapping', $fields)) {
      $importKeys = array();
      foreach ($fields['mapper'] as $mapperPart) {
        $importKeys[] = $mapperPart[0];
      }
      // FIXME: should use the schema titles, not redeclare them
      $requiredFields = array(
        'membership_contact_id' => ts('Contact ID'),
        'membership_type_id' => ts('Membership Type'),
        'membership_start_date' => ts('Membership Start Date'),
      );

      $contactTypeId = $self->get('contactType');
      $contactTypes = array(
        CRM_Member_Import_Parser::CONTACT_INDIVIDUAL => 'Individual',
        CRM_Member_Import_Parser::CONTACT_HOUSEHOLD => 'Household',
        CRM_Member_Import_Parser::CONTACT_ORGANIZATION => 'Organization',
      );
      $dedupeRuleGroup = $self->get('dedupeRuleGroup');
      if(!empty($dedupeRuleGroup)) {
        $ruleParams = array('id' => $dedupeRuleGroup);
      }
      else{
        // default rule group
        $ruleParams = array(
          'contact_type' => $contactType,
          'level' => 'Strict',
        );
      }
      require_once 'CRM/Dedupe/BAO/RuleGroup.php';
      list($ruleFields, $threshold) = CRM_Dedupe_BAO_RuleGroup::dedupeRuleFieldsWeight($ruleParams);
      $weightSum = 0;
      foreach ($importKeys as $key => $val) {
        if (array_key_exists($val, $ruleFields)) {
          $weightSum += $ruleFields[$val];
        }
      }
      foreach ($ruleFields as $field => $weight) {
        $fieldMessage .= ' ' . $field . '(weight ' . $weight . ')';
      }

      foreach ($requiredFields as $field => $title) {
        if (!in_array($field, $importKeys)) {
          if ($field == 'membership_contact_id') {
            if ((($weightSum >= $threshold || in_array('external_identifier', $importKeys)) &&
                $self->_onDuplicate != CRM_Member_Import_Parser::DUPLICATE_UPDATE
              ) ||
              in_array('membership_id', $importKeys)
            ) {
              continue;
            }
            else {
              $errors['_qf_default'] .= ts('Missing required contact matching fields.') . " $fieldMessage " . ts('(Sum of all weights should be greater than or equal to threshold: %1).', array(1 => $threshold)) . ' ' . ts('(OR Membership ID if update mode.)') . '<br />';
            }
          }
          else {
            $errors['_qf_default'] .= ts('Missing required field: %1', array(1 => $title)) . '<br />';
          }
        }
      }
    }


    if (CRM_Utils_Array::value('saveMapping', $fields)) {
      $nameField = CRM_Utils_Array::value('saveMappingName', $fields);
      if (empty($nameField)) {
        $errors['saveMappingName'] = ts('Name is required to save Import Mapping');
      }
      else {
        $mappingTypeId = CRM_Core_OptionGroup::getValue('mapping_type', 'Import Membership', 'name');

        if (CRM_Core_BAO_Mapping::checkMapping($nameField, $mappingTypeId)) {
          $errors['saveMappingName'] = ts('Duplicate Import Membership Mapping Name');
        }
      }
    }

    if (!empty($errors)) {
      if (!empty($errors['saveMappingName'])) {
        $_flag = 1;
        require_once 'CRM/Core/Page.php';
        $assignError = new CRM_Core_Page();
        $assignError->assign('mappingDetailsError', $_flag);
      }
      return $errors;
    }

    return TRUE;
  }

  /**
   * Process the mapped fields and map it into the uploaded file
   * preview the file and extract some summary statistics
   *
   * @return void
   * @access public
   */
  public function postProcess() {
    $params = $this->controller->exportValues('MapField');
    //reload the mapfield if load mapping is pressed
    if (!empty($params['savedMapping'])) {
      $this->set('savedMapping', $params['savedMapping']);
      $this->controller->resetPage($this->_name);
      return;
    }

    $fileName = $this->controller->exportValue('UploadFile', 'uploadFile');
    $skipColumnHeader = $this->controller->exportValue('UploadFile', 'skipColumnHeader');

    $config = CRM_Core_Config::singleton();
    $seperator = $config->fieldSeparator;

    $mapperKeys = array();
    $mapper = array();
    $mapperKeysOrigin = $this->controller->exportValue($this->_name, 'mapper');
    $mapperKeysMain = array();
    $mapperLocType = array();
    $mapperPhoneType = array();

    $mapperWeight = $params['weight'];
    for ($i=0; $i < count($mapperWeight); $i++) {
      $mapperKeys[] = $mapperKeysOrigin[array_search($i, $mapperWeight)];
    }
    $this->set('mapperKeys', $mapperKeys);

    $phoneTypes = CRM_Core_PseudoConstant::phoneType();
    $imProviders = CRM_Core_PseudoConstant::IMProvider();
    $websiteTypes = CRM_Core_PseudoConstant::websiteType();
    $locationTypes = CRM_Core_PseudoConstant::locationType();

    //these mapper params need to set key as array and val as null.
    $mapperParams = array(
      'locations' => 'locationsVal',
      'mapperLocType' => 'mapperLocTypeVal',
      'mapperPhoneType' => 'mapperPhoneTypeVal',
      'mapperImProvider' => 'mapperImProviderVal',
      'mapperWebsiteType' => 'mapperWebsiteTypeVal',
    );
    foreach (array_keys($mapperParams) as $mapperParam) {
      $$mapperParam = array();
    }

    for ($i = 0; $i < $this->_columnCount; $i++) {
      foreach (array_values($mapperParams) as $mapperParam)$$mapperParam = NULL;

      $fldName = CRM_Utils_Array::value(0, $mapperKeys[$i]);
      $selOne = CRM_Utils_Array::value(1, $mapperKeys[$i]);
      $selTwo = CRM_Utils_Array::value(2, $mapperKeys[$i]);
      $selThree = CRM_Utils_Array::value(3, $mapperKeys[$i]);

      $mapper[$i] = $this->_mapperFields[$mapperKeys[$i][0]];
      $mapperKeysMain[$i] = $mapperKeys[$i][0];

      if ($selOne && is_numeric($selOne)) {
        if ($fldName == 'url') {
          $mapperWebsiteTypeVal = $selOne;
        }
        else {
          $locationsVal = $locationTypes[$selOne];
          $mapperLocTypeVal = $selOne;
          if ($selTwo && is_numeric($selTwo)) {
            if ($fldName == 'phone') {
              $mapperPhoneTypeVal = $selTwo;
            }
            elseif ($fldName == 'im') {
              $mapperImProviderVal = $selTwo;
            }
          }
        }
      }
      foreach ($mapperParams as $mapperParamKey => $mapperParamVal) {
        ${$mapperParamKey}[$i] = $$mapperParamVal;
      }
    }

    $this->set('mapper', $mapper);
    $properties = array(
      'ims' => 'mapperImProvider',
      'phones' => 'mapperPhoneType',
      'websites' => 'mapperWebsiteType',
      'locationTypes' => 'mapperLocType',
      'locations' => 'locations',
    );
    foreach ($properties as $propertyName => $propertyVal) {
      $this->set($propertyName, $$propertyVal);
    }

    // store mapping Id to display it in the preview page
    $this->set('loadMappingId', CRM_Utils_Array::value('mappingId', $params));

    //Updating Mapping Records
    if (CRM_Utils_Array::value('updateMapping', $params)) {
      $mappingFields = new CRM_Core_DAO_MappingField();
      $mappingFields->mapping_id = $params['mappingId'];
      $mappingFields->find();

      $mappingFieldsId = array();
      while ($mappingFields->fetch()) {
        if ($mappingFields->id) {
          $mappingFieldsId[$mappingFields->column_number] = $mappingFields->id;
        }
      }

      for ($i = 0; $i < $this->_columnCount; $i++) {
        $updateMappingFields = new CRM_Core_DAO_MappingField();
        $updateMappingFields->id = $mappingFieldsId[$i];
        $updateMappingFields->mapping_id = $params['mappingId'];
        $updateMappingFields->column_number = $i;
        $updateMappingFields->name = $mapper[$i];
        $updateMappingFields->location_type_id = 'NULL';
        $updateMappingFields->phone_type_id = 'NULL';
        $updateMappingFields->im_provider_id = 'NULL';
        $updateMappingFields->website_type_id = 'NULL';

        if (CRM_Utils_Array::value('0', $mapperKeys[$i]) == 'url') {
          $updateMappingFields->website_type_id = isset($mapperKeys[$i][1]) ? $mapperKeys[$i][1] : 'NULL';
        }
        else {
          if (CRM_Utils_Array::value('0', $mapperKeys[$i]) == 'phone') {
            $updateMappingFields->phone_type_id = isset($mapperKeys[$i][2]) ? $mapperKeys[$i][2] : 'NULL';
          }
          elseif (CRM_Utils_Array::value('0', $mapperKeys[$i]) == 'im') {
            $updateMappingFields->im_provider_id = isset($mapperKeys[$i][2]) ? $mapperKeys[$i][2] : 'NULL';
          }
          $location = array_keys($locationTypes, $locations[$i]);
          $updateMappingFields->location_type_id = !empty($location) ? $location[0] : 'NULL';
        }

        $updateMappingFields->contact_type = isset($mapperSoftCredit[$i]) ? $mapperSoftCredit[$i] : 'NULL';
        $updateMappingFields->save();
      }
    }

    //Saving Mapping Details and Records
    if (CRM_Utils_Array::value('saveMapping', $params)) {
      $mappingParams = array('name' => $params['saveMappingName'],
        'description' => $params['saveMappingDesc'],
        'mapping_type_id' => CRM_Core_OptionGroup::getValue('mapping_type',
          'Import Membership',
          'name'
        ),
      );
      $saveMapping = CRM_Core_BAO_Mapping::add($mappingParams);

      for ($i = 0; $i < $this->_columnCount; $i++) {

        $saveMappingFields = new CRM_Core_DAO_MappingField();
        $saveMappingFields->mapping_id = $saveMapping->id;
        $saveMappingFields->column_number = $i;
        $saveMappingFields->name = $mapper[$i];

        
        if (CRM_Utils_Array::value('0', $mapperKeys[$i]) == 'url') {
          $saveMappingFields->website_type_id = isset($mapperKeys[$i][1]) ? $mapperKeys[$i][1] : NULL;
        }
        else {
          if (CRM_Utils_Array::value('0', $mapperKeys[$i]) == 'phone') {
            $saveMappingFields->phone_type_id = isset($mapperKeys[$i][2]) ? $mapperKeys[$i][2] : NULL;
          }
          elseif (CRM_Utils_Array::value('0', $mapperKeys[$i]) == 'im') {
            $saveMappingFields->im_provider_id = isset($mapperKeys[$i][2]) ? $mapperKeys[$i][2] : NULL;
          }
          $saveMappingFields->location_type_id = isset($location_id[0]) ? $location_id[0] : NULL;
        }

        $saveMappingFields->contact_type = isset($mapperSoftCredit[$i]) ? $mapperSoftCredit[$i] : NULL;
        $saveMappingFields->save();
      }
      $this->set('savedMapping', $saveMappingFields->mapping_id);
    }

    $parser = new CRM_Member_Import_Parser_Membership($mapperKeysMain, $mapperLocType,  $mapperPhoneType, $mapperWebsiteType, $mapperImProvider);
    $parser->run($fileName, $seperator, $mapper, $skipColumnHeader,
      CRM_Member_Import_Parser::MODE_PREVIEW, $this->get('contactType')
    );
    // add all the necessary variables to the form
    $parser->set($this);
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   * @access public
   */
  public function getTitle() {
    return ts('Match Fields');
  }
}

