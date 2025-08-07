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
 *
 */



/**
 * This class contains all case related functions that are called using AJAX (jQuery)
 */
class CRM_Case_Page_AJAX {

  /**
   * Retrieve unclosed cases.
   */
  static function unclosedCases() {
    $criteria = explode('-', CRM_Utils_Type::escape(CRM_Utils_Array::value('s', $_GET), 'String'));

    $limit = NULL;
    if ($limit = CRM_Utils_Array::value('limit', $_GET)) {
      $limit = CRM_Utils_Type::escape($limit, 'Integer');
    }

    $params = ['limit' => $limit,
      'case_type' => trim(CRM_Utils_Array::value(1, $criteria)),
      'sort_name' => trim(CRM_Utils_Array::value(0, $criteria)),
    ];

    $excludeCaseIds = [];
    if ($caseIdStr = CRM_Utils_Array::value('excludeCaseIds', $_GET)) {
      $excludeIdStr = CRM_Utils_Type::escape($caseIdStr, 'String');
      $excludeCaseIds = explode(',', $excludeIdStr);
      CRM_Utils_Type::escapeAll($excludeCaseIds, 'Integer');
    }

    $unclosedCases = CRM_Case_BAO_Case::getUnclosedCases($params, $excludeCaseIds);

    foreach ($unclosedCases as $caseId => $details) {
      echo $details['sort_name'] . ' - ' . $details['case_type'] . "|$caseId|" . $details['contact_id'] . '|' . $details['case_type'] . '|' . $details['sort_name'] . "\n";
    }

    CRM_Utils_System::civiExit();
  }

  function processCaseTags() {


    $caseId = CRM_Utils_Type::escape($_POST['case_id'], 'Integer');
    $tags = CRM_Utils_Type::escape($_POST['tag'], 'String');

    if (empty($caseId)) {
      echo 'false';
      CRM_Utils_System::civiExit();
    }

    $tagIds = [];
    if ($tags) {
      $tagIds = explode(',', $tags);
    }

    $params = ['entity_id' => $caseId,
      'entity_table' => 'civicrm_case',
    ];

    CRM_Core_BAO_EntityTag::del($params);

    foreach ($tagIds as $tagid) {
      $params['tag_id'] = $tagid;
      CRM_Core_BAO_EntityTag::add($params);
    }

    $session = &CRM_Core_Session::singleton();



    $activityParams = [];

    $activityParams['source_contact_id'] = $session->get('userID');
    $activityParams['activity_type_id'] = CRM_Core_OptionGroup::getValue('activity_type', 'Change Case Tags', 'name');
    $activityParams['activity_date_time'] = date('YmdHis');
    $activityParams['status_id'] = CRM_Core_OptionGroup::getValue('activity_status', 'Completed', 'name');
    $activityParams['case_id'] = $caseId;
    $activityParams['is_auto'] = 0;
    $activityParams['subject'] = 'Change Case Tags';

    $activity = CRM_Activity_BAO_Activity::create($activityParams);


    $caseParams = ['activity_id' => $activity->id,
      'case_id' => $caseId,
    ];

    CRM_Case_BAO_Case::processCaseActivity($caseParams);

    echo 'true';
    CRM_Utils_System::civiExit();
  }

  function caseDetails() {
    $caseId = CRM_Utils_Type::escape($_GET['caseId'], 'Integer');
    $contactId = CRM_Utils_Type::escape($_GET['contactId'], 'Integer');

    $sql = "SELECT * FROM civicrm_case where id = %1";
    $dao = CRM_Core_DAO::executeQuery($sql, [1 => [$caseId, 'Integer']]);

    while ($dao->fetch()) {
      $caseType = CRM_Case_BAO_Case::getCaseType((str_replace(CRM_Case_BAO_Case::VALUE_SEPERATOR, "",
            $dao->case_type_id
          )));
      $caseStatuses = CRM_Case_PseudoConstant::caseStatus();
      $cs = $caseStatuses[$dao->status_id];
      $caseDetails = "<html><table><tr><td>Case Subject</td><td>$dao->subject</td></tr>
                                          <tr><td>Case Type</td><td>$caseType</td></tr> 
                                          <tr><td> Case Status</td><td>$cs</td></tr>
                                          <tr><td>Case Start Date</td><td>$dao->start_date</td></tr>
                                          <tr><td>Case End Date</td><td></td></tr>$dao->end_date</table></html>";
      echo $caseDetails;
    }
  }
}

