<?php

class CRM_Core_Payment_TaiwanACH extends CRM_Core_Payment {

  protected $_mode = NULL;

  // Used for contribution recurring form ( /CRM/Contribute/Form/ContributionRecur.php ).
  public static $_editableFields = array('amount', 'installments', 'end_date', 'contribution_status_id', 'note_title', 'note_body', 'start_date');

  public static $_hideFields = array('invoice_id');

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
  public static function &singleton($mode, &$paymentProcessor, &$paymentForm = NULL) {
    $processorName = $paymentProcessor['name'];
    if (self::$_singleton[$processorName] === NULL) {
      self::$_singleton[$processorName] = new CRM_Core_Payment_TaiwanACH($mode, $paymentProcessor);
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
    $config = CRM_Core_Config::singleton();

    $error = array();

    if (!empty($this->_paymentProcessor['user_name']) xor !empty($this->_paymentProcessor['password'])) {
      $error[] = ts('User Name is not set in the Administer CiviCRM &raquo; Payment Processor.');
      $error[] = ts('Password is not set in the Administer CiviCRM &raquo; Payment Processor.');
    }


    if (!empty($error)) {
      return CRM_Utils_Array::implode('<br>', $error);
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

  }

  function cancelRecuringMessage($recurID) {

  }

  static function addNote($note, &$contribution){
    require_once 'CRM/Core/BAO/Note.php';
    $note = date("Y/m/d H:i:s "). ts("Transaction record")."Trxn ID: {$contribution->trxn_id} \n\n".$note;
    CRM_Core_Error::debug_log_message( $note );
  }

  static function checkSection(&$fields, &$errors, $section = NULL) {
    $emptyField = 0;
    $isAllEmpty = FALSE;
    $isTestPrefix = ($section == 'test') ? 'test_': '';
    $ppDAO = new CRM_Core_DAO_PaymentProcessorType();
    $ppDAO->name = 'TaiwanACH';
    $ppDAO->find(TRUE);
    if (empty($fields["{$isTestPrefix}user_name"]) || empty($fields["{$isTestPrefix}password"]) || empty($fields["{$isTestPrefix}signature"])) {
      if (empty($fields["{$isTestPrefix}user_name"])) {
        $emptyField++;
        $errors["{$isTestPrefix}user_name"] = ts('Missing required field: %1', array(1 => $ppDAO->user_name_label));
      }
      if (empty($fields["{$isTestPrefix}password"])) {
        $emptyField++;
        $errors["{$isTestPrefix}password"] = ts('Missing required field: %1', array(1 => $ppDAO->password_label));
      }
      if (empty($fields["{$isTestPrefix}signature"])) {
        $emptyField++;
        $errors["{$isTestPrefix}signature"] = ts('Missing required field: %1', array(1 => $ppDAO->signature_label));
      }
    }
    if (count($errors) == 3) {
      if(empty($fields["{$isTestPrefix}subject"])) {
        $errors["{$isTestPrefix}subject"] = ts('Missing required field: Provide %1 or %2', array(
          1 => $ppDAO->subject_label,
          2 => $ppDAO->user_name_label,
        ));
        $emptyField++;
      }
      else {
        $errors = array();
      }
    }
    if ($emptyField == 4) {
      $isAllEmpty = TRUE;
    }
    return $isAllEmpty;
  }
}
