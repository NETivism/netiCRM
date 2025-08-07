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


class CRM_Core_Payment_PayPalImpl extends CRM_Core_Payment {
  /**
   * @var mixed
   */
  public $_processorName;
  CONST CHARSET = 'iso-8859-1';

  protected $_mode = NULL;

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   * @static
   */
  static private $_singleton = NULL;

  /**
   * Constructor
   *
   * @param string $mode the mode of operation: live or test
   *
   * @return void
   */
  function __construct($mode, &$paymentProcessor) {
    $this->_mode = $mode;
    $this->_paymentProcessor = $paymentProcessor;
    $this->_processorName = ts('PayPal Pro');

    if ($this->_paymentProcessor['payment_processor_type'] == 'PayPal_Standard') {
      $this->_processorName = ts('PayPal Standard');
      return;
    }
    elseif ($this->_paymentProcessor['payment_processor_type'] == 'PayPal_Express') {
      $this->_processorName = ts('PayPal Express');
    }

    if (!$this->_paymentProcessor['user_name']) {
      CRM_Core_Error::fatal(ts('Could not find user name for payment processor'));
    }
  }

  /**
   * singleton function used to manage this object
   *
   * @param string $mode the mode of operation: live or test
   *
   * @return object
   * @static
   *
   */
  static function &singleton($mode, &$paymentProcessor, &$paymentForm = NULL) {
    $processorName = $paymentProcessor['name'];
    if (self::$_singleton[$processorName] === NULL) {
      self::$_singleton[$processorName] = new CRM_Core_Payment_PayPalImpl($mode, $paymentProcessor);
    }
    return self::$_singleton[$processorName];
  }

  /**
   * express checkout code. Check PayPal documentation for more information
   *
   * @param  array $params assoc array of input parameters for this transaction
   *
   * @return array the result in an nice formatted array (or an error object)
   * @public
   */
  function setExpressCheckOut(&$params) {
    $args = [];

    $this->initialize($args, 'SetExpressCheckout');

    $args['paymentAction'] = $params['payment_action'];
    $args['amt'] = $params['amount'];
    $args['currencyCode'] = $params['currencyID'];
    $args['invnum'] = $params['invoiceID'];
    $args['returnURL'] = $params['returnURL'];
    $args['cancelURL'] = $params['cancelURL'];
    $args['version'] = '56.0';

    //LCD if recurring, collect additional data and set some values
    if ($params['is_recur']) {
      $args['L_BILLINGTYPE0'] = 'RecurringPayments';
      //$args['L_BILLINGAGREEMENTDESCRIPTION0'] = 'Recurring Contribution';
      $args['L_BILLINGAGREEMENTDESCRIPTION0'] = $params['amount'] . " Per " . $params['frequency_interval'] . " " . $params['frequency_unit'];
      $args['L_PAYMENTTYPE0'] = 'Any';
    }

    // Allow further manipulation of the arguments via custom hooks ..
    CRM_Utils_Hook::alterPaymentProcessorParams($this, $params, $args);

    $result = $this->invokeAPI($args);

    if (is_a($result, 'CRM_Core_Error')) {
      return $result;
    }

    /* Success */

    return $result['token'];
  }

  /**
   * get details from paypal. Check PayPal documentation for more information
   *
   * @param  string $token the key associated with this transaction
   *
   * @return array the result in an nice formatted array (or an error object)
   * @public
   */
  function getExpressCheckoutDetails($token) {
    $args = [];

    $this->initialize($args, 'GetExpressCheckoutDetails');
    $args['token'] = $token;
    // LCD
    $args['method'] = 'GetExpressCheckoutDetails';

    $result = $this->invokeAPI($args);

    if (is_a($result, 'CRM_Core_Error')) {
      return $result;
    }

    /* Success */

    $params = [];
    $params['token'] = $result['token'];
    $params['payer_id'] = $result['payerid'];
    $params['payer_status'] = $result['payerstatus'];
    $params['first_name'] = $result['firstname'];
    $params['middle_name'] = $result['middlename'];
    $params['last_name'] = $result['lastname'];
    $params['street_address'] = $result['shiptostreet'];
    $params['supplemental_address_1'] = $result['shiptostreet2'];
    $params['city'] = $result['shiptocity'];
    $params['state_province'] = $result['shiptostate'];
    $params['postal_code'] = $result['shiptozip'];
    $params['country'] = $result['shiptocountrycode'];

    return $params;
  }

