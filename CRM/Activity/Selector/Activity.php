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
 * This class is used to retrieve and display activities for a contact
 *
 */
class CRM_Activity_Selector_Activity extends CRM_Core_Selector_Base implements CRM_Core_Selector_API {

  /**
   * This defines two actions - Details and Delete.
   *
   * @var array
   * @static
   */
  static $_actionLinks;

  /**
   * we use desc to remind us what that column is, name is used in the tpl
   *
   * @var array
   * @static
   */
  static $_columnHeaders;

  /**
   * contactId - contact id of contact whose activies are displayed
   *
   * @var int
   * @access protected
   */
  protected $_contactId;

  protected $_admin;

  protected $_context;

  protected $_viewOptions;

  /**
   * Class constructor
   *
   * @param int $contactId - contact whose activities we want to display
   * @param int $permission - the permission we have for this contact
   *
   * @return CRM_Contact_Selector_Activity
   * @access public
   */
  function __construct($contactId, $permission, $admin = FALSE, $context = 'activity') {
    $this->_contactId = $contactId;
    $this->_permission = $permission;
    $this->_admin = $admin;
    $this->_context = $context;

    // get all enabled view componentc (check if case is enabled)

    $this->_viewOptions = CRM_Core_BAO_Preferences::valueOptions('contact_view_options', TRUE, NULL, TRUE);
  }

  /**
   * This method returns the action links that are given for each search row.
   * currently the action links added for each row are
   *
   * - View
   *
   * @param string $activityType type of activity
   *
   * @return array
   * @access public
   *
   */
  function actionLinks($activityTypeId,
    $sourceRecordId = NULL,
    $accessMailingReport = FALSE,
    $activityId = NULL,
    $key = NULL,
    $compContext = NULL
  ) {
    $activityTypes = CRM_Core_PseudoConstant::activityType(FALSE);
    $activityTypeIds = array_flip(CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'name'));
    $activityTypeName = array_search($activityTypeId, $activityTypeIds);

    $extraParams = ($key) ? "&key={$key}" : NULL;
    if ($compContext) {
      $extraParams .= "&compContext={$compContext}";
    }

