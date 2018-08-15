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
 *  this file contains functions for synchronizing cms users with CiviCRM contacts
 */

require_once 'DB.php';
class CRM_Core_BAO_CMSUser {

  /**
   * Function for synchronizing cms users with CiviCRM contacts
   *
   * @param NULL
   *
   * @return void
   *
   * @static
   * @access public
   */
  static function synchronize() {
    //start of schronization code
    $config = CRM_Core_Config::singleton();

    CRM_Core_Error::ignoreException();
    $db_uf = &self::dbHandle($config);

    if ($config->userFramework == 'Drupal') {
      $id = 'uid';
      $mail = 'mail';
      $name = 'name';
    }
    elseif ($config->userFramework == 'Joomla') {
      $id = 'id';
      $mail = 'email';
      $name = 'name';
    }
    else {
      CRM_Core_Error::fatal("CMS user creation not supported for this framework");
    }

    set_time_limit(300);

    $sql = "SELECT $id, $mail, $name FROM {$config->userFrameworkUsersTableName} where $mail != ''";
    $query = $db_uf->query($sql);

    $user = new StdClass();
    $uf = $config->userFramework;
    $contactCount = 0;
    $contactCreated = 0;
    $contactMatching = 0;
    while ($row = $query->fetchRow(DB_FETCHMODE_ASSOC)) {
      $user->$id = $row[$id];
      $user->$mail = $row[$mail];
      $user->$name = $row[$name];
      $contactCount++;
      if ($match = CRM_Core_BAO_UFMatch::synchronizeUFMatch($user, $row[$id], $row[$mail], $uf, 1, NULL, TRUE)) {
        $contactCreated++;
      }
      else {
        $contactMatching++;
      }
      if (is_object($match)) {
        $match->free();
      }
    }

    $db_uf->disconnect();

    //end of schronization code
    $status = ts('Synchronize Users to Contacts completed.');
    $status .= ' ' . ts('Checked one user record.', array('count' => $contactCount, 'plural' => 'Checked %count user records.'));
    if ($contactMatching) {
      $status .= ' ' . ts('Found one matching contact record.', array('count' => $contactMatching, 'plural' => 'Found %count matching contact records.'));
    }
    $status .= ' ' . ts('Created one new contact record.', array('count' => $contactCreated, 'plural' => 'Created %count new contact records.'));
    CRM_Core_Session::setStatus($status, TRUE);
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin', 'reset=1'));
  }

  /**
   * Function to create CMS user using Profile
   *
   * @param array  $params associated array
   * @param string $mail email id for cms user
   *
   * @return int contact id that has been created
   * @access public
   * @static
   */
  static function create(&$params, $mail) {
    $config = CRM_Core_Config::singleton();

    $ufID = $config->userSystem->createUser($params, $mail);

    //if contact doesn't already exist create UF Match
    if ($ufID !== FALSE && isset($params['contactID'])) {
      // create the UF Match record
      $ufmatch             = new CRM_Core_DAO_UFMatch();
      $ufmatch->domain_id  = CRM_Core_Config::domainID();
      $ufmatch->uf_id      = $ufID;
      $ufmatch->contact_id = $params['contactID'];
      $ufmatch->uf_name    = $params[$mail];

      if (!$ufmatch->find(TRUE)) {
        $ufmatch->save();
      }
    }

    return $ufID;
  }

