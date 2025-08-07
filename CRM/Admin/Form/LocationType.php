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
 * This class generates form components for Location Type
 *
 */
class CRM_Admin_Form_LocationType extends CRM_Admin_Form {

  /**
   * This function sets the default values for the form. MobileProvider that in edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return None
   */
  function setDefaultValues() {
    if (isset($this->_id) && empty($this->_values)) {
      $this->_values = [];
      $params = ['id' => $this->_id];
      $baoName = $this->_BAOName;
      $baoName::retrieve( $params, $this->_values );
    }
    $defaults = $this->_values;

    if ($this->_action == CRM_Core_Action::DELETE &&
      isset($defaults['name'])
    ) {
      $this->assign('delName', $defaults['name']);
    }

    // its ok if there is no element called is_active
    $defaults['is_active'] = ($this->_id) ? $defaults['is_active'] : 1;
    if (CRM_Utils_Array::value('parent_id', $defaults)) {
      $this->assign('is_parent', TRUE);
    }
    if (!empty($defaults['name'])) {
      $ele = $this->getElement('name');
      $ele->freeze();
    }

    // refs #37993
    if (CRM_Utils_Array::value('is_default', $defaults)) {
      $ele = $this->getElement('is_default');
      $ele->freeze();
      if (CRM_Utils_Array::value('is_active', $defaults)) {
        $ele = $this->getElement('is_active');
        $ele->freeze();
      }
    }
    return $defaults;
  }

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {

    parent::buildQuickForm();

    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    $this->applyFilter('__ALL__', 'trim');
    $this->add('text', 'label', ts('Label'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_LocationType', 'label'), TRUE);
    $this->addRule('label', ts('Lable already exists in Database.'), 'objectExists',  ['CRM_Core_DAO_LocationType', $this->_id]);
    $this->add('text', 'name', ts('Name'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_LocationType', 'name'), TRUE);
    $this->addRule('name', ts('Name already exists in Database.'), 'objectExists',  ['CRM_Core_DAO_LocationType', $this->_id]);
    $this->addRule('name', ts('Name should only have alpha numeric characters.'), 'alphanumeric',  ['CRM_Core_DAO_LocationType', $this->_id]);


    $this->add('text', 'vcard_name', ts('vCard Name'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_LocationType', 'vcard_name'));

    $this->add('text', 'description', ts('Description'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_LocationType', 'description'));

    $this->add('checkbox', 'is_active', ts('Enabled?'));
    $this->add('checkbox', 'is_default', ts('Default?'));
    if ($this->_action == CRM_Core_Action::UPDATE && CRM_Core_DAO::getFieldValue('CRM_Core_DAO_LocationType', $this->_id, 'is_reserved')) {
      $this->freeze(['name', 'description', 'is_active']);
    }
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Core_BAO_LocationType::del($this->_id);
      CRM_Core_Session::setStatus(ts('Selected Location type has been deleted.'));
      return;
    }

    // store the submitted values in an array
    $params = $this->exportValues();
    $params['is_active'] = CRM_Utils_Array::value('is_active', $params, FALSE);
    $params['is_default'] = CRM_Utils_Array::value('is_default', $params, FALSE);
    if ($params['is_default']) {
      $params['is_active'] = 1;
    }

    // action is taken depending upon the mode
    $locationType = new CRM_Core_DAO_LocationType();
    $locationType->label = $params['label'];
    $locationType->name = $params['name'];
    $locationType->vcard_name = $params['vcard_name'];
    $locationType->description = $params['description'];
    $locationType->is_active = $params['is_active'];
    $locationType->is_default = $params['is_default'];

    if ($params['is_default']) {
      $query = "UPDATE civicrm_location_type SET is_default = 0";
      CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);
    }

    if ($this->_action & CRM_Core_Action::UPDATE) {
      $locationType->id = $this->_id;
    }
    $locationType->save();
    // clear cache
    $cache = &CRM_Utils_Cache::singleton();
    $cache->delete('*CRM_Core_DAO_LocationType*');

    CRM_Core_Session::setStatus(ts('The location type \'%1\' has been saved.',
        [1 => $locationType->name]
      ));
  }
  //end of function
}

