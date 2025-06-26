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
 * This class provides the common functionality for creating PDF letter for
 * one or a group of contact ids.
 */
class CRM_Contact_Form_Task_PDFLetterCommon {

  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */
  static function preProcess(&$form) {

    $messageText = [];
    $messageSubject = [];
    $dao = new CRM_Core_BAO_MessageTemplates();
    $dao->is_active = 1;
    $dao->find();
    while ($dao->fetch()) {
      $messageText[$dao->id] = $dao->msg_text;
      $messageSubject[$dao->id] = $dao->msg_subject;
    }

    $form->assign('message', $messageText);
    $form->assign('messageSubject', $messageSubject);
  }

  static function preProcessSingle(&$form, $cid) {
    $form->_contactIds = [$cid];
    // put contact display name in title for single contact mode

    CRM_Contact_Page_View::setTitle($cid);
  }

  /**
   * Build the form
   *
   * @access public
   *
   * @return void
   */
  static function buildQuickForm(&$form) {
    if (count($form->_contactIds) > 100) {
      CRM_Core_Error::statusBounce(ts('PDF generation only allow 100 contacts per run.'));
    }
    $form->assign('totalSelectedContacts', count($form->_contactIds));


    CRM_Mailing_BAO_Mailing::commonLetterCompose($form);
    if ($form->_single) {
      $cancelURL = CRM_Utils_System::url('civicrm/contact/view',
        "reset=1&cid={$form->_cid}&selectedChild=activity",
        FALSE, NULL, FALSE
      );
      if ($form->get('action') == CRM_Core_Action::VIEW) {
        $form->addButtons([
            ['type' => 'cancel',
              'name' => ts('Done'),
              'js' => ['onclick' => "location.href='{$cancelURL}'; return false;"],
            ],
          ]
        );
      }
      else {
        $form->addButtons([
            ['type' => 'submit',
              'name' => ts('Make PDF Letter'),
              'isDefault' => TRUE,
            ],
            ['type' => 'cancel',
              'name' => ts('Done'),
              'js' => ['onclick' => "location.href='{$cancelURL}'; return false;"],
            ],
          ]
        );
      }
    }
    else {
      $form->addDefaultButtons(ts('Make PDF Letters'));
    }

    $form->addFormRule(['CRM_Contact_Form_Task_PDFLetterCommon', 'formRule'], $form);
  }

  /**
   * form rule
   *
   * @param array $fields    the input form values
   * @param array $dontCare
   * @param array $self      additional values form 'this'
   *
   * @return true if no errors, else array of errors
   * @access public
   *
   */
  static function formRule($fields, $dontCare, $self) {
    $errors = [];
    $template = CRM_Core_Smarty::singleton();

    //Added for CRM-1393
    if (CRM_Utils_Array::value('saveTemplate', $fields) && empty($fields['saveTemplateName'])) {
      $errors['saveTemplateName'] = ts("Enter name to save message template");
    }
    return empty($errors) ? TRUE : $errors;
  }