  /**
   * do the express checkout at paypal. Check PayPal documentation for more information
   *
   * @param  string $token the key associated with this transaction
   *
   * @return array the result in an nice formatted array (or an error object)
   * @public
   */
  function doExpressCheckout(&$params) {
    $args = [];

    $this->initialize($args, 'DoExpressCheckoutPayment');

    $args['token'] = $params['token'];
    $args['paymentAction'] = $params['payment_action'];
    $args['amt'] = $params['amount'];
    $args['currencyCode'] = $params['currencyID'];
    $args['payerID'] = $params['payer_id'];
    $args['invnum'] = $params['invoiceID'];
    $args['returnURL'] = $params['returnURL'];
    $args['cancelURL'] = $params['cancelURL'];

    $result = $this->invokeAPI($args);

    if (is_a($result, 'CRM_Core_Error')) {
      return $result;
    }

    /* Success */

    $params['trxn_id'] = $result['transactionid'];
    $params['gross_amount'] = $result['amt'];
    $params['fee_amount'] = $result['feeamt'];
    $params['net_amount'] = $result['settleamt'];
    if ($params['net_amount'] == 0 && $params['fee_amount'] != 0) {
      $params['net_amount'] = $params['gross_amount'] - $params['fee_amount'];
    }
    $params['payment_status'] = $result['paymentstatus'];
    $params['pending_reason'] = $result['pendingreason'];

    return $params;
  }

  //LCD add new function for handling recurring payments for PayPal Express
  function createRecurringPayments(&$params) {
    $args = [];

    $this->initialize($args, 'CreateRecurringPaymentsProfile');

    $start_time = strtotime(date('m/d/Y'));
    $start_date = date('Y-m-d\T00:00:00\Z', $start_time);

    $args['token'] = $params['token'];
    $args['paymentAction'] = $params['payment_action'];
    $args['amt'] = $params['amount'];
    $args['currencyCode'] = $params['currencyID'];
    $args['payerID'] = $params['payer_id'];
    $args['invnum'] = $params['invoiceID'];
    $args['returnURL'] = $params['returnURL'];
    $args['cancelURL'] = $params['cancelURL'];
    $args['profilestartdate'] = $start_date;
    $args['method'] = 'CreateRecurringPaymentsProfile';
    $args['billingfrequency'] = $params['frequency_interval'];
    $args['billingperiod'] = ucwords($params['frequency_unit']);
    $args['desc'] = $params['amount'] . " Per " . $params['frequency_interval'] . " " . $params['frequency_unit'];
    //$args['desc']           = 'Recurring Contribution';
    $args['totalbillingcycles'] = $params['installments'];
    $args['version'] = '56.0';
    $args['profilereference'] = "i=" . $params['invoiceID'] . "&m=" . $component . "&c=" . $params['contactID'] . "&r=" . $params['contributionRecurID'] . "&b=" . $params['contributionID'] . "&p=" . $params['contributionPageID'];

    $result = $this->invokeAPI($args);

    if (is_a($result, 'CRM_Core_Error')) {
      return $result;
    }

    /* Success */

    $params['trxn_id'] = $result['transactionid'];
    $params['gross_amount'] = $result['amt'];
    $params['fee_amount'] = $result['feeamt'];
    $params['net_amount'] = $result['settleamt'];
    if ($params['net_amount'] == 0 && $params['fee_amount'] != 0) {
      $params['net_amount'] = $params['gross_amount'] - $params['fee_amount'];
    }
    $params['payment_status'] = $result['paymentstatus'];
    $params['pending_reason'] = $result['pendingreason'];

    return $params;
  }
  //LCD end
  function initialize(&$args, $method) {
    $args['user'] = $this->_paymentProcessor['user_name'];
    $args['pwd'] = $this->_paymentProcessor['password'];
    $args['version'] = 3.0;
    $args['signature'] = $this->_paymentProcessor['signature'];
    $args['subject'] = $this->_paymentProcessor['subject'];
    $args['method'] = $method;
  }