  /**
   * Function to create Form for CMS user using Profile
   *
   * @param object  $form
   * @param integer $gid id of group of profile
   * @param string $emailPresent true, if the profile field has email(primary)
   *
   * @access public
   * @static
   */
  static function buildForm(&$form, $gid, $emailPresent, $action = CRM_Core_Action::NONE) {
    $config = CRM_Core_Config::singleton();
    $showCMS = FALSE;

    $isDrupal = ucfirst($config->userFramework) == 'Drupal' ? TRUE : FALSE;
    $isJoomla = ucfirst($config->userFramework) == 'Joomla' ? TRUE : FALSE;
    //if CMS is configured for not to allow creating new CMS user,
    //don't build the form,Fixed for CRM-4036
    if ($isJoomla) {
      $userParams = &JComponentHelper::getParams('com_users');
      if (!$userParams->get('allowUserRegistration')) {
        return FALSE;
      }
    }
    elseif ($isDrupal && !variable_get('user_register', TRUE)) {
      return FALSE;
    }
    // if cms is drupal having version greater than equal to 5.1
    // we also need email verification enabled, else we dont do it
    // then showCMS will true
    if ($isDrupal OR $isJoomla) {
      if ($gid) {
        $isCMSUser = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $gid, 'is_cms_user');
      }
      // $cms is true when there is email(primary location) is set in the profile field.
      $session = CRM_Core_Session::singleton();
      $userID = $session->get('userID');
      $showUserRegistration = FALSE;
      if ($action) {
        $showUserRegistration = TRUE;
      }
      elseif (!$action && !$userID) {
        $showUserRegistration = TRUE;
      }

      if ($isCMSUser && $emailPresent) {
        if ($showUserRegistration) {
          if ($isCMSUser != 2) {
            $extra = array(
              'onclick' => "return showHideByValue('cms_create_account','','details','block','radio',false );",
            );
            $form->addElement('checkbox', 'cms_create_account', ts('Create an account?'), NULL, $extra);
            $required = FALSE;
          }
          else {
            $form->add('hidden', 'cms_create_account', 1);
            $required = TRUE;
          }

          $form->assign('isCMS', $required);
          require_once 'CRM/Core/Action.php';
          if (!$userID || $action & CRM_Core_Action::PREVIEW || $action & CRM_Core_Action::PROFILE) {
            $form->add('text', 'cms_name', ts('Username'), NULL, $required);
            if (($isDrupal && !variable_get('user_email_verification', TRUE)) OR ($isJoomla)) {
              $form->add('password', 'cms_pass', ts('Password'));
              $form->add('password', 'cms_confirm_pass', ts('Confirm Password'));
            }

            $form->addFormRule(array('CRM_Core_BAO_CMSUser', 'formRule'), $form);
          }
          $showCMS = TRUE;
        }
      }
    }