    //show  edit link only for meeting/phone and other activities
    $showUpdate = FALSE;
    $showDelete = FALSE;
    $url = '';
    // event registration
    if (in_array($activityTypeId, array(
      $activityTypeIds['Event Registration'],
      $activityTypeIds['Event Notificaiont Email'],
    ))) {
      if ($sourceRecordId) {
        $url = 'civicrm/contact/view/participant';
        $text = 'View Participant';
        $qsView = "action=view&reset=1&id={$sourceRecordId}&cid=%%cid%%&context=%%cxt%%{$extraParams}";
      }
    }
    elseif (in_array($activityTypeId, array(
      $activityTypeIds['Contribution'],
      $activityTypeIds['Email Receipt'],
      $activityTypeIds['Contribution Notification Email'])
      )) {
      if ($sourceRecordId) {
        $url = 'civicrm/contact/view/contribution';
        $text = 'View Contribution';
        $qsView = "action=view&reset=1&id={$sourceRecordId}&cid=%%cid%%&context=%%cxt%%{$extraParams}";
      }
    }
    elseif ($activityTypeId == $activityTypeIds['Print Contribution Receipts']) {
      $url = 'civicrm/contact/view/contribution';
      $text = 'View Contribution';
      $qsView = "action=view&reset=1&id={$sourceRecordId}&cid=%%cid%%&context=%%cxt%%{$extraParams}";

      $showUpdate = TRUE;
      $urlUpdate = 'civicrm/contact/view/activity';
      $qsUpdate = "atype={$activityTypeId}&action=update&reset=1&id=%%id%%&cid=%%cid%%&context=%%cxt%%{$extraParams}";
    }
    elseif (in_array($activityTypeId, array(
      $activityTypeIds['Membership Signup'],
      $activityTypeIds['Membership Renewal'],
      $activityTypeIds['Membership Notification Email'],
      ))) {
      $text = 'View Membership';
      $url = 'civicrm/contact/view/membership';
      $qsView = "action=view&reset=1&id={$sourceRecordId}&cid=%%cid%%&context=%%cxt%%{$extraParams}";
    }
    elseif ($activityTypeId == CRM_Utils_Array::value('Pledge Acknowledgment', $activityTypeIds) ||
      // pledge acknowledgment
      $activityTypeId == CRM_Utils_Array::value('Pledge Reminder', $activityTypeIds)
    ) {
      $url = 'civicrm/contact/view/activity';
      $qsView = "atype={$activityTypeId}&action=view&reset=1&id=%%id%%&cid=%%cid%%&context=%%cxt%%{$extraParams}";
    }
    elseif (in_array($activityTypeId, array(
      $activityTypeIds['Email'], $activityTypeIds['Bulk Email'],
      ))) {
      $url = 'civicrm/activity/view';
      $text = 'View Activity';
      $qsView = "atype={$activityTypeId}&action=view&reset=1&id=%%id%%&cid=%%cid%%&context=%%cxt%%{$extraParams}";
    }
    elseif ($activityTypeId == $activityTypeIds['Inbound Email']) {
      $url = 'civicrm/contact/view/activity';
      $text = 'View Activity';
      $qsView = "atype={$activityTypeId}&action=view&reset=1&id=%%id%%&cid=%%cid%%&context=%%cxt%%{$extraParams}";
    }
    elseif ($activityTypeId == CRM_Utils_Array::value('Open Case', $activityTypeIds) ||
      $activityTypeId == CRM_Utils_Array::value('Change Case Type', $activityTypeIds) ||
      $activityTypeId == CRM_Utils_Array::value('Change Case Status', $activityTypeIds) ||
      $activityTypeId == CRM_Utils_Array::value('Change Case Start Date', $activityTypeIds)
    ) {
      $showUpdate = $showDelete = FALSE;
      $text = 'View Case';
      $url = 'civicrm/contact/view/activity';
      $qsView = "atype={$activityTypeId}&action=view&reset=1&id=%%id%%&cid=%%cid%%&context=%%cxt%%{$extraParams}";
      $qsUpdate = "atype={$activityTypeId}&action=update&reset=1&id=%%id%%&cid=%%cid%%&context=%%cxt%%{$extraParams}";
    }
    elseif ($activityTypeId == $activityTypeIds['SMS']) {
      $showUpdate = $showDelete = FALSE;
      $text = 'View Activity';
      $url = 'civicrm/activity/view';
      $qsView = "action=view&reset=1&id=%%id%%&cid=%%cid%%&context=%%cxt%%{$extraParams}";
    }
    else {
      $showUpdate = $showDelete = TRUE;
      $text = 'View Activity';
      $url = 'civicrm/contact/view/activity';
      $qsView = "atype={$activityTypeId}&action=view&reset=1&id=%%id%%&cid=%%cid%%&context=%%cxt%%{$extraParams}";
      $qsUpdate = "atype={$activityTypeId}&action=update&reset=1&id=%%id%%&cid=%%cid%%&context=%%cxt%%{$extraParams}";
    }

    $qsDelete = "atype={$activityTypeId}&action=delete&reset=1&id=%%id%%&cid=%%cid%%&context=%%cxt%%{$extraParams}";

    if ($this->_context == 'case') {
      $qsView .= "&caseid=%%caseid%%";
      $qsDelete .= "&caseid=%%caseid%%";
      if ($showUpdate) {
        $qsUpdate .= "&caseid=%%caseid%%";
      }
    }

    if ($url) {
      self::$_actionLinks = array(
        CRM_Core_Action::VIEW => array(
          'name' => ts($text),
          'url' => $url,
          'qs' => $qsView,
          'title' => ts($text),
        ),
      );
      // transactional email activity types, show activity detail link
      if ($text != 'View Activity' && in_array($activityTypeName, explode(',', CRM_Mailing_BAO_Transactional::ALLOWED_ACTIVITY_TYPES))) {
        self::$_actionLinks = self::$_actionLinks + array(CRM_Core_Action::FOLLOWUP => array(
          'name' => ts('View Activity'),
          'url' => 'civicrm/contact/view/activity',
          'qs' => "atype={$activityTypeId}&action=view&reset=1&id=%%id%%&cid=%%cid%%&context=%%cxt%%{$extraParams}",
          'title' => ts($text),
        ));
      }
    }

    if ($showUpdate) {
      self::$_actionLinks = self::$_actionLinks + array(CRM_Core_Action::UPDATE =>
        array(
          'name' => ts('Edit'),
          'url' => !empty($urlUpdate) ? $urlUpdate : $url,
          'qs' => $qsUpdate,
          'title' => ts('Update Activity'),
        ),
      );
    }


