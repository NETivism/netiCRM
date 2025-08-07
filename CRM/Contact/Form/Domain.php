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
 * This class is to build the form for adding Group
 */
class CRM_Contact_Form_Domain extends CRM_Core_Form {

  /**
   * the group id, used when editing a group
   *
   * @var int
   */
  protected $_id;

  /**
   * default from email address option value id.
   *
   * @var int
   */
  protected $_fromEmailId = NULL;

  /**
   * how many locationBlocks should we display?
   *
   * @var int
   * @const
   */
  CONST LOCATION_BLOCKS = 1; function preProcess() {

    CRM_Utils_System::setTitle(ts('Domain Information'));
    $breadCrumbPath = CRM_Utils_System::url('civicrm/admin', 'reset=1');
    CRM_Utils_System::appendBreadCrumb(ts('Administer CiviCRM'), $breadCrumbPath);

    $this->_id = CRM_Core_Config::domainID();
    $this->_action = CRM_Utils_Request::retrieve('action', 'String',
      $this, FALSE, 'view'
    );
    //location blocks.
    CRM_Contact_Form_Location::preProcess($this);
  }

  /*
     * This function sets the default values for the form.
     * the default values are retrieved from the database
     *
     * @access public
     * @return None
     */
  function setDefaultValues() {



    $defaults = [];
    $params = [];
    $locParams = [];

    if (isset($this->_id)) {
      $params['id'] = $this->_id;
      CRM_Core_BAO_Domain::retrieve($params, $domainDefaults);

      //get the default domain from email address. fix CRM-3552


      $optionValues = [];
      $grpParams['name'] = 'from_email_address';
      CRM_Core_OptionValue::getValues($grpParams, $optionValues);
      foreach ($optionValues as $Id => $value) {
        if ($value['is_default'] && $value['is_active']) {
          $this->_fromEmailId = $Id;
          $domainDefaults['email_name'] = CRM_Utils_Array::value(1, explode('"', $value['label']));
          $domainDefaults['email_address'] = CRM_Utils_Mail::pluckEmailFromHeader($value['label']);
          break;
        }
      }

      unset($params['id']);
      $locParams = $params + ['entity_id' => $this->_id, 'entity_table' => 'civicrm_domain'];

      $defaults = CRM_Core_BAO_Location::getValues($locParams);

      $config = CRM_Core_Config::singleton();
      if (!isset($defaults['address'][1]['country_id'])) {
        $defaults['address'][1]['country_id'] = $config->defaultContactCountry;
      }

      if (!empty($defaults['address'])) {
        foreach ($defaults['address'] as $key => $value) {
          CRM_Contact_Form_Edit_Address::fixStateSelect($this,
            "address[$key][country_id]",
            "address[$key][state_province_id]",
            CRM_Utils_Array::value('country_id', $value,
              $config->defaultContactCountry
            )
          );
        }
      }
    }
    $defaults = array_merge($defaults, $domainDefaults);
    return $defaults;
  }

  /**
   * Function to actually build the form
   *
   * @return None
   * @access public
   */

  public function buildQuickForm() {

    $this->add('text', 'name', ts('Domain Name'), ['size' => 25], TRUE);
    $this->add('text', 'description', ts('Description'), ['size' => 25]);

    $eleName = $this->add('text', 'email_name', ts('FROM Name'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_Email', 'email'), TRUE);
    $eleName->freeze();
    $eleAddr = $this->add('text', 'email_address', ts('FROM Email Address'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_Email', 'email'), TRUE);
    $eleAddr->freeze();

    $this->addRule("email_address", ts('Domain Email Address must use a valid email address format (e.g. \'info@example.org\').'), 'email');

    //build location blocks.
    CRM_Contact_Form_Location::buildQuickForm($this);

    $this->addButtons([
        ['type' => 'next',
          'name' => ts('Save'),
          'subName' => 'view',
          'isDefault' => TRUE,
        ],
        ['type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]);

    if ($this->_action & CRM_Core_Action::VIEW) {
      $this->freeze();
    }
    $this->assign('emailDomain', TRUE);
  }

  /**
   * Add local and global form rules
   *
   * @access protected
   *
   * @return void
   */
  function addRules() {
    $this->addFormRule(['CRM_Contact_Form_Domain', 'formRule']);
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
    $errors = [];
    // check for state/country mapping
    CRM_Contact_Form_Edit_Address::formRule($fields, $errors);

    //fix for CRM-3552,
    //as we use "fromName"<emailaddresss> format for domain email.
    if (strpos($fields['email_name'], '"') !== FALSE) {
      $errors['email_name'] = ts('Double quotes are not allow in from name.');
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Process the form when submitted
   *
   * @return void
   * @access public
   */

  public function postProcess() {



    $params = [];

    $params = $this->exportValues();
    $params['entity_id'] = $this->_id;
    $params['entity_table'] = CRM_Core_BAO_Domain::getTableName();
    $domain = CRM_Core_BAO_Domain::edit($params, $this->_id);


    $defaultLocationType = &CRM_Core_BAO_LocationType::getDefault();

    $location = [];
    $params['address'][1]['location_type_id'] = $defaultLocationType->id;
    $params['phone'][1]['location_type_id'] = $defaultLocationType->id;
    $params['email'][1]['location_type_id'] = $defaultLocationType->id;

    $location = CRM_Core_BAO_Location::create($params, TRUE, 'domain');

    $params['loc_block_id'] = $location['id'];


    CRM_Core_BAO_Domain::edit($params, $this->_id);

    CRM_Core_Session::setStatus(ts('Domain information for \'%1\' has been saved.', [1 => $domain->name]));
    $session = CRM_Core_Session::singleton();
    $session->replaceUserContext(CRM_Utils_System::url('civicrm/admin', 'reset=1'));
  }
}

