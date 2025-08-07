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






class CRM_Member_BAO_Membership extends CRM_Member_DAO_Membership {

  /**
   * static field for all the membership information that we can potentially import
   *
   * @var array
   * @static
   */
  static $_importableFields = NULL;
  function __construct() {
    parent::__construct();
  }

  /**
   * takes an associative array and creates a membership object
   *
   * the function extract all the params it needs to initialize the create a
   * membership object. the params array could contain additional unused name/value
   * pairs
   *
   * @param array  $params (reference ) an assoc array of name/value pairs
   * @param array $ids    the array that holds all the db ids
   *
   * @return object CRM_Member_BAO_Membership object
   * @access public
   * @static
   */
  static function &add(&$params, &$ids) {


    if (CRM_Utils_Array::value('membership', $ids)) {
      CRM_Utils_Hook::pre('edit', 'Membership', $ids['membership'], $params);
    }
    else {
      CRM_Utils_Hook::pre('create', 'Membership', NULL, $params);
    }

    // converting dates to mysql format
    if (isset($params['join_date'])) {
      $params['join_date'] = CRM_Utils_Date::isoToMysql($params['join_date']);
    }
    if (isset($params['start_date'])) {
      $params['start_date'] = CRM_Utils_Date::isoToMysql($params['start_date']);
    }
    if (CRM_Utils_Array::value('end_date', $params)) {
      $params['end_date'] = CRM_Utils_Date::isoToMysql($params['end_date']);
    }
    else {
      $params['end_date'] = 'null';
    }
    if (CRM_Utils_Array::value('reminder_date', $params)) {
      $params['reminder_date'] = CRM_Utils_Date::isoToMysql($params['reminder_date']);
    }
    else {
      $params['reminder_date'] = 'null';
    }

    if (!CRM_Utils_Array::value('is_override', $params)) {
      $params['is_override'] = 'null';
    }

    $membership = new CRM_Member_BAO_Membership();
    $membership->copyValues($params);

    $membership->id = CRM_Utils_Array::value('membership', $ids);

    $membership->save();
    $membership->free();

    $session = &CRM_Core_Session::singleton();

    //get the log start date.
    //it is set during renewal of membership.
    $logStartDate = CRM_Utils_array::value('log_start_date', $params);
    $logStartDate = ($logStartDate) ? CRM_Utils_Date::isoToMysql($logStartDate) : $membership->start_date;

    $membershipLog = ['membership_id' => $membership->id,
      'status_id' => $membership->status_id,
      'start_date' => $logStartDate,
      'end_date' => CRM_Utils_Date::isoToMysql($membership->end_date),
      'renewal_reminder_date' => $membership->reminder_date,
      'modified_id' => CRM_Utils_Array::value('userId', $ids),
      'modified_date' => date('Ymd'),
    ];


    CRM_Member_BAO_MembershipLog::add($membershipLog, CRM_Core_DAO::$_nullArray);

    // reset the group contact cache since smart groups might be affected due to this

    CRM_Contact_BAO_GroupContactCache::remove();

    if (CRM_Utils_Array::value('membership', $ids)) {
      CRM_Utils_Hook::post('edit', 'Membership', $membership->id, $membership);
    }
    else {
      CRM_Utils_Hook::post('create', 'Membership', $membership->id, $membership);
    }

    return $membership;
  }

  /**
   * Given the list of params in the params array, fetch the object
   * and store the values in the values array
   *
   * @param array   $params input parameters to find object
   * @param array   $values output values of the object
   * @param boolean $active do you want only active memberships to
   *                        be returned
   *
   * @return CRM_Member_BAO_Membership|null the found object or null
   * @access public
   * @static
   */
  static function &getValues(&$params, &$values, $active = FALSE) {
    if (empty($params)) {
      return NULL;
    }
    $membership = new CRM_Member_BAO_Membership();

    $membership->copyValues($params);
    $membership->find();
    $memberships = [];
    while ($membership->fetch()) {
      if ($active &&
        (!CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipStatus',
            $membership->status_id,
            'is_current_member'
          ))
      ) {
        continue;
      }

      CRM_Core_DAO::storeValues($membership, $values[$membership->id]);
      $memberships[$membership->id] = $membership;
    }