    if (CRM_Case_BAO_Case::checkPermission($activityId, 'File On Case', $activityTypeId)) {
      self::$_actionLinks = self::$_actionLinks + array(CRM_Core_Action::ADD =>
        array(
          'name' => ts('File On Case'),
          'url' => CRM_Utils_System::currentPath(),
          'extra' => 'onClick="Javascript:fileOnCase( \'file\', \'%%id%%\' ); return false;"',
          'title' => ts('File On Case'),
        ),
      );
    }

    if ($showDelete) {
      if (!isset($delUrl) || !$delUrl) {
        $delUrl = $url;
      }

      self::$_actionLinks = self::$_actionLinks + array(CRM_Core_Action::DELETE =>
        array(
          'name' => ts('Delete'),
          'url' => $delUrl,
          'qs' => $qsDelete,
          'title' => ts('Delete Activity'),
        ),
      );
    }

    if ($this->_context == 'case') {
      $qsDetach = "atype={$activityTypeId}&action=detach&reset=1&id=%%id%%&cid=%%cid%%&context=%%cxt%%&caseid=%%caseid%%{$extraParams}";

      self::$_actionLinks = self::$_actionLinks + array(CRM_Core_Action::DETACH =>
        array(
          'name' => ts('Detach'),
          'url' => $url,
          'qs' => $qsDetach,
          'title' => ts('Detach Activity'),
        ),
      );
    }

    if ($accessMailingReport) {
      self::$_actionLinks = self::$_actionLinks + array(CRM_Core_Action::BROWSE =>
        array(
          'name' => ts('Mailing Report'),
          'url' => 'civicrm/mailing/report',
          'qs' => "mid={$sourceRecordId}&reset=1&cid=%%cid%%&context=activitySelector",
          'title' => ts('View Mailing Report'),
        ),
      );
    }