  /**
   * This function collects all the information from a web/api form and invokes
   * the relevant payment processor specific functions to perform the transaction
   *
   * @param  array $params assoc array of input parameters for this transaction
   *
   * @return array the result in an nice formatted array (or an error object)
   * @public
   */
  function doDirectPayment(&$params, $component = 'contribute') {
    $args = [];

    $this->initialize($args, 'DoDirectPayment');

    $args['paymentAction'] = $params['payment_action'];
    $args['amt'] = $params['amount'];
    $args['currencyCode'] = $params['currencyID'];
    $args['invnum'] = $params['invoiceID'];
    $args['ipaddress'] = $params['ip_address'];
    $args['creditCardType'] = $params['credit_card_type'];
    $args['acct'] = $params['credit_card_number'];
    $args['expDate'] = sprintf('%02d', $params['month']) . $params['year'];
    $args['cvv2'] = $params['cvv2'];
    $args['firstName'] = $params['first_name'];
    $args['lastName'] = $params['last_name'];
    $args['email'] = $params['email'];
    $args['street'] = $params['street_address'];
    $args['city'] = $params['city'];
    $args['state'] = $params['state_province'];
    $args['countryCode'] = $params['country'];
    $args['zip'] = $params['postal_code'];
    $args['desc'] = $params['description'];
    $args['custom'] = CRM_Utils_Array::value('accountingCode',
      $params
    );
    if ($params['is_recur'] == 1) {
      $start_time = strtotime(date('m/d/Y'));
      $start_date = date('Y-m-d\T00:00:00\Z', $start_time);

      $args['PaymentAction'] = 'Sale';
      $args['billingperiod'] = ucwords($params['frequency_unit']);
      $args['billingfrequency'] = $params['frequency_interval'];
      $args['method'] = "CreateRecurringPaymentsProfile";
      $args['profilestartdate'] = $start_date;
      $args['desc'] = $params['description'] . ": " . $params['amount'] . " Per " . $params['frequency_interval'] . " " . $params['frequency_unit'];
      $args['amt'] = $params['amount'];
      $args['totalbillingcycles'] = $params['installments'];
      $args['version'] = 56.0;
      $args['PROFILEREFERENCE'] = "i=" . $params['invoiceID'] . "&m=" . $component . "&c=" . $params['contactID'] . "&r=" . $params['contributionRecurID'] . "&b=" . $params['contributionID'] . "&p=" . $params['contributionPageID'];
    }

    // Allow further manipulation of the arguments via custom hooks ..
    CRM_Utils_Hook::alterPaymentProcessorParams($this, $params, $args);

    $result = $this->invokeAPI($args);

    //WAG
    if (is_a($result, CRM_Core_Error)) {
      return $result;
    }

    $params['recurr_profile_id'] = NULL;

    if (CRM_Utils_Array::value('is_recur', $params) == 1) {
      $params['recurr_profile_id'] = $result['profileid'];
    }

    /* Success */

    $params['trxn_id'] = $result['transactionid'];
    $params['gross_amount'] = $result['amt'];
    return $params;
  }

  /**
   * This function checks to see if we have the right config values
   *
   * @return string the error message if any
   * @public
   */
  function checkConfig() {
    $error = [];
    if ($this->_paymentProcessor['payment_processor_type'] == 'PayPal_Standard' ||
      $this->_paymentProcessor['payment_processor_type'] == 'PayPal'
    ) {
      if (empty($this->_paymentProcessor['user_name'])) {
        $error[] = ts('User Name is not set in the Administer CiviCRM &raquo; Payment Processor.');
      }
    }

    if ($this->_paymentProcessor['payment_processor_type'] != 'PayPal_Standard') {
      if (empty($this->_paymentProcessor['signature'])) {
        $error[] = ts('Signature is not set in the Administer CiviCRM &raquo; Payment Processor.');
      }

      if (empty($this->_paymentProcessor['password'])) {
        $error[] = ts('Password is not set in the Administer CiviCRM &raquo; Payment Processor.');
      }
    }

    if (!empty($error)) {
      return CRM_Utils_Array::implode('<p>', $error);
    }
    else {
      return NULL;
    }
  }

