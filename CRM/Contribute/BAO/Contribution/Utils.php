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
class CRM_Contribute_BAO_Contribution_Utils {

  /**
   * Function to process payment after confirmation
   *
   * @param object  $form   form object
   * @param array   $paymentParams   array with payment related key
   * value pairs
   * @param array   $premiumParams   array with premium related key
   * value pairs
   * @param int     $contactID       contact id
   * @param int     $contributionTypeId   contribution type id
   * @param int     $component   component id
   *
   * @return false|array FALSE when failed, associated array when success.
   *
   * @static
   * @access public
   */
  static function processConfirm(&$form,
    &$paymentParams,
    &$premiumParams,
    $contactID,
    $contributionTypeId,
    $component = 'contribution'
  ) {

    CRM_Core_Payment_Form::mapParams($form->_bltID, $form->_params, $paymentParams, TRUE);


    $contributionType = new CRM_Contribute_DAO_ContributionType();
    if (isset($paymentParams['contribution_type'])) {
      $contributionType->id = $paymentParams['contribution_type'];
    }
    else {
      $contributionType->id = $contributionTypeId;
    }
    if (!$contributionType->find(TRUE)) {
      CRM_Core_Error::fatal("Could not find a system table");
    }

    // add some contribution type details to the params list
    // if folks need to use it
    $paymentParams['contributionType_name'] = $form->_params['contributionType_name'] = $contributionType->name;
    $paymentParams['contributionType_accounting_code'] = $form->_params['contributionType_accounting_code'] = $contributionType->accounting_code;
    $paymentParams['contributionPageID'] = $form->_params['contributionPageID'] = $form->_values['id'];


    if ($form->_values['is_monetary'] && $form->_amount > 0.0 && is_array($form->_paymentProcessor)) {

      $payment = &CRM_Core_Payment::singleton($form->_mode, $form->_paymentProcessor, $form);
    }

    //fix for CRM-2062
    $now = date('YmdHis');

    $result = NULL;
    if ($form->_contributeMode == 'notify' || $form->_contributeMode == 'iframe' || $form->_params['is_pay_later']) {
      // this is not going to come back, i.e. we fill in the other details
      // when we get a callback from the payment processor
      // also add the contact ID and contribution ID to the params list
      $paymentParams['contactID'] = $form->_params['contactID'] = $contactID;
      $contribution = CRM_Contribute_Form_Contribution_Confirm::processContribution(
        $form,
        $paymentParams,
        NULL,
        $contactID,
        $contributionType,
        TRUE, TRUE, TRUE
      );
      $form->_params['contributionID'] = $contribution->id;
      $form->_params['contributionTypeID'] = $contributionType->id;
      $form->_params['item_name'] = $form->_params['description'];
      $form->_params['receive_date'] = $now;

      // calculate billing expiration date
      $baseTime = CRM_REQUEST_TIME;
      $plusDay = CRM_Core_Payment::PAY_LATER_DEFAULT_EXPIRED_DAY;
      $expiredTime= CRM_Core_Payment::calcExpirationDate($baseTime, $plusDay);
      $form->_params['payment_expired_timestamp'] = $expiredTime;

      if ($form->_values['is_recur'] &&
        $contribution->contribution_recur_id
      ) {
        $form->_params['contributionRecurID'] = $contribution->contribution_recur_id;
      }

      $form->set('params', $form->_params);
      $form->postProcessPremium($premiumParams, $contribution);

      if ($form->_amount > 0.0) {
        // add qfKey so we can send to paypal
        $form->_params['qfKey'] = $form->controller->_key;
        if ($component == 'membership') {
          $membershipResult = [1 => $contribution];
          return $membershipResult;
        }
        else {
          if (!$form->_params['is_pay_later']) {
            // before leave to transfercheckout, call hook
            CRM_Utils_Hook::postProcess(get_class($form), $form);
            $result = &$payment->doTransferCheckout($form->_params, 'contribute');
          }
          else {
            // follow similar flow as IPN
            // send the receipt mail
            $form->set('params', $form->_params);
            if ($contributionType->is_deductible) {
              $form->assign('is_deductible', TRUE);
              $form->set('is_deductible', TRUE);
            }
            if (isset($paymentParams['contribution_source'])) {
              $form->_params['source'] = $paymentParams['contribution_source'];
            }

            // get the price set values for receipt.
            if ($form->_priceSetId && $form->_lineItem) {
              $form->_values['lineItem'] = $form->_lineItem;
              $form->_values['priceSetID'] = $form->_priceSetId;
            }


            $form->_values['contribution_id'] = $contribution->id;
            CRM_Contribute_BAO_ContributionPage::sendMail($contactID,
              $form->_values,
              $contribution->is_test
            );
            return [];
          }
        }
      }
    }
    elseif ($form->_contributeMode == 'express') {
      if ($form->_values['is_monetary'] && $form->_amount > 0.0) {

        //LCD determine if express + recurring and direct accordingly
        if ($paymentParams['is_recur'] == 1) {
          $result = &$payment->createRecurringPayments($paymentParams);
        }
        else {
          $result = &$payment->doExpressCheckout($paymentParams);
        }
      }
    }
    elseif ($form->_values['is_monetary'] && $form->_amount > 0.0) {

      if ($paymentParams['is_recur'] && $form->_contributeMode == 'direct') {

        // For recurring contribution, create Contribution Record first.
        // Contribution ID, Recurring ID and Contact ID needed
        // When we get a callback from the payment processor

        $paymentParams['contactID'] = $contactID;
        $contribution = CRM_Contribute_Form_Contribution_Confirm::processContribution(
          $form,
          $paymentParams,
          NULL,
          $contactID,
          $contributionType,
          TRUE, TRUE, TRUE
        );

        $paymentParams['contributionID'] = $contribution->id;
        $paymentParams['contributionTypeID'] = $contribution->contribution_type_id;
        $paymentParams['contributionPageID'] = $contribution->contribution_page_id;

        if ($form->_values['is_recur'] && $contribution->contribution_recur_id) {
          $paymentParams['contributionRecurID'] = $contribution->contribution_recur_id;
        }
      }

      $result = &$payment->doDirectPayment($paymentParams);
    }

    if ($component == 'membership') {
      $membershipResult = [];
    }

    if (is_a($result, 'CRM_Core_Error')) {
      CRM_Core_Error::displaySessionError($result);
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contribute/transact', "_qf_Confirm_display=true&qfKey={$form->_params['qfKey']}"));
      return FALSE;
    }
    elseif ($result) {
      if ($result) {
        $form->_params = array_merge($form->_params, $result);
      }
      $form->_params['receive_date'] = $now;
      $form->set('params', $form->_params);
      $form->assign('trxn_id', $result['trxn_id']);
      $form->assign('receive_date',
        CRM_Utils_Date::mysqlToIso($form->_params['receive_date'])
      );

      // result has all the stuff we need
      // lets archive it to a financial transaction
      if ($contributionType->is_deductible) {
        $form->assign('is_deductible', TRUE);
        $form->set('is_deductible', TRUE);
      }

      if (isset($paymentParams['contribution_source'])) {
        $form->_params['source'] = $paymentParams['contribution_source'];
      }

      // check if pending was set to true by payment processor
      $pending = FALSE;
      if (CRM_Utils_Array::value('contribution_status_pending',
          $form->_params
        )) {
        $pending = TRUE;
      }
      if (!($paymentParams['is_recur'] && $form->_contributeMode == 'direct')) {
        $contribution = CRM_Contribute_Form_Contribution_Confirm::processContribution($form,
          $form->_params, $result,
          $contactID, $contributionType,
          TRUE, $pending, TRUE
        );
      }
      $form->postProcessPremium($premiumParams, $contribution);

      $membershipResult[1] = $contribution;
    }
    elseif (empty($result) && $form->_values['is_monetary'] && $form->_amount > 0.0) {
      $errorResult = CRM_Core_Error::singleton();
      CRM_Core_Error::displaySessionError($errorResult);
      CRM_Core_Session::setStatus(ts("We apologize for any inconvenience caused, please go back to the <a href='%1'>donation page</a> to retry.", [
        1 => CRM_Utils_System::url('civicrm/contribute/transact', 'reset=1&id='.$form->_params['contributionPageID'])]),
        TRUE, 'error');
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contribute/transact', "_qf_Confirm_display=true&qfKey={$form->_params['qfKey']}"));
      return FALSE;
    }

    if ($component == 'membership') {
      return $membershipResult;
    }
    //Do not send an email if Recurring contribution is done via Direct Mode
    //Email will we send once the IPN will receive.
    if ($paymentParams['is_recur'] && $form->_contributeMode == 'direct') {
      return [];
    }

    // get the price set values for receipt.
    if ($form->_priceSetId && $form->_lineItem) {
      $form->_values['lineItem'] = $form->_lineItem;
      $form->_values['priceSetID'] = $form->_priceSetId;
    }

    // finally send an email receipt

    $form->_values['contribution_id'] = $contribution->id;
    CRM_Contribute_BAO_ContributionPage::sendMail($contactID, $form->_values, $contribution->is_test);
    return [];
  }

