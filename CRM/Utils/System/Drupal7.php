<?php

class CRM_Utils_System_Drupal7 {

  /**
   * Load drupal bootstrap.
   *
   * @param array $params
   *   Either uid, or name & pass.
   *
   * @return bool
   * @Todo Handle setting cleanurls configuration for CiviCRM?
   */
  function loadBootStrap($params = [], $loadUser = TRUE, $throwError = FALSE) {
    $cmsPath = CRM_Utils_System_Drupal::cmsRootPath();
    if (!file_exists("$cmsPath/includes/bootstrap.inc")) {
      if ($throwError) {
        throw new Exception('Sorry, could not locate bootstrap.inc');
      }
      return FALSE;
    }
    require_once "$cmsPath/includes/bootstrap.inc";
    @drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
    // explicitly setting error reporting, since we cannot handle drupal related notices
    // @todo 1 = E_ERROR, but more to the point setting error reporting deep in code
    // causes grief with debugging scripts
		global $user;
    if (empty($user)) {
      if ($throwError) {
        throw new Exception('Sorry, could not load drupal bootstrap.');
      }
      return FALSE;
    }

    // we have user to load
		if (!empty($params)) {
      $config = CRM_Core_Config::singleton();
      $version = $config->userSystem->version;
      $uid = CRM_Utils_Array::value('uid', $params);

      if (!$uid) {
        //load user, we need to check drupal permissions.
        $name = CRM_Utils_Array::value('name', $params, FALSE) ? $params['name'] : trim(CRM_Utils_Array::value('name', $_REQUEST));
        $pass = CRM_Utils_Array::value('pass', $params, FALSE) ? $params['pass'] : trim(CRM_Utils_Array::value('pass', $_REQUEST));

        if ($name) {
          $uid = user_authenticate($name, $pass);
          if (empty($uid)) {
            if ($throwError) {
              throw new Exception('Sorry, unrecognized username or password.');
            }
            return FALSE;
          }
        }
      }
      if ($uid) {
        if ($loadUser) {
          $this->loadUserById($uid);
        }
        return TRUE;
      }

      if ($throwError) {
        throw new Exception('Sorry, can not load CMS user account.');
      }
    }
  }

