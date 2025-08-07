<?php
class CRM_Mailing_Form_Resubscribe extends CRM_Core_Form {

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

    $groups = CRM_Mailing_Event_BAO_Unsubscribe::unsub_from_mailing($job_id, $queue_id, $hash, TRUE);
    $this->assign('groups', $groups);
    $groupExist = NULL;
    foreach ($groups as $key => $value) {
      if ($value) {
        $groupExist = TRUE;
      }
    }
    $this->assign('groupExist', $groupExist);
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
          'name' => ts('Resubscribe'),
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
    $groups = CRM_Mailing_Event_BAO_Resubscribe::resub_to_mailing($job_id, $queue_id, $hash);

    $sentNotification = $this->get($hash);
    if (count($groups) && empty($sentNotification) ) {
      CRM_Mailing_Event_BAO_Resubscribe::send_resub_response($queue_id, $groups, FALSE, $job_id);
      // prevent double sent
      $this->set($hash, 1);
    }
    $url = CRM_Utils_System::url("civicrm/mailing/resubscribe", "reset=1&jid={$job_id}&qid={$queue_id}&h={$hash}&confirm=1");
    CRM_Utils_System::redirect($url);
  }
}

