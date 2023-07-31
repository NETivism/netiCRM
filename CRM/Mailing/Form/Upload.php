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

/**
 * This file is used to build the form configuring mailing details
 */
class CRM_Mailing_Form_Upload extends CRM_Core_Form {
  public $_mailingID;

  function preProcess() {
    $this->_mailingID = $this->get('mailing_id');
    $this->assign('mailingID', $this->_mailingID);
    require_once 'CRM/Core/Permission.php';
    if (CRM_Core_Permission::check('administer CiviCRM')) {
      $this->assign('isAdmin', 1);
    }

    //when user come from search context.
    require_once 'CRM/Contact/Form/Search.php';
    $this->_searchBasedMailing = CRM_Contact_Form_Search::isSearchContext($this->get('context'));
  }

  /**
   * This function sets the default values for the form.
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return None
   */
  function setDefaultValues() {
    $mailingID = CRM_Utils_Request::retrieve('mid', 'Integer', $this, FALSE, NULL);

    //need to differentiate new/reuse mailing, CRM-2873
    $reuseMailing = FALSE;
    if ($mailingID) {
      $reuseMailing = TRUE;
    }
    else {
      $mailingID = $this->_mailingID;
    }

    $count = $this->get('count');
    $this->assign('count', $count);

    $this->set('skipTextFile', FALSE);
    $this->set('skipHtmlFile', FALSE);

    $defaults = array();

    $htmlMessage = NULL;
    if ($mailingID) {
      require_once 'CRM/Mailing/DAO/Mailing.php';
      $dao = new CRM_Mailing_DAO_Mailing();
      $dao->id = $mailingID;
      $dao->find(TRUE);
      $dao->storeValues($dao, $defaults);
      if ($dao->name) {
        CRM_Utils_System::setTitle($dao->name);
      }

      //we don't want to retrieve template details once it is
      //set in session
      $justSavedTemplate = $this->get('justSavedTemplate');
      
      $this->assign('templateSelected', $justSavedTemplate ? $justSavedTemplate: 0);
      if (isset($defaults['msg_template_id']) && !$justSavedTemplate) {
        $messageTemplate = new CRM_Core_DAO_MessageTemplates();
        $messageTemplate->id = $defaults['msg_template_id'];
        $messageTemplate->selectAdd();
        $messageTemplate->selectAdd('msg_text, msg_html');
        $messageTemplate->find(TRUE);

        $jsonMessage = '';
        if ($messageTemplate->msg_text && empty($messageTemplate->msg_html)) {
          $json = json_decode($messageTemplate->msg_text);
          if (!empty($json->sections)) {
            // this is correct json object for nme
            $jsonMessage = $messageTemplate->msg_text;
          } 
          else {
            $jsonMessage = '';
          }
        }
        if (!empty($jsonMessage)) {
          $htmlMessage = '';
        }
        else {
          $htmlMessage = $messageTemplate->msg_html;
        }
        // do not load tempalte txt
        $defaults['text_message'] = '';
      }
      elseif ($justSavedTemplate == $defaults['msg_template_id']) {
        $defaults['template'] = $justSavedTemplate;
      }

      // handling saved template issue
      if (isset($defaults['body_text'])) {
        $defaults['text_message'] = $defaults['body_text'];
        $this->set('textFile', $defaults['body_text']);
        $this->set('skipTextFile', TRUE);
      }

      if (isset($defaults['body_html'])) {
        $htmlMessage = $defaults['body_html'];
        $this->set('htmlFile', $defaults['body_html']);
        $this->set('skipHtmlFile', TRUE);
      }

      if (isset($defaults['body_json'])) {
        $jsonMessage = $defaults['body_json'];
        $this->set('htmlFile', $defaults['body_html']);
        $this->set('skipHtmlFile', TRUE);
      }

      //set default from email address.
      require_once 'CRM/Core/OptionGroup.php';
      if (CRM_Utils_Array::value('from_name', $defaults) && CRM_Utils_Array::value('from_email', $defaults)) {
        $fromMailAddr = '"' . $defaults['from_name'] . '" <' . $defaults['from_email'] . '>';
        $defaults['from_email_address'] = array_search($fromMailAddr, CRM_Core_PseudoConstant::fromEmailAddress());
      }
      else {
        //get the default from email address.
        $defaults['from_email_address'] = 'default';
      }

      if (CRM_Utils_Array::value('replyto_email', $defaults)) {
        $replyToEmail = CRM_Core_OptionGroup::values('from_email_address');
        foreach ($replyToEmail as $value) {
          if (strstr($value, $defaults['replyto_email'])) {
            $replyToEmailAddress = $value;
            break;
          }
        }
        $replyToEmailAddress = explode('<', $replyToEmailAddress);
        $replyToEmailAddress = $replyToEmailAddress[0] . '<' . $replyToEmailAddress[1];
        $defaults['reply_to_address'] = array_search($replyToEmailAddress, $replyToEmail);
      }
    }

    //fix for CRM-2873
    if (!$reuseMailing) {
      $textFilePath = $this->get('textFilePath');
      if ($textFilePath && file_exists($textFilePath)) {
        $defaults['text_message'] = file_get_contents($textFilePath);
        if (strlen($defaults['text_message']) > 0) {
          $this->set('skipTextFile', TRUE);
        }
      }

      $htmlFilePath = $this->get('htmlFilePath');
      if ($htmlFilePath && file_exists($htmlFilePath)) {
        $defaults['html_message'] = file_get_contents($htmlFilePath);
        if (strlen($defaults['html_message']) > 0) {
          $htmlMessage = $defaults['html_message'];
          $this->set('skipHtmlFile', TRUE);
        }
      }
    }

    if ($this->get('html_message')) {
      $htmlMessage = $this->get('html_message');
    }

    $htmlMessage = str_replace(array("\n", "\r"), ' ', $htmlMessage);
    $htmlMessage = str_replace("'", "\'", $htmlMessage);
    $this->assign('message_html', $htmlMessage);

    $defaults['upload_type'] = 2;
    if (!empty($defaults['body_json']) && json_decode($defaults['body_json'])) {
      $defaults['upload_type'] = 2;
    }
    elseif (empty($defaults['body_json']) && !empty($defaults['body_html'])) {
      $defaults['upload_type'] = 1;
    }
    if (isset($defaults['body_html'])) {
      $defaults['html_message'] = $defaults['body_html'];
    }

    //CRM-4678 setdefault to default component when composing new mailing.
    if (!$reuseMailing) {
      $componentFields = array(
        'header_id' => 'Header',
        'footer_id' => 'Footer',
      );
      foreach ($componentFields as $componentVar => $componentType) {
        $defaults[$componentVar] = CRM_Mailing_PseudoConstant::defaultComponent($componentType, '');
      }
    }

    // when we just save template on last step
    // do not check save new template as default option again
    if (!empty($this->_submitValues['saveTemplate']) && !empty($this->_submitValues['saveTemplateName'])) {
      $defaults['saveTemplate'] = 0;
      $defaults['saveTemplateName'] = '';
    }

    return $defaults;
  }

