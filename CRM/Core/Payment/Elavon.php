<?php
/*
 +----------------------------------------------------------------------------+
 | Elavon (Nova) Virtual Merchant Core Payment Module for CiviCRM version 3.3 |
 +----------------------------------------------------------------------------+
 | Licensed to CiviCRM under the Academic Free License version 3.0            |
 |                                                                            |
 | Written & Contributed by Eileen McNaughton - Nov March 2008                |
 +----------------------------------------------------------------------------+
*/

/**
 -----------------------------------------------------------------------------------------------
 The basic functionality of this processor is that variables from the $params object are transformed
 into xml. The xml is submitted to the processor's https site
 using curl and the response is translated back into an array using the processor's function.

 If an array ($params) is returned to the calling function the values from
 the array are merged into the calling functions array.

 If an result of class error is returned it is treated as a failure. No error denotes a success. Be
 WARY of this when coding

 -----------------------------------------------------------------------------------------------
 **/


require_once 'CRM/Core/Payment.php';
class CRM_Core_Payment_Elavon extends CRM_Core_Payment {
  // (not used, implicit in the API, might need to convert?)
  CONST
  CHARSET = 'UFT-8';

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   * @static
   */
  static private $_singleton = NULL;

  /**********************************************************
   * Constructor
   *
   * @param string $mode the mode of operation: live or test
   *
   * @return void
   **********************************************************/
  function __construct($mode, &$paymentProcessor) {
    // live or test
    $this->_mode = $mode;
    $this->_paymentProcessor = $paymentProcessor;
    $this->_processorName = ts('Elavon');
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
      self::$_singleton[$processorName] = new CRM_Core_Payment_Elavon($mode, $paymentProcessor);
    }
    return self::$_singleton[$processorName];
  }

  /**********************************************************
   * This function is set up and put here to make the mapping of fields
   * from the params object  as visually clear as possible for easy editing
   *
   *  Comment out irrelevant fields
   **********************************************************/
  function mapProcessorFieldstoParams($params) {

    /**********************************************************
     * compile array
     * Payment Processor field name fields from $params array
     **********************************************************/
    // credit card name
    $requestFields['ssl_first_name'] = $params['billing_first_name'];
    // credit card name
    //$requestFields['ssl_middle_name']	     = $params['billing_middle_name'];
    // credit card name
    $requestFields['ssl_last_name'] = $params['billing_last_name'];
    // contact name
    $requestFields['ssl_ship_to_first_name'] = $params['first_name'];
    // contact name
    $requestFields['ssl_ship_to_last_name'] = $params['last_name'];
    $requestFields['ssl_card_number'] = $params['credit_card_number'];
    $requestFields['ssl_amount'] = $params['amount'];
    $requestFields['ssl_exp_date'] = sprintf('%02d', (int) $params['month']) . substr($params['year'], 2, 2);;
    $requestFields['ssl_cvv2cvc2'] = $params['cvv2'];
    // CVV field passed to processor
    $requestFields['ssl_cvv2cvc2_indicator'] = "1";
    $requestFields['ssl_avs_address'] = $params['street_address'];
    $requestFields['ssl_city'] = $params['city'];
    $requestFields['ssl_state'] = $params['state_province'];
    $requestFields['ssl_avs_zip'] = $params['postal_code'];
    $requestFields['ssl_country'] = $params['country'];
    $requestFields['ssl_email'] = $params['email'];
    // 32 character string
    $requestFields['ssl_invoice_number'] = $params['invoiceID'];
    $requestFields['ssl_transaction_type'] = "CCSALE";
    $requestFields['ssl_description'] = $params['description'];

    /************************************************************************************
     *  Fields available from civiCRM not implemented for Elavon
     *
     *  $params['qfKey'];
     *  $params['amount_other'];
     *  $params['ip_address'];
     *  $params['contributionType_name'	];
     *  $params['contributionPageID'];
     *  $params['contributionType_accounting_code'];
     *  $params['amount_level'];
     *  $params['credit_card_type'];
     ************************************************************************************/
    return $requestFields;
  }

  /**********************************************************
   * This function sends request and receives response from
   * the processor
   **********************************************************/
  function doDirectPayment(&$params) {
    if ($params['is_recur'] == TRUE) {
      CRM_Core_Error::fatal(ts('Elavon - recurring payments not implemented'));
    }

    if (!defined('CURLOPT_SSLCERT')) {
      CRM_Core_Error::fatal(ts('Elavon / Nova Virtual Merchant Gateway requires curl with SSL support'));
    }

    /*
         *Create the array of variables to be sent to the processor from the $params array
         * passed into this function
         */

    $requestFields = self::mapProcessorFieldstoParams($params);

    /*
         * define variables for connecting with the gateway
         */

    $requestFields['ssl_merchant_id'] = $this->_paymentProcessor['user_name'];
    $requestFields['user_id'] = $this->_paymentProcessor['password'];
    $requestFields['ssl_pin'] = $this->_paymentProcessor['signature'];
    $host = $this->_paymentProcessor['url_site'];

    if ($this->_mode == "test") {
      $requestFields['ssl_test_mode'] = "TRUE";
    }

    // Allow further manipulation of the arguments via custom hooks ..
    CRM_Utils_Hook::alterPaymentProcessorParams($this, $params, $requestFields);

    /**********************************************************
     * Check to see if we have a duplicate before we send
     **********************************************************/
    if ($this->_checkDupe($params['invoiceID'])) {
      return self::errorExit(9003, 'It appears that this transaction is a duplicate.  Have you already submitted the form once?  If so there may have been a connection problem.  Check your email for a receipt.  If you do not receive a receipt within 2 hours you can try your transaction again.  If you continue to have problems please contact the site administrator.');
    }

    /**********************************************************
     * Convert to XML using function below
     **********************************************************/
    $xml = self::buildXML($requestFields);

    /**********************************************************
     * Send to the payment processor using cURL
     **********************************************************/

    $chHost = $host . '?xmldata=' . $xml;

    $ch = curl_init($chHost);
    if (!$ch) {
      return self::errorExit(9004, 'Could not initiate connection to payment gateway');
    }

    curl_setopt($ch, CURLOPT_GET, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    // see - http://curl.haxx.se/docs/sslcerts.html
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    // return the result on success, FALSE on failure
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 36000);
    // set this for debugging -look for output in apache error log
    //curl_setopt ($ch,CURLOPT_VERBOSE,1 );
    // ensures any Location headers are followed
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

    /**********************************************************
     * Send the data out over the wire
     **********************************************************/
    $responseData = curl_exec($ch);

    /**********************************************************
     * See if we had a curl error - if so tell 'em and bail out
     *
     * NOTE: curl_error does not return a logical value (see its documentation), but
     *       a string, which is empty when there was no error.
     **********************************************************/
    if ((curl_errno($ch) > 0) || (strlen(curl_error($ch)) > 0)) {
      curl_close($ch);
      $errorNum = curl_errno($ch);
      $errorDesc = curl_error($ch);

      // Paranoia - in the unlikley event that 'curl' errno fails
      if ($errorNum == 0)
      $errorNum = 9005;

      // Paranoia - in the unlikley event that 'curl' error fails
      if (strlen($errorDesc) == 0)
      $errorDesc = "Connection to payment gateway failed";
      if ($errorNum = 60) {
        return self::errorExit($errorNum, "Curl error - " . $errorDesc . " Try this link for more information http://curl.haxx.se/docs/sslcerts.html");
      }

      return self::errorExit($errorNum, "Curl error - " . $errorDesc . " your key is located at " . $key . " the url is " . $host . " xml is " . $requestxml . " processor response = " . $processorResponse);
    }

    /**********************************************************
     * If null data returned - tell 'em and bail out
     *
     * NOTE: You will not necessarily get a string back, if the request failed for
     *       any reason, the return value will be the boolean false.
     **********************************************************/
    if (($responseData === FALSE) || (strlen($responseData) == 0)) {
      curl_close($ch);
      return self::errorExit(9006, "Error: Connection to payment gateway failed - no data returned.");
    }

    /**********************************************************
     // If gateway returned no data - tell 'em and bail out
     **********************************************************/
    if (empty($responseData)) {
      curl_close($ch);
      return self::errorExit(9007, "Error: No data returned from payment gateway.");
    }

    /**********************************************************
     // Success so far - close the curl and check the data
     **********************************************************/
    curl_close($ch);

    /**********************************************************
     * Payment succesfully sent to gateway - process the response now
     **********************************************************/

    $processorResponse = self::decodeXMLResponse($responseData);
    /*success in test mode returns response "APPROVED"
         * test mode always returns trxn_id = 0
         * fix for CRM-2566
         **********************************************************/


    if ($processorResponse['errorCode']) {
      return self::errorExit(9010, "Error: [" . $processorResponse['errorCode'] . " " . $processorResponse['errorName'] . " " . $processorResponse['errorMessage'] . "] - from payment processor");
    }
    if ($processorResponse['ssl_result_message'] == "APPROVED") {
      if ($this->_mode == 'test') {
        $query = "SELECT MAX(trxn_id) FROM civicrm_contribution WHERE trxn_id LIKE 'test%'";
        $p = array();
        $trxn_id = strval(CRM_Core_DAO::singleValueQuery($query, $p));
        $trxn_id = str_replace('test', '', $trxn_id);
        $trxn_id = intval($trxn_id) + 1;
        $params['trxn_id'] = sprintf('test%08d', $trxn_id);
        return $params;
      }
      else {
        return self::errorExit(9099, "Error: [approval code related to test transaction but mode was " . $this->_mode);
      }
    }

    // transaction failed, print the reason
    if ($processorResponse['ssl_result_message'] != "APPROVAL") {
      return self::errorExit(9009, "Error: [" . $processorResponse['ssl_result_message'] . " " . $processorResponse['ssl_result'] . "] - from payment processor");
    }
    else {
      /*
             * Success !
             */

      if ($this->_mode == 'test') {}
      else {
        // 'trxn_id' is varchar(255) field. returned value is length 37
        $params['trxn_id'] = $processorResponse['ssl_txn_id'];
      }

      $params['trxn_result_code'] = $processorResponse['ssl_approval_code'] . "-Cvv2:" . $processorResponse['ssl_cvv2_response'] . "-avs:" . $processorResponse['ssl_avs_response'];

      return $params;
    }
  }
  // end function doDirectPayment

  /**
   * Checks to see if invoice_id already exists in db
   *
   * @param  int     $invoiceId   The ID to check
   *
   * @return bool                  True if ID exists, else false
   */
  function _checkDupe($invoiceId) {
    require_once 'CRM/Contribute/DAO/Contribution.php';
    $contribution = new CRM_Contribute_DAO_Contribution();
    $contribution->invoice_id = $invoiceId;
    return $contribution->find();
  }

  /**************************************************
   * Produces error message and returns from class
   **************************************************/
  function &errorExit($errorCode = NULL, $errorMessage = NULL) {
    $e = &CRM_Core_Error::singleton();
    if ($errorCode) {
      $e->push($errorCode, 0, NULL, $errorMessage);
    }
    else {
      $e->push(9000, 0, NULL, 'Unknown System Error.');
    }
    return $e;
  }

  /**************************************************
   * NOTE: 'doTransferCheckout' not implemented
   **************************************************/
  function doTransferCheckout(&$params, $component) {
    CRM_Core_Error::fatal(ts('This function is not implemented'));
  }

  /********************************************************************************************
   * This public function checks to see if we have the right processor config values set
   *
   * NOTE: Called by Events and Contribute to check config params are set prior to trying
   *  register any credit card details
   *
   * @param string $mode the mode we are operating in (live or test) - not used
   *
   * returns string $errorMsg if any errors found - null if OK
   *
   ********************************************************************************************/
  //  function checkConfig( $mode )          // CiviCRM V1.9 Declaration
  // CiviCRM V2.0 Declaration
  function checkConfig() {
    $errorMsg = array();

    if (empty($this->_paymentProcessor['user_name'])) {
      $errorMsg[] = ' ' . ts('ssl_merchant_id is not set for this payment processor');
    }

    if (empty($this->_paymentProcessor['url_site'])) {
      $errorMsg[] = ' ' . ts('URL is not set for this payment processor');
    }

    if (!empty($errorMsg)) {
      return CRM_Utils_Array::implode('<p>', $errorMsg);
    }
    else {
      return NULL;
    }
  }
  //end check config
  function buildXML($requestFields) {
    $xmlFieldLength['ssl_first_name'] = 15;
    // credit card name
    $xmlFieldLength['ssl_last_name'] = 15;
    // contact name
    $xmlFieldLength['ssl_ship_to_first_name'] = 15;
    // contact name
    $xmlFieldLength['ssl_ship_to_last_name'] = 15;
    $xmlFieldLength['ssl_card_number'] = 19;
    $xmlFieldLength['ssl_amount'] = 13;
    $xmlFieldLength['ssl_exp_date'] = 4;
    $xmlFieldLength['ssl_cvv2cvc2'] = 4;
    $xmlFieldLength['ssl_cvv2cvc2_indicator'] = 1;
    $xmlFieldLength['ssl_avs_address'] = 20;
    $xmlFieldLength['ssl_city'] = 20;
    $xmlFieldLength['ssl_state'] = 30;
    $xmlFieldLength['ssl_avs_zip'] = 9;
    $xmlFieldLength['ssl_country'] = 50;
    $xmlFieldLength['ssl_email'] = 100;
    // 32 character string
    $xmlFieldLength['ssl_invoice_number'] = 25;
    $xmlFieldLength['ssl_transaction_type'] = 20;
    $xmlFieldLength['ssl_description'] = 255;
    $xmlFieldLength['ssl_merchant_id'] = 15;
    $xmlFieldLength['user_id'] = 15;
    $xmlFieldLength['ssl_pin'] = 6;
    $xmlFieldLength['ssl_test_mode'] = 5;

    $xml = '<txn>';
    foreach ($requestFields as $key => $value) {
      $xml .= '<' . $key . '>' . self::tidyStringforXML($value, $xmlFieldLength[$key]) . '</' . $key . '>';

      //  $xml .= '<' . $key . '>' . rawurlencode($value) . '</' . $key . '>';
    }
    // sales tax appears to be required even though the dev guide says it isn't - not used by civi
    $xml .= '<ssl_salestax>0.00</ssl_salestax></txn>';
    return $xml;
  }

  function tidyStringforXML($value, $fieldlength) {
    // the xml is posted to a url so must not contain spaces etc. It also needs to be cut off at a certain
    // length to match the processor's field length. The cut needs to be made after spaces etc are
    // transformed but must not include a partial transformed character e.g. %20 must be in or out not half-way
    $xmlString = substr(rawurlencode($value), 0, $fieldlength);
    $lastPercent = strrpos($xmlString, '%');
    if ($lastPercent > $fieldlength - 3) {
      $xmlString = substr($xmlString, 0, $lastPercent);
    }
    return $xmlString;
  }

  /************************************************************************
   * Simple function to use in place of the 'simplexml_load_string' call.
   *
   * It returns the NodeValue for a given NodeName
   * or returns an empty string.
   ************************************************************************/
  function GetNodeValue($NodeName, &$strXML) {
    $OpeningNodeName = "<" . $NodeName . ">";
    $ClosingNodeName = "</" . $NodeName . ">";

    $pos1 = stripos($strXML, $OpeningNodeName);
    $pos2 = stripos($strXML, $ClosingNodeName);

    if (($pos1 === FALSE) || ($pos2 === FALSE)) {

      return "";

    }

    $pos1 += strlen($OpeningNodeName);
    $len = $pos2 - $pos1;

    $return = substr($strXML, $pos1, $len);
    // check out rtn values for debug
    // echo " $NodeName &nbsp &nbsp $return <br>";
    return ($return);
  }

  function decodeXMLresponse($Xml) {

    /**
     * $xtr = simplexml_load_string($Xml) or die ("Unable to load XML string!");
     **/

    $processorResponse['ssl_result'] = self::GetNodeValue("ssl_result", $Xml);
    $processorResponse['ssl_result_message'] = self::GetNodeValue("ssl_result_message", $Xml);
    $processorResponse['ssl_txn_id'] = self::GetNodeValue("ssl_txn_id", $Xml);
    $processorResponse['ssl_cvv2_response'] = self::GetNodeValue("ssl_cvv2_response", $Xml);
    $processorResponse['ssl_avs_response'] = self::GetNodeValue("ssl_avs_response", $Xml);
    $processorResponse['ssl_approval_code'] = self::GetNodeValue("ssl_approval_code", $Xml);
    $processorResponse['errorCode'] = self::GetNodeValue("errorCode", $Xml);
    $processorResponse['errorName'] = self::GetNodeValue("errorName", $Xml);
    $processorResponse['errorMessage'] = self::GetNodeValue("errorMessage", $Xml);

    return $processorResponse;
  }
}

