<?php

class CRM_Core_Payment_Backer extends CRM_Core_Payment {

  protected $_mode = NULL;

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   * @static
   */
  private static $_singleton = NULL;

  function __construct($mode, &$paymentProcessor) {
    $this->_mode = $mode;
    $this->_paymentProcessor = $paymentProcessor;
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
  public static function &singleton($mode = 'live', &$paymentProcessor, $paymentForm = NULL) {
    $processorName = $paymentProcessor['name'];
    if (self::$_singleton[$processorName] === NULL) {
      self::$_singleton[$processorName] = new CRM_Core_Payment_Backer($mode, $paymentProcessor);
    }
    return self::$_singleton[$processorName];
  }

  /**
   * This function checks to see if we have the right config values
   *
   * @return string the error message if any
   * @public
   */
  function checkConfig() {
    $error = array();

    if (empty($this->_paymentProcessor['password'])) {
      $error[] = ts('Password is not set in the Administer CiviCRM &raquo; Payment Processor.');
    }


    if (!empty($error)) {
      return implode('<br>', $error);
    }
    else {
      return NULL;
    }
  }

  function setExpressCheckOut(&$params) {
    CRM_Core_Error::fatal(ts('This function is not implemented'));
  }

  function getExpressCheckoutDetails($token) {
    CRM_Core_Error::fatal(ts('This function is not implemented'));
  }

  function doExpressCheckout(&$params) {
    CRM_Core_Error::fatal(ts('This function is not implemented'));
  }

  function doDirectPayment(&$params) {
    CRM_Core_Error::fatal(ts('This function is not implemented'));
  }

  function doTransferCheckout(&$params, $component) {
    CRM_Core_Error::fatal(ts('This function is not implemented'));
  }

  function cancelRecuringMessage($recurID) {
    CRM_Core_Error::fatal(ts('This function is not implemented'));
  }

  function backerCheckSignature($string, $signature = NULL) {
    if (empty($signature)) {
      $headers = CRM_Utils_System::getAllHeaders();
      $signature = $_SERVER['HTTP_X_BACKME_SIGNATURE'];
    }
    if (empty($signature)) {
      return FALSE;
    }
    $secret = $this->_paymentProcessor['password'];
    if (empty($secret)) {
      return FALSE;
    }
    $hash = hash_hmac('sha1', $string, $secret);
    if ($hash === $signature) {
      return TRUE;
    }
    return FALSE;
  }
}