    return self::$_actionLinks;
  }

  /**
   * getter for array of the parameters required for creating pager.
   *
   * @param
   * @access public
   */
  function getPagerParams($action, &$params) {
    $params['status'] = ts('Activities %%StatusMessage%%');
    $params['csvString'] = NULL;
    $params['rowCount'] = CRM_Utils_Pager::ROWCOUNT;

    $params['buttonTop'] = 'PagerTopButton';
    $params['buttonBottom'] = 'PagerBottomButton';
  }

  /**
   * returns the column headers as an array of tuples:
   * (name, sortName (key to the sort array))
   *
   * @param string $action the action being performed
   * @param enum   $output what should the result set include (web/email/csv)
   *
   * @return array the column headers that need to be displayed
   * @access public
   */
  function &getColumnHeaders($action = NULL, $output = NULL) {
    if ($output == CRM_Core_Selector_Controller::EXPORT || $output == CRM_Core_Selector_Controller::SCREEN) {
      $csvHeaders = array(ts('Activity Type'), ts('Description'), ts('Activity Date'));
      foreach (self::_getColumnHeaders() as $column) {
        if (CRM_Utils_Array::arrayKeyExists('name', $column)) {
          $csvHeaders[] = $column['name'];
        }
      }
      return $csvHeaders;
    }
    else {
      return self::_getColumnHeaders();
    }
  }

  /**
   * Returns total number of rows for the query.
   *
   * @param string $action - action being performed
   *
   * @return int Total number of rows
   * @access public
   */
  function getTotalCount($action, $case = NULL) {

    return CRM_Activity_BAO_Activity::getActivitiesCount($this->_contactId, $this->_admin, $case, $this->_context);
  }

  /**
   * returns all the rows in the given offset and rowCount
   *
   * @param enum   $action   the action being performed
   * @param int    $offset   the row number to start from
   * @param int    $rowCount the number of rows to return
   * @param string $sort     the sql string that describes the sort order
   * @param enum   $output   what should the result set include (web/email/csv)
   *
   * @return int   the total number of rows for this action
   */
  function &getRows($action, $offset, $rowCount, $sort, $output = NULL, $case = NULL) {
    $params['contact_id'] = $this->_contactId;
    $config = CRM_Core_Config::singleton();
    $rows = CRM_Activity_BAO_Activity::getActivities($params, $offset, $rowCount, $sort, $this->_admin, $case, $this->_context);

    if (empty($rows)) {
      return $rows;
    }

    $activityStatus = CRM_Core_PseudoConstant::activityStatus();

    //CRM-4418
    $permissions = array($this->_permission);
    if (CRM_Core_Permission::check('delete activities')) {
      $permissions[] = CRM_Core_Permission::DELETE;
    }
    $mask = CRM_Core_Action::mask($permissions);

    // activity types which show details on row
    $showDetails = array(
      CRM_Core_OptionGroup::getValue('activity_type', 'Email Receipt', 'name'),
    );
    foreach ($rows as $k => $row) {
      $row = &$rows[$k];

      if (in_array($row['activity_type_id'], $showDetails)) {
        $row['details'] = CRM_Core_DAO::getFieldValue('CRM_Activity_DAO_Activity', $row['activity_id'], 'details'); 
      }

      // add class to this row if overdue
      if (CRM_Utils_Date::overdue(CRM_Utils_Array::value('activity_date_time', $row))
        && CRM_Utils_Array::value('status_id', $row) == 1
      ) {
        $row['overdue'] = 1;
        $row['class'] = 'status-overdue';
      }
      else {
        $row['overdue'] = 0;
        $row['class'] = 'status-ontime';
      }

      $row['status'] = $row['status_id'] ? $activityStatus[$row['status_id']] : NULL;

      //CRM-3553
      $accessMailingReport = FALSE;
      if (CRM_Utils_Array::value('mailingId', $row)) {
        $accessMailingReport = TRUE;
        // get stat of this contact
        if (!empty($row['data_contact_id']) && !empty($row['source_record_id'])) {
          $mailingResult = CRM_Mailing_BAO_Mailing::getContactReport($row['data_contact_id'], $row['source_record_id']);
          $row['results'] = $mailingResult;
        }
      }

      // #33948, activity types that will get transaction mail report
      if (in_array(CRM_Core_OptionGroup::getName('activity_type', $row['activity_type_id']), explode(',', CRM_Mailing_BAO_Transactional::ALLOWED_ACTIVITY_TYPES))) {
        // get stat of this contact
        if (!empty($row['data_contact_id']) && !empty($row['activity_id'])) {
          $mailingResult = CRM_Mailing_BAO_Transactional::getActivityReport($row['data_contact_id'], $row['activity_id']);
          $row['results'] = $mailingResult;
        }
      }

      $actionLinks = $this->actionLinks(CRM_Utils_Array::value('activity_type_id', $row),
        CRM_Utils_Array::value('source_record_id', $row),
        $accessMailingReport,
        CRM_Utils_Array::value('activity_id', $row),
        $this->_key
      );

      $actionMask = array_sum(array_keys($actionLinks)) & $mask;

      if ($output != CRM_Core_Selector_Controller::EXPORT && $output != CRM_Core_Selector_Controller::SCREEN) {
        $row['action'] = CRM_Core_Action::formLink($actionLinks,
          $actionMask,
          array('id' => $row['activity_id'],
            'cid' => $this->_contactId,
            'cxt' => $this->_context,
            'caseid' => CRM_Utils_Array::value('case_id', $row),
          )
        );
      }

      unset($row);
    }

    return $rows;
  }

  /**
   * name of export file.
   *
   * @param string $output type of output
   *
   * @return string name of the file
   */
  function getExportFileName($output = 'csv') {
    return ts('CiviCRM Activity');
  }

  /**
   * get colunmn headers for search selector
   *
   *
   * @return array $_columnHeaders
   * @access private
   */
  private static function &_getColumnHeaders() {
    if (!isset(self::$_columnHeaders)) {
      self::$_columnHeaders = array(
        array('name' => ts('Type'),
          'sort' => 'activity_type',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
        array('name' => ts('Subject'),
          'sort' => 'subject',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
        array('name' => ts('Added By'),
          'sort' => 'source_contact_name',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
        array('name' => ts('With Contact')),
        array('name' => ts('Assigned')),
        array(
          'name' => ts('Date'),
          'sort' => 'activity_date_time',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
        array(
          'name' => ts('Duration'),
          'sort' => 'duration',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
        array(
          'name' => ts('Status'),
          'sort' => 'status_id',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
        array(
          'name' => ts('Result'),
        ),
        array('desc' => ts('Actions')),
      );
    }

    return self::$_columnHeaders;
  }
}