  /**
   * Function to get the contribution details by month
   * of the year
   *
   * @param int     $param year
   *
   * @return array associated array
   *
   * @static
   * @access public
   */
  static function contributionChartMonthly($param) {
    if ($param) {
      $param = [1 => [$param, 'Integer']];
    }
    else {
      $param = date("Y");
      $param = [1 => [$param, 'Integer']];
    }

    $query = "
    SELECT   sum(contrib.total_amount) AS ctAmt,
             MONTH( contrib.receive_date) AS contribMonth
      FROM   civicrm_contribution AS contrib
INNER JOIN   civicrm_contact AS contact ON ( contact.id = contrib.contact_id ) 
     WHERE   contrib.contact_id = contact.id
       AND   ( contrib.is_test = 0 OR contrib.is_test IS NULL )
       AND   contrib.contribution_status_id = 1
       AND   date_format(contrib.receive_date,'%Y') = %1 
       AND   contact.is_deleted = 0 
  GROUP BY   contribMonth
  ORDER BY   month(contrib.receive_date)";

    $dao = CRM_Core_DAO::executeQuery($query, $param);

    $params = NULL;
    $slot = array_fill(1, 12, 0);
    while ($dao->fetch()) {
      if ($dao->contribMonth) {
        $slot[$dao->contribMonth] = $dao->ctAmt;
      }
    }
    return ['By Month' => $slot];
  }

