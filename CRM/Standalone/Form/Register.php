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


class CRM_Standalone_Form_Register extends CRM_Core_Form {

  protected $_profileID;

  protected $_fields = [];

  protected $_openID;
  
  function preProcess() {
    $config = CRM_Core_Config::singleton();
    if ($config->userFramework !== "Standalone") {
      CRM_Utils_System::redirect();
    }
    // pick the first profile ID that has user register checked

    $ufGroups = &CRM_Core_BAO_UFGroup::getModuleUFGroup('User Registration');

    if (count($ufGroups) > 1) {
       return CRM_Core_Error::statusBounce(ts('You have more than one profile that has been enabled for user registration.'));
    }

    foreach ($ufGroups as $id => $dontCare) {
      $this->_profileID = $id;
    }


    $session = CRM_Core_Session::singleton();
    $this->_openID = $session->get('openid');
  }

  function setDefaultValues() {
    $defaults = [];

    $defaults['user_unique_id'] = $this->_openID;

    return $defaults;
  }

  function buildQuickForm() {
    $this->add('text',
      'user_unique_id',
      ts('OpenID'),
      CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'user_unique_id'),
      TRUE
    );

    $this->add('text',
      'email',
      ts('Email'),
      CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'email'),
      TRUE
    );

    $fields = CRM_Core_BAO_UFGroup::getFields($this->_profileID,
      FALSE,
      CRM_Core_Action::ADD,
      NULL, NULL, FALSE,
      NULL, TRUE
    );
    $this->assign('custom', $fields);


    foreach ($fields as $key => $field) {
      CRM_Core_BAO_UFGroup::buildProfile($this,
        $field,
        CRM_Profile_Form::MODE_CREATE
      );
      $this->_fields[$key] = $field;
    }

    $this->addButtons([
        ['type' => 'next',
          'name' => ts('Save'),
          'isDefault' => TRUE,
        ],
        ['type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]
    );
  }

  function postProcess() {
    $formValues = $this->controller->exportValues($this->_name);





    $user = new CRM_Standalone_User($formValues['user_unique_id'],
      $formValues['email'],
      $formValues['first_name'],
      $formValues['last_name']
    );
    CRM_Utils_System_Standalone::getUserID($user);


    $session = CRM_Core_Session::singleton();
    $contactId = $session->get('userID');

    $query = "SELECT count(id) FROM civicrm_uf_match";
    $ufCount = CRM_Core_DAO::singleValueQuery($query);

    if (($ufCount == 1) || defined('ALLOWED_TO_LOGIN')) {
      $openId = new CRM_Core_DAO_OpenID();
      $openId->contact_id = $contactId;
      $openId->find(TRUE);
      $openId->allowed_to_login = 1;
      $openId->update();
    }

    // add first user to admin group
    if ($ufCount == 1) {


      $group = new CRM_Contact_DAO_Group();
      $group->name = 'Administrators';
      $group->is_active = 1;
      if ($group->find(TRUE)) {
        $contactIds = [$contactId];
        CRM_Contact_BAO_GroupContact::addContactsToGroup($contactIds, $group->id,
          'Web', 'Added'
        );
      }
    }
    elseif ($ufCount > 1 && !defined('CIVICRM_ALLOW_ALL')) {
      $session->set('msg', 'You are not allowed to login. Login failed. Contact your Administrator.');
      $session->set('goahead', "no");
    }

    // Set this to false if the registration is successful
    $session->set('new_install', FALSE);

    header("Location: index.php");
    CRM_Utils_System::civiExit();
  }
}

