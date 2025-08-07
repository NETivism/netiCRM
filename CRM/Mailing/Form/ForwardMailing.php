<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.1                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2011                                |
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
 * @copyright CiviCRM LLC (c) 2004-2011
 * $Id$
 *
 */


class CRM_Mailing_Form_ForwardMailing extends CRM_Core_Form {
  public $_fromEmail;
  function preProcess() {
    $job_id = CRM_Utils_Request::retrieve('jid', 'Positive',
      $this, NULL
    );
    $queue_id = CRM_Utils_Request::retrieve('qid', 'Positive',
      $this, NULL
    );
    $hash = CRM_Utils_Request::retrieve('h', 'String',
      $this, NULL
    );


    $q = CRM_Mailing_Event_BAO_Queue::verify($job_id, $queue_id, $hash);

    if ($q == NULL) {

      /** ERROR **/
      CRM_Core_Error::fatal(ts('Invalid form parameters.'));
       return CRM_Core_Error::statusBounce(ts('Invalid form parameters.'));
    }

    CRM_Contact_BAO_Contact::redirectPreferredLanguage($q->contact_id);
    $mailing = &$q->getMailing();

    if ($hash) {
      $emailId = CRM_Core_DAO::getfieldValue('CRM_Mailing_Event_DAO_Queue', $hash, 'email_id', 'hash');
      $this->_fromEmail = $fromEmail = CRM_Core_DAO::getfieldValue('CRM_Core_DAO_Email', $emailId, 'email');
      $this->assign('fromEmail', $fromEmail);
    }

    /* Show the subject instead of the name here, since it's being
         * displayed to external contacts/users */


    CRM_Utils_System::setTitle(ts('Forward Mailing: %1', [1 => $mailing->subject]));

    $this->set('queue_id', $queue_id);
    $this->set('job_id', $job_id);
    $this->set('hash', $hash);
    $obj = [
      'type' => 'markup',
      'markup' => '<meta name="robots" content="noindex" />'.PHP_EOL,
    ];
    CRM_Utils_System::addHTMLHead($obj);
  }

  /**
   * Function to actually build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    for ($i = 0; $i < 5; $i++) {
      $this->add('text', "email_$i", ts('Email %1', [1 => $i + 1]));
      $this->addRule("email_$i", ts('Email is not valid.'), 'email');
    }

    //insert message Text by selecting "Select Template option"
    $this->add('textarea', 'forward_comment', ts('Comment'), ['cols' => '80', 'rows' => '8', 'style' => 'display:none']);
    $this->addWysiwyg('html_comment',
      ts('HTML Message'),
      ['cols' => '80', 'rows' => '8']
    );

    $this->addButtons([
        [
          'type' => 'next',
          'name' => ts('Forward'),
          'isDefault' => TRUE,
        ],
        [
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]);
  }

  /**
   * Form submission of new/edit contact is processed.
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    $queue_id = $this->get('queue_id');
    $job_id = $this->get('job_id');
    $hash = $this->get('hash');
    $timeStamp = date('YmdHis');

    $formValues = $this->controller->exportValues($this->_name);
    $params = [];
    $params['body_text'] = $formValues['html_comment'];
    $html_comment = $formValues['html_comment'];
    $html_comment = str_replace('%7B', '{', str_replace('%7D', '}', $html_comment));
    $html_comment = str_replace("\n", "<br>", $html_comment);
    $params['body_html'] = $html_comment;

    $emails = [];
    for ($i = 0; $i < 5; $i++) {
      $email = $this->controller->exportValue($this->_name, "email_$i");
      if (!empty($email)) {
        $emails[] = $email;
      }
    }

    $forwarded = NULL;
    foreach ($emails as $email) {
      $params = [
        'version' => 3,
        'job_id' => $job_id,
        'event_queue_id' => $queue_id,
        'hash' => $hash,
        'email' => $email,
        'time_stamp' => $timeStamp,
        'fromEmail' => $this->_fromEmail,
        'params' => $params,
      ];
      $result = civicrm_api('Mailing', 'event_forward', $params);
      if (!civicrm_error($result)) {
        $forwarded++;
      }
    }

    $status = ts('Mailing is not forwarded to the given email address.', ['count' => count($emails), 'plural' => 'Mailing is not forwarded to the given email addresses.']);
    if ($forwarded) {
      $status = ts('Mailing is forwarded successfully to %count email address.', ['count' => $forwarded, 'plural' => 'Mailing is forwarded successfully to %count email addresses.']);
    }


    CRM_Utils_System::setUFMessage($status);

    // always redirect to front page of url
    $session = CRM_Core_Session::singleton();
    $config = CRM_Core_Config::singleton();
    $session->pushUserContext($config->userFrameworkBaseURL);
  }
}