  /**
   * Function to get the contribution details by year
   *
   * @return array associated array
   *
   * @static
   * @access public
   */
  static function contributionChartYearly() {
    $query = '
    SELECT   sum(contrib.total_amount) AS ctAmt,
             year(contrib.receive_date) as contribYear
      FROM   civicrm_contribution AS contrib
INNER JOIN   civicrm_contact contact ON ( contact.id = contrib.contact_id ) 
     WHERE   ( contrib.is_test = 0 OR contrib.is_test IS NULL )
       AND   contrib.contribution_status_id = 1
       AND   contact.is_deleted = 0 
  GROUP BY   contribYear
  ORDER BY   contribYear';
    $dao = CRM_Core_DAO::executeQuery($query);

    $params = NULL;
    while ($dao->fetch()) {
      if (!empty($dao->contribYear)) {
        $params['By Year'][$dao->contribYear] = $dao->ctAmt;
      }
    }
    return $params;
  }

  static function createCMSUser(&$params, $contactID, $mail) {
    // lets ensure we only create one CMS user
    static $created = FALSE;

    if ($created) {
      return;
    }
    $created = TRUE;

    if (CRM_Utils_Array::value('cms_create_account', $params)) {
      $params['contactID'] = $contactID;

      if (!CRM_Core_BAO_CMSUser::create($params, $mail)) {
         return CRM_Core_Error::statusBounce(ts('Your profile is not saved and Account is not created.'));
      }
    }
  }

