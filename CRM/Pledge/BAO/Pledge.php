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


class CRM_Pledge_BAO_Pledge extends CRM_Pledge_DAO_Pledge {

  /**
   * static field for all the pledge information that we can potentially export
   *
   * @var array
   * @static
   */
  static $_exportableFields = NULL;

  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. Typically the valid params are only
   * pledge id. We'll tweak this function to be more full featured over a period
   * of time. This is the inverse function of create. It also stores all the retrieved
   * values in the default array
   *
   * @param array $params   (reference ) an assoc array of name/value pairs
   * @param array $defaults (reference ) an assoc array to hold the flattened values
   *
   * @return object CRM_Pledge_BAO_Pledge object
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
    $pledge = new CRM_Pledge_DAO_Pledge();
    $pledge->copyValues($params);
    if ($pledge->find(TRUE)) {
      CRM_Core_DAO::storeValues($pledge, $defaults);
      return $pledge;
    }
    return NULL;
  }

  /**
   * function to add pledge
   *
   * @param array $params reference array contains the values submitted by the form
   *
   * @access public
   * @static
   *
   * @return object
   */
  static function add(&$params) {

    if (CRM_Utils_Array::value('id', $params)) {
      CRM_Utils_Hook::pre('edit', 'Pledge', $params['id'], $params);
    }
    else {
      CRM_Utils_Hook::pre('create', 'Pledge', NULL, $params);
    }

    $pledge = new CRM_Pledge_DAO_Pledge();

    // if pledge is complete update end date as current date
    if ($pledge->status_id == 1) {
      $pledge->end_date = date('Ymd');
    }

    $pledge->copyValues($params);

    // set currency for CRM-1496
    if (!isset($pledge->currency)) {
      $config = &CRM_Core_Config::singleton();
      $pledge->currency = $config->defaultCurrency;
    }

    $result = $pledge->save();

    if (CRM_Utils_Array::value('id', $params)) {
      CRM_Utils_Hook::post('edit', 'Pledge', $pledge->id, $pledge);
    }
    else {
      CRM_Utils_Hook::post('create', 'Pledge', $pledge->id, $pledge);
    }

    return $result;
  }

  /**
   * Given the list of params in the params array, fetch the object
   * and store the values in the values array
   *
   * @param array $params input parameters to find object
   * @param array $values output values of the object
   * @param array $returnProperties  if you want to return specific fields
   *
   * @return array associated array of field values
   * @access public
   * @static
   */
  static function &getValues(&$params, &$values, $returnProperties = NULL) {
    if (empty($params)) {
      return NULL;
    }
    CRM_Core_DAO::commonRetrieve('CRM_Pledge_BAO_Pledge', $params, $values, $returnProperties);
    return $values;
  }

  /**
   * takes an associative array and creates a pledge object
   *
   * @param array $params (reference ) an assoc array of name/value pairs
   *
   * @return object CRM_Pledge_BAO_Pledge object
   * @access public
   * @static
   */
  static function &create(&$params) {

    //FIXME: a cludgy hack to fix the dates to MySQL format
    $dateFields = ['start_date', 'create_date', 'acknowledge_date', 'modified_date', 'cancel_date', 'end_date'];
    foreach ($dateFields as $df) {
      if (isset($params[$df])) {
        $params[$df] = CRM_Utils_Date::isoToMysql($params[$df]);
      }
    }


    $transaction = new CRM_Core_Transaction();

    $paymentParams = [];
    $paymentParams['status_id'] = $params['status_id'];
    if (CRM_Utils_Array::value('installment_amount', $params)) {
      $params['amount'] = $params['installment_amount'] * $params['installments'];
    }

    //get All Payments status types.

    $paymentStatusTypes = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');

    //update the pledge status only if it does NOT come from form
    if (!isset($params['pledge_status_id'])) {
      if (isset($params['contribution_id'])) {
        if ($params['installments'] > 1) {
          $params['status_id'] = array_search('In Progress', $paymentStatusTypes);
        }
      }
      else {
        $params['status_id'] = array_search('Pending', $paymentStatusTypes);
      }
    }

    $pledge = self::add($params);
    if (is_a($pledge, 'CRM_Core_Error')) {
      $pledge->rollback();
      return $pledge;
    }

    //handle custom data.
    if (CRM_Utils_Array::value('custom', $params) &&
      is_array($params['custom'])
    ) {

      CRM_Core_BAO_CustomValueTable::store($params['custom'], 'civicrm_pledge', $pledge->id);
    }

    // skip payment stuff inedit mode
    if (!isset($params['id']) ||
      CRM_Utils_Array::value('is_pledge_pending', $params)
    ) {



      //if pledge is pending delete all payments and recreate.
      if (CRM_Utils_Array::value('is_pledge_pending', $params)) {
        CRM_Pledge_BAO_Payment::deletePayments($pledge->id);
      }

      //building payment params
      $paymentParams['pledge_id'] = $pledge->id;
      $paymentKeys = ['amount', 'installments', 'scheduled_date', 'frequency_unit',
        'frequency_day', 'frequency_interval', 'contribution_id', 'installment_amount', 'actual_amount',
      ];
      foreach ($paymentKeys as $key) {
        $paymentParams[$key] = CRM_Utils_Array::value($key, $params, NULL);
      }
      CRM_Pledge_BAO_Payment::create($paymentParams);
    }

    $transaction->commit();





    $url = CRM_Utils_System::url('civicrm/contact/view/pledge',
      "action=view&reset=1&id={$pledge->id}&cid={$pledge->contact_id}&context=home"
    );

    $recentOther = [];
    if (CRM_Core_Permission::checkActionPermission('CiviPledge', CRM_Core_Action::UPDATE)) {
      $recentOther['editUrl'] = CRM_Utils_System::url('civicrm/contact/view/pledge',
        "action=update&reset=1&id={$pledge->id}&cid={$pledge->contact_id}&context=home"
      );
    }
    if (CRM_Core_Permission::checkActionPermission('CiviPledge', CRM_Core_Action::DELETE)) {
      $recentOther['deleteUrl'] = CRM_Utils_System::url('civicrm/contact/view/pledge',
        "action=delete&reset=1&id={$pledge->id}&cid={$pledge->contact_id}&context=home"
      );
    }

    $config = CRM_Core_Config::singleton();

    $contributionTypes = CRM_Contribute_PseudoConstant::contributionType();
    $title = CRM_Contact_BAO_Contact::displayName($pledge->contact_id) . ' - (' . ts('Pledged') . ' ' . CRM_Utils_Money::format($pledge->amount) . ' - ' . $contributionTypes[$pledge->contribution_type_id] . ')';

    // add the recently created Pledge
    CRM_Utils_Recent::add($title,
      $url,
      $pledge->id,
      'Pledge',
      $pledge->contact_id,
      NULL,
      $recentOther
    );

    return $pledge;
  }

  /**
   * Function to delete the pledge
   *
   * @param int $id  pledge id
   *
   * @access public
   * @static
   *
   */
  static function deletePledge($id) {
    CRM_Utils_Hook::pre('delete', 'Pledge', $id, CRM_Core_DAO::$_nullArray);


    $transaction = new CRM_Core_Transaction();

    //check for no Completed Payment records with the pledge

    $payment = new CRM_Pledge_DAO_Payment();
    $payment->pledge_id = $id;
    $payment->find();

    while ($payment->fetch()) {
      //also delete associated contribution.
      if ($payment->contribution_id) {

        CRM_Contribute_BAO_Contribution::deleteContribution($payment->contribution_id);
      }
      $payment->delete();
    }

    $dao = new CRM_Pledge_DAO_Pledge();
    $dao->id = $id;
    $results = $dao->delete();

    $transaction->commit();

    CRM_Utils_Hook::post('delete', 'Pledge', $dao->id, $dao);

    // delete the recently created Pledge

    $pledgeRecent = [
      'id' => $id,
      'type' => 'Pledge',
    ];
    CRM_Utils_Recent::del($pledgeRecent);

    return $results;
  }

  /**
   * function to get the amount details date wise.
   */
  static function getTotalAmountAndCount($status = NULL, $startDate = NULL, $endDate = NULL) {
    $where = [];
    $select = $from = $queryDate = NULL;
    //get all status

    $allStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    $statusId = array_search($status, $allStatus);

    switch ($status) {
      case 'Completed':
        $statusId = array_search('Cancelled', $allStatus);
        $where[] = 'status_id != ' . $statusId;
        break;

      case 'Cancelled':
        $where[] = 'status_id = ' . $statusId;
        break;

      case 'In Progress':
        $where[] = 'status_id = ' . $statusId;
        break;

      case 'Pending':
        $where[] = 'status_id = ' . $statusId;
        break;

      case 'Overdue':
        $where[] = 'status_id = ' . $statusId;
        break;
    }

    if ($startDate) {
      $where[] = "create_date >= '" . CRM_Utils_Type::escape($startDate, 'Timestamp') . "'";
    }
    if ($endDate) {
      $where[] = "create_date <= '" . CRM_Utils_Type::escape($endDate, 'Timestamp') . "'";
    }

    $whereCond = CRM_Utils_Array::implode(' AND ', $where);

    $query = "
SELECT sum( amount ) as pledge_amount, count( id ) as pledge_count
FROM   civicrm_pledge
WHERE  $whereCond AND is_test=0
";
    $start = substr($startDate, 0, 8);
    $end = substr($endDate, 0, 8);

    $dao = CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);
    if ($dao->fetch()) {
      $pledge_amount = ['pledge_amount' => $dao->pledge_amount,
        'pledge_count' => $dao->pledge_count,
        'purl' => CRM_Utils_System::url('civicrm/pledge/search',
          "reset=1&force=1&pstatus={$statusId}&pstart={$start}&pend={$end}&test=0"
        ),
      ];
    }

    $where = [];
    $statusId = array_search($status, $allStatus);
    switch ($status) {
      case 'Completed':
        $select = 'sum( total_amount ) as received_pledge , count( cd.id ) as received_count';
        $where[] = 'cp.status_id = ' . $statusId . ' AND cp.contribution_id = cd.id AND cd.is_test=0';
        $queryDate = 'receive_date';
        $from = ' civicrm_contribution cd, civicrm_pledge_payment cp';
        break;

      case 'Cancelled':
        $select = 'sum( total_amount ) as received_pledge , count( cd.id ) as received_count';
        $where[] = 'cp.status_id = ' . $statusId . ' AND cp.contribution_id = cd.id AND cd.is_test=0';
        $queryDate = 'receive_date';
        $from = ' civicrm_contribution cd, civicrm_pledge_payment cp';
        break;

      case 'Pending':
        $select = 'sum( scheduled_amount )as received_pledge , count( cp.id ) as received_count';
        $where[] = 'cp.status_id = ' . $statusId . ' AND pledge.is_test=0';
        $queryDate = 'scheduled_date';
        $from = ' civicrm_pledge_payment cp INNER JOIN civicrm_pledge pledge on cp.pledge_id = pledge.id';
        break;

      case 'Overdue':
        $select = 'sum( scheduled_amount ) as received_pledge , count( cp.id ) as received_count';
        $where[] = 'cp.status_id = ' . $statusId . ' AND pledge.is_test=0';
        $queryDate = 'scheduled_date';
        $from = ' civicrm_pledge_payment cp INNER JOIN civicrm_pledge pledge on cp.pledge_id = pledge.id';
        break;
    }

    if ($startDate) {
      $where[] = " $queryDate >= '" . CRM_Utils_Type::escape($startDate, 'Timestamp') . "'";
    }
    if ($endDate) {
      $where[] = " $queryDate <= '" . CRM_Utils_Type::escape($endDate, 'Timestamp') . "'";
    }

    $whereCond = CRM_Utils_Array::implode(' AND ', $where);

    $query = "
