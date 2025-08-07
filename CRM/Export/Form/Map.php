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
class CRM_Export_Form_Map extends CRM_Core_Form {

  /**
   * mapper fields
   *
   * @var array
   * @access protected
   */
  protected $_mapperFields;

  /**
   * number of columns in import file
   *
   * @var int
   * @access protected
   */
  protected $_exportColumnCount;

  /**
   * loaded mapping ID
   *
   * @var int
   * @access protected
   */
  protected $_mappingId;

  /**
   * mapping object when we don't have mapping id
   *
   * @var int
   * @access protected
   */
  public $_mappingObject;

  /**
   * Function to actually build the form
   *
   * @return None
   * @access public
   */
  public function preProcess() {

    $this->_mappingId =  $this->get('mappingId');
    $this->_exportColumnCount = $this->get('exportColumnCount');
    if (!$this->_mappingId) {
      $this->_mappingObject = $this->get('mappingObject');
    }

    if (! $this->_exportColumnCount ) {
      if( $this->_mappingId ){
        $mapping = CRM_Core_BAO_Mapping::getMappingFields($this->_mappingId);
        $mappingFields = $mapping[0][1];
        $this->_exportColumnCount = count($mappingFields) + 5;
      }
      elseif (!empty($this->_mappingObject)){
        $this->_exportColumnCount = count($this->_mappingObject['mappingName'][1]) + 5;
      }
      else {
        $this->_exportColumnCount = 10;
      }
    } else {
      $this->_exportColumnCount = $this->_exportColumnCount + 10;
    }
  }