  function cancelSubscriptionURL() {
    if ($this->_paymentProcessor['payment_processor_type'] == 'PayPal_Standard') {
      return "{$this->_paymentProcessor['url_site']}cgi-bin/webscr?cmd=_subscr-find&alias=" . urlencode($this->_paymentProcessor['user_name']);
    }
    else {
      return NULL;
    }
  }

  function doTransferCheckout(&$params, $component = 'contribute') {
    $config = CRM_Core_Config::singleton();

    if ($component != 'contribute' && $component != 'event') {
      CRM_Core_Error::fatal(ts('Component is invalid'));
    }

    $testingParam = $this->_mode == 'test' ? '&action=preview' : '';

    $notifyURL = $config->userFrameworkResourceURL . "extern/ipn.php?reset=1&contactID={$params['contactID']}" . "&contributionID={$params['contributionID']}" . "&module={$component}";

    if ($component == 'event') {
      $notifyURL .= "&eventID={$params['eventID']}&participantID={$params['participantID']}";
    }
    else {
      $membershipID = CRM_Utils_Array::value('membershipID', $params);
      if ($membershipID) {
        $notifyURL .= "&membershipID=$membershipID";
      }
      $relatedContactID = CRM_Utils_Array::value('related_contact', $params);
      if ($relatedContactID) {
        $notifyURL .= "&relatedContactID=$relatedContactID";

        $onBehalfDupeAlert = CRM_Utils_Array::value('onbehalf_dupe_alert', $params);
        if ($onBehalfDupeAlert) {
          $notifyURL .= "&onBehalfDupeAlert=$onBehalfDupeAlert";
        }
      }
    }

    $url = ($component == 'event') ? 'civicrm/event/register' : 'civicrm/contribute/transact';
    $returnURL = CRM_Utils_System::url($url, "_qf_ThankYou_display=1&qfKey={$params['qfKey']}", TRUE, NULL, FALSE);
    $cancelURL = CRM_Utils_System::url($url, "_qf_ThankYou_display=1&qfKey={$params['qfKey']}&payment_result_type=4", TRUE, NULL, FALSE);

    $paypalParams = ['business' => $this->_paymentProcessor['user_name'],
      'notify_url' => $notifyURL,
      'item_name' => $params['item_name'],
      'quantity' => 1,
      'undefined_quantity' => 0,
      'cancel_return' => $cancelURL,
      'no_note' => 1,
      'no_shipping' => 1,
      'return' => $returnURL,
      'rm' => 2,
      'currency_code' => $params['currencyID'],
      'invoice' => $params['invoiceID'],
      'lc' => substr($config->lcMessages, -2),
      'charset' => function_exists('mb_internal_encoding') ? mb_internal_encoding() : 'UTF-8',
      'custom' => CRM_Utils_Array::value('accountingCode',
        $params
      ),
    ];

    // add name and address if available, CRM-3130
    $otherVars = ['first_name' => 'first_name',
      'last_name' => 'last_name',
      'street_address' => 'address1',
      'country' => 'country',
      'preferred_language' => 'lc',
      'city' => 'city',
      'state_province' => 'state',
      'postal_code' => 'zip',
      'email' => 'email',
    ];

    foreach (array_keys($params) as $p) {
      // get the base name without the location type suffixed to it
      $parts = explode('-', $p);
      $name = count($parts) > 1 ? $parts[0] : $p;
      if (isset($otherVars[$name])) {
        $value = $params[$p];
        if ($value) {
          if ($name == 'state_province') {
            $stateName = CRM_Core_PseudoConstant::stateProvinceAbbreviation($value);
            $value = $stateName;
          }
          if ($name == 'country') {
            $countryName = CRM_Core_PseudoConstant::countryIsoCode($value);
            $value = $countryName;
          }
          // ensure value is not an array
          // CRM-4174
          if (!is_array($value)) {
            $paypalParams[$otherVars[$name]] = $value;
          }
        }
      }
    }

    // if recurring donations, add a few more items
    if (!empty($params['is_recur'])) {
      if ($params['contributionRecurID']) {
        $notifyURL .= "&contributionRecurID={$params['contributionRecurID']}&contributionPageID={$params['contributionPageID']}";
        $paypalParams['notify_url'] = $notifyURL;
      }
      else {
        CRM_Core_Error::fatal(ts('Recurring contribution, but no database id'));
      }
      $paypalParams += [
        'cmd' => '_xclick-subscriptions',
        'a3' => $params['amount'],
        'p3' => $params['frequency_interval'],
        't3' => ucfirst(substr($params['frequency_unit'], 0, 1)),
        'src' => 1,
        'sra' => 1,
        'srt' => ($params['installments'] > 0) ? $params['installments'] : NULL,
        'no_note' => 1,
        'modify' => 0,
      ];
    }
    else {
      $paypalParams += [
        'cmd' => '_xclick',
        'amount' => $params['amount'],
      ];
    }

    // Allow further manipulation of the arguments via custom hooks ..
    CRM_Utils_Hook::alterPaymentProcessorParams($this, $params, $paypalParams);

    $uri = '';
    foreach ($paypalParams as $key => $value) {
      if ($value === NULL) {
        continue;
      }

      $value = urlencode($value);
      if ($key == 'return' ||
        $key == 'cancel_return' ||
        $key == 'notify_url'
      ) {
        $value = str_replace('%2F', '/', $value);
      }
      $uri .= "&{$key}={$value}";
    }

    $uri = substr($uri, 1);
    $url = $this->_paymentProcessor['url_site'];
    $sub = empty($params['is_recur']) ? 'cgi-bin/webscr' : 'subscriptions';
    $paypalURL = "{$url}{$sub}?$uri";

    CRM_Utils_System::redirect($paypalURL);
  }

