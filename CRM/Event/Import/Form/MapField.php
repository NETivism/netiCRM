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
class CRM_Event_Import_Form_MapField extends CRM_Core_Form {

  public $_onDuplicate;
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
      $matches = preg_grep('/^'.$columnMatch.'/iu', $this->_mapperFields);
      if (!empty($matches)) {
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

      //             if ($this->_fieldUsed[$key])
      //                 continue;

      /* Take a vote over the preview data set */

      $hits = 0;
      for ($i = 0; $i < $n; $i++) {
        if (isset($this->_dataValues[$i][$index])) {
          if (preg_match($re, $this->_dataValues[$i][$index])) {
            $hits++;
          }
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
    asort($this->_mapperFields);
    unset($this->_mapperFields['participant_is_test']);
    $this->_columnCount = $this->get('columnCount');
    $this->assign('columnCount', $this->_columnCount);
    $this->_dataValues = $this->get('dataValues');
    $this->assign('dataValues', $this->_dataValues);

    $skipColumnHeader = $this->controller->exportValue('UploadFile', 'skipColumnHeader');
    $this->_onDuplicate = $this->get('onDuplicate');
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
    if ($this->_onDuplicate == CRM_Event_Import_Parser::DUPLICATE_UPDATE) {
      $remove = ['participant_contact_id', 'email', 'first_name', 'last_name', 'external_identifier'];
      foreach ($remove as $value) {
        unset($this->_mapperFields[$value]);
      }
      $highlightedFieldsArray = ['participant_id', 'event_id', 'event_title', 'participant_status_id'];
      foreach ($highlightedFieldsArray as $name) {
        $highlightedFields[] = $name;
      }
    }
    elseif ($this->_onDuplicate == CRM_Event_Import_Parser::DUPLICATE_SKIP ||
      $this->_onDuplicate == CRM_Event_Import_Parser::DUPLICATE_NOCHECK
    ) {
      unset($this->_mapperFields['participant_id']);
      $highlightedFieldsArray = ['participant_contact_id', 'event_id', 'email', 'first_name', 'last_name', 'external_identifier', 'participant_status_id'];
      foreach ($highlightedFieldsArray as $name) {
        $highlightedFields[] = $name;
      }
    }
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
    }
    else {
      $savedMapping = $this->get('savedMapping');

      list($mappingName, $mappingContactType, $mappingLocation, $mappingPhoneType, $mappingRelation) = CRM_Core_BAO_Mapping::getMappingFields($savedMapping);

      $mappingName = $mappingName[1];
      $mappingContactType = $mappingContactType[1];
      $mappingLocation = CRM_Utils_Array::value('1', $mappingLocation);
      $mappingPhoneType = CRM_Utils_Array::value('1', $mappingPhoneType);
      $mappingRelation = CRM_Utils_Array::value('1', $mappingRelation);

      //mapping is to be loaded from database

      $params = ['id' => $savedMapping];
      $temp = [];
      $mappingDetails = CRM_Core_BAO_Mapping::retrieve($params, $temp);

      $this->assign('loadedMapping', $mappingDetails->name);
      $this->set('loadedMapping', $savedMapping);

      $getMappingName = new CRM_Core_DAO_Mapping();
      $getMappingName->id = $savedMapping;
      $getMappingName->mapping_type = 'Import Participants';
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

    $this->addFormRule(['CRM_Event_Import_Form_MapField', 'formRule'], $this);

    $defaults = [];
    $mapperKeys = array_keys($this->_mapperFields);
    $hasHeaders = !empty($this->_columnHeaders);
    $headerPatterns = $this->get('headerPatterns');
    $dataPatterns = $this->get('dataPatterns');
    $hasLocationTypes = $this->get('fieldTypes');


    /* Initialize all field usages to false */

    foreach ($mapperKeys as $key) {
      $this->_fieldUsed[$key] = FALSE;
    }
    $this->_location_types = &CRM_Core_PseudoConstant::locationType();
    $sel1 = $this->_mapperFields;

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

            $mappingHeader = array_keys($this->_mapperFields, $mappingName[$i]);

            if (!isset($locationId) || !$locationId) {
              $js .= "{$formName}['mapper[$i][1]'].style.display = 'none';\n";
            }

            if (!isset($phoneType) || !$phoneType) {
              $js .= "{$formName}['mapper[$i][2]'].style.display = 'none';\n";
            }

            $js .= "{$formName}['mapper[$i][3]'].style.display = 'none';\n";
            $defaults["mapper[$i]"] = [$mappingHeader[0],
              (isset($locationId)) ? $locationId : "",
              (isset($phoneType)) ? $phoneType : "",
            ];
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
          // this load section to help mapping if we ran out of saved columns when doing Load Mapping
          $js .= "swapOptions($formName, 'mapper[$i]', 0, 3, 'hs_mapper_" . $i . "_');\n";

          if ($hasHeaders) {
            $defaults["mapper[$i]"] = [$this->defaultFromHeader($this->_columnHeaders[$i], $headerPatterns)];
          }
          else {
            $defaults["mapper[$i]"] = [$this->defaultFromData($dataPatterns, $i)];
          }
        }
        //end of load mapping
      }
      else {
        $js .= "swapOptions($formName, 'mapper[$i]', 0, 3, 'hs_mapper_" . $i . "_');\n";
        if ($hasHeaders) {
          // Infer the default from the skipped headers if we have them
          $defaults["mapper[$i]"] = [
            $this->defaultFromHeader($this->_columnHeaders[$i],
              $headerPatterns
            ),
            //                     $defaultLocationType->id
            0,
          ];
        }
        else {
          // Otherwise guess the default from the form of the data
          $defaults["mapper[$i]"] = [
            $this->defaultFromData($dataPatterns, $i),
            //                     $defaultLocationType->id
            0,
          ];
        }
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
        'participant_contact_id' => ts('Contact ID'),
        'event_id' => ts('Event ID'),
      ];

      $contactTypeId = $self->get('contactType');
      $contactTypes = [
        CRM_Event_Import_Parser::CONTACT_INDIVIDUAL => 'Individual',
        CRM_Event_Import_Parser::CONTACT_HOUSEHOLD => 'Household',
        CRM_Event_Import_Parser::CONTACT_ORGANIZATION => 'Organization',
      ];
      $params = [
        'level' => 'Strict',
        'contact_type' => $contactTypes[$contactTypeId],
      ];

      list($ruleFields, $threshold) = CRM_Dedupe_BAO_RuleGroup::dedupeRuleFieldsWeight($params);
      if ($ruleFields['sort_name']) {
        $ruleFields['last_name'] = $ruleFields['sort_name'] / 2;
        $ruleFields['first_name'] = $ruleFields['sort_name'] / 2;
      }
      if ($ruleFields['display_name']) {
        $ruleFields['last_name'] = $ruleFields['display_name'] / 2;
        $ruleFields['first_name'] = $ruleFields['display_name'] / 2;
      }
      unset($ruleFields['sort_name']);
      unset($ruleFields['display_name']);
      $weightSum = 0;
      foreach ($importKeys as $key => $val) {
        if (CRM_Utils_Array::arrayKeyExists($val, $ruleFields)) {
          $weightSum += $ruleFields[$val];
        }
      }
      foreach ($ruleFields as $field => $weight) {
        $fieldMessage .= ' ' . $field . '(weight ' . $weight . ')';
      }

      foreach ($requiredFields as $field => $title) {
        if (!in_array($field, $importKeys)) {
          if ($field == 'participant_contact_id') {
            if ($weightSum >= $threshold || in_array('external_identifier', $importKeys) ||
              in_array('participant_id', $importKeys)
            ) {
              continue;
            }
            if ($self->_onDuplicate == CRM_Event_Import_Parser::DUPLICATE_UPDATE) {
              $errors['_qf_default'] .= ts('Missing required field: Provide Particiapnt ID') . '<br />';
            }
            else {
              $errors['_qf_default'] .= ts('Missing required contact matching fields.') . " $fieldMessage " . ts('(Sum of all weights should be greater than or equal to threshold: %1).', [1 => $threshold]) . ' ' . ts('Or Provide Contact Id or External Identifier.') . '<br />';
            }
          }
          elseif (!in_array('event_title', $importKeys)) {
            $errors['_qf_default'] .= ts('Missing required field: Provide %1 or %2',
              [1 => $title, 2 => 'Event Title']
            ) . '<br />';
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
        $mappingTypeId = CRM_Core_OptionGroup::getValue('mapping_type', 'Import Participant', 'name');
        if (CRM_Core_BAO_Mapping::checkMapping($nameField, $mappingTypeId)) {
          $errors['saveMappingName'] = ts('Duplicate Import Participant Mapping Name');
        }
      }
    }

    //display Error if loaded mapping is not selected
    if (CRM_Utils_Array::arrayKeyExists('loadMapping', $fields)) {
      $getMapName = CRM_Utils_Array::value('savedMapping', $fields);
      if (empty($getMapName)) {
        $errors['savedMapping'] = ts('Select saved mapping');
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
    $seperator = $config->fieldSeparator;

    $mapperKeys = [];
    $mapper = [];
    $mapperKeysOrigin = $this->controller->exportValue($this->_name, 'mapper');
    $mapperKeysMain = [];

    $mapperWeight = $params['weight'];
    for ($i=0; $i < count($mapperWeight); $i++) {
      $mapperKeys[] = $mapperKeysOrigin[array_search($i, $mapperWeight)];
    }
    $this->set('mapperKeys', $mapperKeys);

    for ($i = 0; $i < $this->_columnCount; $i++) {
      $mapper[$i] = $this->_mapperFields[$mapperKeys[$i][0]];
      $mapperKeysMain[$i] = $mapperKeys[$i][0];
    }

    $this->set('mapper', $mapper);

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

        list($id, $first, $second) = explode('_', $mapperKeys[$i][0]);
        $updateMappingFields->name = $mapper[$i];
        $updateMappingFields->save();
      }
    }

    //Saving Mapping Details and Records
    if (CRM_Utils_Array::value('saveMapping', $params)) {
      $mappingParams = ['name' => $params['saveMappingName'],
        'description' => $params['saveMappingDesc'],
        'mapping_type_id' => CRM_Core_OptionGroup::getValue('mapping_type',
          'Import Participant',
          'name'
        ),
      ];
      $saveMapping = CRM_Core_BAO_Mapping::add($mappingParams);


      for ($i = 0; $i < $this->_columnCount; $i++) {
        $saveMappingFields = new CRM_Core_DAO_MappingField();
        $saveMappingFields->mapping_id = $saveMapping->id;
        $saveMappingFields->column_number = $i;

        list($id, $first, $second) = explode('_', $mapperKeys[$i][0]);
        $saveMappingFields->name = $mapper[$i];
        $saveMappingFields->save();
      }
      $this->set('savedMapping', $saveMappingFields->mapping_id);
    }

    $errorFilenamePrefix = CRM_Event_Import_Parser::ERROR_FILE_PREFIX.'_'.date('YmdHis', CRM_REQUEST_TIME);
    $this->set('errorFilenamePrefix', $errorFilenamePrefix);

    $parser = new CRM_Event_Import_Parser_Participant($mapperKeysMain);
    $parser->run(
      $fileName,
      $seperator,
      $mapper,
      $skipColumnHeader,
      CRM_Event_Import_Parser::MODE_PREVIEW,
      $this->get('contactType'),
      CRM_Event_Import_Parser::DUPLICATE_SKIP,
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

