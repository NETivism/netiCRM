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

abstract class CRM_Core_Payment {

  /**
   * how are we getting billing information?
   *
   * FORM   - we collect it on the same page
   * BUTTON - the processor collects it and sends it back to us via some protocol
   */
  CONST BILLING_MODE_FORM = 1, BILLING_MODE_BUTTON = 2, BILLING_MODE_NOTIFY = 4;

  /**
   * which payment type(s) are we using?
   *
   * credit card
   * direct debit
   * or both
   *
   */
  CONST PAYMENT_TYPE_CREDIT_CARD = 1, PAYMENT_TYPE_DIRECT_DEBIT = 2;

  /**
   * Subscription / Recurring payment Status
   * START, END
   *
   */
  CONST RECURRING_PAYMENT_START = 'START', RECURRING_PAYMENT_END = 'END';

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   * @static
   */
  static private $_singleton = NULL;

  protected $_paymentProcessor;

  protected $_paymentForm = NULL;

  /**
   * singleton function used to manage this object
   *
   * @param string $mode the mode of operation: live or test
   *
   * @return object
   * @static
   *
   */
  static function &singleton($mode = 'test', &$paymentProcessor, &$paymentForm = NULL) {
    if (self::$_singleton === NULL) {
      $config = CRM_Core_Config::singleton();

      require_once 'CRM/Core/Extensions.php';
      $ext = new CRM_Core_Extensions();

      if ($ext->isExtensionKey($paymentProcessor['class_name'])) {
        $paymentClass = $ext->keyToClass($paymentProcessor['class_name'], 'payment');
        require_once ($ext->classToPath($paymentClass));
      }
      else {
        $paymentClass = 'CRM_Core_' . $paymentProcessor['class_name'];
        require_once (str_replace('_', DIRECTORY_SEPARATOR, $paymentClass) . '.php');
      }

      self::$_singleton = eval('return ' . $paymentClass . '::singleton( $mode, $paymentProcessor );');

      if ($paymentForm !== NULL) {
        self::$_singleton->setForm($paymentForm);
      }
    }
    return self::$_singleton;
  }

  /**
   * Setter for the payment form that wants to use the processor
   *
   * @param obj $paymentForm
   *
   */
  function setForm(&$paymentForm) {
    $this->_paymentForm = $paymentForm;
  }

  /**
   * Getter for payment form that is using the processor
   *
   * @return obj  A form object
   */
  function getForm() {
    return $this->_paymentForm;
  }

  /**
   * Getter for accessing member vars
   *
   */
  function getVar($name) {
    return isset($this->$name) ? $this->$name : NULL;
  }

  /**
   * This function collects all the information from a web/api form and invokes
   * the relevant payment processor specific functions to perform the transaction
   *
   * @param  array $params assoc array of input parameters for this transaction
   *
   * @return array the result in an nice formatted array (or an error object)
   * @abstract
   */
  abstract function doDirectPayment(&$params);

  /**
   * This function checks to see if we have the right config values
   *
   * @param  string $mode the mode we are operating in (live or test)
   *
   * @return string the error message if any
   * @public
   */
  abstract function checkConfig();

  /**
   * This function returns the URL used to cancel recurring subscriptions
   *
   * @return string the url of the payment processor cancel page
   * @public
   */
  function cancelSubscriptionURL() {
    return NULL;
  }

  static
  function paypalRedirect(&$paymentProcessor) {
    if (!$paymentProcessor) {
      return FALSE;
    }

    if (isset($_GET['payment_date']) &&
      isset($_GET['merchant_return_link']) &&
      CRM_Utils_Array::value('payment_status', $_GET) == 'Completed' &&
      $paymentProcessor['payment_processor_type'] == "PayPal_Standard"
    ) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Function to get Payment Processor Info
   *
   */
  static function getPaymentProcessorInfo() {
    $ppID = CRM_Utils_Type::escape($_POST['ppID'], 'Positive');
    $action = CRM_Utils_Type::escape($_POST['action'], 'String');

    $mode = ($action == 1024) ? 'test' : 'live';

    require_once 'CRM/Core/BAO/PaymentProcessor.php';
    $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($ppID, $mode);

    echo json_encode($paymentProcessor);

    require_once 'CRM/Utils/System.php';
    CRM_Utils_System::civiExit();
  }

  function prepareTransferCheckoutParams($contrib, $params){
    if(is_object($contrib)){
      $values = array();
      if(strstr(get_class($contrib), 'DAO')){
        $contrib = CRM_Core_DAO::storeValues($contrib, $values);
      }
    }
    else{
      $values = $contrib;
    }

    if(!empty($this->_paymentForm) && isset($this->_paymentForm->_ids)){
      $details = $this->_paymentForm->_ids;
    }
    else{
      $details = CRM_Contribute_BAO_Contribution::getComponentDetails(array($values['id']));
      $details = reset($details);
    }

    // prepare vars
    $vars = array(
      'qfKey' => $params['qfKey'],
      'payment_processor' => $params['payment_processor'],
      'civicrm_instrument_id' => $params['civicrm_instrument_id'],
      'amount' => $values['total_amount'],
      'amount_level' => $values['amount_level'],
      'item_name' => $values['amount_level'] ? $values['amount_level'] : $values['total_amount'],
      'description' => $values['source'],
    );

    if(!empty($details['participant'])){
      $vars += array(
        'contributionID' => $values['id'],
        'contributionTypeID' => $values['contribution_type_id'],
        'contactID' => $values['contact_id'],
        'eventID' => $details['event'],
        'participantID' => $details['participant'],
      );
    }
    elseif(!empty($details['membership'])){
      // TODO
    }
    else{
      // TODO 
    }
    return $vars;  
  }
}