  static function _fillCommonParams(&$params, $type = 'paypal') {
    if (CRM_Utils_Array::arrayKeyExists('transaction', $params)) {
      $transaction = &$params['transaction'];
    }
    else {
      $transaction = &$params;
    }

    $params['contact_type'] = 'Individual';

    $billingLocTypeId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_LocationType', 'Billing', 'id', 'name');
    if (!$billingLocTypeId) {
      $billingLocTypeId = 1;
    }
    if (!CRM_Utils_System::isNull($params['address'])) {
      $params['address'][1]['is_primary'] = 1;
      $params['address'][1]['location_type_id'] = $billingLocTypeId;
    }
    if (!CRM_Utils_System::isNull($params['email'])) {
      $params['email'] = [1 => ['email' => $params['email'],
          'location_type_id' => $billingLocTypeId,
        ]];
    }

    if (isset($transaction['trxn_id'])) {
      // set error message if transaction has already been processed.

      $contribution = new CRM_Contribute_DAO_Contribution();
      $contribution->trxn_id = $transaction['trxn_id'];
      if ($contribution->find(TRUE)) {
        $params['error'][] = ts('transaction already processed.');
      }
    }
    else {
      // generate a new transaction id, if not already exist
      $transaction['trxn_id'] = md5(uniqid((string)rand(), TRUE));
    }

    if (!isset($transaction['contribution_type_id'])) {

      $contributionTypes = array_keys(CRM_Contribute_PseudoConstant::contributionType());
      $transaction['contribution_type_id'] = $contributionTypes[0];
    }

    if (($type == 'paypal') && (!isset($transaction['net_amount']))) {
      $transaction['net_amount'] = $transaction['total_amount'] - CRM_Utils_Array::value('fee_amount', $transaction, 0);
    }

    if (!isset($transaction['invoice_id'])) {
      $transaction['invoice_id'] = $transaction['trxn_id'];
    }

    $source = ts('ContributionProcessor: %1 API',
      [1 => ucfirst($type)]
    );
    if (isset($transaction['source'])) {
      $transaction['source'] = $source . ':: ' . $transaction['source'];
    }
    else {
      $transaction['source'] = $source;
    }

    return TRUE;
  }

  static function formatAPIParams($apiParams, $mapper, $type = 'paypal', $category = TRUE) {
    $type = strtolower($type);

    if (!in_array($type, ['paypal', 'google', 'csv'])) {
      // return the params as is
      return $apiParams;
    }
    $params = $transaction = [];

    if ($type == 'paypal') {
      foreach ($apiParams as $detail => $val) {
        if (isset($mapper['contact'][$detail])) {
          $params[$mapper['contact'][$detail]] = $val;
        }
        elseif (isset($mapper['location'][$detail])) {
          $params['address'][1][$mapper['location'][$detail]] = $val;
        }
        elseif (isset($mapper['transaction'][$detail])) {
          switch ($detail) {
            case 'l_period2':
              // Sadly, PayPal seems to send two distinct data elements in a single field,
              // so we break them out here.  This is somewhat ugly and tragic.
              $freqUnits = [
                'D' => 'day',
                'W' => 'week',
                'M' => 'month',
                'Y' => 'year',
              ];
              list($frequency_interval, $frequency_unit) = explode(' ', $val);
              $transaction['frequency_interval'] = $frequency_interval;
              $transaction['frequency_unit'] = $freqUnits[$frequency_unit];
              break;

            case 'subscriptiondate':
            case 'timestamp':
              // PayPal dates are in  ISO-8601 format.  We need a format that
              // MySQL likes
              $unix_timestamp = strtotime($val);
              $transaction[$mapper['transaction'][$detail]] = date('YmdHis', $unix_timestamp);
              break;

            default:
              $transaction[$mapper['transaction'][$detail]] = $val;
          }
        }
      }

      if (!empty($transaction) && $category) {
        $params['transaction'] = $transaction;
      }
      else {
        $params += $transaction;
      }

      self::_fillCommonParams($params, $type);

      return $params;
    }

    if ($type == 'csv') {
      $header = $apiParams['header'];
      unset($apiParams['header']);
      foreach ($apiParams as $key => $val) {
        if (isset($mapper['contact'][$header[$key]])) {
          $params[$mapper['contact'][$header[$key]]] = $val;
        }
        elseif (isset($mapper['location'][$header[$key]])) {
          $params['address'][1][$mapper['location'][$header[$key]]] = $val;
        }
        elseif (isset($mapper['transaction'][$header[$key]])) {
          $transaction[$mapper['transaction'][$header[$key]]] = $val;
        }
        else {
          $params[$header[$key]] = $val;
        }
      }

      if (!empty($transaction) && $category) {
        $params['transaction'] = $transaction;
      }
      else {
        $params += $transaction;
      }

      self::_fillCommonParams($params, $type);

      return $params;
    }

    if ($type == 'google') {
      // return if response smell invalid
      if (!CRM_Utils_Array::arrayKeyExists('risk-information-notification', $apiParams[1][$apiParams[0]]['notifications'])) {
        return FALSE;
      }
      $riskInfo = &$apiParams[1][$apiParams[0]]['notifications']['risk-information-notification'];

      if (CRM_Utils_Array::arrayKeyExists('new-order-notification', $apiParams[1][$apiParams[0]]['notifications'])) {
        $newOrder = &$apiParams[1][$apiParams[0]]['notifications']['new-order-notification'];
      }

      if ($riskInfo['google-order-number']['VALUE'] == $apiParams[2]['google-order-number']['VALUE']) {
        foreach ($riskInfo['risk-information']['billing-address'] as $field => $info) {
          if (CRM_Utils_Array::value($field, $mapper['location'])) {
            $params['address'][1][$mapper['location'][$field]] = $info['VALUE'];
          }
          elseif (CRM_Utils_Array::value($field, $mapper['contact'])) {
            if ($newOrder && CRM_Utils_Array::value('structured-name', $newOrder['buyer-billing-address'])) {
              foreach ($newOrder['buyer-billing-address']['structured-name'] as $namePart => $nameValue) {
                $params[$mapper['contact'][$namePart]] = $nameValue['VALUE'];
              }
            }
            else {
              $params[$mapper['contact'][$field]] = $info['VALUE'];
            }
          }
          elseif (CRM_Utils_Array::value($field, $mapper['transaction'])) {
            $transaction[$mapper['transaction'][$field]] = $info['VALUE'];
          }
        }

        // Response is an huge array. Lets pickup only those which we ineterested in
        // using a local mapper, rather than traversing the entire array.
        $localMapper = ['google-order-number' => $riskInfo['google-order-number']['VALUE'],
          'total-charge-amount' => $apiParams[2]['total-charge-amount']['VALUE'],
          'currency' => $apiParams[2]['total-charge-amount']['currency'],
          'item-name' => $newOrder['shopping-cart']['items']['item']['item-name']['VALUE'],
          'timestamp' => $apiParams[2]['timestamp']['VALUE'],
        ];
        if (CRM_Utils_Array::arrayKeyExists('latest-charge-fee', $apiParams[2])) {
          $localMapper['latest-charge-fee'] = $apiParams[2]['latest-charge-fee']['total']['VALUE'];
          $localMapper['net-amount'] = $localMapper['total-charge-amount'] - $localMapper['latest-charge-fee'];
        }
        foreach ($localMapper as $localKey => $localVal) {
          if (CRM_Utils_Array::value($localKey, $mapper['transaction'])) {
            $transaction[$mapper['transaction'][$localKey]] = $localVal;
          }
        }

        if (!empty($transaction) && $category) {
          $params['transaction'] = $transaction;
        }
        else {
          $params += $transaction;
        }

        self::_fillCommonParams($params, $type);
      }
      return $params;
    }
  }

