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
 * This class contains all the function that are called using AJAX
 */
class CRM_Admin_Page_AJAX {

  /**
   * Function to build menu tree
   */
  static function getNavigationList() {

    echo CRM_Core_BAO_Navigation::buildNavigation(TRUE);
    CRM_Utils_System::civiExit();
  }

  /**
   * Function to process drag/move action for menu tree
   */
  static function menuTree() {

    echo CRM_Core_BAO_Navigation::processNavigation($_GET);
    CRM_Utils_System::civiExit();
  }

  /**
   * Function to build status message while
   * enabling/ disabling various objects
   */
  static function getStatusMsg() {
    $recordID = CRM_Utils_Type::escape($_POST['recordID'], 'Integer');
    $recordBAO = CRM_Utils_Type::escape($_POST['recordBAO'], 'String');
    $op = CRM_Utils_Type::escape($_POST['op'], 'String');
    $show = NULL;

    if ($op == 'disable-enable') {
      $status = ts('Are you sure you want to enable this record?');
    }
    else {
      switch ($recordBAO) {
        case 'CRM_Core_BAO_UFGroup':
          require_once (str_replace('_', DIRECTORY_SEPARATOR, $recordBAO) . ".php");
          $method = 'getUFJoinRecord';
          $result = [$recordBAO, $method];
          $ufJoin = call_user_func_array(($result), [$recordID, TRUE]);
          if (!empty($ufJoin)) {
            $status = ts('This profile is currently used for %1.', [1 => CRM_Utils_Array::implode(', ', $ufJoin)]) . ' <br/><br/>' . ts('If you disable the profile - it will be removed from these forms and/or modules. Do you want to continue?');
          }
          else {
            $status = ts('Are you sure you want to disable this profile?');
          }
          break;

        case 'CRM_Price_BAO_Set':
          require_once (str_replace('_', DIRECTORY_SEPARATOR, $recordBAO) . ".php");
          $usedBy = CRM_Price_BAO_Set::getUsedBy($recordID);
          $priceSet = CRM_Price_BAO_Set::getTitle($recordID);

          if (!CRM_Utils_System::isNull($usedBy)) {
            $template = CRM_Core_Smarty::singleton();
            $template->assign('usedBy', $usedBy);
            $comps = ["Event" => "civicrm_event",
              "Contribution" => "civicrm_contribution_page",
            ];
            $contexts = [];
            foreach ($comps as $name => $table) {
              if (CRM_Utils_Array::arrayKeyExists($table, $usedBy)) {
                $contexts[] = $name;
              }
            }
            $template->assign('contexts', $contexts);

            $show = "noButton";
            $table = $template->fetch('CRM/Price/Page/table.tpl');
            $status = ts('Unable to disable the \'%1\' price set - it is currently in use by one or more active events, contribution pages or contributions.', [1 => $priceSet]) . "<br/> $table";
          }
          else {
            $status = ts('Are you sure you want to disable \'%1\' Price Set?', [1 => $priceSet]);
          }
          break;

        case 'CRM_Event_BAO_Event':
          $status = ts('Are you sure you want to disable this Event?');
          break;

        case 'CRM_Core_BAO_UFField':
          $status = ts('Are you sure you want to disable this CiviCRM Profile field?');
          break;

        case 'CRM_Contribute_BAO_ManagePremiums':
          $status = ts('Are you sure you want to disable this premium? This action will remove the premium from any contribution pages that currently offer it. However it will not delete the premium record - so you can re-enable it and add it back to your contribution page(s) at a later time.');
          break;

        case 'CRM_Contact_BAO_RelationshipType':
          $status = ts('Are you sure you want to disable this relationship type?') . '<br/><br/>' . ts('Users will no longer be able to select this value when adding or editing relationships between contacts.');
          break;

        case 'CRM_Contribute_BAO_ContributionType':
          $status = ts('Are you sure you want to disable this contribution type?');
          break;

        case 'CRM_Core_BAO_PaymentProcessor':
          $status = ts('Are you sure you want to disable this payment processor?') . ' <br/><br/>' . ts('Users will no longer be able to select this value when adding or editing transaction pages.');
          break;

        case 'CRM_Core_BAO_PaymentProcessorType':
          $status = ts('Are you sure you want to disable this payment processor type?');
          break;

        case 'CRM_Core_BAO_LocationType':
          $status = ts('Are you sure you want to disable this location type?') . ' <br/><br/>' . ts('Users will no longer be able to select this value when adding or editing contact locations.');
          break;

        case 'CRM_Event_BAO_ParticipantStatusType':
          $status = ts('Are you sure you want to disable this Participant Status?') . '<br/><br/> ' . ts('Users will no longer be able to select this value when adding or editing Participant Status.');
          break;

        case 'CRM_Mailing_BAO_Component':
          $status = ts('Are you sure you want to disable this component?');
          break;

        case 'CRM_Core_BAO_CustomField':
          $status = ts('Are you sure you want to disable this custom data field?');
          break;

        case 'CRM_Core_BAO_CustomGroup':
          $status = ts('Are you sure you want to disable this custom data group? Any profile fields that are linked to custom fields of this group will be disabled.');
          break;

        case 'CRM_Core_BAO_MessageTemplates':
          $status = ts('Are you sure you want to disable this message tempate?');
          break;

        case 'CRM_ACL_BAO_ACL':
          $status = ts('Are you sure you want to disable this ACL?');
          break;

        case 'CRM_ACL_BAO_EntityRole':
          $status = ts('Are you sure you want to disable this ACL Role Assignment?');
          break;

        case 'CRM_Member_BAO_MembershipType':
          $status = ts('Are you sure you want to disable this membership type?');
          break;

        case 'CRM_Member_BAO_MembershipStatus':
          $status = ts('Are you sure you want to disable this membership status rule?');
          break;

        case 'CRM_Price_BAO_Field':
          $status = ts('Are you sure you want to disable this price field?');
          break;

        case 'CRM_Contact_BAO_Group':
          $status = ts('Are you sure you want to disable this Group?');
          if (!empty($recordID)) {
            $provider = CRM_Mailing_External_SmartMarketing::getProviderByGroup($recordID);
            if (!empty($provider)) {
              $status .= '<br><br>'.ts('The smart markeintg journey is still in progress. To delete the contact or remove contact from group list, you need to go to the external smart marketing tool to proceed.');
            }
          }
          break;

        case 'CRM_Core_BAO_OptionGroup':
          $status = ts('Are you sure you want to disable this Option?');
          break;

        case 'CRM_Contact_BAO_ContactType':
          $status = ts('Are you sure you want to disable this Contact Type?');
          break;

        case 'CRM_Core_BAO_OptionValue':
          require_once (str_replace('_', DIRECTORY_SEPARATOR, $recordBAO) . ".php");
          $label = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', $recordID, 'label');
          $status = ts('Are you sure you want to disable this \'%1\' record ?', [1 => $label]);
          break;

        case 'CRM_Contribute_BAO_ContributionRecur':
          $status = ts('Are you sure you want to mark this recurring contribution as cancelled?');
          // Judge $recurID is belong to neweb or not.
          $processor_id = NULL;
          $is_test = 0;
          $sql = "SELECT processor_id, $is_test FROM civicrm_contribution_recur WHERE id = %1";
          $params = [ 1 => [$recordID, 'Positive']];
          $dao = CRM_Core_DAO::executeQuery($sql, $params);
          while ($dao->fetch()) {
            if ($dao->processor_id) {
              $processor_id = $dao->processor_id;
              $is_test = $dao->is_test;
            }
          }
          if (empty($processor_id)) {
            $ccid = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_contribution cc WHERE cc.contribution_recur_id = %1", $params);
            $contrib = new CRM_Contribute_DAO_Contribution( );
            $contrib->id = $ccid;

            if($contrib->find(true)){
              $processor_id = $contrib->payment_processor_id;
              $is_test = $contrib->is_test;
            }
          }

          if (!empty($processor_id)) {
            $pp = CRM_Core_BAO_PaymentProcessor::getPayment($processor_id, $is_test);
            if($pp['class_name']){
              $classname = 'CRM_Core_'.$pp['class_name']; 
              $payment = new $classname($is_test, $processor_id);
              if(method_exists($payment, 'cancelRecuringMessage')){
                $status = $payment->cancelRecuringMessage($recordID);  
              }
            }
          }
          break;
        case 'CRM_Contribute_BAO_ContributionPage':
          $config = CRM_Core_Config::singleton();
          $defaultPageId = $config->defaultRenewalPageId;
          if ($defaultPageId == $recordID) {
            $recurringSettingURL = CRM_Utils_System::url('civicrm/admin/recurring', 'reset=1');
            $status = ts("This contribution page is set as the default page for one-time recurring contribution links and cannot be deleted or disable.<br/>Please go to <a href='%1'>Administer >> CiviContribute >> Recurring Settings</a> and update the Default contribution page for one-time renewal link field before trying again.", [1 => $recurringSettingURL]);
            $show = "noButton";
            break;
          }
          $status = ts('Are you sure you want to disable this record?');
          break;
        default:
          $status = ts('Are you sure you want to disable this record?');
          break;
      }
    }
    $statusMessage['status'] = $status;
    $statusMessage['show'] = $show;

    echo json_encode($statusMessage);

    exit;
  }