SELECT $select
FROM $from
WHERE  $whereCond 
";
    if ($select) {
      // CRM_Core_Error::debug($status . ' start:' . $startDate . '- end:' . $endDate, $query);
      $dao = CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);
      if ($dao->fetch()) {
        return array_merge($pledge_amount, ['received_amount' => $dao->received_pledge,
            'received_count' => $dao->received_count,
            'url' => CRM_Utils_System::url('civicrm/pledge/search',
              "reset=1&force=1&status={$statusId}&start={$start}&end={$end}&test=0"
            ),
          ]);
      }
    }
    else {
      return $pledge_amount;
    }
    return NULL;
  }

  /**
   * Function to get list of pledges In Honor of contact Ids
   *
   * @param int $honorId In Honor of Contact ID
   *
   * @return return the list of pledge fields
   *
   * @access public
   * @static
   */
  static function getHonorContacts($honorId) {
    $params = [];

    $honorDAO = new CRM_Pledge_DAO_Pledge();
    $honorDAO->honor_contact_id = $honorId;
    $honorDAO->find();

    //get all status.

    while ($honorDAO->fetch()) {
      $params[$honorDAO->id] = [
        'honorId' => $honorDAO->contact_id,
        'amount' => $honorDAO->amount,
        'status' => CRM_Contribute_PseudoConstant::contributionStatus($honorDAO->status_id),
        'create_date' => $honorDAO->create_date,
        'acknowledge_date' => $honorDAO->acknowledge_date,
        'type' => CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionType',
          $honorDAO->contribution_type_id, 'name'
        ),
        'display_name' => CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
          $honorDAO->contact_id, 'display_name'
        ),
      ];
    }
    return $params;
  }

  /**
   * Function to send Acknowledgment and create activity.
   *
   * @param object $form form object.
   * @param array  $params (reference ) an assoc array of name/value pairs.
   * @access public
   *
   * @return None.
   */
  static function sendAcknowledgment(&$form, $params) {
    //handle Acknowledgment.
    $allPayments = $payments = [];

    //get All Payments status types.

    $paymentStatusTypes = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    $returnProperties = ['status_id', 'scheduled_amount', 'scheduled_date', 'contribution_id'];
    //get all paymnets details.
    CRM_Core_DAO::commonRetrieveAll('CRM_Pledge_DAO_Payment', 'pledge_id', $params['id'], $allPayments, $returnProperties);

    if (!empty($allPayments)) {
      foreach ($allPayments as $payID => $values) {
        $contributionValue = $contributionStatus = [];
        if (isset($values['contribution_id'])) {
          $contributionParams = ['id' => $values['contribution_id']];
          $returnProperties = ['contribution_status_id', 'receive_date'];
          CRM_Core_DAO::commonRetrieve('CRM_Contribute_DAO_Contribution',
            $contributionParams, $contributionStatus, $returnProperties
          );
          $contributionValue = [
            'status' => CRM_Utils_Array::value('contribution_status_id', $contributionStatus),
            'receive_date' => CRM_Utils_Array::value('receive_date', $contributionStatus),
          ];
        }
        $payments[$payID] = array_merge($contributionValue,
          ['amount' => CRM_Utils_Array::value('scheduled_amount', $values),
            'due_date' => CRM_Utils_Array::value('scheduled_date', $values),
          ]
        );

        //get the first valid payment id.
        if (!$form->paymentId && ($paymentStatusTypes[$values['status_id']] == 'Pending' ||
            $paymentStatusTypes[$values['status_id']] == 'Overdue'
          )) {
          $form->paymentId = $values['id'];
        }
      }
    }
    //end

    //assign pledge fields value to template.
    $pledgeFields = ['create_date', 'total_pledge_amount', 'frequency_interval', 'frequency_unit',
      'installments', 'frequency_day', 'scheduled_amount',
    ];
    foreach ($pledgeFields as $field) {
      if (CRM_Utils_Array::value($field, $params)) {
        $form->assign($field, $params[$field]);
      }
    }

    //assign all payments details.
    if ($payments) {
      $form->assign('payments', $payments);
    }

    //assign honor fields.
    $honor_block_is_active = FALSE;
    //make sure we have values for it
    if (CRM_Utils_Array::value('honor_type_id', $params) &&
      ((!empty($params["honor_first_name"]) && !empty($params["honor_last_name"])) ||
        (!empty($params["honor_email"]))
      )
    ) {
      $honor_block_is_active = TRUE;

      $prefix = CRM_Core_PseudoConstant::individualPrefix();
      $honor = CRM_Core_PseudoConstant::honor();
      $form->assign("honor_type", $honor[$params["honor_type_id"]]);
      $form->assign("honor_prefix", $prefix[$params["honor_prefix_id"]]);
      $form->assign("honor_first_name", $params["honor_first_name"]);
      $form->assign("honor_last_name", $params["honor_last_name"]);
      $form->assign("honor_email", $params["honor_email"]);
    }
    $form->assign('honor_block_is_active', $honor_block_is_active);

    //handle domain token values

    $domain = &CRM_Core_BAO_Domain::getDomain();
    $tokens = ['domain' => ['name', 'phone', 'address', 'email'],
      'contact' => CRM_Core_SelectValues::contactTokens(),
    ];
    $domainValues = [];
    foreach ($tokens['domain'] as $token) {
      $domainValues[$token] = CRM_Utils_Token::getDomainTokenReplacement($token, $domain);
    }
    $form->assign('domain', $domainValues);

    //handle contact token values.


    $ids = [$params['contact_id']];
    $fields = array_merge(array_keys(CRM_Contact_BAO_Contact::importableFields()),
      ['display_name', 'checksum', 'contact_id']
    );
    foreach ($fields as $key => $val) {
      $returnProperties[$val] = TRUE;
    }
    $details = CRM_Mailing_BAO_Mailing::getDetails($ids, $returnProperties);
    $form->assign('contact', $details[0][$params['contact_id']]);

    //handle custom data.
    if (CRM_Utils_Array::value('hidden_custom', $params)) {

      $groupTree = &CRM_Core_BAO_CustomGroup::getTree('Pledge', CRM_Core_DAO::$_nullObject, $params['id']);
      $pledgeParams = [['pledge_id', '=', $params['id'], 0, 0]];
      $customGroup = [];
      // retrieve custom data

      foreach ($groupTree as $groupID => $group) {
        $customFields = $customValues = [];
        if ($groupID == 'info') {
          continue;
        }
        foreach ($group['fields'] as $k => $field) {
          $field['title'] = $field['label'];
          $customFields["custom_{$k}"] = $field;
        }

        //to build array of customgroup & customfields in it
        CRM_Core_BAO_UFGroup::getValues($params['contact_id'], $customFields, $customValues, FALSE, $pledgeParams);
        $customGroup[$group['title']] = $customValues;
      }

      $form->assign('customGroup', $customGroup);
    }

    //handle acknowledgment email stuff.


    list($pledgerDisplayName,
      $pledgerEmail
    ) = CRM_Contact_BAO_Contact_Location::getEmailDetails($params['contact_id']);

    //check for online pledge.
    $session = CRM_Core_Session::singleton();
    if (CRM_Utils_Array::value('receipt_from_email', $params)) {
      $userName = CRM_Utils_Array::value('receipt_from_name', $params);
      $userEmail = CRM_Utils_Array::value('receipt_from_email', $params);
    }
    elseif ($userID = $session->get('userID')) {
      //check for loged in user.
      list($userName, $userEmail) = CRM_Contact_BAO_Contact_Location::getEmailDetails($userID);
    }
    else {
      //set the domain values.
      $userName = CRM_Utils_Array::value('name', $domainValues);
      $userEmail = CRM_Utils_Array::value('email', $domainValues);
    }
    $receiptFrom = "$userName <$userEmail>";


    list($sent, $subject, $message, $html) = CRM_Core_BAO_MessageTemplates::sendTemplate(
      [
        'groupName' => 'msg_tpl_workflow_pledge',
        'valueName' => 'pledge_acknowledge',
        'contactId' => $params['contact_id'],
        'from' => $receiptFrom,
        'toName' => $pledgerDisplayName,
        'toEmail' => $pledgerEmail,
      ]
    );

    //check if activity record exist for this pledge
    //Acknowledgment, if exist do not add activity.

    $activityType = 'Pledge Acknowledgment';
    $activity = new CRM_Activity_DAO_Activity();
    $activity->source_record_id = $params['id'];
    $activity->activity_type_id = CRM_Core_OptionGroup::getValue('activity_type',
      $activityType,
      'name'
    );
    $config = CRM_Core_Config::singleton();
    $money = $config->defaultCurrencySymbol;
    $details = 'Total Amount ' . $money . $params['total_pledge_amount'] . ' To be paid in ' . $params['installments'] . ' installments of ' . $money . $params['scheduled_amount'] . ' every ' . $params['frequency_interval'] . ' ' . $params['frequency_unit'] . '(s)';

    if (!$activity->find()) {
      $activityParams = ['subject' => $subject,
        'source_contact_id' => $params['contact_id'],
        'source_record_id' => $params['id'],
        'activity_type_id' => CRM_Core_OptionGroup::getValue('activity_type',
          $activityType,
          'name'
        ),
        'activity_date_time' => CRM_Utils_Date::isoToMysql($params['acknowledge_date']),
        'is_test' => $params['is_test'],
        'status_id' => 2,
        'details' => $details,
      ];

      if (is_a(civicrm_activity_create($activityParams), 'CRM_Core_Error')) {
        CRM_Core_Error::fatal("Failed creating Activity for acknowledgment");
      }
    }
  }

  /**
   * combine all the exportable fields from the lower levels object
   *
   * @return array array of exportable Fields
   * @access public
   */
  static function &exportableFields() {
    if (!self::$_exportableFields) {
      if (!self::$_exportableFields) {
        self::$_exportableFields = [];
      }


      $fields = CRM_Pledge_DAO_Pledge::export();


      $fields = array_merge($fields, CRM_Pledge_DAO_Payment::export());

      //set title to calculated fields
      $calculatedFields = ['pledge_total_paid' => ['title' => ts('Total Paid')],
        'pledge_balance_amount' => ['title' => ts('Balance Amount')],
        'pledge_next_pay_date' => ['title' => ts('Next Payment Date')],
        'pledge_next_pay_amount' => ['title' => ts('Next Payment Amount')],
        'pledge_payment_paid_amount' => ['title' => ts('Paid Amount')],
        'pledge_payment_paid_date' => ['title' => ts('Paid Date')],
        'pledge_payment_status' => ['title' => ts('Pledge Payment Status'),
          'name' => 'pledge_payment_status',
          'data_type' => CRM_Utils_Type::T_STRING,
        ],
      ];


      $pledgeFields = ['pledge_status' => ['title' => 'Pledge Status',
          'name' => 'pledge_status',
          'data_type' => CRM_Utils_Type::T_STRING,
        ],
        'pledge_frequency_unit' => ['title' => 'Pledge Frequency Unit',
          'name' => 'pledge_frequency_unit',
          'data_type' => CRM_Utils_Type::T_ENUM,
        ],
        'pledge_frequency_interval' => ['title' => 'Pledge Frequency Interval',
          'name' => 'pledge_frequency_interval',
          'data_type' => CRM_Utils_Type::T_INT,
        ],
        'pledge_contribution_page_id' => ['title' => 'Pledge Contribution Page Id',
          'name' => 'pledge_contribution_page_id',
          'data_type' => CRM_Utils_Type::T_INT,
        ],
      ];

      $fields = array_merge($fields, $pledgeFields, $calculatedFields);

      // add custom data
      $fields = array_merge($fields, CRM_Core_BAO_CustomField::getFieldsForImport('Pledge'));
      self::$_exportableFields = $fields;
    }

    return self::$_exportableFields;
  }

  /**
   * Function to get pending or in progress pledges
   *
   * @param int $contactID contact id
   *
   * @return array associated array of pledge id(s)
   * @static
   */
  static function getContactPledges($contactID) {
    $pledgeDetails = [];

    $pledgeStatuses = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');

    $status = [];

    //get pending and in progress status
    foreach (['Pending', 'In Progress', 'Overdue'] as $name) {
      if ($statusId = array_search($name, $pledgeStatuses)) {
        $status[] = $statusId;
      }
    }
    if (empty($status)) {
      return $pledgeDetails;
    }

    $statusClause = " IN (" . CRM_Utils_Array::implode(',', $status) . ")";

    $query = "
SELECT civicrm_pledge.id id
FROM civicrm_pledge
WHERE civicrm_pledge.status_id  {$statusClause}        
  AND civicrm_pledge.contact_id = %1
";

    $params[1] = [$contactID, 'Integer'];
    $pledge = CRM_Core_DAO::executeQuery($query, $params);

    while ($pledge->fetch()) {
      $pledgeDetails[] = $pledge->id;
    }

    return $pledgeDetails;
  }

  /**
   * Function to get pledge record count for a Contact
   *
   * @param int $contactId Contact ID
   *
   * @return int count of pledge records
   * @access public
   * @static
   */
  static function getContactPledgeCount($contactID) {
    $query = "SELECT count(*) FROM civicrm_pledge WHERE civicrm_pledge.contact_id = {$contactID} AND civicrm_pledge.is_test = 0";
    return CRM_Core_DAO::singleValueQuery($query);
  }
}