  /**
   * hash_call: Function to perform the API call to PayPal using API signature
   * @methodName is name of API  method.
   * @nvpStr is nvp string.
   * returns an associtive array containing the response from the server.
   */
  function invokeAPI($args, $url = NULL) {

    if ($url === NULL) {
      if (empty($this->_paymentProcessor['url_api'])) {
        CRM_Core_Error::fatal(ts('Please set the API URL. Please refer to the documentation for more details'));
      }

      $url = $this->_paymentProcessor['url_api'] . 'nvp';
    }

    if (!function_exists('curl_init')) {
      CRM_Core_Error::fatal("curl functions NOT available.");
    }

    //setting the curl parameters.
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_VERBOSE, 1);

    //turning off the server and peer verification(TrustManager Concept).
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);

    $p = [];
    foreach ($args as $n => $v) {
      $p[] = "$n=" . urlencode($v);
    }

    //NVPRequest for submitting to server
    $nvpreq = CRM_Utils_Array::implode('&', $p);

    //setting the nvpreq as POST FIELD to curl
    curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);

    //getting response from server
    $response = curl_exec($ch);

    //converting NVPResponse to an Associative Array
    $result = self::deformat($response);

    if (curl_errno($ch)) {
      $e = &CRM_Core_Error::singleton();
      $e->push(curl_errno($ch),
        0, NULL,
        curl_error($ch)
      );
      return $e;
    }
    else {
      curl_close($ch);
    }

    if (strtolower($result['ack']) != 'success' &&
      strtolower($result['ack']) != 'successwithwarning'
    ) {
      $e = &CRM_Core_Error::singleton();
      $e->push($result['l_errorcode0'],
        0, NULL,
        "{$result['l_shortmessage0']} {$result['l_longmessage0']}"
      );
      return $e;
    }

    return $result;
  }

  /** This function will take NVPString and convert it to an Associative Array and it will decode the response.
   * It is usefull to search for a particular key and displaying arrays.
   * @nvpstr is NVPString.
   * @nvpArray is Associative Array.
   */

  static function deformat($str) {
    $result = [];

    while (strlen($str)) {
      // postion of key
      $keyPos = strpos($str, '=');

      // position of value
      $valPos = strpos($str, '&') ? strpos($str, '&') : strlen($str);

      /*getting the Key and Value values and storing in a Associative Array*/

      $key = substr($str, 0, $keyPos);
      $val = substr($str, $keyPos + 1, $valPos - $keyPos - 1);

      //decoding the respose
      $result[strtolower(urldecode($key))] = urldecode($val);
      $str = substr($str, $valPos + 1, strlen($str));
    }

    return $result;
  }
}

