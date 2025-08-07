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


class CRM_Member_BAO_MembershipType extends CRM_Member_DAO_MembershipType {

  /**
   * static holder for the default LT
   */
  static $_defaultMembershipType = NULL;

  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. Typically the valid params are only
   * contact_id. We'll tweak this function to be more full featured over a period
   * of time. This is the inverse function of create. It also stores all the retrieved
   * values in the default array
   *
   * @param array $params   (reference ) an assoc array of name/value pairs
   * @param array $defaults (reference ) an assoc array to hold the flattened values
   *
   * @return object CRM_Member_BAO_MembershipType object
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
    $membershipType = new CRM_Member_DAO_MembershipType();
    $membershipType->copyValues($params);
    if ($membershipType->find(TRUE)) {
      CRM_Core_DAO::storeValues($membershipType, $defaults);
      return $membershipType;
    }
    return NULL;
  }

  /**
   * update the is_active flag in the db
   *
   * @param int      $id        id of the database record
   * @param boolean  $is_active value we want to set the is_active field
   *
   * @return Object             DAO object on sucess, null otherwise
   * @static
   */
  static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Member_DAO_MembershipType', $id, 'is_active', $is_active);
  }

  /**
   * function to add the membership types
   *
   * @param array $params reference array contains the values submitted by the form
   * @param array $ids    reference array contains the id
   *
   * @access public
   * @static
   *
   * @return object
   */
  static function add(&$params, &$ids) {
    $params['is_active'] = CRM_Utils_Array::value('is_active', $params, FALSE);

    // action is taken depending upon the mode
    $membershipType = new CRM_Member_DAO_MembershipType();

    $membershipType->copyValues($params);

    $membershipType->domain_id = CRM_Core_Config::domainID();

    $membershipType->id = CRM_Utils_Array::value('membershipType', $ids);
    $membershipType->member_of_contact_id = CRM_Utils_Array::value('memberOfContact', $ids);

    $membershipType->save();

    return $membershipType;
  }

  /**
   * Function to delete membership Types
   *
   * @param int $membershipTypeId
   * @static
   */

  static function del($membershipTypeId) {
    //check dependencies
    $check = FALSE;
    $status = [];
    $dependancy = [
      'Membership' => 'membership_type_id',
    ];

    foreach ($dependancy as $name => $field) {
      $baoString = 'CRM_Member_BAO_' . $name;
      $dao = new $baoString();
      $dao->$field = $membershipTypeId;
      if ($dao->find(TRUE)) {
        $check = TRUE;
        $status[] = $name;
      }
    }
    if ($check) {

      $session = CRM_Core_Session::singleton();
      $cnt = 1;
      $message = ts('This membership type cannot be deleted due to following reason(s):');
      if (in_array('Membership', $status)) {
        $deleteURL = CRM_Utils_System::url('civicrm/member/search', 'reset=1');
        $message .= '<br/>' . ts('%2. There are some contacts who have this membership type assigned to them. Search for contacts with this membership type on the <a href=\'%1\'>CiviMember >> Find Members</a> page. If you delete all memberships of this type, you will then be able to delete the membership type on this page. To delete the membership type, all memberships of this type should be deleted.', [1 => $deleteURL, 2 => $cnt]);
        $cnt++;
      }

      if (in_array('MembershipBlock', $status)) {
        $deleteURL = CRM_Utils_System::url('civicrm/admin/contribute', 'reset=1');
        $message .= '<br/>' . ts('%2. This Membership Type is being link to <a href=\'%1\'>Online Contribution page</a>. Please change/delete it in order to delete this Membership Type.', [1 => $deleteURL, 2 => $cnt]);
      }
      CRM_Core_Session::setStatus($message);

      return CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/member/membershipType', 'reset=1&action=browse'));
    }

    //delete from membership Type table

    $membershipType = new CRM_Member_DAO_MembershipType();
    $membershipType->id = $membershipTypeId;

    //fix for membership type delete api
    $result = FALSE;
    if ($membershipType->find(TRUE)) {
      $membershipType->delete();
      $result = TRUE;
    }

    return $result;
  }

  /**
   * Function to convert membership Type's 'start day' & 'rollover day' to human readable formats.
   *
   * @param array $membershipType an array of membershipType-details.
   * @static
   */

  static function convertDayFormat(&$membershipType) {
    $periodDays = [
      'fixed_period_start_day',
      'fixed_period_rollover_day',
    ];
    foreach ($membershipType as $id => $details) {
      foreach ($periodDays as $pDay) {
        if (CRM_Utils_Array::value($pDay, $details)) {
          $month = substr($details[$pDay], 0, strlen($details[$pDay]) - 2);
          $day = substr($details[$pDay], -2);
          $monthMap = [
            '1' => 'Jan',
            '2' => 'Feb',
            '3' => 'Mar',
            '4' => 'Apr',
            '5' => 'May',
            '6' => 'Jun',
            '7' => 'Jul',
            '8' => 'Aug',
            '9' => 'Sep',
            '10' => 'Oct',
            '11' => 'Nov',
            '12' => 'Dec',
          ];
          $membershipType[$id][$pDay] = $monthMap[$month] . ' ' . $day;
        }
      }
    }
  }

  /**
   * Function to get membership Types
   *
   * @param int $membershipTypeId
   * @static
   */
  static function getMembershipTypes($public = TRUE) {

    $membershipTypes = [];
    $membershipType = new CRM_Member_DAO_MembershipType();
    $membershipType->is_active = 1;
    if ($public) {
      $membershipType->visibility = 'Public';
    }
    $membershipType->orderBy(' weight');
    $membershipType->find();
    while ($membershipType->fetch()) {
      $membershipTypes[$membershipType->id] = $membershipType->name;
    }
    $membershipType->free();
    return $membershipTypes;
  }

  /**
   * Function to get membership Type Details
   *
   * @param int $membershipTypeId
   * @static
   */
  static function getMembershipTypeDetails($membershipTypeId) {

    $membershipTypeDetails = [];

    $membershipType = new CRM_Member_DAO_MembershipType();
    $membershipType->is_active = 1;
    $membershipType->id = $membershipTypeId;
    if ($membershipType->find(TRUE)) {
      CRM_Core_DAO::storeValues($membershipType, $membershipTypeDetails);
      $membershipType->free();
      return $membershipTypeDetails;
    }
    else {
      return NULL;
    }
  }

  /**
   * Function to calculate start date and end date for new membership
   *
   * @param int  $membershipTypeId membership type id
   * @param date $joinDate join date ( in mysql date format )
   * @param date $startDate start date ( in mysql date format )
   *
   * @return array associated array with  start date, end date and join date for the membership
   * @static
   */
  static function getDatesForMembershipType($membershipTypeId, $joinDate = NULL, $startDate = NULL, $endDate = NULL) {
    $membershipTypeDetails = self::getMembershipTypeDetails($membershipTypeId);

    // convert all dates to 'Y-m-d' format.
    foreach (['joinDate', 'startDate', 'endDate'] as $dateParam) {
      if (!empty($$dateParam)) {
        $$dateParam = CRM_Utils_Date::processDate($$dateParam, NULL, FALSE, 'Y-m-d');
      }
    }
    if (!$joinDate) {
      $joinDate = date('Y-m-d');
    }
    $actualStartDate = $joinDate;
    if ($startDate) {
      $actualStartDate = $startDate;
    }

    $fixed_period_rollover = FALSE;
    if (CRM_Utils_Array::value('period_type', $membershipTypeDetails) == 'rolling') {
      if (!$startDate) {
        $startDate = $joinDate;
      }
      $actualStartDate = $startDate;
    }
    elseif (CRM_Utils_Array::value('period_type', $membershipTypeDetails) == 'fixed') {
      //calculate start date

      // today is always join date, in case of Online join date
      // is equal to current system date
      if ($startDate) {
        $toDay = explode('-', $startDate);
      }
      else {
        $toDay = explode('-', $joinDate);
      }

      // get year from join date
      $year = $toDay[0];
      $month = $toDay[1];

      if ($membershipTypeDetails['duration_unit'] == 'year') {

        //get start fixed day
        $startMonth = substr($membershipTypeDetails['fixed_period_start_day'], 0,
          strlen($membershipTypeDetails['fixed_period_start_day']) - 2
        );
        $startDay = substr($membershipTypeDetails['fixed_period_start_day'], -2);

        $fixedStartDate = date('Y-m-d', mktime(0, 0, 0, $startMonth, $startDay, $year));

        //get start rollover day
        $rolloverMonth = substr($membershipTypeDetails['fixed_period_rollover_day'], 0,
          strlen($membershipTypeDetails['fixed_period_rollover_day']) - 2
        );
        $rolloverDay = substr($membershipTypeDetails['fixed_period_rollover_day'], -2);

        $fixedRolloverDate = date('Y-m-d', mktime(0, 0, 0, $rolloverMonth, $rolloverDay, $year));

        //store orginal fixed rollover date calculated based on joining date
        $actualRolloverDate = $fixedRolloverDate;

        // check if rollover date is less than fixed start date,
        // if yes increment, another edge case handling
        if ($fixedRolloverDate <= $fixedStartDate && ($startMonth.$startDay == '101')) {
          $year = $year + 1;
          $actualRolloverDate = date('Y-m-d', mktime(0, 0, 0, $rolloverMonth, $rolloverDay, $year));
        }

        // if join date is less than start date as well as rollover date
        // then decrement the year by 1
        /* #27316, doesn't know why should back for 1 year. comment out will have expect result
        if (($joinDate < $fixedStartDate) && ($joinDate < $actualRolloverDate) && $) {
          $year = $year - 1;
          $actualRolloverDate = date('Y-m-d', mktime(0, 0, 0, $rolloverMonth, $rolloverDay, $year));
        }
        */

        // calculate start date if join date is in rollover window
        // if join date is greater than the rollover date,
        // then consider the following year as the start date
        /*
        if ($actualRolloverDate <= $joinDate) {
          $fixed_period_rollover = TRUE;
          $year = $year + 1;
        }
        */

        $actualStartDate = date('Y-m-d', mktime(0, 0, 0, $startMonth, $startDay, $year));
        if (!$startDate) {
          $startDate = $actualStartDate;
        }
      }
      elseif ($membershipTypeDetails['duration_unit'] == 'month') {
        //here start date is always from start of the joining
        //month irrespective when you join during the month,
        //so if you join on 1 Jan or 15 Jan your start
        //date will always be 1 Jan
        if (!$startDate) {
          $actualStartDate = $startDate = $year . '-' . $month . '-01';
        }
      }
    }

    //calculate end date if it is not passed by user
    if (!$endDate) {
      //end date calculation
      $date = explode('-', $actualStartDate);
      $year = $date[0];
      $month = $date[1];
      $day = $date[2];

      switch ($membershipTypeDetails['duration_unit']) {
        case 'year':
          $year = $year + $membershipTypeDetails['duration_interval'];
          break;

        case 'month':
          $month = $month + $membershipTypeDetails['duration_interval'];

          if ($fixed_period_rollover) {
            //Fix Me: Currently we don't allow rollover if
            //duration interval is month
          }
          break;

        case 'day':
          $day = $day + $membershipTypeDetails['duration_interval'];

          if ($fixed_period_rollover) {
            //Fix Me: Currently we don't allow rollover if
            //duration interval is day
          }
          break;
      }

      if ($membershipTypeDetails['duration_unit'] == 'lifetime') {
        $endDate = NULL;
      }
      else {
        $endDate = date('Y-m-d', mktime(0, 0, 0, $month, $day - 1, $year));
      }
    }

    $reminderDate = NULL;
    $membershipDates = [];

    if (isset($membershipTypeDetails["renewal_reminder_day"]) &&
      $membershipTypeDetails["renewal_reminder_day"] &&
      $endDate
    ) {
      $reminderDate = self::calcReminderDate($endDate, $membershipTypeDetails['renewal_reminder_day']);
    }

    $dates = ['start_date' => 'startDate',
      'end_date' => 'endDate',
      'join_date' => 'joinDate',
      'reminder_date' => 'reminderDate',
    ];
    foreach ($dates as $varName => $valName) {
      $membershipDates[$varName] = CRM_Utils_Date::customFormat($$valName, '%Y%m%d');
    }

    if (!$endDate) {
      $membershipDates['reminder_date'] = NULL;
    }

    return $membershipDates;
  }

  /**
   * Function to calculate start date and end date for renewal membership
   *
   * @param int $membershipId
   *
   * @return Array array fo the start date, end date and join date of the membership
   * @static
   */
  static function getRenewalDatesForMembershipType($membershipId, $changeToday = NULL) {


    $params = ['id' => $membershipId];

    $membership = new CRM_Member_BAO_Membership();

    //$membership->copyValues( $params );
    $membership->id = $membershipId;
    $membership->find(TRUE);

    $membershipDetails = CRM_Member_BAO_Membership::getValues($params, $values);
    $statusID = $membershipDetails[$membershipId]->status_id;
    $membershipTypeDetails = self::getMembershipTypeDetails($membershipDetails[$membershipId]->membership_type_id);
    $statusDetails = CRM_Member_BAO_MembershipStatus::getMembershipStatus($statusID);

    if ($statusDetails['is_current_member'] == 1) {
      $startDate = $membershipDetails[$membershipId]->start_date;
      $date = explode('-', $membershipDetails[$membershipId]->end_date);
      $logStartDate = date('Y-m-d', mktime(0, 0, 0,
          (float) $date[1],
          (float)($date[2] + 1),
          (float) $date[0]
        ));
      $date = explode('-', $logStartDate);

      $year = $date[0];
      $month = $date[1];
      $day = $date[2];

      switch ($membershipTypeDetails['duration_unit']) {
        case 'year':
          $year = $year + $membershipTypeDetails['duration_interval'];
          break;

        case 'month':
          $month = $month + $membershipTypeDetails['duration_interval'];
          break;

        case 'day':
          $day = $day + $membershipTypeDetails['duration_interval'];
          break;
      }
      if ($membershipTypeDetails['duration_unit'] == 'lifetime') {
        $endDate = NULL;
      }
      else {
        $endDate = date('Y-m-d', mktime(0, 0, 0,
            $month,
            $day - 1,
            $year
          ));
      }
      $today = date('Y-m-d');
    }
    else {
      //get date in 'Ymd' format, CRM-5795
      $today = date('Ymd');
      if ($changeToday) {
        $today = CRM_Utils_Date::processDate($changeToday, NULL, FALSE, 'Ymd');
      }

      $rollover = FALSE;

      if (CRM_Utils_Array::value('period_type', $membershipTypeDetails) == 'rolling') {
        $startDate = $logStartDate = CRM_Utils_Date::mysqlToIso($today);
      }
      elseif (CRM_Utils_Array::value('period_type', $membershipTypeDetails) == 'fixed') {
        // Renewing expired membership is two step process.
        // 1. Renew the start date
        // 2. Renew the end date
        $yearValue = date('Y');
        $fixedStartDay = substr($membershipTypeDetails['fixed_period_start_day'], -2);
        $fixedStartMonth = substr($membershipTypeDetails['fixed_period_start_day'], 0, -2);
        $startDate = $logStartDate = date('Y-m-d', mktime(0, 0, 0,
          (float) $fixedStartMonth,
          (float) $fixedStartDay,
          $yearValue
        ));

        // before moving to the step 2, check if TODAY is in
        // rollover window.
        $rolloverDay = substr($membershipTypeDetails['fixed_period_rollover_day'], -2);
        $rolloverMonth = substr($membershipTypeDetails['fixed_period_rollover_day'], 0, -2);

        if (($rolloverMonth - $fixedStartMonth) < 0) {
          $rolloverDate = date('Ymd',
            mktime(0, 0, 0,
              (float) $rolloverMonth,
              (float) $rolloverDay,
              $yearValue + 1
            )
          );
        }
        else {
          $rolloverDate = date('Ymd',
            mktime(0, 0, 0,
              (float) $rolloverMonth,
              (float) $rolloverDay,
              $yearValue
            )
          );
        }

        if ($today > $rolloverDate) {
          $rollover = TRUE;
        }
      }

      // 2.
      $date = explode('-', $startDate);

      $year = (float) $date[0];
      $month = (float) $date[1];
      $day = (float) $date[2];

      switch ($membershipTypeDetails['duration_unit']) {
        case 'year':
          $year = $year + $membershipTypeDetails['duration_interval'];

          if ($rollover) {
            $year = $year + $membershipTypeDetails['duration_interval'];
          }
          break;

        case 'month':
          $month = $month + $membershipTypeDetails['duration_interval'];

          if ($rollover) {
            $month = $month + $membershipTypeDetails['duration_interval'];
          }
          break;

        case 'day':
          $day = $day + $membershipTypeDetails['duration_interval'];

          if ($rollover) {
            $day = $day + $membershipTypeDetails['duration_interval'];
          }
          break;
      }

      if ($membershipTypeDetails['duration_unit'] == 'lifetime') {
        $endDate = NULL;
      }
      else {
        $endDate = date('Y-m-d',
          mktime(0, 0, 0,
            $month,
            $day - 1,
            $year
          )
        );
      }
    }

    $membershipDates = [];
    $membershipDates['today'] = CRM_Utils_Date::customFormat($today, '%Y%m%d');
    $membershipDates['start_date'] = CRM_Utils_Date::customFormat($startDate, '%Y%m%d');
    $membershipDates['end_date'] = CRM_Utils_Date::customFormat($endDate, '%Y%m%d');

    if (CRM_Utils_Array::value("renewal_reminder_day", $membershipTypeDetails)) {
      $reminderDate = self::calcReminderDate($endDate, $membershipTypeDetails['renewal_reminder_day']);
      $membershipDates['reminder_date'] = CRM_Utils_Date::customFormat($reminderDate, '%Y%m%d');
    }

    $membershipDates['log_start_date'] = CRM_Utils_Date::customFormat($logStartDate, '%Y%m%d');

    return $membershipDates;
  }

  /**
   * Function to retrieve all Membership Types associated
   * with an Organization
   *
   * @param int $orgID  Id of Organization
   *
   * @return Array array of the details of membership types
   * @static
   */
  static function getMembershipTypesByOrg($orgID) {
    $membershipTypes = [];
    $dao = new CRM_Member_DAO_MembershipType();
    $dao->member_of_contact_id = $orgID;
    $dao->find();
    while ($dao->fetch()) {
      $membershipTypes[$dao->id] = [];
      CRM_Core_DAO::storeValues($dao, $membershipTypes[$dao->id]);
    }
    return $membershipTypes;
  }

  static function calcReminderDate($endDate, $renewalReminderDay) {
    $endTimestamp = strtotime($endDate);
    $reminderDate = '';

    // only set reminder date when end date is not passed
    // or reminder later will be sent after end date (originally, we expect sent before end date)
    if (CRM_REQUEST_TIME <= $endTimestamp) {
      if ($renewalReminderDay != 0) {
        $reminderDate = date('Y-m-d', $endTimestamp - 86400*($renewalReminderDay));
      }
      else {
        $reminderDate = date('Y-m-d', $endTimestamp);
      }
      // do not set reminder date when it's passed
      // or reminder letter will be sent to user
      if (CRM_REQUEST_TIME > strtotime($reminderDate)) {
        $reminderDate = '';
      }
    }
    return $reminderDate;
  }
}

