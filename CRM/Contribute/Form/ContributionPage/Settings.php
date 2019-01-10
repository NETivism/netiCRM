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

require_once 'CRM/Contribute/Form/ContributionPage.php';
require_once 'CRM/Contribute/PseudoConstant.php';
class CRM_Contribute_Form_ContributionPage_Settings extends CRM_Contribute_Form_ContributionPage {

  protected $_contributionType = NULL;

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    //custom data related code
    $this->_cdType = CRM_Utils_Array::value('type', $_GET);
    $this->assign('cdType', FALSE);
    if ($this->_cdType) {
      $this->assign('cdType', TRUE);
      return CRM_Custom_Form_CustomData::preProcess($this);
    }

    parent::preProcess();

    // custom data need entityID for template
    if ($this->_id) {
      $this->assign('entityID', $this->_id);
    }

    // when custom data is included in this page
    if (CRM_Utils_Array::value("hidden_custom", $_POST)) {
      $this->set('type', 'ContributionPage');
      $this->set('subType', CRM_Utils_Array::value('contribution_type_id', $_POST));
      $this->set('entityId', $this->_id);

      CRM_Custom_Form_CustomData::preProcess($this);
      CRM_Custom_Form_CustomData::buildQuickForm($this);
      CRM_Custom_Form_CustomData::setDefaultValues($this);
    }
  }

  /**
   * This function sets the default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return void
   */
  function setDefaultValues() {
    // custom data related
    if ($this->_cdType) {
      return CRM_Custom_Form_CustomData::setDefaultValues($this);
    }

    if ($this->_id) {
      $title = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionPage',
        $this->_id,
        'title'
      );
      CRM_Utils_System::setTitle(ts('Title and Settings (%1)',
          array(1 => $title)
        ));
    }
    else {
      CRM_Utils_System::setTitle(ts('Title and Settings'));
    }
    $defaults = parent::setDefaultValues();

    if(!empty($this->_id)){
      $is_active = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionPage',
        $this->_id,
        'is_active'
      );
      $defaults['is_special'] = $is_active & CRM_Contribute_BAO_ContributionPage::IS_SPECIAL;

      $background_URL = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionPage',
        $this->_id,
        'background_URL'
      );
      $this->assign('background_URL', $background_URL);
      $mobile_background_URL = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionPage',
        $this->_id,
        'mobile_background_URL'
      );
      $this->assign('mobile_background_URL', $mobile_background_URL);
      $defaults['deleteBackgroundImage'] = '';
      $defaults['deleteMobileBackgroundImage'] = '';
    }

    // in update mode, we need to set custom data subtype to tpl
    if (CRM_Utils_Array::value('contribution_type_id', $defaults)) {
      $this->assign('customDataSubType', $defaults["contribution_type_id"]);
    }
    return $defaults;
  }

  /**
   * Function to actually build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    // custom data related
    if ($this->_cdType) {
      return CRM_Custom_Form_CustomData::buildQuickForm($this);
    }
    //need to assign custom data type and subtype to the template
    $this->assign('customDataType', 'ContributionPage');

    require_once 'CRM/Utils/Money.php';

    $this->_first = TRUE;
    $attributes = CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_ContributionPage');

    // name
    $this->add('text', 'title', ts('Title'), $attributes['title'], TRUE);

    $this->add('select', 'contribution_type_id',
      ts('Contribution Type'),
      CRM_Contribute_PseudoConstant::contributionType(NULL, FALSE, TRUE),
      TRUE,
      array('onChange' => "buildCustomData( 'ContributionPage', this.value );")
    );

    $this->addWysiwyg('intro_text', ts('Introductory Message'), $attributes['intro_text']);

    $this->addWysiwyg('footer_text', ts('Footer Message'), $attributes['footer_text']);

    // is on behalf of an organization ?
    $this->addElement('checkbox', 'is_organization', ts('Allow individuals to contribute and / or signup for membership on behalf of an organization?'), NULL, array('onclick' => "showHideByValue('is_organization',true,'for_org_text','table-row','radio',false);showHideByValue('is_organization',true,'for_org_option','table-row','radio',false);"));
    $options = array();
    $options[] = HTML_QuickForm::createElement('radio', NULL, NULL, ts('Optional'), 1);
    $options[] = HTML_QuickForm::createElement('radio', NULL, NULL, ts('Required'), 2);
    $this->addGroup($options, 'is_for_organization', ts(''));
    $this->add('textarea', 'for_organization', ts('On behalf of Label'), $attributes['for_organization']);

    // collect goal amount
    $this->addElement('checkbox', 'display_progress_bar', ts('Progress Bar'), NULL, array('onclick' => "showHideByValue('display_progress_bar',true,'goal_amount_row','table-row','radio',false);"));
    $this->add('number', 'goal_amount', ts('Goal Amount'), array('min' => 0));
    $this->add('number', 'goal_recurring', ts('Goal Subscription'), array('min' => 0));
    $this->addRule('goal_amount', ts('Please enter a valid money value (e.g. %1).', array(1 => CRM_Utils_Money::format('99.99', ' '))), 'money');

    // is this page active ?
    $this->addElement('checkbox', 'is_active', ts('Is this Online Contribution Page Active?'), NULL, array('onclick' => "showSpecial()"));

    $this->addElement('checkbox', 'is_special', ts('Is this Online Contribution Page in the Special Style?'), NULL, array('onclick' => "showSpecial()"));

    // should the honor be enabled
    $this->addElement('checkbox', 'honor_block_is_active', ts('Honoree Section Enabled'), NULL, array('onclick' => "showHonor()"));

    $this->add('text', 'honor_block_title', ts('Honoree Section Title'), $attributes['honor_block_title']);

    $this->add('textarea', 'honor_block_text', ts('Honoree Introductory Message'), $attributes['honor_block_text']);

    // add optional start and end dates
    $this->addDateTime('start_date', ts('Start Date'));
    $this->addDateTime('end_date', ts('End Date'));

    $this->addFormRule(array('CRM_Contribute_Form_ContributionPage_Settings', 'formRule'));


    $this->add('file', 'uploadBackgroundImage', ts('Background image'));
    $this->add('file', 'uploadMobileBackgroundImage', ts('Background image of mobile'));
    $config = CRM_Core_Config::singleton();
    $this->controller->addActions($config->imageUploadDir, array('uploadBackgroundImage', 'uploadMobileBackgroundImage'));

    $this->add('hidden', 'deleteBackgroundImage');
    $this->add('hidden', 'deleteMobileBackgroundImage');

    parent::buildQuickForm();
  }

  /**
   * global validation rules for the form
   *
   * @param array $values posted values of the form
   *
   * @return array list of errors to be posted back to the form
   * @static
   * @access public
   */
  static function formRule($values) {
    $errors = array();

    //CRM-4286
    if (strstr($values['title'], '/')) {
      $errors['title'] = ts("Please do not use '/' in Title");
    }

    return $errors;
  }

  /**
   * Process the form
   *
   * @return void
   * @access public
   */
  public function postProcess() {
    // get the submitted form values.
    $params = $this->controller->exportValues($this->_name);

    // we do this in case the user has hit the forward/back button
    if ($this->_id) {
      $params['id'] = $this->_id;
    }
    else {
      $session = CRM_Core_Session::singleton();
      $params['created_id'] = $session->get('userID');
      $params['created_date'] = date('YmdHis');
      $config = CRM_Core_Config::singleton();
      $params['currency'] = $config->defaultCurrency;
    }

    $params['is_active'] = CRM_Utils_Array::value('is_active', $params, FALSE);
    if($params['is_active'] && $params['is_special']){
      $params['is_active'] = 3;
    }
    $params['is_credit_card_only'] = CRM_Utils_Array::value('is_credit_card_only', $params, FALSE);
    $params['honor_block_is_active'] = CRM_Utils_Array::value('honor_block_is_active', $params, FALSE);
    $params['is_for_organization'] = CRM_Utils_Array::value('is_organization', $params) ? CRM_Utils_Array::value('is_for_organization', $params, FALSE) : 0;

    $params['start_date'] = CRM_Utils_Date::processDate($params['start_date'], $params['start_date_time'], TRUE);
    $params['end_date'] = CRM_Utils_Date::processDate($params['end_date'], $params['end_date_time'], TRUE);

    if ($params['display_progress_bar']) {
      $params['goal_amount'] = CRM_Utils_Rule::cleanMoney($params['goal_amount']);
    }
    else {
      $params['goal_amount'] = 'null';
      $params['goal_recurring'] = 'null';
    }


    if (!$params['honor_block_is_active']) {
      $params['honor_block_title'] = NULL;
      $params['honor_block_text'] = NULL;
    }

    require_once 'CRM/Contribute/BAO/ContributionPage.php';
    $customFields = CRM_Core_BAO_CustomField::getFields('ContributionPage', FALSE, FALSE, CRM_Utils_Array::value('contribution_type_id', $params));
    $params['custom'] = CRM_Core_BAO_CustomField::postProcess($params, $customFields, $this->_id, 'ContributionPage');

    $config = CRM_Core_Config::singleton();

    $deleteBackgroundImage = CRM_Utils_Array::value('deleteBackgroundImage', $params);
    $deleteMobileBackgroundImage = CRM_Utils_Array::value('deleteMobileBackgroundImage', $params);
    unset($params['deleteBackgroundImage']);
    unset($params['deleteMobileBackgroundImage']);

    if(!empty($deleteBackgroundImage)){
      $params['background_URL'] = '';
    }
    if(!empty($deleteMobileBackgroundImage)){
      $params['mobile_background_URL'] = '';
    }

    $uploadBackgroundImage = CRM_Utils_Array::value('uploadBackgroundImage', $params);
    if(!empty($uploadBackgroundImage)){
      $newBackgroundImage = $this->compressImage($uploadBackgroundImage['name'], 1920, 50);
      $params['background_URL'] = str_replace($config->imageUploadDir, $config->imageUploadURL, $newBackgroundImage);
    }

    $uploadMobileBackgroundImage = CRM_Utils_Array::value('uploadMobileBackgroundImage', $params);
    if(!empty($uploadMobileBackgroundImage)){
      $newBackgroundImage = $this->compressImage($uploadMobileBackgroundImage['name'], 480, 100);
      $params['mobile_background_URL'] = str_replace($config->imageUploadDir, $config->imageUploadURL, $newBackgroundImage);
    }


    $dao = &CRM_Contribute_BAO_ContributionPage::create($params);

    $this->set('id', $dao->id);

    parent::postProcess();
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   * @access public
   */
  public function getTitle() {
    return ts('Title and Settings');
  }

  private function compressImage($filename, $width, $quality) {
    // to check wether GD is installed or not
    $gdSupport = CRM_Utils_System::getModuleSetting('gd', 'GD Support');
    if($gdSupport) {
      $pathParts = pathinfo($filename);
      $newFilename = $pathParts['dirname']."/".$pathParts['filename']."_compressed.".$pathParts['extension'];
      $imageInfo = getimagesize($filename);
      $widthOrig = $imageInfo[0];
      $heightOrig = $imageInfo[1];

      if($imageInfo['mime'] == 'image/gif') {
        $source = imagecreatefromgif($filename);
      }
      elseif($imageInfo['mime'] == 'image/png') {
        $source = imagecreatefrompng($filename);
      }
      else {
        $source = imagecreatefromjpeg($filename);
      }

      if($widthOrig > $width){
        $widthNew = $width;
        $heightNew = $heightOrig * $widthNew / $widthOrig;
        $image = imagecreatetruecolor($widthNew, $heightNew);
        imagecopyresampled($image, $source, 0, 0, 0, 0, $widthNew, $heightNew, $widthOrig, $heightOrig);
      }else{
        $image = $source;
      }

      imagejpeg($image, $newFilename, $quality);
      return $newFilename;

    }else{
      return false;
    }
  }
}

