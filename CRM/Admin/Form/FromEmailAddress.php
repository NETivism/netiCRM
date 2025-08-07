<?php
class CRM_Admin_Form_FromEmailAddress extends CRM_Core_Form {
  /**
   * Validation bit indicator
   *
   * This will save to OptionValue.filter
   *
   * @var const
   * @access public
   */
  const
    VALID_EMAIL = 1,
    VALID_SPF = 2,
    VALID_DKIM = 4;

  /**
   * The id of the object being edited / created
   *
   * @var int
   */
  protected $_id;

  /**
   * The default values for form fields
   *
   * @var array
   */
  protected $_values;

  /**
   * The default from email address
   *
   * @var string
   */
  protected $_defaultFrom;

  /**
   * Preprocess Form
   *
   * @return void
   */
  function preProcess() {
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, TRUE);
    if ($this->_action & CRM_Core_Action::DELETE || $this->_action & CRM_Core_Action::UPDATE || $this->get('id')) {
      $this->_id = CRM_Utils_Request::retrieve('id', 'Integer', $this, TRUE);
    }
    if ($this->_id) {
      $this->_values = self::loadEmailAddress($this->_id);
    }
    else {
      $this->_values = [
        'is_active' => 0,
      ];
    }
    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }
		$this->_defaultFrom = trim(CRM_Mailing_BAO_Mailing::defaultFromMail());
  }

  function buildQuickForm() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      $this->assign('email', $this->_values['email']);
      $this->addButtons([
          [
            'type' => 'next',
            'name' => ts('Delete Mail Settings'),
            'isDefault' => TRUE,
          ],
          [
            'type' => 'cancel',
            'name' => ts('Cancel'),
          ],
        ]
      );
    }
  }

  function postProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Core_BAO_OptionValue::del($this->_id);
      CRM_Core_Session::setStatus(ts('Selected option value has been deleted.'));
    }
  }

  public function saveValues() {
    $saved = self::saveEmailAddress($this->_action, $this->_id, $this->_values);
    if ($this->_action & CRM_Core_Action::ADD && !empty($saved->id)) {
      $this->_id = $saved->id;
      $this->set('id', $this->_id);
    }
  }

  /**
   * Load email address from option
   *
   * @param int $id
   * @return void
   */
  public static function loadEmailAddress($id) {
    $values = [];
    $params = ['id' => $id];
    CRM_Core_BAO_OptionValue::retrieve($params, $values);
    if (!empty($values['label'])) {
      $values['from'] = CRM_Utils_Mail::pluckNameFromHeader($values['label']);
      $values['email'] = CRM_Utils_Mail::pluckEmailFromHeader($values['label']);
    }
    return $values;
  }

  /**
   * Save email address to option value
   *
   * Called by children form pages.
   *
   * @param int $action CRM_Core_Action::ADD or CRM_Core_Action::UPDATE
   * @param int $id option value id when update, null for add
   * @param array $params associative array to save from email address
   *   'from' => 'Email From Name',
   *   'email' => 'Email From Address',
   *   'is_active' => 1,
   *   'filter' => logic combination of self::VALID_EMAIL / self::VALID_SPF / self::VALID_DKIM
   *   'description' => 'description of this email'
   * @return object
   */
  public static function saveEmailAddress($action, $id, $params) {
    $groupParams = ['name' => ('from_email_address')];
    $params['label'] = CRM_Utils_Mail::formatRFC822Email($params['from'], $params['email'], TRUE);
    $params['name'] = $params['label'];
    unset($params['from']);
    unset($params['email']);
    return CRM_Core_OptionValue::addOptionValue($params, $groupParams, $action, $id);
  }

  /**
   * Send Validation Email
   *
   * Include one-time validation link to verify owner of email address.
   *
   * @return void
   * @static
   */
  public static function sendValidationEmail($email, $id) {
    if (!CRM_Utils_Rule::email($email)) {
      CRM_Core_Error::fatal('Invalid email address when sending validation email.');
      return;
    }
    $config = CRM_Core_Config::singleton();
    $domain = CRM_Core_BAO_Domain::getDomain();

    //get the default domain email address.
    list($domainEmailName, $domainEmailAddress) = CRM_Core_BAO_Domain::getNameAndEmail();

    $localpart = CRM_Core_BAO_MailSettings::defaultLocalpart();
    $emailDomain = CRM_Core_BAO_MailSettings::defaultDomain();

    $headers = [
      'Subject' => ts('Email Validation - Confirm you are the email owner by click link'),
      'From' => "\"{$domainEmailName}\" <{$domainEmailAddress}>",
      'To' => $email,
      'Reply-To' => "do-not-reply@$emailDomain",
      'Return-Path' => "do-not-reply@$emailDomain",
    ];

    $hash = new CRM_Core_DAO_Sequence();
    $hash->name = __FUNCTION__.':'.$id;
    if ($hash->find(TRUE)) {
      $hash->value = $email;
      $hash->timestamp = microtime(true);
      $hash->update();
    }
    else {
      $hash->value = $email;
      $hash->timestamp = microtime(true);
      $hash->insert();
    }

    $key = CRM_Core_Key::get($email);
    $url = CRM_Utils_System::url('civicrm/admin/from_email_address', "reset=1&action=renew&id={$id}&k={$key}", TRUE);
    $text = ts('You receive this email because you were trying to add new from email address in %1.', [1 => $domainEmailName])."\n";
    $text .= ts('To finish email validation, please click the confirmation link below:')."\n";
    $text .= $url;

    // render the &amp; entities in text mode, so that the links work
    $text = str_replace('&amp;', '&', $text);

    $message = new Mail_mime("\n");
    $message->setTxtBody($text);
    $b = CRM_Utils_Mail::setMimeParams($message);
    $h = &$message->headers($headers);
    CRM_Mailing_BAO_Mailing::addMessageIdHeader($h, 'v', '0', '0', $key);

    $mailer = &$config->getMailer();
    if (is_object($mailer)) {
      $mailer->send($email, $h, $b);
      CRM_Core_Session::setStatus(ts('Email has been sent to : %1', [1 => $email]));
      CRM_Core_Error::setCallback();
    }
  }

  public static function verifyEmail($id) {
    $key = CRM_Utils_Request::retrieve('k', 'String', CRM_Core_DAO::$_nullObject, TRUE);
    $id = CRM_Utils_Request::retrieve('id', 'Integer', CRM_Core_DAO::$_nullObject, TRUE);
    $hash = new CRM_Core_DAO_Sequence();
    $hash->name = 'sendValidationEmail:'.$id;
    if ($hash->find(TRUE)) {
      $email = $hash->value;
      $hash->delete();
      if (CRM_Core_Key::validate($key, $email)) {
        $values = self::loadEmailAddress($id);
        if (!$values['filter'] & self::VALID_EMAIL) {
          $values['filter'] = $values['filter'] | self::VALID_EMAIL;
          self::saveEmailAddress(CRM_Core_Action::UPDATE, $id, $values);
          CRM_Core_Session::setStatus(ts("<strong>%1 - your email address '%2' has been successfully verified.</strong>", [
            1 => ts('Validation Success'),
            2 => $email,
          ]));
          CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/from_email_address', 'reset=1&action=update&id='.$id));
          return;
        }
        else {
          CRM_Core_Error::statusBounce(ts("Your email validation have been processed before."), CRM_Utils_System::url('civicrm/admin/from_email_address', 'reset=1'));
        }
      }
    }
    CRM_Core_Error::statusBounce(ts('Invalid URL'));
  }


  /**
   * Return verified email array
   *
   * @param int $verifiedType self::VALID_EMAIL self::VALID_SPF self::VALID_DKIM logic check
   * @param string $returnType 'email' to return full email, 'domain' to return domain only
   * @return array
   */
  public static function getVerifiedEmail($verifiedType = self::VALID_EMAIL | self::VALID_SPF | self::VALID_DKIM, $returnType = 'email') {
    $all = [];
    CRM_Core_OptionGroup::getAssoc('from_email_address', $all);
    $verified = [];
    foreach($all['filter'] as $idx => $filter) {
      if ($filter == $verifiedType) {
        $verified[$all['value'][$idx]] = CRM_Utils_Mail::pluckEmailFromHeader($all['label'][$idx]);
      }
    }
    $defaultFrom = CRM_Mailing_BAO_Mailing::defaultFromMail();
    $verified['default'] = $defaultFrom;
    if ($returnType === 'domain') {
      foreach($verified as $idx => $email) {
        $domain = preg_replace('/^[^@]+@/', '', $email);
        $verified[$idx] = $domain;
      }
      $verified = array_unique($verified);
    }
    return $verified;
  }

  /**
   * Migrate from email address from event / contribution page
   *
   * @param string $fromName
   * @param string $fromEmail
   *
   * @return bool
   */
  public static function migrateEmailFromPages($fromName, $fromEmail) {
    $availableEmails = CRM_Core_PseudoConstant::fromEmailAddress(TRUE, TRUE);
    $migrateEmail = TRUE;
    foreach($availableEmails as $info) {
      if ($info['email'] === $fromEmail) {
        $migrateEmail = FALSE;
        return TRUE;
        break;
      }
    }
    if ($migrateEmail) {
      // check if domain already verified
      $verifiedSPFDKIM = self::VALID_EMAIL;
      $existsFrom = [];
      list($dontcare, $domain) = explode('@', $fromEmail);
      CRM_Core_OptionGroup::getAssoc('from_email_address', $existsFrom);
      foreach($existsFrom['filter'] as $idx => $filter) {
        $filterPassed = (self::VALID_EMAIL | self::VALID_SPF | self::VALID_DKIM);
        if ($filter == $filterPassed) {
          $validAddr = $existsFrom['label'][$idx];
          $validEmail = CRM_Utils_Mail::pluckEmailFromHeader($validAddr);
          list($account, $validDomain) = explode('@', $validEmail);
          if ($validDomain === $domain) {
            $verifiedSPFDKIM = $filterPassed;
          }
        }
      }

      $params = [];
      $params['email'] = trim($fromEmail);
      $params['from'] = trim($fromName);
      $params['is_active'] = 1;
      $params['filter'] = $verifiedSPFDKIM;
      $params['description'] = ts('Automatically Generated');
      self::saveEmailAddress(CRM_Core_Action::ADD, NULL, $params);
      return TRUE;
    }
    return FALSE;
  }
}