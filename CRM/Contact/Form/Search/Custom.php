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


class CRM_Contact_Form_Search_Custom extends CRM_Contact_Form_Search {

  public $selector;
  public $_elementIndex;
  protected $_customClass = NULL;

  public function preProcess() {
    $this->set('searchFormName', 'Custom');

    $this->set('context', 'custom');


    $csID = CRM_Utils_Request::retrieve('csid', 'Integer', $this);
    $ssID = CRM_Utils_Request::retrieve('ssID', 'Integer', $this);
    $gID = CRM_Utils_Request::retrieve('gid', 'Integer', $this);
    $force = CRM_Utils_Request::retrieve('force', 'Boolean', CRM_Core_DAO::$_nullObject);

    list($this->_customSearchID, $this->_customSearchClass, $formValues) = CRM_Contact_BAO_SearchCustom::details($csID, $ssID, $gID);

    if (!$this->_customSearchID) {
      CRM_Core_Error::fatal('Could not get details for custom search.');
    }

    if (!empty($formValues)) {
      $this->_formValues = $formValues;
    }

    // use the custom selector

    $this->_selectorName = 'CRM_Contact_Selector_Custom';

    $this->set('customSearchID', $this->_customSearchID);
    $this->set('customSearchClass', $this->_customSearchClass);

    // purge group related contact cache
    if ($gID && $ssID && $force) {
      CRM_Contact_BAO_GroupContactCache::check($gID);
    }
    parent::preProcess();

    if (!empty($this->selector->_search)) {
      $this->_customClass =& $this->selector->_search;
    }
    else {
      $objectName = $this->_customSearchClass;
      $this->_customClass = new $objectName($this->_formValues);
    }

    if (method_exists($this->_customClass, 'prepareForm')) {
      $this->_customClass->prepareForm($this);
    }
    if (method_exists($this->_customClass, 'setTitle')) {
      $this->_customClass->setTitle();
    }
    else {
      $titles = CRM_Core_OptionGroup::values('custom_search');
      if(!empty($titles[$this->_customSearchID])){
        $this->setTitle($titles[$this->_customSearchID]);
      }
    }

    if (method_exists($this->_customClass, 'setBreadcrumb')) {
      $this->_customClass->setBreadcrumb();
    }
  }

  function setDefaultValues() {
    $formValues = $this->_formValues;
    unset($formValues['component_mode']);
    unset($formValues['qfKey']);
    unset($formValues['uf_group_id']);
    if (empty($formValues) && isset($this->selector->_search) && method_exists($this->selector->_search, 'setDefaultValues')) {
      $defaults = $this->selector->_search->setDefaultValues($this);
      return $defaults;
    }
    else {
      return $this->_formValues;
    }
  }

  function buildQuickForm() {
    if (method_exists($this->_customClass, 'buildForm')) {
      $this->_customClass->buildForm($this);
    }
    parent::buildQuickForm();

    // add additional tasks when custom search
    if (method_exists($this->_customClass, 'tasks') && !empty($this->_elementIndex['task'])) {
      $tasks = $this->_customClass->tasks();
      // re-build tasks drop down select
      CRM_Contact_Task::initTasks($tasks);
      $permission = CRM_Core_Permission::getPermission();
      $tasks = ['' => ts('- actions -')];
      $tasks += CRM_Contact_Task::permissionedTaskTitles($permission);
      $this->removeElement('task');
      $this->add('select', 'task', ts('Actions:') . ' ', $tasks);
    }
  }

  function getTemplateFileName() {


    $ext = new CRM_Core_Extensions();

    if ($ext->isExtensionClass(CRM_Utils_System::getClassName($this->_customClass))) {
      $filename = $ext->getTemplatePath(CRM_Utils_System::getClassName($this->_customClass));
    }
    else {
      $fileName = $this->_customClass->templateFile();
    }

    return $fileName ? $fileName : parent::getTemplateFileName();
  }

  function postProcess() {
    $this->set('isAdvanced', '3');
    $this->set('isCustom', '1');

    // get user submitted values
    // get it from controller only if form has been submitted, else preProcess has set this
    if (!empty($_POST)) {
      $this->_formValues = $this->controller->exportValues($this->_name);

      $this->_formValues['customSearchID'] = $this->_customSearchID;
      $this->_formValues['customSearchClass'] = $this->_customSearchClass;

      if (method_exists($this->_customClass, 'postCustomSearchProcess')) {
        $this->_customClass->postCustomSearchProcess($this);
        $this->_selectorName = 'CRM_Contact_Selector_Custom';
        parent::postProcess();
        return;
      }
    }

    //use the custom selector
    $this->_selectorName = 'CRM_Contact_Selector_Custom';

    parent::postProcess();
  }

  function setTitle($title){
    if ($title) {
      CRM_Utils_System::setTitle($title);
    }
    else {
      CRM_Utils_System::setTitle(ts('Custom Search'));
    }
  }

}

