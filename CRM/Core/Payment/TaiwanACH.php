<?php
/**
 * Taiwan ACH (Automated Clearing House) payment processor for handling bank transfer recurring donations.
 *
 * @package CiviCRM_PaymentProcessor
 */

class CRM_Core_Payment_TaiwanACH extends CRM_Core_Payment {

  protected $_mode = NULL;

  // Used for contribution recurring form ( /CRM/Contribute/Form/ContributionRecur.php ).
  public static $_editableFields = ['amount', 'installments', 'end_date', 'contribution_status_id', 'note_title', 'note_body', 'start_date'];

  public static $_hideFields = ['invoice_id'];

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   * @static
   */
  private static $_singleton = NULL;

  /**
   * Class constructor.
   *
   * @param string $mode the mode of operation: live or test
   * @param array &$paymentProcessor payment processor parameters
   */
  public function __construct($mode, &$paymentProcessor) {
    $this->_mode = $mode;
    $this->_paymentProcessor = $paymentProcessor;
  }

  /**
   * Singleton function used to manage this object.
   *
   * @param string $mode the mode of operation: live or test
   * @param array &$paymentProcessor payment processor parameters
   * @param CRM_Core_Form|null &$paymentForm payment form object
   *
   * @return CRM_Core_Payment_TaiwanACH
   */
  public static function &singleton($mode, &$paymentProcessor, &$paymentForm = NULL) {
    $processorName = $paymentProcessor['name'];
    if (self::$_singleton[$processorName] === NULL) {
      self::$_singleton[$processorName] = new CRM_Core_Payment_TaiwanACH($mode, $paymentProcessor);
    }
    return self::$_singleton[$processorName];
  }

  /**
   * Check if the processor has the right configuration values.
   *
   * @return string|null error message if any, else NULL
   */
  public function checkConfig() {
    $config = CRM_Core_Config::singleton();

    $error = [];

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

  public function setExpressCheckOut(&$params) {
    CRM_Core_Error::fatal(ts('This function is not implemented'));
  }

  public function getExpressCheckoutDetails($token) {
    CRM_Core_Error::fatal(ts('This function is not implemented'));
  }

  public function doExpressCheckout(&$params) {
    CRM_Core_Error::fatal(ts('This function is not implemented'));
  }

  public function doDirectPayment(&$params) {
    CRM_Core_Error::fatal(ts('This function is not implemented'));
  }

  public function doTransferCheckout(&$params, $component) {

  }

  public function cancelRecuringMessage($recurID) {

  }

  /**
   * Add a note to the contribution record log.
   *
   * @param string $note note content
   * @param CRM_Contribute_DAO_Contribution &$contribution contribution object
   *
   * @return void
   */
  public static function addNote($note, &$contribution) {

    $note = date("Y/m/d H:i:s "). ts("Transaction record")."Trxn ID: {$contribution->trxn_id} \n\n".$note;
    CRM_Core_Error::debug_log_message($note);
  }

  /**
   * Check if the configuration section is valid.
   *
   * @param array &$fields form fields
   * @param array &$errors array to store errors
   * @param string|null $section configuration section (e.g., 'test')
   *
   * @return bool TRUE if all fields are empty
   */
  public static function checkSection(&$fields, &$errors, $section = NULL) {
    $emptyField = 0;
    $isAllEmpty = FALSE;
    $isTestPrefix = ($section == 'test') ? 'test_' : '';
    $ppDAO = new CRM_Core_DAO_PaymentProcessorType();
    $ppDAO->name = 'TaiwanACH';
    $ppDAO->find(TRUE);
    if (empty($fields["{$isTestPrefix}user_name"]) || empty($fields["{$isTestPrefix}password"]) || empty($fields["{$isTestPrefix}signature"])) {
      if (empty($fields["{$isTestPrefix}user_name"])) {
        $emptyField++;
        $errors["{$isTestPrefix}user_name"] = ts('Missing required field: %1', [1 => $ppDAO->user_name_label]);
      }
      if (empty($fields["{$isTestPrefix}password"])) {
        $emptyField++;
        $errors["{$isTestPrefix}password"] = ts('Missing required field: %1', [1 => $ppDAO->password_label]);
      }
      if (empty($fields["{$isTestPrefix}signature"])) {
        $emptyField++;
        $errors["{$isTestPrefix}signature"] = ts('Missing required field: %1', [1 => $ppDAO->signature_label]);
      }
    }
    if (count($errors) == 3) {
      if (empty($fields["{$isTestPrefix}subject"])) {
        $errors["{$isTestPrefix}subject"] = ts('Missing required field: Provide %1 or %2', [
          1 => $ppDAO->subject_label,
          2 => $ppDAO->user_name_label,
        ]);
        $emptyField++;
      }
      else {
        $errors = [];
      }
    }
    if ($emptyField == 4) {
      $isAllEmpty = TRUE;
    }
    return $isAllEmpty;
  }
}