  static function getTagList() {
    $name = CRM_Utils_Type::escape($_GET['name'], 'String');
    $parentId = CRM_Utils_Type::escape($_GET['parentId'], 'Integer');

    $tags = [];

    $query = "SELECT id, name FROM civicrm_tag WHERE parent_id = {$parentId} and name LIKE '%{$name}%'";
    $dao = CRM_Core_DAO::executeQuery($query);

    while ($dao->fetch()) {
      $tags[] = ['name' => $dao->name,
        'id' => $dao->id,
      ];
    }

    if (empty($tags)) {
      $tags[] = ['name' => $name,
        'id' => $name,
      ];
    }

    echo json_encode($tags);
    CRM_Utils_System::civiExit();
  }

  static function processTags() {
    $skipTagCreate = $skipEntityAction = $entityId = NULL;
    $action = CRM_Utils_Type::escape($_POST['action'], 'String');
    $parentId = CRM_Utils_Type::escape($_POST['parentId'], 'Integer');
    if ($_POST['entityId']) {
      $entityId = CRM_Utils_Type::escape($_POST['entityId'], 'Integer');
    }

    $entityTable = CRM_Utils_Type::escape($_POST['entityTable'], 'String');

    if ($_POST['skipTagCreate']) {
      $skipTagCreate = CRM_Utils_Type::escape($_POST['skipTagCreate'], 'Integer');
    }

    if ($_POST['skipEntityAction']) {
      $skipEntityAction = CRM_Utils_Type::escape($_POST['skipEntityAction'], 'Integer');
    }

    $tagID = $_POST['tagID'];


    $tagInfo = [];
    // if action is select
    if ($action == 'select') {
      // check the value of tagID
      // if numeric that means existing tag
      // else create new tag
      if (!$skipTagCreate && !is_numeric($tagID)) {
        $params = ['name' => $tagID,
          'parent_id' => $parentId,
        ];


        $tagObject = CRM_Core_BAO_Tag::add($params, CRM_Core_DAO::$_nullArray);

        $tagInfo = ['name' => $tagID,
          'id' => $tagObject->id,
          'action' => $action,
        ];
        $tagID = $tagObject->id;
      }

      if (!$skipEntityAction && $entityId) {
        // save this tag to contact
        $params = ['entity_table' => $entityTable,
          'entity_id' => $entityId,
          'tag_id' => $tagID,
        ];

        CRM_Core_BAO_EntityTag::add($params);
      }
      // if action is delete
    }
    elseif ($action == 'delete') {
      if (!is_numeric($tagID)) {
        $tagID = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Tag', $tagID, 'id', 'name');
      }
      if ($entityId) {
        // delete this tag entry for the entity
        $params = ['entity_table' => $entityTable,
          'entity_id' => $entityId,
          'tag_id' => $tagID,
        ];

        CRM_Core_BAO_EntityTag::del($params);
      }
      $tagInfo = ['id' => $tagID,
        'action' => $action,
      ];
    }

    echo json_encode($tagInfo);
    CRM_Utils_System::civiExit();
  }
}