  /**
   * process the form after the input has been submitted and validated
   *
   * @access public
   *
   * @return None
   */
  static function postProcess(&$form) {
    $formValues = $form->controller->exportValues($form->getName());

    // process message template

    if (CRM_Utils_Array::value('saveTemplate', $formValues) || CRM_Utils_Array::value('updateTemplate', $formValues)) {
      $messageTemplate = ['msg_text' => NULL,
        'msg_html' => $formValues['html_message'],
        'msg_subject' => NULL,
        'is_active' => TRUE,
      ];

      if ($formValues['saveTemplate']) {
        $messageTemplate['msg_title'] = $formValues['saveTemplateName'];
        CRM_Core_BAO_MessageTemplates::add($messageTemplate);
      }

      if ($formValues['template'] && $formValues['updateTemplate']) {
        $messageTemplate['id'] = $formValues['template'];
        unset($messageTemplate['msg_title']);
        CRM_Core_BAO_MessageTemplates::add($messageTemplate);
      }
    }



    $html = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><style>body { margin: 56px; }</style></head><body>';

    $tokens = [];
    CRM_Utils_Hook::tokens($tokens);
    $categories = array_keys($tokens);

    $html_message = $formValues['html_message'];
    self::formatMessage($html_message);

    $messageToken = CRM_Utils_Token::getTokens($html_message);
    $returnProperties = [];
    if (isset($messageToken['contact'])) {
      foreach ($messageToken['contact'] as $key => $value) {
        $returnProperties[$value] = 1;
      }
    }

    // refs #32614, disable smarty evaluation functions
    $mailing = new CRM_Mailing_BAO_Mailing();

    $first = TRUE;
    $domain = CRM_Core_BAO_Domain::getDomain();
    foreach ($form->_contactIds as $contactId) {
      $params = ['contact_id' => $contactId];

      list($contact) = $mailing->getDetails($params, $returnProperties, FALSE);

      if (civicrm_error($contact)) {
        $notSent[] = $contactId;
        continue;
      }

      $tokenHtml = CRM_Utils_Token::replaceContactTokens($html_message, $contact[$contactId], TRUE, $messageToken);
      $tokenHtml = CRM_Utils_Token::replaceDomainTokens($tokenHtml, $domain, TRUE, $messageToken);
      $tokenHtml = CRM_Utils_Token::replaceHookTokens($tokenHtml, $contact[$contactId], $categories, TRUE);

      if ($first == TRUE) {
        $first = FALSE;
        $html .= $tokenHtml;
      }
      else {
        $html .= '<div style="page-break-after: always;"></div>'.$tokenHtml;
      }
    }
    $html .= '</body></html>';

    $session = CRM_Core_Session::singleton();
    $userID = $session->get('userID');
    $activityTypeID = CRM_Core_OptionGroup::getValue('activity_type', 'Print PDF Letter', 'name');
    $activityParams = ['source_contact_id' => $userID,
      'activity_type_id' => $activityTypeID,
      'activity_date_time' => date('YmdHis'),
      'details' => $html_message,
    ];
    if ($form->_activityId) {
      $activityParams += ['id' => $form->_activityId];
    }
    if ($form->_cid) {
      $activity = CRM_Activity_BAO_Activity::create($activityParams);
    }
    else {
      // create  Print PDF activity for each selected contact. CRM-6886
      $activityIds = [];
      foreach ($form->_contactIds as $contactId) {
        $activityID = CRM_Activity_BAO_Activity::create($activityParams);
        $activityIds[$contactId] = $activityID->id;
      }
    }

    foreach ($form->_contactIds as $contactId) {
      $activityTargetParams = ['activity_id' => empty($activity->id) ? $activityIds[$contactId] : $activity->id,
        'target_contact_id' => $contactId,
      ];
      CRM_Activity_BAO_Activity::createActivityTarget($activityTargetParams);
    }

    CRM_Utils_PDF_Utils::html2pdf($html, 'CiviLetter.pdf', 'portrait', 'a4', TRUE);
    CRM_Utils_System::civiExit(1);
  }

  static function formatMessage(&$message) {
    $newLineOperators = ['p' => ['oper' => '<p>',
        'pattern' => '/<(\s+)?p(\s+)?>/m',
      ],
      'br' => ['oper' => '<br />',
        'pattern' => '/<(\s+)?br(\s+)?\/>/m',
      ],
    ];

    $htmlMsg = preg_split($newLineOperators['p']['pattern'], $message);
    foreach ($htmlMsg as $k => & $m) {
      $messages = preg_split($newLineOperators['br']['pattern'], $m);
      foreach ($messages as $key => & $msg) {
        $msg = trim($msg);
        $matches = [];
        if (preg_match('/^(&nbsp;)+/', $msg, $matches)) {
          $spaceLen = strlen($matches[0]) / 6;
          $trimMsg = ltrim($msg, '&nbsp; ');
          $charLen = strlen($trimMsg);
          $totalLen = $charLen + $spaceLen;
          if ($totalLen > 100) {
            $spacesCount = 10;
            if ($spaceLen > 50) {
              $spacesCount = 20;
            }
            if ($charLen > 100) {
              $spacesCount = 1;
            }
            $msg = str_repeat('&nbsp;', $spacesCount) . $trimMsg;
          }
        }
      }
      $m = CRM_Utils_Array::implode($newLineOperators['br']['oper'], $messages);
    }
    $message = CRM_Utils_Array::implode($newLineOperators['p']['oper'], $htmlMsg);
    $message = CRM_Utils_String::htmlPurifier($message);
  }
}

