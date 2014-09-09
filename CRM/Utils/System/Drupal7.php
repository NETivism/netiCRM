<?php

/**
 * Check if username and email exists in the drupal db
 *
 * @params $params    array   array of name and mail values
 * @params $errors    array   array of errors
 * @params $emailName string  field label for the 'email'
 *
 * @return void
 */
function checkUserNameEmailExists(&$params, &$errors, $emailName = 'email') {
  $config = CRM_Core_Config::singleton();

  $dao    = new CRM_Core_DAO();
  $name   = $dao->escape(CRM_Utils_Array::value('name', $params));
  $email  = $dao->escape(CRM_Utils_Array::value('mail', $params));
  $errors = form_get_errors();
  if ($errors) {
    // unset drupal messages to avoid twice display of errors
    unset($_SESSION['messages']);
  }

  if (CRM_Utils_Array::value('name', $params)) {
    if ($nameError = user_validate_name($params['name'])) {
      $errors['cms_name'] = $nameError;
    }
    else {
      $uid = db_query(
        "SELECT uid FROM {users} WHERE name = :name",
        array(':name' => $params['name'])
      )->fetchField();
      if ((bool) $uid) {
        $errors['cms_name'] = ts('The username %1 is already taken. Please select another username.', array(1 => $params['name']));
      }
    }
  }

  if (CRM_Utils_Array::value('mail', $params)) {
    if ($emailError = user_validate_mail($params['mail'])) {
      $errors[$emailName] = $emailError;
    }
    else {
      $uid = db_query(
        "SELECT uid FROM {users} WHERE mail = :mail",
        array(':mail' => $params['mail'])
      )->fetchField();
      if ((bool) $uid) {
        $errors[$emailName] = ts('This email %1 is already registered. Please select another email.',
          array(1 => $params['mail'])
        );
      }
    }
  }
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
 *
 */
function createUser(&$params, $mail) {
  $form_state = array();
  $form_state['input'] = array(
    'name' => $params['cms_name'],
    'mail' => $params[$mail],
    'op' => 'Create new account',
  );

  $admin = user_access('administer users');
  if (!variable_get('user_email_verification', TRUE) || $admin) {
          $form_state['input']['pass'] = array('pass1'=>$params['cms_pass'],'pass2'=>$params['cms_pass']);
  }

  $form_state['rebuild'] = FALSE;
  $form_state['programmed'] = TRUE;
  $form_state['method'] = 'post';
  $form_state['build_info']['args'] = array();

  $config = CRM_Core_Config::singleton();

  // we also need to redirect b
  $config->inCiviCRM = TRUE;

  $form = drupal_retrieve_form('user_register_form', $form_state);
  $form_state['process_input'] = 1;
  $form_state['submitted'] = 1;

  drupal_process_form('user_register_form', $form, $form_state);

  $config->inCiviCRM = FALSE;

  if (form_get_errors()) {
    return FALSE;
  }
  else {
    return $form_state['user']->uid;
  }
}

/**
 *  Change user name in host CMS
 *
 *  @param integer $ufID User ID in CMS
 *  @param string $ufName User name
 */
function updateCMSName($ufID, $ufName) {
  // CRM-5555
  if (function_exists('user_load')) {
    $user = user_load($ufID);
    if ($user->mail != $ufName) {
      user_save($user, array('mail' => $ufName));
      $user = user_load($ufID);
    }
  }
}

