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








/**
 * This class gets the name of the file to upload
 */
class CRM_Member_Import_Form_MapField extends CRM_Core_Form {

  public $_onDuplicate;
  public $_locationFields;
  public $_location_types;
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
      $columnMatch = trim(preg_replace('/([\.\?\+\*\(\)\[\]\{\}])/', '\\\\$1', preg_replace('/\(.*\)/', '', $columnName)));
      $matches = preg_grep('/^'.$columnMatch.'|\::('.$columnMatch.')\b/iu', $this->_mapperFields);
      if (!empty($matches) && count($matches) == 1) {
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
    $this->_onDuplicate = $this->get('onDuplicate', $onDuplicate ?? "");

    $highlightedFields = [];
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
    $importMode = $this->get('importMode');
    if ($importMode & CRM_Member_Import_Parser::IMPORT_UPDATE) {
      $highlightedFields = array_merge($highlightedFields, ['membership_start_date', 'membership_type_id']);
      $dataReferenceField = $this->get('dataReferenceField');
      $highlightedFields[] = $dataReferenceField;
      $this->_mapperFields[$dataReferenceField] .= " " . ts('(match to membership record)');
      if($dataReferenceField != 'membership_id'){
        unset($this->_mapperFields['membership_id']);
      }
    }
    if ($importMode & CRM_Member_Import_Parser::IMPORT_CREATE) {
      unset($this->_mapperFields['membership_id']);
      $highlightedFields = array_merge($highlightedFields, ['membership_contact_id', 'external_identifier', 'membership_start_date', 'membership_type_id']);

      $dedupeRuleGroup = $this->get('dedupeRuleGroup');
      if(!empty($dedupeRuleGroup)) {
        $ruleParams = ['id' => $dedupeRuleGroup];
      }
      else{
        $contactType = $self->get('contactType');
        // default rule group
        $ruleParams = [
          'contact_type' => $contactType,
          'level' => 'Strict',
        ];
      }
      $dedupeFields = CRM_Dedupe_BAO_Rule::dedupeRuleFieldsMapping($ruleParams);
      $dedupeFields = array_merge($dedupeFields, ['membership_contact_id', 'external_identifier']);
      foreach ($dedupeFields as $fieldName) {
        $this->_mapperFields[$fieldName] .= ' ' . ts('(match to contact)');
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

      $params = ['id' => $savedMapping];
      $temp = [];
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

    $this->addElement('checkbox', 'saveMapping', $saveDetailsName, NULL, ['onclick' => "showSaveDetails(this)"]);

    $this->addFormRule(['CRM_Member_Import_Form_MapField', 'formRule'], $this);

    //-------- end of saved mapping stuff ---------

    $defaults = [];
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
      $this->_location_types = [$defaultLocationType->id => $defaultLocation] + $this->_location_types;
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

    
    // start of soft credit section
    // get contact type for this import
    $contactTypeId = $this->get('contactType');
    $contactTypes = [
      CRM_Contribute_Import_Parser::CONTACT_INDIVIDUAL => 'Individual',
      CRM_Contribute_Import_Parser::CONTACT_HOUSEHOLD => 'Household',
      CRM_Contribute_Import_Parser::CONTACT_ORGANIZATION => 'Organization',
    ];

    $contactType = $contactTypes[$contactTypeId];

    // get imporatable fields for contact type

    $contactFields = CRM_Contact_BAO_Contact::importableFields($contactType, NULL);

    $sel2[''] = NULL;

    $js = "<script type='text/javascript'>\n";
    $formName = 'document.forms.' . $this->_name;

    //used to warn for mismatch column count or mismatch mapping
    $warning = 0;

    for ($i = 0; $i < $this->_columnCount; $i++) {
      $this->add('hidden', "weight[$i]");
      $defaults["weight[$i]"] = $i;
      $sel = &$this->addElement('hierselect', "mapper[$i]", ts('Mapper for Field %1', [1 => $i]), NULL);
      $jsSet = FALSE;
      if ($this->get('savedMapping')) {
        if (isset($mappingName[$i])) {
          if ($mappingName[$i] != ts('- do not import -')) {
            $mapName = $mappingName[$i];
            $patterns = [];
            $mappingHeader = $this->defaultFromHeader($mapName, $patterns);

            // Prepare values and sub type values.
            // If the field have sub type but the subtype value is NULL, the subtype value would be 0.
            // Otherwise, the field have no subtype, the subtype value should be NULL. 
            $websiteTypeId = NULL;
            if ($mappingHeader == 'url') {
              $websiteTypeId = $mappingWebsiteType[$i] ?? 0;
            }
            $locationId = NULL;
            if ($contactFields[$mappingHeader]['hasLocationType']) {
              $locationId = $mappingLocation[$i] ?? 0;
            }
            $phoneType = NULL;
            if ($mappingHeader == 'phone') {
              $phoneType = $mappingPhoneType[$i] ?? 0;
            }
            $imProvider = NULL;
            if ($mappingHeader == 'im') {
              $imProvider = $mappingImProvider[$i] ?? 0;
            }

            // Set visibility of each fields and sub type selection.
            // If subtype is NULL, the subtype selector would be hided.
            if (!is_null($locationId) || !is_null($websiteTypeId)) {
              if (!is_null($websiteTypeId)) {
                $defaults["mapper[$i]"] = [$mappingHeader, $websiteTypeId];
                $js .= "{$formName}['mapper[$i][2]'].style.display = 'none';\n";
              }
              else {
                //default for IM/phone without related contact
                $typeId = NULL;
                if (!is_null($phoneType)) {
                  $typeId = $phoneType;
                }
                elseif (!is_null($imProvider)) {
                  $typeId = $imProvider;
                }
                if (!is_null($typeId)) {
                  $defaults["mapper[$i]"] = [$mappingHeader, $locationId ? $locationId : 1, $typeId];
                }
                else {
                  $defaults["mapper[$i]"] = [$mappingHeader, $locationId];
                  $js .= "{$formName}['mapper[$i][2]'].style.display = 'none';\n";
                }
              }
            }
            else {
              // No location type fields.
              if ($mappingHeader) {
                $defaults["mapper[$i]"] = [$mappingHeader];
              }
              $js .= "{$formName}['mapper[$i][1]'].style.display = 'none';\n";
              $js .= "{$formName}['mapper[$i][2]'].style.display = 'none';\n";
            }
            $js .= "{$formName}['mapper[$i][3]'].style.display = 'none';\n";

            $jsSet = TRUE;
          }
          else {
            $defaults["mapper[$i]"] = [];
          }
          if (!$jsSet) {
            for ($k = 1; $k < 4; $k++) {
              $js .= "{$formName}['mapper[$i][$k]'].style.display = 'none';\n";
            }
          }
        }
        else {
          for ($k = 1; $k < 4; $k++) {
            $js .= "{$formName}['mapper[$i][$k]'].style.display = 'none';\n";
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
          $defaults["mapper[$i]"] = [$mappedHeaderKey, $mappedLocationId, $mappedTypeId];
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
      $sel->setOptions([$sel1, $sel2, (isset($sel3)) ? $sel3 : "", (isset($sel4)) ? $sel4 : ""]);
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

    $this->setDefaults($defaults);

    $this->addButtons([
        ['type' => 'back',
          'name' => ts('<< Previous'),
        ],
        ['type' => 'next',
          'name' => ts('Continue >>'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ],
        ['type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]
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
    $errors = [];
    $fieldMessage = NULL;

    if (!CRM_Utils_Array::arrayKeyExists('savedMapping', $fields)) {
      $importKeys = [];
      foreach ($fields['mapper'] as $mapperPart) {
        $importKeys[] = $mapperPart[0];
      }

      // FIXME: should use the schema titles, not redeclare them
      $requiredFields = [
        'membership_type_id' => ts('Membership Type'),
        'membership_start_date' => ts('Membership Start Date'),
      ];

      $importMode = $self->get('importMode');

      if($importMode & CRM_Member_Import_Parser::IMPORT_CREATE){
        $dedupeRuleGroup = $self->get('dedupeRuleGroup');
        if(!empty($dedupeRuleGroup)) {
          $ruleParams = ['id' => $dedupeRuleGroup];
        }
        else{
          // default rule group
          $contactType = $self->get('contactType');
          $ruleParams = [
            'contact_type' => $contactType,
            'level' => 'Strict',
          ];
        }

        list($ruleFields, $threshold) = CRM_Dedupe_BAO_RuleGroup::dedupeRuleFieldsWeight($ruleParams);
        $weightSum = 0;
        foreach ($importKeys as $key => $val) {
          if (CRM_Utils_Array::arrayKeyExists($val, $ruleFields)) {
            $weightSum += $ruleFields[$val];
          }
        }
        if($weightSum < $threshold){
          $daoContact = new CRM_Contact_DAO_Contact();
          $daoEmail = new CRM_Core_DAO_Email();
          $daoPhone = new CRM_Core_DAO_Phone();
          $daoAddress = new CRM_Core_DAO_Address();
          $fields = $daoContact->fields() + $daoEmail->fields() + $daoPhone->fields() + $daoAddress->fields();
          unset($daoContact);
          unset($daoEmail);
          unset($daoPhone);
          unset($daoAddress);
          foreach ($ruleFields as $field => $weight) {
            $fieldLable = $fields[$field]['title'];
            $fieldMessage .= ' ' . $fieldLable . ' (' . ts('Weight') . ': ' . $weight . ')';
          }
          $errors['_qf_default'] .= ts('Missing required contact matching fields.') . " $fieldMessage " . ts('(Sum of all weights should be greater than or equal to threshold: %1).', [1 => $threshold]) . '<br />'; 
        }
      }else{
        // $importMode is CRM_Member_Import_Parser::IMPORT_UPDATE AND not IMPORT_CREATE
        $dataReferenceField = $self->get('dataReferenceField');
        $referenceFieldOptions = $self->get('referenceFieldOptions');
        $requiredFields[$dataReferenceField] = $referenceFieldOptions[$dataReferenceField];
      }

      foreach ($requiredFields as $field => $title) {
        if (!in_array($field, $importKeys)) {
          $errors['_qf_default'] .= ts('Missing required field: %1', [1 => $title]) . '<br />';
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
    $separator = $config->fieldSeparator;

    $mapperKeys = [];
    $mapper = [];
    $mapperKeysOrigin = $this->controller->exportValue($this->_name, 'mapper');
    $mapperKeysMain = [];
    $mapperLocType = [];
    $mapperPhoneType = [];

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
    $mapperParams = [
      'locations' => 'locationsVal',
      'mapperLocType' => 'mapperLocTypeVal',
      'mapperPhoneType' => 'mapperPhoneTypeVal',
      'mapperImProvider' => 'mapperImProviderVal',
      'mapperWebsiteType' => 'mapperWebsiteTypeVal',
    ];
    foreach (array_keys($mapperParams) as $mapperParam) {
      $$mapperParam = [];
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
    $properties = [
      'ims' => 'mapperImProvider',
      'phones' => 'mapperPhoneType',
      'websites' => 'mapperWebsiteType',
      'locationTypes' => 'mapperLocType',
      'locations' => 'locations',
    ];
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

      $mappingFieldsId = [];
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
          $updateMappingFields->website_type_id = $mapperKeys[$i][1] ?? 'NULL';
        }
        else {
          if (CRM_Utils_Array::value('0', $mapperKeys[$i]) == 'phone') {
            $updateMappingFields->phone_type_id = $mapperKeys[$i][2] ?? 'NULL';
          }
          elseif (CRM_Utils_Array::value('0', $mapperKeys[$i]) == 'im') {
            $updateMappingFields->im_provider_id = $mapperKeys[$i][2] ?? 'NULL';
          }
          $location = array_keys($locationTypes, $locations[$i]);
          $updateMappingFields->location_type_id = !empty($location) ? $location[0] : 'NULL';
        }

        $updateMappingFields->contact_type = $mapperSoftCredit[$i] ?? 'NULL';
        $updateMappingFields->save();
      }
    }

    //Saving Mapping Details and Records
    if (CRM_Utils_Array::value('saveMapping', $params)) {
      $mappingParams = ['name' => $params['saveMappingName'],
        'description' => $params['saveMappingDesc'],
        'mapping_type_id' => CRM_Core_OptionGroup::getValue('mapping_type',
          'Import Membership',
          'name'
        ),
      ];
      $saveMapping = CRM_Core_BAO_Mapping::add($mappingParams);

      for ($i = 0; $i < $this->_columnCount; $i++) {

        $saveMappingFields = new CRM_Core_DAO_MappingField();
        $saveMappingFields->mapping_id = $saveMapping->id;
        $saveMappingFields->column_number = $i;
        $saveMappingFields->name = $mapper[$i];

        
        if (CRM_Utils_Array::value('0', $mapperKeys[$i]) == 'url') {
          $saveMappingFields->website_type_id = $mapperKeys[$i][1] ?? NULL;
        }
        else {
          if (CRM_Utils_Array::value('0', $mapperKeys[$i]) == 'phone') {
            $saveMappingFields->phone_type_id = $mapperKeys[$i][2] ?? NULL;
          }
          elseif (CRM_Utils_Array::value('0', $mapperKeys[$i]) == 'im') {
            $saveMappingFields->im_provider_id = $mapperKeys[$i][2] ?? NULL;
          }
          $location = array_keys($locationTypes, $locations[$i]);
          $saveMappingFields->location_type_id = !empty($location) ? $location[0] : 'NULL';
        }

        $saveMappingFields->contact_type = $mapperSoftCredit[$i] ?? NULL;
        $saveMappingFields->save();
      }
      $this->set('savedMapping', $saveMappingFields->mapping_id);
    }

    $errorFilenamePrefix = CRM_Member_Import_Parser::ERROR_FILE_PREFIX.'_'.date('YmdHis', CRM_REQUEST_TIME);
    $this->set('errorFilenamePrefix', $errorFilenamePrefix);

    $parser = new CRM_Member_Import_Parser_Membership($mapperKeysMain, $mapperLocType,  $mapperPhoneType, $mapperWebsiteType, $mapperImProvider);
    $parser->run(
      $fileName,
      $separator,
      $mapper,
      $skipColumnHeader,
      CRM_Member_Import_Parser::MODE_PREVIEW,
      $this->get('contactType'),
      CRM_Member_Import_Parser::DUPLICATE_SKIP,
      CRM_Member_Import_Parser::CONTACT_NOIDCREATE,
      0,
      '',
      $errorFilenamePrefix
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

