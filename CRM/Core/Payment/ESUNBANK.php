<?php
/**
 * E.SUN Bank payment processor for handling credit card and recurring donation transactions via the E.SUN Bank gateway.
 *
 * @package CiviCRM_PaymentProcessor
 */

date_default_timezone_set('Asia/Taipei');

class CRM_Core_Payment_Esunbank extends CRM_Core_Payment {

  /**
   * @var string
   */
  public $_processorName;
  /**
   * @var object
   */
  public $_config;
  /**
   * mode of operation: live or test
   *
   * @var object
   * @static
   */
  protected static $_mode = NULL;

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
    $this->_processorName = 'ESUNBANK';
    $config = &CRM_Core_Config::singleton();
    $this->_config = $config;
  }

  /**
   * Singleton function used to manage this object.
   *
   * @param string $mode the mode of operation: live or test
   * @param array &$paymentProcessor payment processor parameters
   * @param CRM_Core_Form|null &$paymentForm payment form object
   *
   * @return CRM_Core_Payment_Esunbank
   */
  public static function &singleton($mode, &$paymentProcessor, &$paymentForm = NULL) {
    $processorName = $paymentProcessor['name'];
    if (self::$_singleton[$processorName] === NULL) {
      self::$_singleton[$processorName] = new CRM_Core_Payment_Esunbank($mode, $paymentProcessor);
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

    if (empty($this->_paymentProcessor['user_name'])) {
      $error[] = ts('User Name is not set in the Administer CiviCRM &raquo; Payment Processor.');
    }

    if (empty($this->_paymentProcessor['password'])) {
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

  /**
   * Handle transfer checkout (redirect to payment gateway).
   *
   * @param array &$params name-value pairs of contribution data
   * @param string $component component name ('contribute' or 'event')
   *
   * @return void
   */
  public function doTransferCheckout(&$params, $component) {
    $component = strtolower($component);
    if ($component != 'contribute' && $component != 'event') {
      CRM_Core_Error::fatal(ts('Component is invalid'));
    }
    if (module_load_include('inc', 'civicrm_esunbank', 'civicrm_esunbank.checkout') === FALSE) {
      CRM_Core_Error::fatal('Module civicrm_esunbank doesn\'t exists.');
    }
    else {
      civicrm_esunbank_do_transfer_checkout($params, $component, $this->_paymentProcessor, $this->_paymentForm);
    }
  }

  /**
   * Function called from contributionRecur page to show tappay detail information
   *
   * @param int $contributionId the contribution id
   *
   * @return array The label as the key to value.
   */
  public static function getRecordDetail($contributionId) {
    $table = [];

    $contribution = new CRM_Contribute_DAO_Contribution();
    $contribution->id = $contributionId;
    $contribution->find(TRUE);
    $is_test = $contribution->is_test ? 'test' : '';
    if (empty($contribution->payment_processor_id)) {
      return $table;
    }
    $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($contribution->payment_processor_id, $is_test);
    $table[ts('Payment Processor')] = $paymentProcessor['name']." - ".ts('ID').$paymentProcessor['id'];

    if (function_exists('civicrm_esunbank_get_record_detail')) {
      civicrm_esunbank_get_record_detail($contributionId, $table);
    }

    return $table;
  }
}