    $loginUrl = $config->userFrameworkBaseURL;
    if ($isJoomla) {
      $loginUrl = str_replace('administrator/', '', $loginUrl);
      $loginUrl .= 'index.php?option=com_user&view=login';
    }
    elseif ($isDrupal) {
      $loginUrl .= 'user';
      // For Drupal we can redirect user to current page after login by passing it as destination.
      require_once 'CRM/Utils/System.php';
      $args = array('reset' => 1);

      $id = $form->get('id');
      if ($id) {
        $args['id'] = $id;
      }
      else {
        $gid = $form->get('gid');
        if ($gid) {
          $args['gid'] = $gid;
        }
        else {
          // Setup Personal Campaign Page link uses pageId
          $pageId = $form->get('pageId');
          if ($pageId) {
            $args['pageId'] = $pageId;
            $args['action'] = 'add';
          }
        }
      }
      foreach ($_GET as $k => $v) {
        if (!isset($args[$k]) && !empty($v) && $k != 'q') {
          $args[$k] = $v;
        }
      }

      if (!empty($args)) {
        // append destination so user is returned to form they came from after login
        $destination = CRM_Utils_System::currentPath() . "?" . http_build_query($args, '', '&');;
        $loginUrl .= '?destination=' . urlencode($destination);
      }
    }
    $form->assign('loginUrl', $loginUrl);
    $form->assign('showCMS', $showCMS);
  }

  static function formRule($fields, $files, $self) {
    if (!CRM_Utils_Array::value('cms_create_account', $fields)) {
      return TRUE;
    }

    $config = CRM_Core_Config::singleton();

    $isDrupal = ucfirst($config->userFramework) == 'Drupal' ? TRUE : FALSE;
    $isJoomla = ucfirst($config->userFramework) == 'Joomla' ? TRUE : FALSE;

    $errors = array();
    if ($isDrupal || $isJoomla) {
      $emailName = NULL;
      if (!empty($self->_bltID)) {
        // this is a transaction related page
        $emailName = 'email-' . $self->_bltID;
      }
      else {
        // find the email field in a profile page
        foreach ($fields as $name => $dontCare) {
          if (substr($name, 0, 5) == 'email') {
            $emailName = $name;
            break;
          }
        }
      }

      if ($emailName == NULL) {
        $errors['_qf_default'] == ts('Could not find an email address.');
        return $errors;
      }

      if (empty($fields['cms_name'])) {
        $errors['cms_name'] = ts('Please specify a username.');
      }

      if (empty($fields[$emailName])) {
        $errors[$emailName] = ts('Please specify a valid email address.');
      }

      if (($isDrupal && !variable_get('user_email_verification', TRUE)) OR ($isJoomla)) {
        if (empty($fields['cms_pass']) ||
          empty($fields['cms_confirm_pass'])
        ) {
          $errors['cms_pass'] = ts('Please enter a password.');
        }
        if ($fields['cms_pass'] != $fields['cms_confirm_pass']) {
          $errors['cms_pass'] = ts('Password and Confirm Password values are not the same.');
        }
      }

      if (!empty($errors)) {
        return $errors;
      }

      // now check that the cms db does not have the user name and/or email
      if ($isDrupal OR $isJoomla) {
        $params = array('name' => $fields['cms_name'],
          'mail' => $fields[$emailName],
        );
      }

      $errors = $config->userSystem->checkUserNameEmailExists($params, $emailName);
    }
    return (!empty($errors)) ? $errors : TRUE;
  }

  /**
   * Function to check if a cms user already exists.
   *
   * @param  Array $contact array of contact-details
   *
   * @return uid if user exists, false otherwise
   *
   * @access public
   * @static
   */
  static function userExists(&$contact) {
    $config = CRM_Core_Config::singleton();

    $isDrupal = ucfirst($config->userFramework) == 'Drupal' ? TRUE : FALSE;
    $isJoomla = ucfirst($config->userFramework) == 'Joomla' ? TRUE : FALSE;

    $db_uf = DB::connect($config->userFrameworkDSN);

    if (DB::isError($db_uf)) {
      die("Cannot connect to UF db via $dsn, " . $db_uf->getMessage());
    }

    if (!$isDrupal && !$isJoomla) {
      die("Unknown user framework");
    }

    if ($isDrupal) {
      $id = 'uid';
      $mail = 'mail';
    }
    elseif ($isJoomla) {
      $id = 'id';
      $mail = 'email';
    }

    $sql = "SELECT $id FROM {$config->userFrameworkUsersTableName} where $mail='" . $contact['email'] . "'";

    $query = $db_uf->query($sql);

    if ($row = $query->fetchRow(DB_FETCHMODE_ASSOC)) {
      $contact['user_exists'] = TRUE;
      if ($isDrupal) {
        $result = $row['uid'];
      }
      elseif ($isJoomla) {
        $result = $row['id'];
      }
    }
    else {
      $result = FALSE;
    }

    $db_uf->disconnect();
    return $result;
  }

  /**
   * Function to create a user in Drupal.
   *
   * @param array  $params associated array
   * @param string $mail email id for cms user
   *
   * @return uid if user exists, false otherwise
   *
   * @access public
   * @static
   */
  static function createDrupalUser(&$params, $mail) {
    $values['values'] = array(
      'name' => $params['cms_name'],
      'mail' => $params[$mail],
      'op' => 'Create new account',
    );
    if (!variable_get('user_email_verification', TRUE)) {
      $values['values']['pass']['pass1'] = $params['cms_pass'];
      $values['values']['pass']['pass2'] = $params['cms_pass'];
    }
    $values['values']['civicrm_register'] = TRUE;

    $config = CRM_Core_Config::singleton();

    // we also need to redirect b
    $config->inCiviCRM = TRUE;

    $res = drupal_execute('user_register', $values);

    $config->inCiviCRM = FALSE;

    if (form_get_errors()) {
      return FALSE;
    }

    //Fetch id of newly added user
    $id_sql = "SELECT uid FROM {$config->userFrameworkUsersTableName} where name = '%s'";
    $id_query = db_query($id_sql, $params['cms_name']);
    $id_row = db_fetch_array($id_query);
    return $id_row['uid'];
  }

  static function &dbHandle(&$config) {
    CRM_Core_Error::ignoreException();
    $db_uf = DB::connect($config->userFrameworkDSN);
    CRM_Core_Error::setCallback();
    if (!$db_uf ||
      DB::isError($db_uf)
    ) {
      $session = CRM_Core_Session::singleton();
      $session->pushUserContext(CRM_Utils_System::url('civicrm/admin', 'reset=1'));
      CRM_Core_Error::statusBounce(ts("Cannot connect to UF db via %1. Please check the CIVICRM_UF_DSN value in your civicrm.settings.php file",
          array(1 => $db_uf->getMessage())
        ));
    }
    $db_uf->query('/*!40101 SET NAMES utf8mb4 */');
    return $db_uf;
  }
}

