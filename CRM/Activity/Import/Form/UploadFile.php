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
require_once 'CRM/Activity/Import/Parser/Activity.php';

/**
 * This class gets the name of the file to upload
 */
class CRM_Activity_Import_Form_UploadFile extends CRM_Core_Form {

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/import/activity', 'reset=1'));
  }

  /**
   * Function to actually build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    //Setting Upload File Size
    $config = CRM_Core_Config::singleton();
    if ($config->maxImportFileSize >= 8388608) {
      $uploadFileSize = 8388608;
    }
    else {
      $uploadFileSize = $config->maxImportFileSize;
    }
    $uploadSize = round(($uploadFileSize / (1024 * 1024)), 2);

    $this->assign('uploadSize', $uploadSize);

    $this->add('file', 'uploadFile', ts('Import Data File'), 'size=30 maxlength=60', TRUE);

    $this->addRule('uploadFile', ts('A valid file must be uploaded.'), 'uploadedfile');
    $this->addRule('uploadFile', ts('File size should be less than %1 MBytes (%2 bytes)', array(1 => $uploadSize, 2 => $uploadFileSize)), 'maxfilesize', $uploadFileSize);
    $this->setMaxFileSize($uploadFileSize);
    $this->addRule('uploadFile', ts('Input file must be in CSV format'), 'utf8File');

    $this->addElement('checkbox', 'skipColumnHeader', ts('First row contains column headers'));

    $duplicateOptions = array();
    $duplicateOptions[] = $this->createElement('radio',
      NULL, NULL, ts('Skip'), CRM_Activity_Import_Parser::DUPLICATE_SKIP
    );
    $duplicateOptions[] = $this->createElement('radio',
      NULL, NULL, ts('Update'), CRM_Activity_Import_Parser::DUPLICATE_UPDATE
    );
    $duplicateOptions[] = $this->createElement('radio',
      NULL, NULL, ts('Fill'), CRM_Activity_Import_Parser::DUPLICATE_FILL
    );

    $this->addGroup($duplicateOptions, 'onDuplicate',
      ts('On duplicate entries')
    );

    //get the saved mapping details
    require_once "CRM/Core/BAO/Mapping.php";
    require_once "CRM/Core/OptionGroup.php";
    $mappingArray = CRM_Core_BAO_Mapping::getMappings(CRM_Core_OptionGroup::getValue('mapping_type',
        'Import Activity',
        'name'
      ));
    $this->assign('savedMapping', $mappingArray);
    $this->add('select', 'savedMapping', ts('Mapping Option'), array('' => ts('- select -')) + $mappingArray);

    if ($loadeMapping = $this->get('loadedMapping')) {
      $this->assign('loadedMapping', $loadeMapping);
      $this->setDefaults(array('savedMapping' => $loadeMapping));
    }

    $this->setDefaults(array('onDuplicate' =>
        CRM_Activity_Import_Parser::DUPLICATE_SKIP,
      ));

    //build date formats
    require_once 'CRM/Core/Form/Date.php';
    CRM_Core_Form_Date::buildAllowedDateFormats($this);

    $this->addButtons(array(
        array('type' => 'upload',
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
   * Process the uploaded file
   *
   * @return void
   * @access public
   */
  public function postProcess() {
    $this->controller->resetPage('MapField');

    $fileName = $this->controller->exportValue($this->_name, 'uploadFile');
    $skipColumnHeader = $this->controller->exportValue($this->_name, 'skipColumnHeader');
    $onDuplicate = $this->controller->exportValue($this->_name,
      'onDuplicate'
    );
    $dateFormats = $this->controller->exportValue($this->_name, 'dateFormats');
    $savedMapping = $this->controller->exportValue($this->_name, 'savedMapping');

    $this->set('onDuplicate', $onDuplicate);
    $this->set('dateFormats', $dateFormats);
    $this->set('savedMapping', $savedMapping);

    $session = CRM_Core_Session::singleton();
    $session->set("dateTypes", $dateFormats);

    $config = CRM_Core_Config::singleton();
    $seperator = $config->fieldSeparator;

    $mapper = array();

    $parser = new CRM_Activity_Import_Parser_Activity($mapper);
    $parser->setMaxLinesToProcess(100);
    $parser->run($fileName, $seperator,
      $mapper,
      $skipColumnHeader,
      CRM_Activity_Import_Parser::MODE_MAPFIELD
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
    return ts('Upload Data');
  }
}

