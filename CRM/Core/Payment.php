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
 * Abstract base class for payment processor implementations
 *
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */

abstract class CRM_Core_Payment {

  /**
   * how are we getting billing information?
   *
   * FORM   - we collect it on the same page
   * BUTTON - the processor collects it and sends it back to us via some protocol
   */
  public const BILLING_MODE_FORM = 1, BILLING_MODE_BUTTON = 2, BILLING_MODE_NOTIFY = 4, BILLING_MODE_DUMMY = 7, BILLING_MODE_IFRAME = 8;
  public const PAY_LATER_DEFAULT_EXPIRED_DAY = 7; // day, refs #22026

  /**
   * which payment type(s) are we using?
   *
   * credit card
   * direct debit
   * or both
   *
   */
  public const PAYMENT_TYPE_CREDIT_CARD = 1, PAYMENT_TYPE_DIRECT_DEBIT = 2;

  /**
   * Subscription / Recurring payment Status
   * START, END
   *
   */
  public const RECURRING_PAYMENT_START = 'START', RECURRING_PAYMENT_END = 'END';

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   * @static
   */
  private static $_singleton = [];

  protected $_paymentProcessor;

  protected $_paymentForm = NULL;

  public static $_editableFields = [];

  /**
   * Singleton function used to manage this object.
   *
   * @param string $mode The mode of operation: live or test.
   * @param array $paymentProcessor The payment processor details.
   * @param object|null $paymentForm The form object.
   *
   * @return CRM_Core_Payment The payment object.
   */
  public static function &singleton($mode, &$paymentProcessor, &$paymentForm = NULL) {
    $processorId = $paymentProcessor['id'];
    if (!isset(self::$_singleton[$processorId])) {
      $config = CRM_Core_Config::singleton();

      $ext = new CRM_Core_Extensions();

      if ($ext->isExtensionKey($paymentProcessor['class_name'])) {
        $paymentClass = $ext->keyToClass($paymentProcessor['class_name'], 'payment');
        require_once($ext->classToPath($paymentClass));
      }
      else {
        $paymentClass = 'CRM_Core_' . $paymentProcessor['class_name'];
        require_once(str_replace('_', DIRECTORY_SEPARATOR, $paymentClass) . '.php');
      }

      self::$_singleton[$processorId] = call_user_func_array([$paymentClass, 'singleton'], [$mode, $paymentProcessor]);

      if ($paymentForm !== NULL) {
        self::$_singleton[$processorId]->setForm($paymentForm);
      }
    }
    return self::$_singleton[$processorId];
  }

  /**
   * Setter for the payment form that wants to use the processor.
   *
   * @param object $paymentForm The form object.
   */
  public function setForm(&$paymentForm) {
    $this->_paymentForm = $paymentForm;
  }

  /**
   * Getter for payment form that is using the processor.
   *
   * @return object|null A form object.
   */
  public function getForm() {
    return $this->_paymentForm;
  }

  /**
   * Getter for accessing member vars.
   *
   * @param string $name The variable name.
   *
   * @return mixed|null The variable value.
   */
  public function getVar($name) {
    return $this->$name ?? NULL;
  }

  /**
   * Performs direct payment transaction.
   *
   * @param array $params Associative array of input parameters for this transaction.
   *
   * @return array The result in a formatted array.
   */
  abstract public function doDirectPayment(&$params);

  /**
   * This function checks to see if we have the right config values.
   *
   * @return string|null The error message if any.
   */
  abstract public function checkConfig();

  /**
   * This function returns the URL used to cancel recurring subscriptions.
   *
   * @return string|null The URL of the payment processor cancel page.
   */
  public function cancelSubscriptionURL() {
    return NULL;
  }

  /**
   * Check if redirect to PayPal is needed.
   *
   * @param array $paymentProcessor The payment processor details.
   *
   * @return bool
   */
  public static function paypalRedirect(&$paymentProcessor) {
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
   * Get payment processor information for AJAX requests.
   */
  public static function getPaymentProcessorInfo() {
    $ppID = CRM_Utils_Type::escape($_POST['ppID'], 'Positive');
    $action = CRM_Utils_Type::escape($_POST['action'], 'String');

    $mode = ($action == 1024) ? 'test' : 'live';

    $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($ppID, $mode);

    echo json_encode($paymentProcessor);

    CRM_Utils_System::civiExit();
  }

  /**
   * Prepare parameters for transfer checkout.
   *
   * @param object|array $contrib The contribution details.
   * @param array $params The transaction parameters.
   *
   * @return array<string, mixed> The prepared variables.
   */
  public function prepareTransferCheckoutParams($contrib, $params) {
    if (is_object($contrib)) {
      $values = [];
      if (strstr(get_class($contrib), 'DAO')) {
        $contrib = CRM_Core_DAO::storeValues($contrib, $values);
      }
    }
    else {
      $values = $contrib;
    }

    if (!empty($this->_paymentForm) && isset($this->_paymentForm->_ids)) {
      $details = $this->_paymentForm->_ids;
    }
    else {
      $details = CRM_Contribute_BAO_Contribution::getComponentDetails([$values['id']]);
      $details = reset($details);
    }

    // prepare vars
    $vars = [
      'qfKey' => $params['qfKey'],
      'payment_processor' => $params['payment_processor'],
      'civicrm_instrument_id' => $params['civicrm_instrument_id'],
      'amount' => $values['total_amount'],
      'amount_level' => $values['amount_level'],
      'item_name' => $values['amount_level'] ? $values['amount_level'] : $values['total_amount'],
      'description' => $values['source'],
      'currencyID' => $values['currency'],
    ];

    if (!empty($details['participant'])) {
      $vars += [
        'contributionID' => $values['id'],
        'contributionTypeID' => $values['contribution_type_id'],
        'contactID' => $values['contact_id'],
        'eventID' => $details['event'],
        'participantID' => $details['participant'],
      ];
    }
    elseif (!empty($details['membership'])) {
      // TODO
    }
    else {
      // TODO
    }
    return $vars;
  }

  /**
   * Calculate expiration based on event setting and specific date.
   *
   * @param int|null $baseTime Timestamp for calculation base time.
   * @param int $plusDay Days after base time to be expiration.
   *
   * @return int Timestamp.
   */
  public static function calcExpirationDate($baseTime, $plusDay = self::PAY_LATER_DEFAULT_EXPIRED_DAY) {
    // refs #22026
    if (empty($baseTime)) {
      $baseTime = CRM_REQUEST_TIME;
    }
    // stick on 23:59:59
    $expiredDate = date('Y-m-d', $baseTime + 86400 * $plusDay);
    return strtotime($expiredDate.' 23:59:59'); // timestamp
  }
}
