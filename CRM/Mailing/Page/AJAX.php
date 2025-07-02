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
 * This class contains all the function that are called using AJAX
 */
class CRM_Mailing_Page_AJAX {

  /**
   * Function to fetch the template text/html messages
   */
  static function template() {

    $templateId = CRM_Utils_Type::escape($_POST['tid'], 'Integer');


    $messageTemplate = new CRM_Core_DAO_MessageTemplates();
    $messageTemplate->id = $templateId;
    $messageTemplate->selectAdd();
    $messageTemplate->selectAdd('msg_text, msg_html, msg_subject');
    $messageTemplate->find(TRUE);
    $messages = array(
      'subject' => $messageTemplate->msg_subject,
      'msg_text' => $messageTemplate->msg_text,
      'msg_html' => $messageTemplate->msg_html,
    );

    echo json_encode($messages);
    CRM_Utils_System::civiExit();
  }

  /**
   * AJAX wrapper for sync contact
   *
   * @return string
   */
  public static function addContactToRemote() {
    $groupId = CRM_Utils_Request::retrieve('group_id', 'Integer', CRM_Core_DAO::$_nullObject, FALSE, NULL, 'POST');
    if (empty($groupId)) {
      http_response_code(400);
      $output = json_encode(array('success' => FALSE, 'message' => 'please provide correct arguments'));
      echo $output;
      CRM_Utils_System::civiExit();
    }

    try {
      $syncResult = CRM_Mailing_External_SmartMarketing::syncGroup($groupId);
      if ($syncResult['batch'] && !empty($syncResult['batch_id'])) {
        $remoteResult = array(
          'success' => TRUE,
          'message' => '<p>'.ts('Because of the large amount of data you are about to perform, we have scheduled this job for the batch process. You will receive an email notification when the work is completed.'). '</p><p>&raquo; <a href="'.CRM_Utils_System::url('civicrm/admin/batch', "reset=1&id={$syncResult['batch_id']}").'" target="_blank">'.ts('Batch Job List').'</a></p>',
        );
      }
      elseif (!empty($syncResult['result']['#count']) && !empty($syncResult['result']['#report'])) {
        $report = ts('Successful synced');
        foreach($syncResult['result']['#report'] as $rep) {
          $report .= "<p>$rep</p>";
        }
        $remoteResult = array(
          'success' => TRUE,
          'message' => $report,
        );
      }
      elseif (!empty($syncResult['result']['#report']['error'])) {
        $remoteResult = array(
          'success' => FALSE,
          'message' => ts('Synchronize error').': '.$syncResult['result']['#report']['error'],
        );
      }
      else {
        $report = ts('Synchronize error').': ';
        if (!empty($syncResult['result']['#report']) && is_array($syncResult['result']['#report'])) {
          foreach($syncResult['result']['#report'] as $rep) {
            $report .= "<span>$rep</span>";
          }
        }
        else {
          $report .= ts('Unknown error occurred');
        }
        $remoteResult = array(
          'success' => FALSE,
          'message' => $report,
        );
      }
    }
    catch(CRM_Core_Exception $e) {
      $remoteResult = array('success' => FALSE, 'message' => $e->getMessage());
    }
    $output = json_encode($remoteResult);
    echo $output;
    CRM_Utils_System::civiExit();
  }
}