  /**
   * Check if username and email exists in the drupal db
   *
   * @params $params    array   array of name and mail values
   * @params $emailName string  field label for the 'email'
   *
   * @return void
   */
  function checkUserNameEmailExists($params, $emailName = 'email') {
    $config = CRM_Core_Config::singleton();
    $errors = [];

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
          [':name' => $params['name']]
        )->fetchField();
        if ((bool) $uid) {
          $errors['cms_name'] = ts('The username %1 is already taken. Please select another username.', [1 => $params['name']]);
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
          [':mail' => $params['mail']]
        )->fetchField();
        if ((bool) $uid) {
          $errors[$emailName] = ts('This email %1 is already registered. Please select another email.',
            [1 => $params['mail']]
          );
        }
      }
    }
    return $errors;
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
  function createUser($params, $mail) {
    $form_state = [];
    $form_state['input'] = [
      'name' => $params['cms_name'],
      'mail' => $params[$mail],
      'op' => 'Create new account',
    ];

    $admin = user_access('administer users');
    if (!variable_get('user_email_verification', TRUE) || $admin) {
            $form_state['input']['pass'] = ['pass1'=>$params['cms_pass'],'pass2'=>$params['cms_pass']];
    }

    $form_state['rebuild'] = FALSE;
    $form_state['programmed'] = TRUE;
    $form_state['method'] = 'post';
    $form_state['build_info']['args'] = [];

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
        // check if duplicated email on drupal
        if ((bool) db_select('users')->fields('users', ['uid'])->condition('mail', db_like($ufName), 'LIKE')->range(0, 1)->execute()->fetchField()) {
          // drupal user mail already be taken
        }
        else {
          user_save($user, ['mail' => $ufName]);
          $user = user_load($ufID);
        }
      }
    }
  }

  function languageNegotiationURL($url, $addLanguagePart = TRUE, $removeLanguagePart = FALSE) {
    static $exists;
    if (empty($url)) {
      return $url;
    }

    if($exists || function_exists('language_negotiation_get')){
      $exists = TRUE;
      global $language;

      //does user configuration allow language
      //support from the URL (Path prefix or domain)
      if (language_negotiation_get('language') == 'locale-url') {
        $urlType = variable_get('locale_language_negotiation_url_part');

        //url prefix
        if ($urlType == LOCALE_LANGUAGE_NEGOTIATION_URL_PREFIX) {
          if (isset($language->prefix) && $language->prefix) {
            if ($addLanguagePart) {
              $url .= $language->prefix . '/';
            }
            if ($removeLanguagePart) {
              $url = str_replace("/{$language->prefix}/", '/', $url);
            }
          }
        }
        //domain
        if ($urlType == LOCALE_LANGUAGE_NEGOTIATION_URL_DOMAIN) {
          if (isset($language->domain) && $language->domain) {
            if ($addLanguagePart) {
              $cleanedUrl = preg_replace('#^https?://#', '', $language->domain);
              // drupal function base_path() adds a "/" to the beginning and end of the returned path
              if (substr($cleanedUrl, -1) == '/') {
                $cleanedUrl = substr($cleanedUrl, 0, -1);
              }
              $url = (CRM_Utils_System::isSSL() ? 'https' : 'http') . '://' . $cleanedUrl . base_path();
            }
            if ($removeLanguagePart && defined('CIVICRM_UF_BASEURL')) {
              $url = str_replace('\\', '/', $url);
              $parseUrl = parse_url($url);

              //kinda hackish but not sure how to do it right
              //hope http_build_url() will help at some point.
              if (is_array($parseUrl) && !empty($parseUrl)) {
                $urlParts           = explode('/', $url);
                $hostKey            = array_search($parseUrl['host'], $urlParts);
                $ufUrlParts         = parse_url(CIVICRM_UF_BASEURL);
                $urlParts[$hostKey] = $ufUrlParts['host'];
                $url                = CRM_Utils_Array::implode('/', $urlParts);
              }
            }
          }
        }
      }
    }
    return $url;
  }

  function setTitle($pageTitle) {
    $title = CRM_Utils_String::htmlPurifier($pageTitle);
    drupal_set_title($title, PASS_THROUGH);
  }

  /**
   * @inheritDoc
   */
  public function authenticate($name, $password, $loadCMSBootstrap = FALSE, $realPath = NULL) {
    $this->loadBootStrap([], FALSE);
    $uid = user_authenticate($name, $password);
    if ($uid) {
      if ($this->loadUserByName($name)) {
        $this->synchronizeUser();
        $contact_id = CRM_Core_BAO_UFMatch::getContactId($uid);
        return [$contact_id, $uid, mt_rand()];
      }
    }

    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function loadUserByName($username) {
    if (!empty($username)) {
      $account = user_load_by_name($username);
    }
    if ($account && $account->uid && $account->name == $username) {
      global $user;
      $user = $account;
      $uid = $account->uid;
      $contact_id = CRM_Core_BAO_UFMatch::getContactId($uid);

      // Store the contact id and user id in the session
      $session = CRM_Core_Session::singleton();
      $session->set('ufID', $uid);
      $session->set('userID', $contact_id);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function loadUserById($uid) {
    if (!empty($uid) && CRM_Utils_Rule::positiveInteger($uid)) {
      $account = user_load($uid);
    }
    if ($account && $account->uid == $uid) {
      global $user;
      $user = $account;
      $contact_id = CRM_Core_BAO_UFMatch::getContactId($uid);

      // Store the contact id and user id in the session
      $session = CRM_Core_Session::singleton();
      $session->set('ufID', $uid);
      $session->set('userID', $contact_id);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function synchronizeUser() {
    global $user;
    $uid = $user->uid;
    $email = $user->email;
    if (!empty($user) && !empty($uid) && !empty($email)) {
      return CRM_Core_BAO_UFMatch::synchronizeUFMatch($user, $uid, $email, 'Drupal');
    }
    return FALSE;
  }

}