  /**
   * Function to actually build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    $session = CRM_Core_Session::singleton();
    $config = CRM_Core_Config::singleton();
    $options = array();
    $tempVar = FALSE;

    // this seems so hacky, not sure what we are doing here and why. Need to investigate and fix
    $session->getVars($options, "CRM_Mailing_Controller_Send_{$this->controller->_key}");

    $fromEmails = CRM_Contact_BAO_Contact_Utils::fromEmailAddress();
    $availableEmails = array();
    foreach($fromEmails as $emailType => $emails) {
      if (!empty($emails)) {
        array_keys($emails);
        foreach($emails as $header => $display) {
          $availableEmails[$header] = $display;
        }
      }
    }
    if (empty($availableEmails)) {
      //redirect user to enter from email address.
      $url = CRM_Utils_System::url('civicrm/admin/from_email', 'reset=1');
      $status = ts("There is no valid from email address present. You can add here <a href='%1'>Add From Email Address.</a>", array(1 => $url));
      $session->setStatus($status);
    }

    $selectableEmails = array();
    if (!empty($fromEmails['system'])) {
      $selectableEmails[ts('Default').' '.ts('(built-in)')] = $fromEmails['system'];
    }
    else {
      $selectableEmails[ts('Default')] = $fromEmails['default'];
    }
    if (!empty($fromEmails['contact'])) {
      $selectableEmails[ts('Your Email')] = $fromEmails['contact'];
    }
    else {
      $this->assign('show_spf_dkim_notice', TRUE);
    }
    if (!empty($fromEmails['domain'])) {
      $selectableEmails[ts('Other')] = $fromEmails['domain'];
    }
    $this->addSelect('from_email_address', ts('From Email Address'), $selectableEmails, NULL, TRUE);

    // Added code to add custom field as Reply-To on form when it is enabled from Mailer settings
    if ($config->replyTo && !CRM_Utils_Array::value('override_verp', $options)) {
      $this->add('select', 'reply_to_address', ts('Reply-To'),
        array(
          '' => ts('- select -'),
        ) + $availableEmails
      );
    }
    elseif (CRM_Utils_Array::value('override_verp', $options)) {
      $trackReplies = TRUE;
      $this->assign('trackReplies', $trackReplies);
    }

    $this->add('text', 'subject', ts('Mailing Subject'),
      CRM_Core_DAO::getAttribute('CRM_Mailing_DAO_Mailing', 'subject'), TRUE
    );

    $attributes = array('onclick' => "showHideUpload();");
    $options = array("2" => ts('Compose On-screen'), "1" => ts('Traditional Editor'), "0" => ts('Upload Content'));

    $this->addRadio('upload_type', ts('I want to'), $options, $attributes, "&nbsp;&nbsp;");

    require_once 'CRM/Mailing/BAO/Mailing.php';
    CRM_Mailing_BAO_Mailing::commonCompose($this);

    $this->addElement('file', 'textFile', ts('Upload TEXT Message'), 'size=30 maxlength=60');
    $this->setMaxFileSize(1024 * 1024);
    $this->addRule('textFile', ts('File size should be less than 1 MByte'), 'maxfilesize', 1024 * 1024);
    $this->addRule('textFile', ts('File must be in UTF-8 encoding'), 'utf8File');

    $this->addElement('file', 'htmlFile', ts('Upload HTML Message'), 'size=30 maxlength=60');
    $this->setMaxFileSize(1024 * 1024);
    $this->addRule('htmlFile',
      ts('File size should be less than %1 MByte(s)',
        array(1 => 1)
      ),
      'maxfilesize',
      1024 * 1024
    );
    $this->addRule('htmlFile', ts('File must be in UTF-8 encoding'), 'utf8File');

    // refs #23719. Add a field to save json data of mailing content.
    $this->addElement('textarea', 'body_json', ts('Mailing content data'));

    //fix upload files when context is search. CRM-3711
    $ssID = $this->get('ssID');
    if ($this->_searchBasedMailing && $ssID) {
      $this->set('uploadNames', array('textFile', 'htmlFile'));
    }

    require_once 'CRM/Core/BAO/File.php';
    CRM_Core_BAO_File::buildAttachment($this,
      'civicrm_mailing',
      $this->_mailingID
    );

    require_once 'CRM/Mailing/PseudoConstant.php';
    $this->add('select', 'header_id', ts('Mailing Header'),
      array('' => ts('- none -')) + CRM_Mailing_PseudoConstant::component('Header')
    );

    $this->add('select', 'footer_id', ts('Mailing Footer'),
      array('' => ts('- none -')) + CRM_Mailing_PseudoConstant::component('Footer')
    );

    $this->addFormRule(array('CRM_Mailing_Form_Upload', 'formRule'), $this);

    //FIXME : currently we are hiding save an continue later when
    //search base mailing, we should handle it when we fix CRM-3876
    $buttons = array(
      array('type' => 'back',
        'name' => ts('<< Previous'),
      ),
      array(
        'type' => 'upload',
        'name' => ts('Next >>'),
        'spacing' => '&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;',
        'isDefault' => TRUE,
      ),
      array(
        'type' => 'submit',
        'name' => ts('Save & Continue Later'),
      ),
      array(
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ),
    );
    if ($this->_searchBasedMailing && $ssID) {
      $buttons = array(
        array('type' => 'back',
          'name' => ts('<< Previous'),
        ),
        array(
          'type' => 'upload',
          'name' => ts('Next >>'),
          'spacing' => '&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;',
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      );
    }
    if ($config->nextEnabled) {
      $this->assign('ai_completion_default', CRM_AI_BAO_AICompletion::getDefaultTemplate('CiviMail'));
      $this->assign('ai_completion_url_basepath', $config->userSystem->languageNegotiationURL('/'));
    }
    $this->addButtons($buttons);
  }

  public function postProcess() {
    $params = $ids = array();
    $uploadParams = array('header_id', 'footer_id', 'subject', 'from_name', 'from_email');
    $fileType = array('textFile', 'htmlFile');

    $formValues = $this->controller->exportValues($this->_name);

    $composeFields = array(
      'template', 'saveTemplate',
      'updateTemplate', 'saveTemplateName',
    );
    foreach ($uploadParams as $key) {
      if (CRM_Utils_Array::value($key, $formValues) || in_array($key, array('header_id', 'footer_id')) ) {
        $params[$key] = $formValues[$key];
        $this->set($key, $formValues[$key]);
      }
    }

    if (!$formValues['upload_type']) {
      foreach ($fileType as $key) {
        $contents = NULL;
        if (isset($formValues[$key]) &&
          !empty($formValues[$key])
        ) {
          $contents = file_get_contents($formValues[$key]['name']);
          $this->set($key, $formValues[$key]['name']);
        }
        if ($contents) {
          $params['body_' . substr($key, 0, 4)] = $contents;
        }
        else {
          $params['body_' . substr($key, 0, 4)] = 'NULL';
        }
      }
    }
    else {
      if($formValues['upload_type'] == 2 && !empty($formValues['body_json'])){
        $params['body_json'] = $formValues['body_json'];
      }
      else {
        $params['body_json'] = 'null';
      }
      $text_message = $formValues['text_message'];
      $params['body_text'] = $text_message;
      $this->set('textFile', $params['body_text']);
      $this->set('text_message', $params['body_text']);
      $html_message = $formValues['html_message'];

      // dojo editor does some html conversion when tokens are
      // inserted as links. Hence token replacement fails.
      // this is hack to revert html conversion for { to %7B and
      // } to %7D by dojo editor
      $html_message = str_replace('%7B', '{', str_replace('%7D', '}', $html_message));

      $params['body_html'] = $html_message;
      $this->set('htmlFile', $params['body_html']);
      $this->set('html_message', $params['body_html']);
    }

    $params['name'] = $this->get('name');

    $session = CRM_Core_Session::singleton();
    $params['contact_id'] = $session->get('userID');
    $msgTemplate = NULL;
    //mail template is composed
    if ($formValues['upload_type']) {
      foreach ($composeFields as $key) {
        if (CRM_Utils_Array::value($key, $formValues)) {
          $composeParams[$key] = $formValues[$key];
        }
      }

      if (CRM_Utils_Array::value('saveTemplate', $composeParams)) {
        if ($params['body_json'] != 'null') {
          $templateParams = array(
            'msg_text' => $params['body_json'],
            'msg_html' => '',
            'msg_subject' => $params['subject'],
            'is_active' => TRUE,
          );
        }
        else {
          $templateParams = array(
            'msg_text' => '',
            'msg_html' => $html_message,
            'msg_subject' => $params['subject'],
            'is_active' => TRUE,
          );
        }

        $templateParams['msg_title'] = $composeParams['saveTemplateName'];

        $msgTemplate = CRM_Core_BAO_MessageTemplates::add($templateParams);
      }
      elseif (CRM_Utils_Array::value('updateTemplate', $composeParams)) {
        if ($params['body_json'] != 'null') {
          $templateParams = array(
            'msg_text' => $params['body_json'],
            'msg_html' => '',
            'msg_subject' => $params['subject'],
            'is_active' => TRUE,
          );
        }
        else {
          $templateParams = array(
            'msg_text' => '',
            'msg_html' => $html_message,
            'msg_subject' => $params['subject'],
            'is_active' => TRUE,
          );
        }

        $templateParams['id'] = $formValues['template'];
        $msgTemplate = CRM_Core_BAO_MessageTemplates::add($templateParams);
      }


      if (isset($msgTemplate->id)) {
        $params['msg_template_id'] = $msgTemplate->id;
      }
      else {
        $params['msg_template_id'] = CRM_Utils_Array::value('template', $formValues);
      }
      $this->set('justSavedTemplate', $params['msg_template_id']);
    }

    CRM_Core_BAO_File::formatAttachment($formValues,
      $params,
      'civicrm_mailing',
      $this->_mailingID
    );
    $ids['mailing_id'] = $this->_mailingID;

    //handle mailing from name & address.
    $fromEmailAddress = $formValues['from_email_address'];

    //get the from email address
    require_once 'CRM/Utils/Mail.php';
    $params['from_email'] = CRM_Utils_Mail::pluckEmailFromHeader($fromEmailAddress);

    //get the from Name
    $params['from_name'] = CRM_Utils_Mail::pluckNameFromHeader($fromEmailAddress);

    //Add Reply-To to headers
    if (CRM_Utils_Array::value('reply_to_address', $formValues)) {
      $params['replyto_email'] = CRM_Utils_Mail::pluckEmailFromHeader($formValues['reply_to_address']);
    }

    /* Build the mailing object */