    return $memberships;
  }

  /**
   * takes an associative array and creates a membership object
   *
   * @param array    $params      (reference ) an assoc array of name/value pairs
   * @param array    $ids         the array that holds all the db ids
   * @param boolean  $callFromAPI Is this function called from API?
   *
   * @return object CRM_Member_BAO_Membership object
   * @access public
   * @static
   */
  static function &create(&$params, &$ids, $skipRedirect = FALSE, $activityType = 'Membership Signup') {
    // always cal status if is_override/skipStatusCal is not true.
    // giving respect to is_override during import.  CRM-4012

    // To skip status cal we should use 'skipStatusCal'.
    // eg pay later membership, membership update cron CRM-3984

    if (!CRM_Utils_Array::value('is_override', $params) &&
      !CRM_Utils_Array::value('skipStatusCal', $params)
    ) {

      $startDate = $endDate = $joinDate = NULL;
      if (isset($params['start_date'])) {
        $startDate = CRM_Utils_Date::customFormat($params['start_date'], '%Y%m%d');
      }
      if (isset($params['end_date'])) {
        if ($params['end_date'] == 'null') {
          $endDate = NULL;
        }
        else {
          $endDate = CRM_Utils_Date::customFormat($params['end_date'], '%Y%m%d');
        }
      }
      if (isset($params['join_date'])) {
        $joinDate = CRM_Utils_Date::customFormat($params['join_date'], '%Y%m%d');
      }


      //fix for CRM-3570, during import exclude the statuses those having is_admin = 1
      $excludeIsAdmin = CRM_Utils_Array::value('exclude_is_admin', $params, FALSE);

      //CRM-3724 always skip is_admin if is_override != true.
      if (!$excludeIsAdmin &&
        !CRM_Utils_Array::value('is_override', $params)
      ) {
        $excludeIsAdmin = TRUE;
      }

      $calcStatus = CRM_Member_BAO_MembershipStatus::getMembershipStatusByDate($startDate, $endDate, $joinDate,
        'today', $excludeIsAdmin
      );
      if (empty($calcStatus)) {
        if (!$skipRedirect) {
          // Redirect the form in case of error
          CRM_Core_Session::setStatus(ts('The membership cannot be saved.') .
            '<br/>' .
            ts('No valid membership status for given dates.')
          );
          return CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/view',
              "reset=1&force=1&cid={$params['contact_id']}&selectedChild=member"
            ));
        }
        // Return the error message to the api
        $error = [];
        $error['is_error'] = ts('The membership cannot be saved. No valid membership status for given dates');
        return $error;
      }
      $params['status_id'] = $calcStatus['id'];
    }

    // calc reminder date if not provided
    if (empty($params['reminder_date'])) {
      $calcDates = CRM_Member_BAO_MembershipType::getDatesForMembershipType($params['membership_type_id'], $params['join_date'], $params['start_date'], $params['end_date']);
      if (!empty($calcDates['reminder_date'])) {
        $params['reminder_date'] = $calcDates['reminder_date'];
      }
    }


    $transaction = new CRM_Core_Transaction();

    $membership = &self::add($params, $ids);

    if (is_a($membership, 'CRM_Core_Error')) {
      $transaction->rollback();
      return $membership;
    }

    // add custom field values
    if (CRM_Utils_Array::value('custom', $params)
      && is_array($params['custom'])
    ) {

      CRM_Core_BAO_CustomValueTable::store($params['custom'], 'civicrm_membership', $membership->id);
    }

    $params['membership_id'] = $membership->id;
    if (isset($ids['membership'])) {
      $ids['contribution'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipPayment',
        $ids['membership'],
        'contribution_id',
        'membership_id'
      );
    }
    //record contribution for this membership
    if (CRM_Utils_Array::value('contribution_status_id', $params)) {
      $contributionParams = [];
      $config = CRM_Core_Config::singleton();
      $contributionParams['currency'] = $config->defaultCurrency;
      $contributionParams['receipt_date'] = (CRM_Utils_Array::value('receipt_date', $params)) ? $params['receipt_date'] : 'null';
      $contributionParams['source'] = CRM_Utils_Array::value('contribution_source', $params);
      $contributionParams['non_deductible_amount'] = 'null';
      $recordContribution = ['contact_id', 'total_amount', 'receive_date', 'contribution_type_id', 'payment_instrument_id', 'trxn_id', 'invoice_id', 'is_test', 'contribution_status_id', 'check_number'];
      foreach ($recordContribution as $f) {
        $contributionParams[$f] = CRM_Utils_Array::value($f, $params);
      }


      $contribution = &CRM_Contribute_BAO_Contribution::create($contributionParams, $ids);

      //insert payment record for this membership
      if (!CRM_Utils_Array::value('contribution', $ids)) {

        $mpDAO = new CRM_Member_DAO_MembershipPayment();
        $mpDAO->membership_id = $membership->id;
        $mpDAO->contribution_id = $contribution->id;
        CRM_Utils_Hook::pre('create', 'MembershipPayment', NULL, $mpDAO);
        $mpDAO->save();
        CRM_Utils_Hook::post('create', 'MembershipPayment', $mpDAO->id, $mpDAO);
      }
    }

    // add activity record only during create mode and renew mode
    // also add activity if status changed CRM-3984 and CRM-2521
    if (!CRM_Utils_Array::value('membership', $ids) ||
      $activityType == 'Membership Renewal' ||
      CRM_Utils_Array::value('createActivity', $params)
    ) {

      if (CRM_Utils_Array::value('membership', $ids)) {
        CRM_Core_DAO::commonRetrieveAll('CRM_Member_DAO_Membership',
          'id',
          $membership->id,
          $data,
          ['contact_id', 'membership_type_id', 'source']
        );

        $membership->contact_id = $data[$membership->id]['contact_id'];
        $membership->membership_type_id = $data[$membership->id]['membership_type_id'];
        $membership->source = CRM_Utils_Array::value('source', $data[$membership->id]);
      }

      // since we are going to create activity record w/
      // individual contact as a target in case of on behalf signup,
      // so get the copy of organization id, CRM-5551
      $realMembershipContactId = $membership->contact_id;

      // create activity source = individual, target = org CRM-4027
      $targetContactID = NULL;
      if (CRM_Utils_Array::value('is_for_organization', $params)) {
        $targetContactID = $membership->contact_id;
        $membership->contact_id = $ids['userId'];
      }


      CRM_Activity_BAO_Activity::addActivity($membership, $activityType, $targetContactID);

      // we might created activity record w/ individual
      // contact as target so update membership object w/
      // original organization id, CRM-5551
      $membership->contact_id = $realMembershipContactId;
    }

    $transaction->commit();

    self::createRelatedMemberships($params, $membership);

    // do not add to recent items for import, CRM-4399
    if (!CRM_Utils_Array::value('skipRecentView', $params)) {



      $url = CRM_Utils_System::url('civicrm/contact/view/membership',
        "action=view&reset=1&id={$membership->id}&cid={$membership->contact_id}&context=home"
      );

      $membershipTypes = CRM_Member_PseudoConstant::membershipType();
      $title = CRM_Contact_BAO_Contact::displayName($membership->contact_id) . ' - ' . ts('Membership Type:') . ' ' . $membershipTypes[$membership->membership_type_id];


      $recentOther = [];
      if (CRM_Core_Permission::checkActionPermission('CiviMember', CRM_Core_Action::UPDATE)) {
        $recentOther['editUrl'] = CRM_Utils_System::url('civicrm/contact/view/membership',
          "action=update&reset=1&id={$membership->id}&cid={$membership->contact_id}&context=home"
        );
      }
      if (CRM_Core_Permission::checkActionPermission('CiviMember', CRM_Core_Action::DELETE)) {
        $recentOther['deleteUrl'] = CRM_Utils_System::url('civicrm/contact/view/membership',
          "action=delete&reset=1&id={$membership->id}&cid={$membership->contact_id}&context=home"
        );
      }

      // add the recently created Membership
      CRM_Utils_Recent::add($title,
        $url,
        $membership->id,
        'Membership',
        $membership->contact_id,
        NULL,
        $recentOther
      );
    }

    return $membership;
  }

  /**
   * Function to check the membership extended through relationship
   *
   * @param int $membershipId membership id
   * @param int $contactId    contact id
   *
   * @return Array    array of contact_id of all related contacts.
   * @static
   */
  static function checkMembershipRelationship($membershipId, $contactId, $action = CRM_Core_Action::ADD) {
    $contacts = [];
    $membershipTypeID = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership', $membershipId, 'membership_type_id');


    $membershipType = CRM_Member_BAO_MembershipType::getMembershipTypeDetails($membershipTypeID);

    $relationships = [];
    if (isset($membershipType['relationship_type_id'])) {
      $relationships = CRM_Contact_BAO_Relationship::getRelationship($contactId,
        CRM_Contact_BAO_Relationship::CURRENT
      );
      if ($action & CRM_Core_Action::UPDATE) {
        $pastRelationships = CRM_Contact_BAO_Relationship::getRelationship($contactId,
          CRM_Contact_BAO_Relationship::PAST
        );
        $relationships = array_merge($relationships, $pastRelationships);
      }
    }

    if (!empty($relationships)) {

      // check for each contact relationships
      foreach ($relationships as $values) {
        //get details of the relationship type
        $relType = ['id' => $values['civicrm_relationship_type_id']];
        $relValues = [];
        CRM_Contact_BAO_RelationshipType::retrieve($relType, $relValues);
        // Check if contact's relationship type exists in membership type
        $relTypeDirs = [];
        $relTypeIds = explode(CRM_Core_DAO::VALUE_SEPARATOR, $membershipType['relationship_type_id']);
        $relDirections = explode(CRM_Core_DAO::VALUE_SEPARATOR, $membershipType['relationship_direction']);
        $bidirectional = FALSE;
        foreach ($relTypeIds as $key => $value) {
          $relTypeDirs[] = $value . "_" . $relDirections[$key];
          if (in_array($value, $relType) &&
            $relValues['name_a_b'] == $relValues['name_b_a']
          ) {
            $bidirectional = TRUE;
            break;
          }
        }
        $relTypeDir = $values['civicrm_relationship_type_id'] . '_' . $values['rtype'];
        if ($bidirectional || in_array($relTypeDir, $relTypeDirs)) {
          // $values['status'] is going to have value for
          // current or past relationships.
          $contacts[$values['cid']] = $values['status'];
        }
      }
    }

    return $contacts;
  }

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. We'll tweak this function to be more
   * full featured over a period of time. This is the inverse function of
   * create.  It also stores all the retrieved values in the default array
   *
   * @param array $params   (reference ) an assoc array of name/value pairs
   * @param array $defaults (reference ) an assoc array to hold the name / value pairs
   *                        in a hierarchical manner
   * @param array $ids      (reference) the array that holds all the db ids
   *
   * @return object CRM_Member_BAO_Membership object
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
    $membership = new CRM_Member_DAO_Membership();

    $membership->copyValues($params);

    if ($membership->find(TRUE)) {
      CRM_Core_DAO::storeValues($membership, $defaults);

      //get the membership status and type values.
      $statusANDType = self::getStatusANDTypeVaues($membership->id);
      foreach (['status', 'membership_type'] as $fld) {
        $defaults[$fld] = CRM_Utils_Array::value($fld, $statusANDType[$membership->id]);
      }
      if (CRM_Utils_Array::value('is_current_member', $statusANDType[$membership->id])) {
        $defaults['active'] = TRUE;
      }

      $membership->free();

      return $membership;
    }

    return NULL;
  }

  static function getStatusANDTypeVaues($membershipId) {
    $values = [];
    if (!$membershipId) {
      return $values;
    }
    $sql = '
    SELECT  membership.id as id,
            status.id as status_id,
            status.label as status,
            status.is_current_member as is_current_member,
            type.id as membership_type_id,
            type.name as membership_type
      FROM  civicrm_membership membership
INNER JOIN  civicrm_membership_status status ON ( status.id = membership.status_id )
INNER JOIN  civicrm_membership_type type ON ( type.id = membership.membership_type_id )
     WHERE  membership.id = %1';
    $dao = CRM_Core_DAO::executeQuery($sql, [1 => [$membershipId, 'Positive']]);
    $properties = ['status', 'status_id', 'membership_type', 'membership_type_id', 'is_current_member'];
    while ($dao->fetch()) {
      foreach ($properties as $property) {
        $values[$dao->id][$property] = $dao->$property;
      }
    }

    return $values;
  }

  /**
   * Function to delete membership.
   *
   * @param int $membershipId membership id that needs to be deleted
   *
   * @static
   *
   * @return $results   no of deleted Membership on success, false otherwise
   * @access public
   */
  static function deleteMembership($membershipId) {

    
    // find related contribution
    $relatedContributions = [];
    $membershipPayment = new CRM_Member_DAO_MembershipPayment();
    $membershipPayment ->membership_id = $membershipId;
    $membershipPayment->find();
    while ($membershipPayment->fetch()) {
      $relatedContributions[] = $membershipPayment->contribution_id;
    }

    $transaction = new CRM_Core_Transaction();
    $results = NULL;
    //delete activity record
    $activityTypes = CRM_Core_PseudoConstant::activityType(TRUE, FALSE, FALSE, 'name');
    $membershipActivityTypeId = array_search('Membership Renewal', $activityTypes);


    $params = [
      'source_record_id' => $membershipId,
      'activity_type_id' => [
        array_search('Membership Signup', $activityTypes),
        array_search('Membership Renewal', $activityTypes),
      ],
    ];

    CRM_Activity_BAO_Activity::deleteActivity($params);

    
    // #28574, do not delete membersihp payment
    // we need to do this since the cascaded constraints
    // we need to keep payment record on database in case accounting issue
    #self::deleteMembershipPayment($membershipId);


    $membership = new CRM_Member_DAO_Membership();
    $membership->id = $membershipId;
    $membership->find(TRUE);
    $assigneeId = $membership->contact_id;
    $results = $membership->delete();
    $transaction->commit();

    // add activity to "Membership Renewal"
    $userID = CRM_Core_Session::singleton()->get('userID');
    $subject = ts('Deleted Member(s): %1', [1 => $id]);
    if (!empty($relatedContributions)) {
      $membership->contributions = $relatedContributions;
      $line = ts('Contribution ID').' '.CRM_Utils_Array::implode(',', $relatedContributions);
      $subject .= ts('%1 reserved', [1 => $line]);
    }
    $statusId = CRM_Core_OptionGroup::getValue('activity_status', 'Completed', 'name');
    $activityParams = [
      'activity_type_id' => $membershipActivityTypeId,
      'activity_date_time' => date('Y-m-d H:i:s'),
      'status_id' => $statusId,
      'subject' => $subject,
      'assignee_contact_id' => $assigneeId,
      'source_contact_id' => $userID,
    ];
    CRM_Activity_BAO_Activity::create($activityParams);

    // delete the recently created Membership

    $membershipRecent = [
      'id' => $membershipId,
      'type' => 'Membership',
    ];
    CRM_Utils_Recent::del($membershipRecent);

    return $results;
  }

  /**
   * Function to obtain active/inactive memberships from the list of memberships passed to it.
   *
   * @param array  $memberships membership records
   * @param string $status      active or inactive
   *
   * @return array $actives array of memberships based on status
   * @static
   * @access public
   */
  static function activeMembers($memberships, $status = 'active') {
    $actives = [];
    if ($status == 'active') {
      foreach ($memberships as $f => $v) {
        if (CRM_Utils_Array::value('active', $v)) {
          $actives[$f] = $v;
        }
      }
      return $actives;
    }
    elseif ($status == 'inactive') {
      foreach ($memberships as $f => $v) {
        if (!CRM_Utils_Array::value('active', $v)) {
          $actives[$f] = $v;
        }
      }
      return $actives;
    }
    return NULL;
  }

  /**
   * Function to build Membership  Block in Contribution Pages
   *
   * @param object  $form                      form object
   * @param int     $pageId                    contribution page id
   * @param boolean $formItems
   * @param int     $selectedMembershipTypeID  selected membership id
   * @param boolean $thankPage                 thank you page
   * @param boolean $memContactId              contact who is to be
   * checked for having a current membership for a particular membership
   *
   * @static
   */
  static function buildMembershipBlock(&$form,
    $pageID,
    $formItems = FALSE,
    $selectedMembershipTypeID = NULL,
    $thankPage = FALSE,
    $isTest = NULL,
    $memberContactId = NULL
  ) {


    $separateMembershipPayment = FALSE;
    if ($form->_membershipBlock) {



      if (!$memberContactId) {
        $session = &CRM_Core_Session::singleton();
        $cid = $session->get('userID');
      }
      else {
        $cid = $memberContactId;
      }

      $membershipBlock = $form->_membershipBlock;
      $membershipTypeIds = [];
      $membershipTypes = [];
      $radio = [];

      $separateMembershipPayment = CRM_Utils_Array::value('is_separate_payment', $membershipBlock);
      if ($membershipBlock['membership_types']) {
        $membershipTypeIds = explode(',', $membershipBlock['membership_types']);
      }

      if (!empty($membershipTypeIds)) {
        //set status message if wrong membershipType is included in membershipBlock
        if (isset($form->_mid)) {
          $membershipTypeID = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership',
            $form->_mid,
            'membership_type_id'
          );
          if (!in_array($membershipTypeID, $membershipTypeIds)) {
            CRM_Core_Session::setStatus(ts("Oops. The membership you're trying to renew appears to be invalid. Contact your site administrator if you need assistance. If you continue, you will be issued a new membership."));
          }
        }

        $membershipTypeValues = self::buildMembershipTypeValues($form, $membershipTypeIds);

        foreach ($membershipTypeIds as $value) {
          $memType = $membershipTypeValues[$value];
          if ($selectedMembershipTypeID != NULL) {
            if ($memType['id'] == $selectedMembershipTypeID) {
              $form->assign('minimum_fee',
                CRM_Utils_Array::value('minimum_fee', $memType)
              );
              $form->assign('membership_name', $memType['name']);
              if (!$thankPage && $cid) {
                $membership = new CRM_Member_DAO_Membership();
                $membership->contact_id = $cid;
                $membership->membership_type_id = $memType['id'];
                if ($membership->find(TRUE)) {
                  $form->assign("renewal_mode", TRUE);
                  $mem['current_membership'] = $membership->end_date;
                }
              }
              $membershipTypes[] = $memType;
            }
          }
          elseif ($memType['is_active']) {
            $radio[$memType['id']] = $form->createElement('radio', NULL, NULL, NULL, $memType['id'], NULL);
            if ($cid) {
              $membership = new CRM_Member_DAO_Membership();
              $membership->contact_id = $cid;
              $membership->membership_type_id = $memType['id'];

              //show current membership, skip pending membership record,
              //because we take first membership record id for renewal
              $membership->whereAdd('status_id != 5');

              if (!is_null($isTest)) {
                $membership->is_test = $isTest;
              }

              //CRM-4297
              $membership->orderBy('end_date DESC');

              if ($membership->find(TRUE)) {
                $form->assign("renewal_mode", TRUE);
                $memType['current_membership'] = $membership->end_date;
              }
            }
            $membershipTypes[] = $memType;
          }
        }
      }

      $form->assign('showRadio', $formItems);
      if ($formItems) {
        if (!$membershipBlock['is_required']) {
          $form->assign('showRadioNoThanks', TRUE);
          $radio[''] = $form->createElement('radio', NULL, NULL, NULL, 'no_thanks', NULL);
        }
        elseif ($membershipBlock['is_required'] && count($radio) == 1) {
          $form->assign('singleMembership', TRUE);
        }
        $form->addGroup($radio, 'selectMembership', NULL);
        $form->addRule('selectMembership', ts("Please select one of the memberships"), 'required');
      }

      $form->assign('membershipBlock', $membershipBlock);
      $form->assign('membershipTypes', $membershipTypes);
    }

    return $separateMembershipPayment;
  }

  /**
   * Function to return Membership  Block info in Contribution Pages
   *
   * @param int $pageId contribution page id
   *
   * @static
   */
  static function getMembershipBlock($pageID) {
    $membershipBlock = [];

    $dao = new CRM_Member_DAO_MembershipBlock();
    $dao->entity_table = 'civicrm_contribution_page';

    $dao->entity_id = $pageID;
    $dao->is_active = 1;
    if ($dao->find(TRUE)) {
      CRM_Core_DAO::storeValues($dao, $membershipBlock);
    }
    else {
      return NULL;
    }

    return $membershipBlock;
  }

  /**
   * Function to return current membership of given contacts
   *
   * @param int $contactID  contact id
   * @static
   */
  static function getContactMembership($contactID, $memType, $isTest, $membershipId = NULL) {
    $dao = new CRM_Member_DAO_Membership();
    if ($membershipId) {
      $dao->id = $membershipId;
    }
    $dao->contact_id = $contactID;
    $dao->membership_type_id = $memType;

    //fetch proper membership record.
    if ($isTest) {
      $dao->is_test = $isTest;
    }
    else {
      $dao->whereAdd('is_test IS NULL OR is_test = 0');
    }

    //avoid pending membership as current memebrship: CRM-3027

    $pendingStatusId = array_search('Pending', CRM_Member_PseudoConstant::membershipStatus());
    $dao->whereAdd("status_id != $pendingStatusId AND join_date IS NOT NULL");

    // order by start date to find mos recent membership first, CRM-4545
    $dao->orderBy('start_date DESC');

    if ($dao->find(TRUE)) {
      $membership = [];
      CRM_Core_DAO::storeValues($dao, $membership);

      $membership['is_current_member'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipStatus',
        $membership['status_id'],
        'is_current_member', 'id'
      );
      return $membership;
    }
    return FALSE;
  }

  /**
   * Combine all the importable fields from the lower levels object
   *
   * @param string  $contactType contact type
   * @param boolean $status
   *
   * @return array array of importable Fields
   * @access public
   */
  static function &importableFields($contactType = 'Individual', $status = TRUE) {
    if (!self::$_importableFields) {
      if (!self::$_importableFields) {
        self::$_importableFields = [];
      }

      if (!$status) {
        $fields = ['' => ['title' => '- ' . ts('do not import') . ' -']];
      }
      else {
        $fields = ['' => ['title' => '- ' . ts('Membership Fields') . ' -']];
      }

      $note = CRM_Core_DAO_Note::import();
      $tmpFields = CRM_Member_DAO_Membership::import();
      $tmpFields['membership_contact_id']['title'] = ts('Contacts') . '::' .$tmpFields['membership_contact_id']['title'];
      unset($tmpFields['option_value']);
      $optionFields = CRM_Core_OptionValue::getFields($mode = 'member');

      $contactFields = [];
      $tmpContactFields = CRM_Contact_BAO_Contact::importableFields($contacType, NULL);
      $tmpContactFields = CRM_Core_BAO_Address::validateAddressOptions($tmpContactFields);
      $contactFieldsIgnore = ['id', 'note', 'do_not_import', 'contact_sub_type', 'group_name', 'tag_name'];


      // Using new Dedupe rule.
      $ruleParams = [
        'contact_type' => $contactType,
        'level' => 'Strict',
      ];
      $dupeFields = CRM_Dedupe_BAO_Rule::dedupeRuleFieldsMapping($ruleParams);
      if (!is_array($dupeFields)) {
        $dupeFields = [];
      }

      foreach ($tmpContactFields as $fieldName => $fieldData) {
        $fieldName = trim($fieldName);
        if (in_array($fieldName, $contactFieldsIgnore)) {
          continue;
        }
        $index = $fieldName;
        $contactFields[$index] = $fieldData;
        $contactFields[$index]['title'] = ts('Contacts') . '::' .$contactFields[$index]['title'];
      }

      $fields = array_merge($fields, $tmpFields);
      $fields = array_merge($fields, CRM_Core_BAO_CustomField::getFieldsForImport('Membership'));
      $fields = array_merge($fields, $optionFields);
      $fields = array_merge($fields, $note);
      $fields = array_merge($fields, $contactFields);
      self::$_importableFields = $fields;
    }
    return self::$_importableFields;
  }

  /**
   * function to get all exportable fields
   *
   * @retun array return array of all exportable fields
   */
  static function &exportableFields() {

    $expFieldMembership = CRM_Member_DAO_Membership::export();
    $expFieldsMemType = CRM_Member_DAO_MembershipType::export();
    $fields = array_merge($expFieldMembership, $expFieldsMemType);
    $fields = array_merge($fields, $expFieldMembership);
    $membershipStatus = ['membership_status' => ['title' => 'Membership Status',
        'name' => 'membership_status',
        'type' => CRM_Utils_Type::T_STRING,
        'where' => 'civicrm_membership_status.name',
      ]];
    //CRM-6161 fix for customdata export
    $fields = array_merge($fields, $membershipStatus, CRM_Core_BAO_CustomField::getFieldsForImport('Membership'));
    return $fields;
  }

  /**
   * Function to get membership joins/renewals for a specified membership
   * type.  Specifically, retrieves a count of memberships whose start_date
   * is within a specified date range.  Dates match the regexp
   * "yyyy(mm(dd)?)?".  Omitted portions of a date match the earliest start
   * date or latest end date, i.e., 200803 is March 1st as a start date and
   * March 31st as an end date.
   *
   * @param int    $membershipTypeId  membership type id
   * @param int    $startDate         date on which to start counting
   * @param int    $endDate           date on which to end counting
   * @param bool   $isTest             if true, membership is for a test site
   *
   * @return returns the number of members of type $membershipTypeId whose
   *         start_date is between $startDate and $endDate
   */
  static function getMembershipStarts($membershipTypeId, $startDate, $endDate, $isTest = 0) {
    $query = "SELECT count(civicrm_membership.id) as member_count
  FROM   civicrm_membership left join civicrm_membership_status on ( civicrm_membership.status_id = civicrm_membership_status.id )
WHERE  membership_type_id = %1 AND start_date >= '$startDate' AND start_date <= '$endDate' 
AND civicrm_membership_status.is_current_member = 1
AND civicrm_membership.contact_id NOT IN (SELECT id FROM civicrm_contact WHERE is_deleted = 1)
AND is_test = %2";
    $params = [1 => [$membershipTypeId, 'Integer'],
      2 => [$isTest, 'Boolean'],
    ];
    $memberCount = CRM_Core_DAO::singleValueQuery($query, $params);
    return (int)$memberCount;
  }

  /**
   * Function to get a count of membership for a specified membership type,
   * optionally for a specified date.  The date must have the form yyyymmdd.
   *
   * If $date is omitted, this function counts as a member anyone whose
   * membership status_id indicates they're a current member.
   * If $date is given, this function counts as a member anyone who:
   *  -- Has a start_date before $date and end_date after $date, or
   *  -- Has a start_date before $date and is currently a member, as indicated
   *     by the the membership's status_id.
   * The second condition takes care of records that have no end_date.  These
   * are assumed to be lifetime memberships.
   *
   * @param int    $membershipTypeId   membership type id
   * @param string $date               the date for which to retrieve the count
   * @param bool   $isTest             if true, membership is for a test site
   *
   * @return returns the number of members of type $membershipTypeId as of
   *         $date.
   */
  static function getMembershipCount($membershipTypeId, $date = NULL, $isTest = 0) {
    if (!is_null($date) && !preg_match('/^\d{8}$/', $date)) {
       return CRM_Core_Error::statusBounce(ts('Invalid date "%1" (must have form yyyymmdd).', [1 => $date]));
    }

    $params = [1 => [$membershipTypeId, 'Integer'],
      2 => [$isTest, 'Boolean'],
    ];
    $query = "SELECT  count(civicrm_membership.id ) as member_count
FROM   civicrm_membership left join civicrm_membership_status on ( civicrm_membership.status_id = civicrm_membership_status.id  )
WHERE  civicrm_membership.membership_type_id = %1 
AND civicrm_membership.contact_id NOT IN (SELECT id FROM civicrm_contact WHERE is_deleted = 1)
AND civicrm_membership.is_test = %2";
    if (!$date) {
      $query .= " AND civicrm_membership_status.is_current_member = 1";
    }
    else {
      $date = substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2);
      $query .= " AND civicrm_membership.start_date <= '$date' AND civicrm_membership_status.is_current_member = 1";
    }
    $memberCount = CRM_Core_DAO::singleValueQuery($query, $params);
    return (int)$memberCount;
  }

  /**
   * Function check the status of the membership before adding membership for a contact
   *
   * @param int $contactId contact id
   *
   * @return
   */
  static function statusAvilability($contactId) {

    $membership = new CRM_Member_DAO_MembershipStatus();
    $membership->whereAdd('1');
    $count = $membership->count();

    if (!$count) {
      $session = CRM_Core_Session::singleton();
      CRM_Core_Session::setStatus(ts('There are no status present, You cannot add membership.'));
      return CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/view', "reset=1&force=1&cid={$contactId}&selectedChild=member"));
    }
  }

  /**
   * Process the Memberships
   *
   * @param array  $membershipParams array of membership fields
   * @param int    $contactID        contact id
   * @param object $form             form object
   *
   * @return void
   * @access public
   */
  public static function postProcessMembership($membershipParams, $contactID, &$form, &$premiumParams) {
    $tempParams = $membershipParams;
    $paymentDone = FALSE;
    $result = NULL;
    $isTest = CRM_Utils_Array::value('is_test', $membershipParams, FALSE);
    $form->assign('membership_assign', TRUE);

    $form->set('membershipTypeID', $membershipParams['selectMembership']);



    $membershipTypeID = $membershipParams['selectMembership'];
    $membershipDetails = self::buildMembershipTypeValues($form, $membershipTypeID);
    $form->assign('membership_name', $membershipDetails['name']);

    $minimumFee = CRM_Utils_Array::value('minimum_fee', $membershipDetails);

    $contributionTypeId = NULL;

    if ($form->_values['amount_block_is_active']) {
      $contributionTypeId = $form->_values['contribution_type_id'];
    }
    else {
      $paymentDone = TRUE;
      $params['amount'] = $minimumFee;
      $contributionTypeId = $membershipDetails['contribution_type_id'];
    }

    //amount must be greater than zero for
    //adding contribution record  to contribution table.
    //this condition is arises when separate membership payment is
    //enable and contribution amount is not selected. fix for CRM-3010

    if ($form->_amount > 0.0) {
      $result = CRM_Contribute_BAO_Contribution_Utils::processConfirm($form, $membershipParams,
        $premiumParams, $contactID,
        $contributionTypeId,
        'membership'
      );
    }
    else {
      $form->assign('is_pay_later', 0);
      // create the CMS contact here since we normally do this under processConfirm
      CRM_Contribute_BAO_Contribution_Utils::createCMSUser($membershipParams,
        $membershipParams['cms_contactID'],
        'email-' . $form->_bltID
      );
    }

    $errors = [];
    if (is_a($result[1], 'CRM_Core_Error')) {
      $errors[1] = CRM_Core_Error::getMessages($result[1]);
    }
    else {
      $contribution[1] = $result[1];
    }


    $memBlockDetails = CRM_Member_BAO_Membership::getMembershipBlock($form->_id);
    if ($memBlockDetails['is_separate_payment'] && !$paymentDone) {

      $contributionType = new CRM_Contribute_DAO_ContributionType();
      $contributionType->id = $membershipDetails['contribution_type_id'];
      if (!$contributionType->find(TRUE)) {
        CRM_Core_Error::fatal("Could not find a system table");
      }
      $tempParams['amount'] = $minimumFee;
      $invoiceID = md5(uniqid((string)rand(), TRUE));
      $tempParams['invoiceID'] = $invoiceID;

      //we don't allow recurring membership.CRM-3781.
      if (CRM_Utils_Array::value('is_recur', $tempParams)) {
        $tempParams['is_recur'] = 0;
      }

      $result = NULL;
      if ($form->_values['is_monetary'] && !$form->_params['is_pay_later']) {

        $payment = &CRM_Core_Payment::singleton($form->_mode, $form->_paymentProcessor, $form);

        if ($form->_contributeMode == 'express') {
          $result = &$payment->doExpressCheckout($tempParams);
        }
        else {
          $result = &$payment->doDirectPayment($tempParams);
        }
      }

      if (is_a($result, 'CRM_Core_Error')) {
        $errors[2] = CRM_Core_Error::getMessages($result);
      }
      else {
        //assign receive date when separate membership payment
        //and contribution amount not selected.
        if ($form->_amount == 0) {
          $now = date('YmdHis');
          $form->_params['receive_date'] = $now;
          $receiveDate = CRM_Utils_Date::mysqlToIso($now);
          $form->set('params', $form->_params);
          $form->assign('receive_date', $receiveDate);
        }

        $form->set('membership_trx_id', $result['trxn_id']);
        $form->set('membership_amount', $minimumFee);

        $form->assign('membership_trx_id', $result['trxn_id']);
        $form->assign('membership_amount', $minimumFee);

        // we dont need to create the user twice, so lets disable cms_create_account
        // irrespective of the value, CRM-2888
        $tempParams['cms_create_account'] = 0;

        $pending = $form->_params['is_pay_later'] ? TRUE : FALSE;

        //set this variable as we are not creating pledge for
        //separate membership payment contribution.
        //so for differentiating membership contributon from
        //main contribution.
        $form->_params['separate_membership_payment'] = 1;

        $contribution[2] = CRM_Contribute_Form_Contribution_Confirm::processContribution($form,
          $tempParams,
          $result,
          $contactID,
          $contributionType,
          FALSE,
          $pending
        );
      }
    }

    $index = $memBlockDetails['is_separate_payment'] ? 2 : 1;

    if (!CRM_Utils_Array::value($index, $errors)) {

      $membership = self::renewMembership($contactID, $membershipTypeID,
        $isTest, $form, NULL,
        CRM_Utils_Array::value('cms_contactID', $membershipParams)
      );
      if (isset($contribution[$index])) {
        //insert payment record

        $dao = new CRM_Member_DAO_MembershipPayment();
        $dao->membership_id = $membership->id;
        $dao->contribution_id = $contribution[$index]->id;
        //Fixed for avoiding duplicate entry error when user goes
        //back and forward during payment mode is notify
        if (!$dao->find(TRUE)) {
          CRM_Utils_Hook::pre('create', 'MembershipPayment', NULL, $dao);
          $dao->save();
          CRM_Utils_Hook::post('create', 'MembershipPayment', $dao->id, $dao);
        }
      }
    }


    CRM_Core_BAO_CustomValueTable::postProcess($form->_params,
      CRM_Core_DAO::$_nullArray,
      'civicrm_membership',
      $membership->id,
      'Membership'
    );

    if (!empty($errors)) {
      foreach ($errors as $error) {
        if (is_string($error)) {
          $message[] = $error;
        }
      }
      $message = ts("Payment Processor Error message") . ": " . CRM_Utils_Array::implode('<br/>', $message);
      $session = CRM_Core_Session::singleton();
      $session->setStatus($message);
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contribute/transact',
          "_qf_Main_display=true&qfKey={$form->_params['qfKey']}"
        ));
    }

    $form->_params['membershipID'] = $membership->id;

    if ($form->_contributeMode == 'notify' || $form->_contributeMode == 'iframe') {
      if ($form->_values['is_monetary'] && $form->_amount > 0.0 && !$form->_params['is_pay_later']) {
        // before leave for transfer, trigger hook
        CRM_Utils_Hook::postProcess(get_class($form), $form);
        $payment = &CRM_Core_Payment::singleton($form->_mode, $form->_paymentProcessor, $form);
        $payment->doTransferCheckout($form->_params, 'contribute');
      }
    }

    $form->_values['membership_id'] = $membership->id;
    if (isset($contribution[$index]->id)) {
      $form->_values['contribution_id'] = $contribution[$index]->id;
    }
    //finally send an email receipt

    CRM_Contribute_BAO_ContributionPage::sendMail($contactID,
      $form->_values,
      $isTest
    );
  }

  /**
   * This method will renew / create the membership depending on
   * whether the given contact has membership or not. And will add
   * the modified dates for mebership and in the log table.
   *
   * @param int     $contactID           id of the contact
   * @param int     $membershipTypeID    id of the membership type
   * @param boolean $is_test             if this is test contribution or live contribution
   * @param object  $form                form object
   * @param array   $ipnParams           array of name value pairs, to be used (for e.g source) when $form not present
   * @param int     $modifiedID          individual contact id in case of On Behalf signup (CRM-4027 )
   *
   * @return object $membership          object of membership
   *
   * @static
   * @access public
   *
   **/
  static function renewMembership($contactID, $membershipTypeID, $is_test,
    &$form, $changeToday = NULL, $modifiedID = NULL
  ) {

    $statusFormat = '%Y-%m-%d';
    $format = '%Y%m%d';
    $ids = [];

    //get all active statuses of membership.

    $allStatus = CRM_Member_PseudoConstant::membershipStatus();

    // check is it pending. - CRM-4555
    $pending = FALSE;
    if (($form->_contributeMode == 'notify' || $form->_contributeMode == 'iframe' || $form->_params['is_pay_later']) &&
      ($form->_values['is_monetary'] && $form->_amount > 0.0)
    ) {
      $pending = TRUE;
    }

    //decide status here, if needed.
    $updateStatusId = NULL;

    if ($currentMembership =
      CRM_Member_BAO_Membership::getContactMembership($contactID, $membershipTypeID, $is_test, $form->_membershipId)
    ) {
      $activityType = 'Membership Renewal';
      $form->set("renewal_mode", TRUE);

      // Do NOT do anything.
      //1. membership with status : PENDING/CANCELLED (CRM-2395)
      //2. Paylater/IPN renew. CRM-4556.
      if ($pending || in_array($currentMembership['status_id'], [array_search('Pending', $allStatus),
            array_search('Cancelled', $allStatus),
          ])) {
        $membership = new CRM_Member_DAO_Membership();
        $membership->id = $currentMembership['id'];
        $membership->find(TRUE);
        return $membership;
      }

      //we might renew expired membership, CRM-6277
      if (!$changeToday) {
        if ($form->get('renewDate')) {
          $changeToday = $form->get('renewDate');
        }
        elseif (get_class($form) == 'CRM_Contribute_Form_Contribution_Confirm') {
          $changeToday = date('YmdHis');
        }
      }

      // Check and fix the membership if it is STALE
      self::fixMembershipStatusBeforeRenew($currentMembership, $changeToday);

      // Now Renew the membership
      if (!$currentMembership['is_current_member']) {
        // membership is not CURRENT

        $dates = CRM_Member_BAO_MembershipType::getRenewalDatesForMembershipType($currentMembership['id'],
          $changeToday
        );

        $currentMembership['join_date'] = CRM_Utils_Date::customFormat($currentMembership['join_date'], $format);
        $currentMembership['start_date'] = CRM_Utils_Array::value('start_date', $dates);
        $currentMembership['end_date'] = CRM_Utils_Array::value('end_date', $dates);
        $currentMembership['reminder_date'] = CRM_Utils_Array::value('reminder_date', $dates);
        $currentMembership['is_test'] = $is_test;

        if ($form->_params['membership_source']) {
          $currentMembership['source'] = $form->_params['membership_source'];
        }
        elseif ($form->_values['title']) {
          $currentMembership['source'] = ts('Online Contribution:') . ' ' . $form->_values['title'];
        }
        else {
          $currentMembership['source'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership',
            $currentMembership['id'],
            'source'
          );
        }

        if (CRM_Utils_Array::value('id', $currentMembership)) {
          $ids['membership'] = $currentMembership['id'];
        }
        $memParams = $currentMembership;

        //set the log start date.
        $memParams['log_start_date'] = CRM_Utils_Date::customFormat($dates['log_start_date'], $format);
      }
      else {
        // CURRENT Membership
        $membership = new CRM_Member_DAO_Membership();
        $membership->id = $currentMembership['id'];
        $membership->find(TRUE);


        $dates = CRM_Member_BAO_MembershipType::getRenewalDatesForMembershipType($membership->id,
          $changeToday
        );

        // Insert renewed dates for CURRENT membership
        $memParams = [];
        $memParams['join_date'] = CRM_Utils_Date::isoToMysql($membership->join_date);
        $memParams['start_date'] = CRM_Utils_Date::isoToMysql($membership->start_date);
        $memParams['end_date'] = CRM_Utils_Array::value('end_date', $dates);
        $memParams['reminder_date'] = CRM_Utils_Array::value('reminder_date', $dates);

        //set the log start date.
        $memParams['log_start_date'] = CRM_Utils_Date::customFormat($dates['log_start_date'], $format);

        if (empty($membership->source)) {
          if (CRM_Utils_Array::value('membership_source', $form->_params)) {
            $currentMembership['source'] = $form->_params['membership_source'];
          }
          elseif (CRM_Utils_Array::value('title', $form->_values)) {
            $currentMembership['source'] = ts('Online Contribution:') . ' ' . $form->_values['title'];
          }
          else {
            $currentMembership['source'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership',
              $currentMembership['id'],
              'source'
            );
          }
        }

        if (CRM_Utils_Array::value('id', $currentMembership)) {
          $ids['membership'] = $currentMembership['id'];
        }
      }
      //CRM-4555
      if ($pending) {
        $updateStatusId = array_search('Pending', $allStatus);
      }
    }
    else {
      // NEW Membership

      $activityType = 'Membership Signup';
      $memParams = ['contact_id' => $contactID,
        'membership_type_id' => $membershipTypeID,
      ];

      if (!$pending) {

        $dates = CRM_Member_BAO_MembershipType::getDatesForMembershipType($membershipTypeID);

        $memParams['join_date'] = CRM_Utils_Array::value('join_date', $dates);
        $memParams['start_date'] = CRM_Utils_Array::value('start_date', $dates);
        $memParams['end_date'] = CRM_Utils_Array::value('end_date', $dates);
        $memParams['reminder_date'] = CRM_Utils_Array::value('reminder_date', $dates);


        $status = CRM_Member_BAO_MembershipStatus::getMembershipStatusByDate(CRM_Utils_Date::customFormat($dates['start_date'],
            $statusFormat
          ),
          CRM_Utils_Date::customFormat($dates['end_date'],
            $statusFormat
          ),
          CRM_Utils_Date::customFormat($dates['join_date'],
            $statusFormat
          ),
          'today', TRUE
        );
        $updateStatusId = CRM_Utils_Array::value('id', $status);
      }
      else {
        // if IPN/Pay-Later set status to: PENDING
        $updateStatusId = array_search('Pending', $allStatus);
      }

      if (CRM_Utils_Array::value('membership_source', $form->_params)) {
        $memParams['source'] = $form->_params['membership_source'];
      }
      else {
        $memParams['source'] = ts('Online Contribution:') . ' ' . $form->_values['title'];
      }

      $memParams['is_test'] = $is_test;
      $memParams['is_pay_later'] = $form->_params['is_pay_later'];
    }

    //CRM-4555
    //if we decided status here and want to skip status
    //calculation in create( ); than need to pass 'skipStatusCal'.
    if ($updateStatusId) {
      $memParams['status_id'] = $updateStatusId;
      $memParams['skipStatusCal'] = TRUE;
    }

    //since we are renewing,
    //make status override false.
    $memParams['is_override'] = FALSE;

    //CRM-4027, create log w/ individual contact.
    if ($modifiedID) {
      $ids['userId'] = $modifiedID;
      $memParams['is_for_organization'] = TRUE;
    }
    else {
      $ids['userId'] = $contactID;
    }

    $membership = &self::create($memParams, $ids, FALSE, $activityType);
    // not sure why this statement is here, seems quite odd :( - Lobo: 12/26/2010
    // related to: http://forum.civicrm.org/index.php/topic,11416.msg49072.html#msg49072
    $membership->find(TRUE);
    if (!empty($dates)) {
      $form->assign('mem_start_date',
        CRM_Utils_Date::customFormat($dates['start_date'], $format)
      );
      $form->assign('mem_end_date',
        CRM_Utils_Date::customFormat($dates['end_date'], $format)
      );
    }
    return $membership;
  }

  /**
   * Method to fix membership status of stale membership
   *
   * This method first checks if the membership is stale. If it is,
   * then status will be updated based on existing start and end
   * dates and log will be added for the status change.
   *
   * @param  array  $currentMembership   referance to the array
   *                                     containing all values of
   *                                     the current membership
   * @param  array  $changeToday         array of month, day, year
   *                                     values in case today needs
   *                                     to be customised, null otherwise
   *
   * @return void
   * @static
   */
  static function fixMembershipStatusBeforeRenew(&$currentMembership, $changeToday) {
    $today = NULL;
    if ($changeToday) {
      $today = CRM_Utils_Date::processDate($changeToday, NULL, FALSE, 'Y-m-d');
    }


    $status = CRM_Member_BAO_MembershipStatus::getMembershipStatusByDate(
      $currentMembership['start_date'],
      $currentMembership['end_date'],
      $currentMembership['join_date'],
      $today
    );


    if (empty($status) ||
      empty($status['id'])
    ) {
       return CRM_Core_Error::statusBounce(ts('Oops, it looks like there is no valid membership status corresponding to the membership start and end dates for this membership. Contact the site administrator for assistance.'));
    }

    $currentMembership['today_date'] = $today;

    if ($status['id'] !== $currentMembership['status_id']) {
      $memberDAO = new CRM_Member_BAO_Membership();
      $memberDAO->id = $currentMembership['id'];
      $memberDAO->find(TRUE);

      $memberDAO->status_id = $status['id'];
      $memberDAO->join_date = CRM_Utils_Date::isoToMysql($memberDAO->join_date);
      $memberDAO->start_date = CRM_Utils_Date::isoToMysql($memberDAO->start_date);
      $memberDAO->end_date = CRM_Utils_Date::isoToMysql($memberDAO->end_date);
      $memberDAO->save();
      CRM_Core_DAO::storeValues($memberDAO, $currentMembership);

      $memberDAO->free();

      $currentMembership['is_current_member'] = CRM_Core_DAO::getFieldValue(
        'CRM_Member_DAO_MembershipStatus',
        $currentMembership['status_id'],
        'is_current_member'
      );
      $format = '%Y%m%d';
      $logParams = ['membership_id' => $currentMembership['id'],
        'status_id' => $status['id'],
        'start_date' => CRM_Utils_Date::customFormat(
          $currentMembership['start_date'],
          $format
        ),
        'end_date' => CRM_Utils_Date::customFormat(
          $currentMembership['end_date'],
          $format
        ),
        'modified_id' => $currentMembership['contact_id'],
        'modified_date' => date('Ymd'),
        'renewal_reminder_date' => CRM_Utils_Date::customFormat(
          $currentMembership['reminder_date'],
          $format
        ),
      ];
      $dontCare = NULL;

      CRM_Member_BAO_MembershipLog::add($logParams, $dontCare);
    }
  }

  /**
   * Function to get the contribution page id from the membership record
   *
   * @param int membershipId membership id
   *
   * @return int $contributionPageId contribution page id
   * @access public
   * @static
   */
  static function getContributionPageId($membershipID) {
    $query = "
SELECT c.contribution_page_id as pageID
  FROM civicrm_membership_payment mp, civicrm_contribution c
 WHERE mp.contribution_id = c.id
   AND mp.membership_id = " . CRM_Utils_Type::escape($membershipID, 'Integer');

    return CRM_Core_DAO::singleValueQuery($query,
      CRM_Core_DAO::$_nullArray
    );
  }

  /**
   * Function to delete related memberships
   *
   * @param int $ownerMembershipId
   * @param int $contactId
   *
   * @return null
   * @static
   */
  static function deleteRelatedMemberships($ownerMembershipId, $contactId = NULL) {
    if (!$ownerMembershipId && !$contactId) {
      return;
    }

    $membership = new CRM_Member_DAO_Membership();
    $membership->owner_membership_id = $ownerMembershipId;

    if ($contactId) {
      $membership->contact_id = $contactId;
    }

    $membership->find();
    while ($membership->fetch()) {
      //delete related first and then delete parent.
      self::deleteRelatedMemberships($membership->id);
      self::deleteMembership($membership->id);
    }
    $membership->free();
  }

  /**
   * Function to updated related memberships
   *
   * @param int   $ownerMembershipId owner Membership Id
   * @param array $params            formatted array of key => value..
   * @static
   */
  static function updateRelatedMemberships($ownerMembershipId, $params) {
    $membership = new CRM_Member_DAO_Membership();
    $membership->owner_membership_id = $ownerMembershipId;
    $membership->find();

    while ($membership->fetch()) {
      $relatedMembership = new CRM_Member_DAO_Membership();
      $relatedMembership->id = $membership->id;
      $relatedMembership->copyValues($params);
      $relatedMembership->save();
      $relatedMembership->free();
    }

    $membership->free();
  }

  /**
   * Function to get list of membership fields for profile
   * For now we only allow custom membership fields to be in
   * profile
   *
   * @return return the list of membership fields
   * @static
   * @access public
   */
  static function getMembershipFields() {
    $fields = CRM_Member_DAO_Membership::export();

    unset($fields['membership_contact_id']);
    $fields = array_merge($fields, CRM_Core_BAO_CustomField::getFieldsForImport('Membership'));


    $membershipType = CRM_Member_DAO_MembershipType::export();


    $membershipStatus = CRM_Member_DAO_MembershipStatus::export();

    $fields = array_merge($fields, $membershipType, $membershipStatus);

    return $fields;
  }

  /**
   * function to get the sort name of a contact for a particular membership
   *
   * @param  int    $id      id of the membership
   *
   * @return null|string     sort name of the contact if found
   * @static
   * @access public
   */
  static function sortName($id) {
    $id = CRM_Utils_Type::escape($id, 'Integer');

    $query = "
SELECT civicrm_contact.sort_name
FROM   civicrm_membership, civicrm_contact
WHERE  civicrm_membership.contact_id = civicrm_contact.id
  AND  civicrm_membership.id = {$id}
";
    return CRM_Core_DAO::singleValueQuery($query, CRM_Core_DAO::$_nullArray);
  }

  /**
   * function to create memberships for related contacts
   *
   * @param  array      $params       array of key - value pairs
   * @param  object     $membership   membership object
   *
   * @return null|relatedMembership     array of memberships if created
   * @static
   * @access public
   */
  static function createRelatedMemberships(&$params, &$membership) {
    static $relatedContactIds = [];

    // required since create method doesn't return all the
    // parameters in the returned membership object
    if (!$membership->find(TRUE)) {
      return;
    }

    $deceasedStatusId = array_search('Deceased', CRM_Member_PseudoConstant::membershipStatus());

    $allRelatedContacts = [];
    $relatedContacts = [];
    if (!is_a($membership, 'CRM_Core_Error')) {
      $allRelatedContacts = CRM_Member_BAO_Membership::checkMembershipRelationship($membership->id,
        $membership->contact_id,
        CRM_Utils_Array::value('action', $params)
      );
    }

    // check for loops. CRM-4213
    // remove repeated related contacts, which already inherited membership.
    $relatedContactIds[$membership->contact_id] = TRUE;
    foreach ($allRelatedContacts as $cid => $status) {
      if (!CRM_Utils_Array::value($cid, $relatedContactIds)) {
        $relatedContactIds[$cid] = TRUE;

        //don't create membership again for owner contact.
        $nestedRelationship = FALSE;
        if ($membership->owner_membership_id) {
          $nestedRelMembership = new CRM_Member_DAO_Membership();
          $nestedRelMembership->id = $membership->owner_membership_id;
          $nestedRelMembership->contact_id = $cid;
          $nestedRelationship = $nestedRelMembership->find(TRUE);
          $nestedRelMembership->free();
        }
        if (!$nestedRelationship) {
          $relatedContacts[$cid] = $status;
        }
      }
    }

    //lets cleanup related membership if any.
    if (empty($relatedContacts)) {

      CRM_Member_BAO_Membership::deleteRelatedMemberships($membership->id);
    }
    else {
      // Edit the params array
      unset($params['id']);
      // Reminder should be sent only to the direct membership
      unset($params['reminder_date']);
      // unset the custom value ids
      if (is_array(CRM_Utils_Array::value('custom', $params))) {
        foreach ($params['custom'] as $k => $v) {
          unset($params['custom'][$k]['id']);
        }
      }
      if (!isset($params['membership_type_id'])) {
        $params['membership_type_id'] = $membership->membership_type_id;
      }

      foreach ($relatedContacts as $contactId => $relationshipStatus) {
        //use existing membership record.
        $relMembership = new CRM_Member_DAO_Membership();
        $relMembership->contact_id = $contactId;
        $relMembership->owner_membership_id = $membership->id;
        $relMemIds = [];
        if ($relMembership->find(TRUE)) {
          $params['id'] = $relMemIds['membership'] = $relMembership->id;
        }
        $params['contact_id'] = $contactId;
        $params['owner_membership_id'] = $membership->id;

        // set status_id as it might have been changed for
        // past relationship
        $params['status_id'] = $membership->status_id;

        if ($deceasedStatusId &&
          CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $contactId, 'is_deceased')
        ) {
          $params['status_id'] = $deceasedStatusId;
        }
        elseif ((CRM_Utils_Array::value('action', $params) & CRM_Core_Action::UPDATE) &&
          ($relationshipStatus == CRM_Contact_BAO_Relationship::PAST)
        ) {
          // FIXME : While updating/ renewing the
          // membership, if the relationship is PAST then
          // the membership of the related contact must be
          // expired.
          // For that, getting Membership Status for which
          // is_current_member is 0. It works for the
          // generated data as there is only one membership
          // status having is_current_member = 0.
          // But this wont work exactly if there will be
          // more than one status having is_current_member = 0.

          $membershipStatus = new CRM_Member_DAO_MembershipStatus();
          $membershipStatus->is_current_member = 0;
          if ($membershipStatus->find(TRUE)) {
            $params['status_id'] = $membershipStatus->id;
          }
        }

        //don't calculate status again in create( );
        $params['skipStatusCal'] = TRUE;

        //do create activity if we changed status.
        if ($params['status_id'] != $relMembership->status_id) {
          $params['createActivity'] = TRUE;
        }

        // we should not created contribution record for related contacts, CRM-3371
        unset($params['contribution_status_id']);

        CRM_Member_BAO_Membership::create($params, $relMemIds);
      }
    }
  }

  /**
   * Delete the record that are associated with this Membership Payment
   *
   * @param  int  $membershipId  membsership id.
   *
   * @return boolean  true if deleted false otherwise
   * @access public
   */
  static function deleteMembershipPayment($membershipId) {


    $membesrshipPayment = new CRM_Member_DAO_MembershipPayment();
    $membesrshipPayment->membership_id = $membershipId;
    $membesrshipPayment->find();

    while ($membesrshipPayment->fetch()) {
      CRM_Utils_Hook::pre('delete', 'MembershipPayment', $membesrshipPayment->id, $membesrshipPayment);
      $membesrshipPayment->delete();
      CRM_Utils_Hook::post('delete', 'MembershipPayment', $membesrshipPayment->id, $membesrshipPayment);
    }
    return $membesrshipPayment;
  }

  static function &buildMembershipTypeValues(&$form, $membershipTypeID = NULL) {
    $whereClause = NULL;
    if (is_array($membershipTypeID)) {
      $allIDs = CRM_Utils_Array::implode(',', $membershipTypeID);
      $whereClause = "WHERE id IN ( $allIDs )";
    }
    elseif (is_numeric($membershipTypeID) &&
      $membershipTypeID > 0
    ) {
      $whereClause = "WHERE id = $membershipTypeID";
    }

    $query = "
SELECT *
FROM   civicrm_membership_type
       $whereClause;
";
    $dao = CRM_Core_DAO::executeQuery($query);

    $membershipTypeValues = [];
    $membershipTypeFields = ['id', 'minimum_fee', 'name', 'is_active', 'description', 'contribution_type_id',
    ];

    while ($dao->fetch()) {
      $membershipTypeValues[$dao->id] = [];
      foreach ($membershipTypeFields as $mtField) {
        $membershipTypeValues[$dao->id][$mtField] = $dao->$mtField;
      }
    }
    $dao->free();


    CRM_Utils_Hook::membershipTypeValues($form, $membershipTypeValues);

    if (is_numeric($membershipTypeID) &&
      $membershipTypeID > 0
    ) {
      return $membershipTypeValues[$membershipTypeID];
    }
    else {
      return $membershipTypeValues;
    }
  }

  /**
   * Function to get membership record count for a Contact
   *
   * @param int $contactId Contact ID
   *
   * @return int count of membership records
   * @access public
   * @static
   */
  static function getContactMembershipCount($contactID) {
    $query = "SELECT count(*) FROM civicrm_membership WHERE civicrm_membership.contact_id = {$contactID} AND civicrm_membership.is_test = 0 ";
    return CRM_Core_DAO::singleValueQuery($query);
  }

  /**
   * Check if this page is for membership renewal only and user has permission to access.
   */
  public static function checkRenewalPagePermission($form) {
    $access = TRUE;
  // Check it is main page of contribution.
    $id = $form->get('id');
    $membershipBlockFields = CRM_Member_DAO_MembershipBlock::fields();
    // Check if 'is_renewal_only' is exist in membership block table.
    if (in_array('is_renewal_only', array_keys($membershipBlockFields))) {
      // Fetch 'is_renewal_only' of this page.
      $sql = "SELECT is_renewal_only, membership_types FROM civicrm_membership_block WHERE entity_id = %1 AND entity_table = 'civicrm_contribution_page'";
      $params = [ 1 => [$id, 'Positive']];
      $dao = CRM_Core_DAO::executeQuery($sql, $params);
      while($dao->fetch()) {
        $isRenewalOnly = $dao->is_renewal_only;
        if (!empty($isRenewalOnly)) {
          // Get login user id.
          $session = CRM_Core_Session::singleton();
          $userId = $session->get('userID');
          if ($userId) {
            // Assume contact is not in any membership type
            $membershipTypeError = TRUE;
            // Get membership types this page used.
            $allowMembershipTypes = explode(',', $dao->membership_types);
            $isTest = ($form->_mode == 'test') ? 1 : 0;
            // Get contact membership types.
            $sql = "SELECT membership_type_id FROM civicrm_membership WHERE contact_id = %1 AND is_test = %2";
            $params = [
              1 => [$userId, 'Positive'],
              2 => [$isTest, 'Positive'],
            ];
            $dao2 = CRM_Core_DAO::executeQuery($sql, $params);
            while ($dao2->fetch()) {
              $membershipTypeId = $dao2->membership_type_id;
              if (in_array($membershipTypeId, $allowMembershipTypes)) {
                $membershipTypeError = FALSE;
              }
            }
            if ($membershipTypeError) {
               return CRM_Core_Error::statusBounce(ts("Oops. The membership you're trying to renew appears to be invalid. Contact your site administrator if you need assistance."));
            }
          }
          // not login user.
          else {
            $access = FALSE;
          }
        }
      }
    }
    return $access;
  }

  function updateReminderDate($membershipId) {
    $memberships = [];
    $params = ['id' => $membershipId]; 
    self::getValues($params, $memberships);
    $member = reset($memberships);
    if (!empty($member) && $member['id']) {
      $reminderDate = 'null';
      $membershipTypeDetails = CRM_Member_BAO_MembershipType::getMembershipTypeDetails($member['membership_type_id']);
      if (!empty($membershipTypeDetails['renewal_msg_id']) && !empty($member['end_date'])) {
        if($membershipTypeDetails['renewal_reminder_day'] === '0' || !empty($membershipTypeDetails['renewal_reminder_day'])) {
          $reminderDate = CRM_Member_BAO_MembershipType::calcReminderDate($member['end_date'], $membershipTypeDetails['renewal_reminder_day']);
          if (empty($reminderDate)) {
            $reminderDate = 'null';
          }
        }
      } 
      CRM_Core_DAO::setFieldValue('CRM_Member_DAO_Membership', $member['id'], 'reminder_date', $reminderDate);
    }
    else {
      return FALSE;
    }
  }
}