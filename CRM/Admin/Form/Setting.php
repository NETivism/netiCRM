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
 * This class generates form components generic to CiviCRM settings
 *
 */
class CRM_Admin_Form_Setting extends CRM_Core_Form {

  protected $_defaults;

  /**
   * This function sets the default values for the form.
   * default values are retrieved from the database
   *
   * @access public
   *
   * @return None
   */
   function setDefaultValues() {
    if (!$this->_defaults) {
      $this->_defaults = [];
      $formArray = ['Component', 'Localization'];
      $formMode = FALSE;
      if (in_array($this->_name, $formArray)) {
        $formMode = TRUE;
      }

      CRM_Core_BAO_ConfigSetting::retrieve($this->_defaults);
      CRM_Core_Config_Defaults::setValues($this->_defaults, $formMode);
      $config = CRM_Core_Config::singleton();
      foreach($config as $name => $value) {
        $this->_defaults[$name] = $value;
      }


      $list = array_flip(CRM_Core_OptionGroup::values('contact_autocomplete_options',
          FALSE, FALSE, TRUE, NULL, 'name'
        ));


      $listEnabled = CRM_Core_BAO_Preferences::valueOptions('contact_autocomplete_options');

      $autoSearchFields = [];
      if (!empty($list) && !empty($listEnabled)) {
        $autoSearchFields = array_combine($list, $listEnabled);
      }

      //Set sort_name for default
      $this->_defaults['autocompleteContactSearch'] = ['1' => 1] + $autoSearchFields;
    }
    return $this->_defaults;
  }

  /**
   * Function to actually build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    $this->addButtons([
        ['type' => 'upload',
          'name' => ts('Save'),
          'isDefault' => TRUE,
        ],
        ['type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]
    );
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    // store the submitted values in an array
    $params = $this->controller->exportValues($this->_name);

    self::commonProcess($params);
  }

  public function commonProcess(&$params) {
    $vars = $params;
    CRM_Core_BAO_ConfigSetting::add($vars);

    // also delete the CRM_Core_Config key from the database
    $cache = &CRM_Utils_Cache::singleton();
    $cache->delete('CRM_Core_Config');

    // save autocomplete search options
    if (CRM_Utils_Array::value('autocompleteContactSearch', $params)) {
      $config = new CRM_Core_DAO_Preferences();
      $config->domain_id = CRM_Core_Config::domainID();
      $config->find(TRUE);
      $config->contact_autocomplete_options = CRM_Core_DAO::VALUE_SEPARATOR . CRM_Utils_Array::implode(CRM_Core_DAO::VALUE_SEPARATOR,
        array_keys($params['autocompleteContactSearch'])
      ) . CRM_Core_DAO::VALUE_SEPARATOR;
      $config->save();
    }

    // update time for date formats when global time is changed
    if (CRM_Utils_Array::value('timeInputFormat', $params)) {
      $query = "UPDATE civicrm_preferences_date SET time_format = " . $params['timeInputFormat'] . " 
                      WHERE time_format IS NOT NULL AND time_format <> ''";

      CRM_Core_DAO::executeQuery($query);
    }

    CRM_Core_Session::setStatus(ts('Your changes have been saved.'));
  }

  public function rebuildMenu() {
    // ensure config is set with new values
    $config = CRM_Core_Config::singleton(TRUE, TRUE);

    // rebuild menu items

    CRM_Core_Menu::store();
  }
}