    require_once 'CRM/Mailing/BAO/Mailing.php';
    CRM_Mailing_BAO_Mailing::create($params, $ids);

    if (CRM_Utils_Array::value('_qf_Upload_submit', $this->_submitValues)) {
      //when user perform mailing from search context
      //redirect it to search result CRM-3711.
      $ssID = $this->get('ssID');
      if ($ssID && $this->_searchBasedMailing) {
        if ($this->_action == CRM_Core_Action::BASIC) {
          $fragment = 'search';
        }
        elseif ($this->_action == CRM_Core_Action::PROFILE) {
          $fragment = 'search/builder';
        }
        elseif ($this->_action == CRM_Core_Action::ADVANCED) {
          $fragment = 'search/advanced';
        }
        else {
          $fragment = 'search/custom';
        }

        $context = $this->get('context');
        if (!CRM_Contact_Form_Search::isSearchContext($context)) {
          $context = 'search';
        }
        $urlParams = "force=1&reset=1&ssID={$ssID}&context={$context}";
        $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', $this);
        if (CRM_Utils_Rule::qfKey($qfKey)) {
          $urlParams .= "&qfKey=$qfKey";
        }

        $session = CRM_Core_Session::singleton();
        $draftURL = CRM_Utils_System::url('civicrm/mailing/browse/unscheduled', 'scheduled=false&reset=1');
        $status = ts("Your mailing has been saved. You can continue later by clicking the 'Continue' action to resume working on it.<br /> From <a href='%1'>Draft and Unscheduled Mailings</a>.", array(1 => $draftURL));
        CRM_Core_Session::setStatus($status);

        //replace user context to search.
        $url = CRM_Utils_System::url('civicrm/contact/' . $fragment, $urlParams);
        CRM_Utils_System::redirect($url);
      }
      else {
        $status = ts("Your mailing has been saved. Click the 'Continue' action to resume working on it.");
        CRM_Core_Session::setStatus($status);
        $url = CRM_Utils_System::url('civicrm/mailing/browse/unscheduled', 'scheduled=false&reset=1');
        CRM_Utils_System::redirect($url);
      }
    }
  }

  /**
   * Function for validation
   *
   * @param array $params (ref.) an assoc array of name/value pairs
   *
   * @return mixed true or array of errors
   * @access public
   * @static
   */
  static function formRule($params, $files, $self) {
    if (CRM_Utils_Array::value('_qf_Import_refresh', $_POST)) {
      return TRUE;
    }
    $errors = array();
    $template = CRM_Core_Smarty::singleton();


    if (isset($params['html_message'])) {
      $htmlMessage = str_replace(array("\n", "\r"), ' ', $params['html_message']);
      $htmlMessage = str_replace("'", "\'", $htmlMessage);
      $template->assign('htmlContent', $htmlMessage);
    }
    require_once 'CRM/Core/BAO/Domain.php';

    $domain = CRM_Core_BAO_Domain::getDomain();

    require_once 'CRM/Mailing/BAO/Mailing.php';
    $mailing = new CRM_Mailing_BAO_Mailing();
    $mailing->id = $self->_mailingID;
    $mailing->find(TRUE);

    $session = CRM_Core_Session::singleton();
    $values = array('contact_id' => $session->get('userID'),
      'version' => 3,
    );
    require_once 'api/api.php';
    $contact = civicrm_api('contact', 'get', $values);

    //CRM-4524
    $contact = reset($contact['values']);

    $verp = array_flip(array('optOut', 'reply', 'unsubscribe', 'resubscribe', 'owner'));
    foreach ($verp as $key => $value) {
      $verp[$key]++;
    }

    $urls = array_flip(array('forward', 'optOutUrl', 'unsubscribeUrl', 'resubscribeUrl'));
    foreach ($urls as $key => $value) {
      $urls[$key]++;
    }

    require_once 'CRM/Mailing/BAO/Component.php';

    // set $header and $footer
    foreach (array(
        'header', 'footer',
      ) as $part) {
      $$part = array();
      if ($params["{$part}_id"]) {
        //echo "found<p>";
        $component = new CRM_Mailing_BAO_Component();
        $component->id = $params["{$part}_id"];
        $component->find(TRUE);
        ${$part}['textFile'] = $component->body_text;
        ${$part}['htmlFile'] = $component->body_html;
        $component->free();
      }
      else {
        ${$part}['htmlFile'] = ${$part}['textFile'] = '';
      }
    }

    $skipTextFile = $self->get('skipTextFile');
    $skipHtmlFile = $self->get('skipHtmlFile');

    if (!$params['upload_type']) {
      if ((!isset($files['textFile']) || !file_exists($files['textFile']['tmp_name'])) &&
        (!isset($files['htmlFile']) || !file_exists($files['htmlFile']['tmp_name']))
      ) {
        if (!($skipTextFile || $skipHtmlFile)) {
          $errors['textFile'] = ts('Please provide either a Text or HTML formatted message - or both.');
        }
      }
    }
    else {
      if (!CRM_Utils_Array::value('text_message', $params) && !CRM_Utils_Array::value('html_message', $params)) {
        $errors['html_message'] = ts('Please provide either a Text or HTML formatted message - or both.');
      }
      if (CRM_Utils_Array::value('saveTemplate', $params) && !CRM_Utils_Array::value('saveTemplateName', $params)) {
        $errors['saveTemplateName'] = ts('Please provide a Template Name.');
      }
    }

    foreach (array(
        'text', 'html',
      ) as $file) {
      if (!$params['upload_type'] && !file_exists(CRM_Utils_Array::value('tmp_name', $files[$file . 'File']))) {
        continue;
      }
      if ($params['upload_type'] && !$params[$file . '_message']) {
        continue;
      }

      if (!$params['upload_type']) {
        $str = file_get_contents($files[$file . 'File']['tmp_name']);
        $name = $files[$file . 'File']['name'];
      }
      else {
        $str = $params[$file . '_message'];
        $str = ($file == 'html') ? str_replace('%7B', '{', str_replace('%7D', '}', $str)) : $str;
        $name = $file . ' message';
      }

      /* append header/footer */


      $str = $header[$file . 'File'] . $str . $footer[$file . 'File'];

      $dataErrors = array();

      /* First look for missing tokens */

      // refs #14980, on specific way to skip check. Use carefully
      if(empty($params['skip_unsubscribe_check'])){
        $err = CRM_Utils_Token::requiredTokens($str);
        if ($err !== TRUE) {
          foreach ($err as $token => $desc) {
            $dataErrors[] = '<li>' . ts('This message is missing a required token - {%1}: %2',
              array(1 => $token, 2 => $desc)
            ) . '</li>';
          }
        }
      }

      /* Do a full token replacement on a dummy verp, the current
             * contact and domain, and the first organization. */



      // here we make a dummy mailing object so that we
      // can retrieve the tokens that we need to replace
      // so that we do get an invalid token error
      // this is qute hacky and I hope that there might
      // be a suggestion from someone on how to
      // make it a bit more elegant

      require_once 'CRM/Mailing/BAO/Mailing.php';
      $dummy_mail = new CRM_Mailing_BAO_Mailing();
      $mess = "body_{$file}";
      $dummy_mail->$mess = $str;
      $tokens = $dummy_mail->getTokens();

      $str = CRM_Utils_Token::replaceSubscribeInviteTokens($str);
      $str = CRM_Utils_Token::replaceDomainTokens($str, $domain, NULL, $tokens[$file]);
      $str = CRM_Utils_Token::replaceMailingTokens($str, $mailing, NULL, $tokens[$file]);
      $str = CRM_Utils_Token::replaceOrgTokens($str, $org);
      $str = CRM_Utils_Token::replaceActionTokens($str, $verp, $urls, NULL, $tokens[$file]);
      $str = CRM_Utils_Token::replaceContactTokens($str, $contact, NULL, $tokens[$file]);

      $unmatched = CRM_Utils_Token::unmatchedTokens($str);

      if (!empty($unmatched) && 0) {
        foreach ($unmatched as $token) {
          $dataErrors[] = '<li>' . ts('Invalid token code') . ' {' . $token . '}</li>';
        }
      }
      if (!empty($dataErrors)) {
        $errors[$file . 'File'] = ts('The following errors were detected in %1:', array(
            1 => $name,
          )) . ' <ul>' . CRM_Utils_Array::implode('', $dataErrors) . '</ul><br /><a href="' . CRM_Utils_System::docURL2('Sample CiviMail Messages', TRUE) . '" target="_blank">' . ts('More information on required tokens...') . '</a>';
      }
    }

    $templateName = CRM_Core_BAO_MessageTemplates::getMessageTemplates();
    if (CRM_Utils_Array::value('saveTemplate', $params)
      && in_array(CRM_Utils_Array::value('saveTemplateName', $params), $templateName)
    ) {
      $errors['saveTemplate'] = ts('Duplicate Template Name.');
    }
    if (empty($params['template']) && !empty($params['updateTemplate'])) {
      $errors['template'] = ts('You need to specify a template to update.');
    }
    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Display Name of the form
   *
   * @access public
   *
   * @return string
   */
  public function getTitle() {
    return ts('Mailing Content');
  }

  /**
   * List available tokens for this form.
   *
   * @return array
   */
  public function listTokens() {
    $tokens = CRM_Core_SelectValues::contactTokens();
    $tokens = array_merge(CRM_Core_SelectValues::mailingTokens(), $tokens);
    return $tokens;
  }
}

