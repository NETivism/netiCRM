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

require_once 'CRM/Activity/Import/Parser/Activity.php';

/**
 * This class gets the name of the file to upload
 */
class CRM_Activity_Import_Form_MapField extends CRM_Core_Form {

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
    unset($this->_mapperFields['id']);
    asort($this->_mapperFields);

    $this->_columnCount = $this->get('columnCount');
    $this->assign('columnCount', $this->_columnCount);
    $this->_dataValues = $this->get('dataValues');
    $this->assign('dataValues', $this->_dataValues);

    $skipColumnHeader = $this->controller->exportValue('UploadFile', 'skipColumnHeader');

    if ($skipColumnHeader) {
      $this->assign('skipColumnHeader', $skipColumnHeader);
      $this->assign('rowDisplayCount', 3);
      /* if we had a column header to skip, stash it for later */

      $this->_columnHeaders = $this->_dataValues[0];
    }
    else {
      $this->assign('rowDisplayCount', 2);
    }
    $highlightedFields = array();
    $requiredFields = array('activity_date_time', 'activity_type_id', 'activity_name', 'target_contact_id', 'activity_subject');
    foreach ($requiredFields as $val) {
      $highlightedFields[] = $val;
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
    require_once "CRM/Core/BAO/Mapping.php";
    require_once "CRM/Core/OptionGroup.php";
    //to save the current mappings
    if (!$this->get('savedMapping')) {
      $saveDetailsName = ts('Save this field mapping');
      $this->applyFilter('saveMappingName', 'trim');
      $this->add('text', 'saveMappingName', ts('Name'));
      $this->add('text', 'saveMappingDesc', ts('Description'));
    }
    else {
      $savedMapping = $this->get('savedMapping');
      //mapping is to be loaded from database

      list($mappingName, $mappingContactType, $mappingLocation, $mappingPhoneType, $mappingRelation) = CRM_Core_BAO_Mapping::getMappingFields($savedMapping);

      //get loaded Mapping Fields
      $mappingName = CRM_Utils_Array::value('1', $mappingName);
      $mappingContactType = CRM_Utils_Array::value('1', $mappingContactType);
      $mappingLocation = CRM_Utils_Array::value('1', $mappingLocation);
      $mappingPhoneType = CRM_Utils_Array::value('1', $mappingPhoneType);
      $mappingRelation = CRM_Utils_Array::value('1', $mappingRelation);

      $this->assign('loadedMapping', $savedMapping);
      $this->set('loadedMapping', $savedMapping);

      $params = array('id' => $savedMapping);
      $temp = array();
      $mappingDetails = CRM_Core_BAO_Mapping::retrieve($params, $temp);

      $this->assign('savedName', $mappingDetails->name);

      $this->add('hidden', 'mappingId', $savedMapping);

      $this->addElement('checkbox', 'updateMapping', ts('Update this field mapping'), NULL);
      $saveDetailsName = ts('Save as a new field mapping');
      $this->add('text', 'saveMappingName', ts('Name'));
      $this->add('text', 'saveMappingDesc', ts('Description'));
    }

    $this->addElement('checkbox', 'saveMapping', $saveDetailsName, NULL, array('onclick' => "showSaveDetails(this)"));

    $this->addFormRule(array('CRM_Activity_Import_Form_MapField', 'formRule'));

    //-------- end of saved mapping stuff ---------

    $defaults = array();
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
      $sel = &$this->addElement('hierselect', "mapper[$i]", ts('Mapper for Field %1', array(1 => $i)), NULL);
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
            $defaults["mapper[$i]"] = array($mappingHeader[0],
              (isset($locationId)) ? $locationId : "",
              (isset($phoneType)) ? $phoneType : "",
            );
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
        else {
          // this load section to help mapping if we ran out of saved columns when doing Load Mapping
          $js .= "swapOptions($formName, 'mapper[$i]', 0, 3, 'hs_mapper_" . $i . "_');\n";

          if ($hasHeaders) {
            $defaults["mapper[$i]"] = array($this->defaultFromHeader($this->_columnHeaders[$i],
                $headerPatterns
              ));
          }
          else {
            $defaults["mapper[$i]"] = array($this->defaultFromData($dataPatterns, $i));
          }
        }
        //end of load mapping
      }
      else {
        $js .= "swapOptions($formName, 'mapper[$i]', 0, 3, 'hs_mapper_" . $i . "_');\n";
        if ($hasHeaders) {
          // Infer the default from the skipped headers if we have them
          $defaults["mapper[$i]"] = array(
            $this->defaultFromHeader($this->_columnHeaders[$i],
              $headerPatterns
            ),
            //                     $defaultLocationType->id
            0,
          );
        }
        else {
          // Otherwise guess the default from the form of the data
          $defaults["mapper[$i]"] = array(
            $this->defaultFromData($dataPatterns, $i),
            //                     $defaultLocationType->id
            0,
          );
        }
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
  static function formRule($fields) {
    $errors = array();
    $fieldMessage = NULL;
    if (!CRM_Utils_Array::arrayKeyExists('savedMapping', $fields)) {
      $importKeys = array();
      foreach ($fields['mapper'] as $mapperPart) {
        $importKeys[] = $mapperPart[0];
      }
      // FIXME: should use the schema titles, not redeclare them
      $requiredFields = array(
        'target_contact_id' => ts('Contact ID'),
        'activity_date_time' => ts('Activity Date'),
        'activity_subject' => ts('Activity Subject'),
        'activity_type_id' => ts('Activity Type Id'),
      );

      $params = array(
        'level' => 'Strict',
        'contact_type' => 'Individual',
      );
      require_once 'CRM/Dedupe/BAO/RuleGroup.php';
      list($ruleFields, $threshold) = CRM_Dedupe_BAO_RuleGroup::dedupeRuleFieldsWeight($params);
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
          if ($field == 'target_contact_id') {
            if ($weightSum >= $threshold || in_array('external_identifier', $importKeys)) {
              continue;
            }
            else {
              $errors['_qf_default'] .= ts('Missing required contact matching fields.') . $fieldMessage . ' ' . ts('(Sum of all weights should be greater than or equal to threshold: %1).', array(1 => $threshold)) . '<br />';
            }
          }
          elseif ($field == 'activity_type_id') {
            if (in_array('activity_name', $importKeys)) {
              continue;
            }
            else {
              $errors['_qf_default'] .= ts('Missing required field: Provide %1 or %2',
                array(1 => $title,
                  2 => 'Activity Type Label',
                )
              ) . '<br />';
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
        $mappingTypeId = CRM_Core_OptionGroup::getValue('mapping_type', 'Import Activity', 'name');
        if (CRM_Core_BAO_Mapping::checkMapping($nameField, $mappingTypeId)) {
          $errors['saveMappingName'] = ts('Duplicate Import Mapping Name');
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
    $separator = $config->fieldSeparator;

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

    for ($i = 0; $i < $this->_columnCount; $i++) {
      $mapper[$i] = $this->_mapperFields[$mapperKeys[$i][0]];
      $mapperKeysMain[$i] = $mapperKeys[$i][0];

      if (is_numeric($mapperKeys[$i][1])) {
        $mapperLocType[$i] = $mapperKeys[$i][1];
      }
      else {
        $mapperLocType[$i] = NULL;
      }

      if (!is_numeric($mapperKeys[$i][2])) {
        $mapperPhoneType[$i] = $mapperKeys[$i][2];
      }
      else {
        $mapperPhoneType[$i] = NULL;
      }
    }

    $this->set('mapper', $mapper);
    // store mapping Id to display it in the preview page
    $this->set('loadMappingId', $params['mappingId']);

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

        list($id, $first, $second) = explode('_', $mapperKeys[$i][0]);
        $updateMappingFields->name = $mapper[$i];
        $updateMappingFields->save();
      }
    }

    //Saving Mapping Details and Records
    if (CRM_Utils_Array::value('saveMapping', $params)) {
      $mappingParams = array('name' => $params['saveMappingName'],
        'description' => $params['saveMappingDesc'],
        'mapping_type_id' => CRM_Core_OptionGroup::getValue('mapping_type',
          'Import Activity',
          'name'
        ),
      );
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


    $errorFilenamePrefix = CRM_Activity_Import_Parser::ERROR_FILE_PREFIX.'_'.date('YmdHis', CRM_REQUEST_TIME);
    $this->set('errorFilenamePrefix', $errorFilenamePrefix);

    $parser = new CRM_Activity_Import_Parser_Activity($mapperKeysMain, $mapperLocType, $mapperPhoneType);
    $parser->run(
      $fileName,
      $separator,
      $mapper,
      $skipColumnHeader,
      CRM_Activity_Import_Parser::MODE_PREVIEW,
      CRM_Activity_Import_Parser::DUPLICATE_SKIP,
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