  public function buildQuickForm() {

    $customSearchID = $this->get('customSearchID');
    if ($customSearchID) {
      $customHeader = $this->get('customHeader');
      $this->assign('customHeader', $customHeader);
    }
    CRM_Core_BAO_Mapping::buildMappingForm($this, 'Export', $this->_mappingId, $this->_exportColumnCount, $blockCnt = 2, $this->get('exportMode'));

    $this->addButtons([
        ['type' => 'back',
          'name' => ts('<< Previous'),
        ],
        ['type' => 'next',
          'name' => ts('Export >>'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
        ],
        ['type' => 'done',
          'name' => ts('Done'),
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
  static function formRule($fields, $values, $mappingTypeId) {
    $errors = [];

    if (CRM_Utils_Array::value('saveMapping', $fields)) {
      $nameField = CRM_Utils_Array::value('saveMappingName', $fields);
      if (empty($nameField)) {
        $errors['saveMappingName'] = ts('Name is required to save Export Mapping');
      }
      else {
        //check for Duplicate mappingName
        if (CRM_Core_BAO_Mapping::checkMapping($nameField, $mappingTypeId)) {
          $errors['saveMappingName'] = ts('Duplicate Export Mapping Name');
        }
      }
    }

    if (!empty($errors)) {
      $_flag = 1;

      $assignError = new CRM_Core_Page();
      $assignError->assign('mappingDetailsError', $_flag);
      return $errors;
    }
    else {
      return TRUE;
    }
  }

  /**
   * Process the uploaded file
   *
   * @return void
   * @access public
   */
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);

    $currentPath = CRM_Utils_System::currentPath();

    $urlParams = NULL;
    $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', $this);

    if (CRM_Utils_Rule::qfKey($qfKey)) {
      $urlParams = "&qfKey=$qfKey";
    }

    //get the button name
    $buttonName = $this->controller->getButtonName('done');
    $buttonName1 = $this->controller->getButtonName('next');
    if ($buttonName == '_qf_Map_done') {
      $this->updateAndSaveMapping($params);
      $this->set('exportColumnCount', NULL);
      $this->controller->resetPage($this->_name);
      return CRM_Utils_System::redirect(CRM_Utils_System::url($currentPath, 'force=1' . $urlParams));
    }

    if ($this->controller->exportValue($this->_name, 'addMore')) {
      $this->set('exportColumnCount', $this->_exportColumnCount);
      return;
    }

    $mapperKeysOrigin = $params['mapper'][1];
    $mapperWeight = $params['weight'][1];
    for ($i=0; $i < count($mapperWeight); $i++) {
      $mapperKeys[] = $mapperKeysOrigin[array_search($i, $mapperWeight)];
    }

    $checkEmpty = 0;
    foreach ($mapperKeys as $value) {
      if ($value[0]) {
        $checkEmpty++;
      }
    }

    $customSearchID = $this->get('customSearchID');
    if (!$checkEmpty && empty($customSearchID)) {
      $this->set('mappingId', NULL);

      CRM_Utils_System::redirect(CRM_Utils_System::url($currentPath, '_qf_Map_display=true' . $urlParams));
    }

    if ($buttonName1 == '_qf_Map_next') {
      if (!empty($params['saveMapping']) && !empty($params['saveMappingName'])) {
        $params['updateMapping'] = 0;
        $this->updateAndSaveMapping($params);
      }
      elseif (!empty($params['updateMapping']) && !empty($this->get('mappingId'))) {
        $params['mappingId'] = $this->get('mappingId');
        $this->updateAndSaveMapping($params);
      }
    }

    //get the csv file

    $mappingId = $this->get('mappingId');
    $separateMode = $this->get('separateMode');
    $customHeaders = $this->get('customHeader');
    $customSearchID = $this->get('customSearchID');
    if ($customSearchID) {
      $customSearchClass = $this->get('customSearchClass');
      $primaryIDName = '';
      if (property_exists($customSearchClass, '_primaryIDName')) {
        $primaryIDName = $customSearchClass::$_primaryIDName;
      }
      $exportCustomVars = [
        'customSearchClass' => $this->get('customSearchClass'),
        'formValues' => $this->get('formValues'),
        'order' => $this->get(CRM_Utils_Sort::SORT_ORDER),
        'pirmaryIDName' => $primaryIDName,
      ];
      // If select fields is empty, than only export custom search result table.
      $isSelectorEmpty = TRUE;
      foreach ($mapperKeys as $selectors) {
        if (count($selectors) != 1) {
          $isSelectorEmpty = FALSE;
          break;
        }
      }
      if ($isSelectorEmpty) {
        CRM_Export_BAO_Export::exportCustom($this->get('customSearchClass'),
          $this->get('formValues'),
          $this->get(CRM_Utils_Sort::SORT_ORDER), 
          $primaryIDName, 
          TRUE,
          TRUE,
          $this->get('exportMode')
        );
      }
    }
    CRM_Export_BAO_Export::exportComponents($this->get('selectAll'),
      $this->get('componentIds'),
      $this->get('queryParams'),
      $this->get(CRM_Utils_Sort::SORT_ORDER),
      $mapperKeys,
      $this->get('returnProperties'),
      $this->get('exportMode'),
      $this->get('componentClause'),
      $this->get('componentTable'),
      $this->get('mergeSameAddress'),
      $this->get('mergeSameHousehold'),
      $mappingId,
      $separateMode,
      $exportCustomVars
    );
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   * @access public
   */
  public function getTitle() {
    return ts('Select Fields to Export');
  }

  /**
   * Execute when press "Export"( name = _qf_Map_next ) and "Done"( name = _qf_Map_done ) buttom.
   * @param  $params  The parameters in postProcess();
   * @return none
   */
  private function updateAndSaveMapping($params){
    if ( CRM_Utils_Array::value('updateMapping', $params)) {
      //save mapping fields
      CRM_Core_BAO_Mapping::saveMappingFields($params, $params['mappingId'] );
    }

    if ( CRM_Utils_Array::value('saveMapping', $params) ) {
      $mappingParams = [
        'name'      => $params['saveMappingName'],
        'description'   => $params['saveMappingDesc'],
        'mapping_type_id' => $this->get( 'mappingTypeId'),
      ];

      $saveMapping = CRM_Core_BAO_Mapping::add( $mappingParams );
      $this->set('mappingId', $saveMapping->id);

      //save mapping fields
      CRM_Core_BAO_Mapping::saveMappingFields($params, $saveMapping->id);
    }
  }


}