  static function processAPIContribution($params) {
    if (empty($params) || CRM_Utils_Array::arrayKeyExists('error', $params)) {
      return FALSE;
    }

    // add contact using dedupe rule

    $dedupeParams = CRM_Dedupe_Finder::formatParams($params, 'Individual');
    $dedupeParams['check_permission'] = FALSE;
    $dupeIds = CRM_Dedupe_Finder::dupesByParams($dedupeParams, 'Individual');
    // if we find more than one contact, use the first one
    if (CRM_Utils_Array::value(0, $dupeIds)) {
      $params['contact_id'] = $dupeIds[0];
    }

    $contact = CRM_Contact_BAO_Contact::create($params);
    if (!$contact->id) {
      return FALSE;
    }

    // only pass transaction params to contribution::create, if available
    if (CRM_Utils_Array::arrayKeyExists('transaction', $params)) {
      $params = $params['transaction'];
      $params['contact_id'] = $contact->id;
    }

    // handle contribution custom data
    $customFields = CRM_Core_BAO_CustomField::getFields('Contribution',
      FALSE,
      FALSE,
      CRM_Utils_Array::value('contribution_type_id',
        $params
      )
    );
    $params['custom'] = CRM_Core_BAO_CustomField::postProcess($params,
      $customFields,
      CRM_Utils_Array::value('id', $params, NULL),
      'Contribution'
    );
    // create contribution

    // if this is a recurring contribution then process it first
    if ($params['trxn_type'] == 'subscrpayment') {
      // see if a recurring record already exists

      $recurring = new CRM_Contribute_BAO_ContributionRecur;
      $recurring->external_id = $params['external_id'];
      if (!$recurring->find(TRUE)) {
        $recurring = new CRM_Contribute_BAO_ContributionRecur;
        $recurring->invoice_id = $params['invoice_id'];
        $recurring->find(TRUE);
      }

      // This is the same thing the CiviCRM IPN handler does to handle
      // subsequent recurring payments to avoid duplicate contribution
      // errors due to invoice ID. See:
      // ./CRM/Core/Payment/PayPalIPN.php:200
      if ($recurring->id) {
        $params['invoice_id'] = md5(uniqid((string)rand(), TRUE));
      }

      $recurring->copyValues($params);
      $recurring->save();
      if (is_a($recurring, 'CRM_Core_Error')) {
        return FALSE;
      }
      else {
        $params['contribution_recur_id'] = $recurring->id;
      }
    }


    $contribution = &CRM_Contribute_BAO_Contribution::create($params,
      CRM_Core_DAO::$_nullArray
    );
    if (!$contribution->id) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Payment result message after submit contribution
   *
   * @param object  $form   form object
   * @param array   $params array with contribution related field to find contribution
   * @param int     $status contribution status for indicate message type
   */
  public static function paymentResultType(&$form, $params, $status = NULL, $message = NULL){
    if(empty($status)){
      // trying to get status from GET
      $status = CRM_Utils_Request::retrieve('payment_result_type', 'Int', $form);
      if (empty($status)) {
        $contribution = new CRM_Contribute_BAO_Contribution();
        $contribution->copyValues($params);
        if($contribution->find(TRUE)) {
          $status = $contribution->contribution_status_id;

          // Check if it is tappay 3d validation
          if ($contribution->payment_processor_id) {
            $mode = $contribution->is_test ? 'test' : 'live';
            $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($contribution->payment_processor_id, $mode);
            if ($paymentProcessor['payment_processor_type'] == 'TapPay' && $paymentProcessor['url_site'] == 1) {
              $status = CRM_Utils_Request::retrieve('status', 'String', $form);
              if ($status == '0') {
                $status = 1;
              }
              else {
                $status = 4;
              }
            }
          }
        }
      }
    }

    switch ($status){
      case 1: // success
        $form->assign('payment_result_type', 1);
        break;
      case 2:
        if(empty($contribution)){
          $contribution = new CRM_Contribute_BAO_Contribution();
          $contribution->copyValues($params);
          $contribution->find(TRUE);
        }
        if(!empty($contribution->payment_instrument_id)){
          $form->assign('payment_result_type', 2);
        }
        break;
      case 4: // failed
        CRM_Utils_System::setTitle(ts('Payment failed.'));
        $form->assign('payment_result_type', 4);
        break;
    }

    if (empty($message)) {
      $message = CRM_Utils_Request::retrieve('payment_result_message', 'String', $form);
    }
    $form->assign('payment_result_message', $message);
    return $status;
  }

  /**
   * Payment result message after submit contribution
   *
   * @param int     $contribution_id
   */
  public static function invoiceLink($contribution_id, $check = FALSE){
    if($check){
      $contribution = new CRM_Contribute_DAO_Contribution();
      $contribution->id = $contribution_id;
      if($contribution->find(TRUE)){
        if($contribution->contribution_status_id == 2){
          CRM_Utils_Hook::prepareInvoice($contribution_id, $tplParams, $message);
          if(!empty($tplParams)){
            $invoice_id = $contribution->invoice_id;
          }
        }
      }
    }
    else{
      $invoice_id = CRM_Core_DAO::singleValueQuery("SELECT invoice_id FROM civicrm_contribution WHERE id = %1", [1 => [$contribution_id, 'Integer']]);
    }
    if(!empty($invoice_id)){
      return CRM_Utils_System::url('civicrm/contribute/invoice', 'reset=1&ii='.$invoice_id, TRUE);
    }
    return FALSE;
  }

  /**
   * Purge one time link record in database
   */
  public static function clearDonateAgainLink() {
    $aweekbefore = CRM_REQUEST_TIME - 86400*7;
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_sequence WHERE `name` LIKE 'DA_%' AND `timestamp` < %1", [
      1 => [$aweekbefore, 'Integer']
    ]);
  }  
}