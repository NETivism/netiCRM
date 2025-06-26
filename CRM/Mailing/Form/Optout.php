<?php
class CRM_Mailing_Form_Optout extends CRM_Core_Form {

  function preProcess() {
    parent::preProcess();
    $this->controller->setDestination(NULL, TRUE);

    $job_id = CRM_Utils_Request::retrieve('jid', 'Integer', $this);
    $queue_id = CRM_Utils_Request::retrieve('qid', 'Integer', $this);
    $hash = CRM_Utils_Request::retrieve('h', 'String', $this);
    $confirm = CRM_Utils_Request::retrieve('confirm', 'Integer', CRM_Core_DAO::$_nullObject);

    if (!$job_id || !$queue_id || !$hash) {
      CRM_Core_Error::fatal(ts("Missing input parameters"));
    }

    // verify that the three numbers above match
    $q = CRM_Mailing_Event_BAO_Queue::verify($job_id, $queue_id, $hash);
    if (!$q) {
      CRM_Core_Error::fatal(ts("There was an error in your request"));
    }
    CRM_Contact_BAO_Contact::redirectPreferredLanguage($q->contact_id);
    list($displayName, $email) = CRM_Mailing_Event_BAO_Queue::getContactInfo($queue_id);
    $this->assign('display_name', $displayName);
    $this->assign('email', $email);
    $this->assign('confirm', $confirm);
    $obj = [
      'type' => 'markup',
      'markup' => '<meta name="robots" content="noindex" />'.PHP_EOL,
    ];
    CRM_Utils_System::addHTMLHead($obj);
  }

  public function buildQuickForm() {
    $addCaptcha = TRUE;
    // if recaptcha is not set, then dont add it
    $config = CRM_Core_Config::singleton();
    if (empty($config->recaptchaPublicKey) || empty($config->recaptchaPrivateKey)) {
      $addCaptcha = FALSE;
    }

    if ($addCaptcha) {
      $captcha = CRM_Utils_ReCAPTCHA::singleton();
      $captcha->add($this);
    }

    $this->addButtons([
        [
          'type' => 'next',
          'name' => ts('Optout'),
          'isDefault' => TRUE,
        ],
        [
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]
    );
  }

  function postProcess() {
    $job_id = CRM_Utils_Request::retrieve('jid', 'Integer', $this);
    $queue_id = CRM_Utils_Request::retrieve('qid', 'Integer', $this);
    $hash = CRM_Utils_Request::retrieve('h', 'String', $this);
    if (CRM_Mailing_Event_BAO_Unsubscribe::unsub_from_domain($job_id, $queue_id, $hash)) {
      CRM_Mailing_Event_BAO_Unsubscribe::send_unsub_response($queue_id, NULL, TRUE, $job_id);
    }
    $url = CRM_Utils_System::url("civicrm/mailing/optout", "reset=1&jid={$job_id}&qid={$queue_id}&h={$hash}&confirm=1");
    CRM_Utils_System::redirect($url);
  }
}

