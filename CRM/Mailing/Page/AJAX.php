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
    require_once 'CRM/Utils/Type.php';
    $templateId = CRM_Utils_Type::escape($_POST['tid'], 'Integer');

    require_once "CRM/Core/DAO/MessageTemplates.php";
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
    $providerId = CRM_Utils_Request::retrieve('provider_id', 'Integer', CRM_Core_DAO::$_nullObject, TRUE, NULL, 'POST');
    $groupId = CRM_Utils_Request::retrieve('group_id', 'Integer', CRM_Core_DAO::$_nullObject, TRUE, NULL, 'POST');
    $providers = CRM_SMS_BAO_Provider::getProviders(NULL, array('id' => $providerId));

    $groupParams = array(
      'id' => $groupId,
    );
    $group = array();
    CRM_Contact_BAO_Group::retrieve($groupParams, $group);
    $syncData = json_decode($group['sync_data'], TRUE);
    if (!empty($providers) && $group['id'] == $groupId && !empty($syncData['remote_group_id'])) {
      $apiParams = array(
        'group_id' => $groupId,
        'version' => 3,
      );
      $result = civicrm_api('group_contact', 'get', $apiParams);
      if (!empty($result['values'])) {
        $contactIds = array();
        array_walk($result['values'], function($item) {
          $contactIds[$item['contact_id']] = $item['contact_id'];
        }, $contactIds);
        $provider = reset($providers);
        $names = explode('_', $provider['name']);
        $vendorName = end($names);
        $smartMarketingClass = 'CRM_Mailing_External_SmartMarketing_'.$vendorName;
        $smartMarketingService = new $smartMarketingClass($providerId);
        try {
          $smartMarketingService->batchSchedule($contactIds, $syncData['remote_group_id'], $providerId);
          $remoteResult = array('success' => TRUE, 'message' => '');
        }
        catch(CRM_Core_Exception $e) {
          $remoteResult = array('success' => FALSE, 'message' => $e->getMessage());
        }
        echo json_encode($remoteResult);
        CRM_Utils_System::civiExit();
      }
    }
  }
}

