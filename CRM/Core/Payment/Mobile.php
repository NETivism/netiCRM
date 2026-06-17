<?php
/**
 * Wrapper of mobile payment which should integrate with other paymewnt module to work together
 *
 * @package CiviCRM_PaymentProcessor
 */

/*
  +--------------------------------------------------------------------+
  | CiviCRM version 3.3                                                |
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

/*
 * PxPay Functionality Copyright (C) 2008 Lucas Baker, Logistic Information Systems Limited (Logis)
 * PxAccess Functionality Copyright (C) 2008 Eileen McNaughton
 * Licensed to CiviCRM under the Academic Free License version 3.0.
 *
 * Grateful acknowledgements go to Donald Lobo for invaluable assistance
 * in creating this payment processor module
 */

class CRM_Core_Payment_Mobile extends CRM_Core_Payment {
  /**
   * @var mixed
   */
  public $_processorName;
  public $_instrumentType;
  public $_mobilePayment;
  public const CHARSET = 'iso-8859-1';
  protected static $_mode = NULL;
  protected static $_params = [];

  /**
   * Failed (4) / Overdue-Expired (6) can never be picked by a human on the
   * Recurring contribution edit form for a LINE Pay preapproved recurring;
   * the system sets them (refs #45587).
   */
  public static $_excludedStatuses = [4, 6];

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
    $this->_processorName = ts('DPS Payment Express');
  }

  /**
   * Singleton function used to manage this object.
   *
   * @param string $mode the mode of operation: live or test
   * @param array &$paymentProcessor payment processor parameters
   * @param CRM_Core_Form|null &$paymentForm payment form object
   *
   * @return CRM_Core_Payment_Mobile
   */
  public static function &singleton($mode, &$paymentProcessor, &$paymentForm = NULL) {
    $processorName = $paymentProcessor['name'];
    if (self::$_singleton[$processorName] === NULL) {
      self::$_singleton[$processorName] = new CRM_Core_Payment_Mobile($mode, $paymentProcessor);
    }
    return self::$_singleton[$processorName];
  }

  /**
   * Setter for the payment form that wants to use the processor.
   *
   * @param CRM_Core_Form &$paymentForm
   *
   * @return void
   */
  public function setForm(&$paymentForm) {
    parent::setForm($paymentForm);
    // event registration doesn't use this...
  }

  /**
   * Check if the processor has the right configuration values.
   *
   * @return string|null error message if any, else NULL
   */
  public function checkConfig() {
    $error = [];

    if ($this->_paymentProcessor['user_name'] !== 'none' && empty($this->_paymentProcessor['password'])) {
      $error[] = ts('Merchant is not set in the Administer CiviCRM &raquo; Payment Processor.');
    }
    // LinePay: if either channelId or channelSecret is set, both are required
    $hasChannelId = !empty($this->_paymentProcessor['url_site']);
    $hasChannelSecret = !empty($this->_paymentProcessor['url_api']);
    if ($hasChannelId xor $hasChannelSecret) {
      if (!$hasChannelId) {
        $error[] = ts('LINE Pay Channel ID is not set in the Administer CiviCRM &raquo; Payment Processor.');
      }
      else {
        $error[] = ts('LINE Pay Channel Secret is not set in the Administer CiviCRM &raquo; Payment Processor.');
      }
    }

    if (!empty($error)) {
      return CRM_Utils_Array::implode('<br>', $error);
    }
    return NULL;
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
    $instrument_id = $params['civicrm_instrument_id'];
    if (!empty($instrument_id)) {
      // civicrm_instrument_by_id($params['civicrm_instrument_id'], 'name');
      $options = [1 => [ $instrument_id, 'Integer']];
      $instrument_name = CRM_Core_DAO::singleValueQuery("SELECT v.name FROM civicrm_option_value v INNER JOIN civicrm_option_group g ON v.option_group_id = g.id WHERE g.name = 'payment_instrument' AND v.is_active = 1 AND v.value = %1;", $options);
      $this->_instrumentType = strtolower($instrument_name);
    }

    $cid = $params['contributionID'];
    $iid = $params['civicrm_instrument_id'];
    if ($cid && $iid) {
      $options = [
        1 => [$iid, 'Integer'],
        2 => [$cid, 'Integer'],
      ];
      CRM_Core_DAO::executeQuery("UPDATE civicrm_contribution SET payment_instrument_id = %1 WHERE id = %2", $options);
    }
    CRM_Core_Error::debug_var('mobile_payment_params', $params);

    if ($this->_instrumentType == 'linepay') {
      $this->_mobilePayment = new CRM_Core_Payment_LinePay($this->_mode, $this->_paymentProcessor);
      try {
        // doRequest() redirects to LINE Pay on success; on failure it throws.
        $this->_mobilePayment->doRequest($params);
      }
      catch (Exception $e) {
        // Bounce to the thank you page with the failure flag
        // (payment_result_type=4) so the donor sees an error message instead of
        // mistaking a failed request for a completed transaction (refs #45587).
        CRM_Core_Error::debug_log_message('LINE Pay doRequest failed: ' . $e->getMessage());
        $thankyou_url = CRM_Core_Payment_LinePay::prepareThankYouUrl(CRM_Utils_System::currentPath(), $params['qfKey'], TRUE);
        CRM_Utils_System::redirect($thankyou_url);
      }
      return;
    }

    CRM_Core_Error::debug_var('mobile_payment_others', $cid);
    // If not use linepay, We need another payment processor.
    $qfKey = $params['qfKey'];
    $paymentProcessor = $this->_paymentProcessor;

    $provider_name = $paymentProcessor['password'];

    if (!empty($params['eventID'])) {
      $event = new CRM_Event_DAO_Event();
      $event->id = $params['eventID'];
      $event->find(1);
      $page_title = $event->title;
    }
    else {
      $contribution_pgae = new CRM_Contribute_DAO_ContributionPage();
      $contribution_pgae->id = $params['contributionPageID'];
      $contribution_pgae->find(1);
      $page_title = $contribution_pgae->title;
    }

    $description = !empty($params['amount_level']) ? $page_title . ' - ' . $params['amount_level'] : $page_title;

    if ($this->_mode == 'test') {
      $is_test = 1;
    }
    else {
      $is_test = 0;
    }

    if ($this->_instrumentType == 'applepay') {
      $smarty = CRM_Core_Smarty::singleton();
      $smarty->assign('after_redirect', 0);
      $payment_params = [
        'cid' => $params['contributionID'],
        'provider' => $provider_name,
        'description' => $description,
        'amount' => $params['amount'],
        'qfKey' => $qfKey,
        'is_test' => $is_test,
      ];
      if (!empty($params['participantID'])) {
        $payment_params['pid'] = $params['participantID'];
      }
      if (!empty($params['eventID'])) {
        $payment_params['eid'] = $params['eventID'];
      }
      if (!empty($params['membershipID'])) {
        $payment_params['mid'] = $params['membershipID'];
      }
      $smarty->assign('params', $payment_params);
      $page = $smarty->fetch('CRM/Core/Payment/ApplePay.tpl');
      print($page);
      CRM_Utils_System::civiExit();
    }
    elseif ($this->_instrumentType == 'googlepay' && $provider_name == 'spgateway') {
      $mode = $is_test ? 'test' : '';
      $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($this->_paymentProcessor['user_name'], $mode);
      $payment = new CRM_Core_Payment_SPGATEWAY($mode, $paymentProcessor);
      $payment->doTransferCheckout($params, $component);
    }
    elseif ($this->_instrumentType == 'applepayfront' && $provider_name == 'spgateway') {
      $mode = $is_test ? 'test' : '';
      $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($this->_paymentProcessor['user_name'], $mode);
      $payment = new CRM_Core_Payment_SPGATEWAY($mode, $paymentProcessor);
      $payment->doTransferCheckout($params, $component);
    }
  }

  /**
   * Fields editable on the Recurring contribution edit form
   * (CRM/Contribute/Form/ContributionRecur.php).
   *
   * refs #45587. Only LINE Pay preapproved recurrings are editable through
   * this form; other Mobile-type processors (Apple Pay, Google Pay, ...) are
   * fully frozen.
   *
   * @param array $paymentProcessor payment processor params
   * @param CRM_Core_Form $form the edit form, used to read the recurring ID
   *
   * @return array editable field names, or [] when the form should be frozen
   */
  public static function getEditableFields($paymentProcessor, $form) {
    if (!CRM_Core_Payment_LinePay::isLinePayPreapprovedProcessor($paymentProcessor)) {
      return [];
    }
    $recurId = $form->get('id');
    if (!self::isLinePayInstrument($recurId)) {
      return [];
    }
    return CRM_Core_Payment_LinePay::getEditableFields($recurId);
  }

  /**
   * Apply LINE Pay side-effects when the Recurring contribution edit form is saved.
   *
   * refs #45587. Only called by the form when getEditableFields() returned a
   * non-empty list, i.e. only for an unlocked LINE Pay preapproved recurring.
   *
   * @param array $params changed fields, plus contribution_recur_id and trxn_id
   * @param bool $debug debug mode
   *
   * @return array ['is_error' => int, 'msg' => string]
   */
  public function doUpdateRecur($params, $debug = FALSE) {
    if (!CRM_Core_Payment_LinePay::isLinePayPreapprovedProcessor($this->_paymentProcessor)) {
      return $params;
    }
    if (!self::isLinePayInstrument($params['contribution_recur_id'])) {
      return $params;
    }
    return CRM_Core_Payment_LinePay::updateRecur($params, $debug);
  }

  /**
   * Assign template variables for the Recurring contribution edit form.
   *
   * refs #45587. Provides the LINE Pay preapproved key's 180-day expiry window
   * so the form can warn an admin who pauses (Suspended, status 7) a recurring.
   *
   * @param CRM_Core_Form $form the edit form
   *
   * @return void
   */
  public static function postBuildForm(&$form) {
    $recurId = $form->get('id');
    if (empty($recurId)) {
      return;
    }
    $processorId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionRecur', $recurId, 'processor_id');
    if (empty($processorId)) {
      return;
    }
    $isTest = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionRecur', $recurId, 'is_test');
    $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($processorId, $isTest ? 'test' : 'live');
    if (!CRM_Core_Payment_LinePay::isLinePayPreapprovedProcessor($paymentProcessor)) {
      return;
    }
    if (!self::isLinePayInstrument($recurId)) {
      return;
    }
    $expiry = CRM_Core_Payment_LinePay::getRegKeyExpiryInfo($recurId);
    if (!empty($expiry)) {
      $form->assign('linepay_last_charge_date', $expiry['last_charge_date']);
      $form->assign('linepay_regkey_expiry_date', $expiry['expiry_date']);
    }
  }

  /**
   * Handle recurring transaction trigger.
   *
   * refs #45587. "Process now" on the Recurring contribution view is only
   * meaningful for a LINE Pay preapproved recurring whose latest contribution
   * was itself paid via LINE Pay; other Mobile-type processors (Apple Pay,
   * Google Pay, ...) don't support this action.
   *
   * @param int|null $recurId recurring ID
   * @param bool $sendMail whether to send a confirmation email
   *
   * @return array result note
   */
  public static function doRecurTransact($recurId = NULL, $sendMail = FALSE) {
    if (empty($recurId) || !CRM_Utils_Type::validate($recurId, 'Positive')) {
      $msg = ts('Missing required field: %1', [1 => 'Recurring Id']);
      return ['status' => '', 'msg' => $msg];
    }

    $processorId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionRecur', $recurId, 'processor_id');
    $isTest = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionRecur', $recurId, 'is_test');
    $paymentProcessor = !empty($processorId) ? CRM_Core_BAO_PaymentProcessor::getPayment($processorId, $isTest ? 'test' : 'live') : NULL;
    if (empty($paymentProcessor) || !CRM_Core_Payment_LinePay::isLinePayPreapprovedProcessor($paymentProcessor)) {
      $msg = ts("Recurring contribution %1 doesn't have LINE Pay Recurring enabled.", [1 => $recurId]);
      CRM_Core_Error::debug_log_message($msg);
      return ['status' => '', 'msg' => $msg];
    }

    if (!self::isLinePayInstrument($recurId)) {
      $msg = ts("The latest contribution of recurring %1 was not paid with LINE Pay.", [1 => $recurId]);
      CRM_Core_Error::debug_log_message($msg);
      return ['status' => '', 'msg' => $msg];
    }

    return CRM_Core_Payment_LinePay::doRecurTransact($recurId, $sendMail);
  }

  /**
   * Whether the latest contribution of a recurring was paid via LINE Pay.
   *
   * refs #45587. Used to gate LINE Pay preapproved actions (editing, manual
   * "Process now") to recurrings whose payments actually flow through LINE Pay.
   *
   * @param int $recurId contribution recur ID
   *
   * @return bool TRUE when the latest contribution's payment instrument is LINE Pay
   */
  private static function isLinePayInstrument($recurId) {
    // Compare the option value machine name ('LinePay'), never the label:
    // the label is localized and free to be renamed by the site.
    $instrumentName = CRM_Core_DAO::singleValueQuery("SELECT v.name FROM civicrm_contribution c INNER JOIN civicrm_option_value v ON v.value = c.payment_instrument_id INNER JOIN civicrm_option_group g ON v.option_group_id = g.id WHERE g.name = 'payment_instrument' AND c.contribution_recur_id = %1 ORDER BY c.id DESC LIMIT 1", [
      1 => [$recurId, 'Positive'],
    ]);
    return strtolower((string) $instrumentName) === 'linepay';
  }

  /**
   * Handle Apple Pay checkout via POST data.
   *
   * @return string themed HTML page
   */
  public static function checkout() {
    if ($_POST['instrument'] == 'ApplePay') {
      $domain = CRM_Core_BAO_Domain::getDomain();
      $smarty = CRM_Core_Smarty::singleton();
      $smarty->assign('after_redirect', 1);
      $smarty->assign('organization', $domain->name);
      foreach ($_POST as $key => $value) {
        $smarty->assign($key, $value);
      }
      $page = $smarty->fetch('CRM/Core/Payment/ApplePay.tpl');
      CRM_Utils_System::setTitle(ts('Contribute Now'));
      return CRM_Utils_System::theme($page);
    }
  }

  /**
   * Validate an Apple Pay session URL.
   *
   * @return void
   */
  public static function validate() {
    $contributionId = CRM_Utils_Request::retrieve('cid', 'Positive', CRM_Core_DAO::$_nullObject, TRUE, NULL, 'REQUEST');
    $validationUrl = CRM_Utils_Request::retrieve('validationURL', 'String', CRM_Core_DAO::$_nullObject, TRUE, NULL, 'REQUEST');
    $domainName = CRM_Utils_Request::retrieve('domain_name', 'String', CRM_Core_DAO::$_nullObject, TRUE, NULL, 'REQUEST');
    $isTest = CRM_Utils_Request::retrieve('is_test', 'Boolean', CRM_Core_DAO::$_nullObject, FALSE, FALSE, 'REQUEST');
    $path = CRM_Utils_Request::retrieve('q', 'String', CRM_Core_DAO::$_nullObject, FALSE, NULL, 'REQUEST');
    $arg = explode('/', $path);

    $mobile_paymentProcessor_id = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $contributionId, 'payment_processor_id');
    $merchantIdentifier = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_PaymentProcessor', $mobile_paymentProcessor_id, 'signature');

    if ($arg[2] == 'applepay') {
      // Refs: Document: [Requesting an Apple Pay Payment Session] https://goo.gl/CJAe4M
      $data = [
        'merchantIdentifier' => $merchantIdentifier,
        'displayName' => 'test',
        'initiative' => 'web',
        'initiativeContext' => $domainName,
      ];
      $host = '';
      if (!self::doCheckValidationUrl($validationUrl, $isTest, $host)) {
        $note = ts('URL: %1 is not accessable, Where ip is %2', [
          1 => $validationUrl,
          2 => $host,
        ]);
        $contribution = new CRM_Contribute_DAO_Contribution();
        $contribution->id = $contributionId;
        $contribution->find(TRUE);
        self::addNote($note, $contribution);
        exit;
      }
      $file_name = 'applepaycert_'.$mobile_paymentProcessor_id.'.inc';
      $file_path = CRM_Utils_System::cmsRootPath() . '/' . CRM_Utils_System::confPath().'/' . $file_name;
      global $civicrm_root;
      $cafile_path = $civicrm_root.'cert/cacert.pem';

      $ch = curl_init($validationUrl);
      $opt = [];
      $opt[CURLOPT_RETURNTRANSFER] = TRUE;
      $opt[CURLOPT_POST] = TRUE;
      $opt[CURLOPT_HTTPHEADER] = ["Content-Type: application/json"];
      $opt[CURLOPT_POSTFIELDS] = json_encode($data);
      $opt[CURLOPT_SSLCERT] = $file_path;
      $opt[CURLOPT_CAINFO] = $cafile_path;
      curl_setopt_array($ch, $opt);

      $cmd = 'curl --request POST --url "'.$validationUrl.'" --cacert '.$cafile_path.' --cert '.$file_path.' -H "Content-Type: application/json" --data "'. json_encode($data).'"';
      CRM_Core_Error::debug('cmd', $cmd);

      $result = curl_exec($ch);
      $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      if ($result === FALSE) {
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        $curlError = [$errno => $err];
      }
      else {
        $curlError = [];
      }

      CRM_Core_Error::debug('applepay_filepath', $file_path);
      CRM_Core_Error::debug('applepay_validate_post', $_POST);
      CRM_Core_Error::debug('applepay_validate_get', $_GET);
      CRM_Core_Error::debug('applepay_validate_curl_result', $result);
      CRM_Core_Error::debug('applepay_validate_curl_status', $status);
      CRM_Core_Error::debug('applepay_validate_curl_error', $curlError);

      curl_close($ch);
    }
    echo $result;
    CRM_Utils_System::civiExit();
  }

  /**
   * Process a mobile payment transaction.
   *
   * @return void
   */
  public static function transact() {
    $contributionId = CRM_Utils_Request::retrieve('cid', 'Positive', CRM_Core_DAO::$_nullObject, TRUE, NULL, 'REQUEST');
    $ppProvider = CRM_Utils_Request::retrieve('provider', 'String', CRM_Core_DAO::$_nullObject, TRUE, NULL, 'REQUEST');
    $participant_id = CRM_Utils_Request::retrieve('pid', 'Positive', CRM_Core_DAO::$_nullObject, FALSE, NULL, 'REQUEST');
    $event_id = CRM_Utils_Request::retrieve('eid', 'Positive', CRM_Core_DAO::$_nullObject, FALSE, NULL, 'REQUEST');
    $membership_id = CRM_Utils_Request::retrieve('mid', 'Positive', CRM_Core_DAO::$_nullObject, FALSE, NULL, 'REQUEST');
    $post = $_POST;

    $contribution = new CRM_Contribute_DAO_Contribution();
    $contribution->id = $contributionId;
    $contribution->find(TRUE);

    if ($contribution->contribution_status_id == 1) {
      // The contribution is solved, avoid solve twice.
      CRM_Core_Error::debug('applepay_transact_post_duplicated_condition', $_POST);
      CRM_Core_Error::debug('applepay_transact_get_duplicated_condition', $_GET);
      CRM_Core_Error::debug_log_message('This contribution has already been processed.');
      CRM_Utils_System::civiExit();
    }

    // Prepare objects to put in checkout function.
    $originPaymentProcessorId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_PaymentProcessor', $contribution->payment_processor_id, 'user_name');
    $merchantPaymentProcessor = new CRM_Core_DAO_PaymentProcessor();
    $merchantPaymentProcessor->id = $originPaymentProcessorId;
    $merchantPaymentProcessor->find(TRUE);
    $objects = [
      'contribution' => $contribution,
      'payment_processor' => $merchantPaymentProcessor,
    ];

    if (strstr($_GET['q'], 'applepay')) {
      $type = 'applepay';
    }
    // call mobile checkout function
    $paymentProviderClass = 'CRM_Core_Payment_'.strtoupper($ppProvider);
    if (!is_callable([$paymentProviderClass, 'mobileCheckout'])) {
      return CRM_Core_Error::fatal('Function '.$paymentProviderClass.'::mobileCheckout doesn\'t exists.');
    }
    $return = call_user_func([$paymentProviderClass, 'mobileCheckout'], $type, $post, $objects);

    if (!empty($return)) {

      CRM_Core_Error::debug('applepay_transact_post', $_POST);
      CRM_Core_Error::debug('applepay_transact_get', $_GET);
      CRM_Core_Error::debug('applepay_transact_checkout_result', $return);

      // execute ipn transact
      $ipn = new CRM_Core_Payment_BaseIPN();
      $input = $ids = $objects = [];
      if (!empty($participant_id) && !empty($event_id)) {
        $input['component'] = 'event';
        $ids['participant'] = $participant_id;
        $ids['event'] = $event_id;
      }
      else {
        if (!empty($membership_id)) {
          $ids['membership'] = $membership_id;
        }
        $input['component'] = 'contribute';
      }
      $ids['contribution'] = $contribution->id;
      $ids['contact'] = $contribution->contact_id;
      $validate_result = $ipn->validateData($input, $ids, $objects, FALSE);
      if ($validate_result) {
        $transaction = new CRM_Core_Transaction();
        if ($return['is_success']) {
          $input['payment_instrument_id'] = $contribution->payment_instrument_id;
          $input['amount'] = $contribution->amount;
          $objects['contribution']->receive_date = date('YmdHis');
          $objects['contribution']->trxn_id = $ids['contribution']; // Workaround, should use MerchantOrderNo from transact result
          $transaction_result = $ipn->completeTransaction($input, $ids, $objects, $transaction);

          $result = ['is_success' => 1];
        }
        else {
          $ipn->failed($objects, $transaction, $error);
          $note = $error . $return['message'];
          self::addNote($note, $contribution);
          $result = ['is_success' => 0];
        }
        $transaction->commit();
      }
    }

    echo json_encode($return);
    CRM_Utils_System::civiExit();
  }

  /**
   * Add a note to the contribution record.
   *
   * @param string $note note content
   * @param CRM_Contribute_DAO_Contribution &$contribution contribution object
   *
   * @return void
   */
  public static function addNote($note, &$contribution) {

    $note = date("Y/m/d H:i:s "). ts("Transaction record").": \n\n".$note."\n===============================\n";
    $note_exists = CRM_Core_BAO_Note::getNote($contribution->id, 'civicrm_contribution');
    if (count($note_exists)) {
      $note_id = [ 'id' => reset(array_keys($note_exists)) ];
      $note = $note . reset($note_exists);
    }
    else {
      $note_id = NULL;
    }
    $noteParams = [
      'entity_table'  => 'civicrm_contribution',
      'note'          => $note,
      'entity_id'     => $contribution->id,
      'contact_id'    => $contribution->contact_id,
      'modified_date' => date('Ymd')
    ];
    CRM_Core_BAO_Note::add($noteParams, $note_id);
  }

  /**
   * Get the administrative fields for this payment processor.
   *
   * @param object $ppDAO payment processor DAO
   * @param CRM_Core_Form $form the settings form
   *
   * @return array array of administrative fields
   */
  public static function getAdminFields($ppDAO, $form) {
    $text = ts('If the provider needs server IP address, the IP address of this website is %1', [1 => gethostbyname($_SERVER['HTTP_HOST'])]);
    CRM_Core_Session::setStatus($text);
    return [
      ['name' => 'user_name',
        'label' => $ppDAO->user_name_label,
      ],
      ['name' => 'password',
        'label' => $ppDAO->password_label,
      ],
      ['name' => 'signature',
        'label' => $ppDAO->signature_label,
      ],
      ['name' => 'subject',
        'label' => $ppDAO->subject_label,
      ],
      ['name' => 'url_site',
        'label' => ts('LinePay Channel ID'),
      ],
      ['name' => 'url_api',
        'label' => ts('LinePay Channel Secret Key'),
      ],
    ];
  }

  /**
   * Check if a validation URL is accessible and allowed by Apple Pay.
   *
   * @param string $url validation URL
   * @param bool $isTest whether in test mode
   * @param string &$host reference to store the host IP
   *
   * @return bool TRUE if allowed
   */
  public static function doCheckValidationUrl($url, $isTest = FALSE, &$host = NULL) {
    $isPass = FALSE;

    if (!preg_match('/^https:\/\//', $url)) {
      return $isPass;
    }
    $validateUrl = str_replace("https://", "", $url);
    $validateUrl = preg_replace("/\/.+$/", "", $validateUrl);

    if ($isTest) {
      $accessList = [
        "apple-pay-gateway-cert.apple.com" => "17.171.85.7",
        "cn-apple-pay-gateway-cert.apple.com" => "101.230.204.235",
      ];
    }
    else {
      $accessList = [
        "apple-pay-gateway-nc-pod1.apple.com" => "17.171.78.7",
        "apple-pay-gateway-nc-pod2.apple.com" => "17.171.78.71",
        "apple-pay-gateway-nc-pod3.apple.com" => "17.171.78.135",
        "apple-pay-gateway-nc-pod4.apple.com" => "17.171.78.199",
        "apple-pay-gateway-nc-pod5.apple.com" => "17.171.79.12",
        "apple-pay-gateway-pr-pod1.apple.com" => "17.141.128.7",
        "apple-pay-gateway-pr-pod2.apple.com" => "17.141.128.71",
        "apple-pay-gateway-pr-pod3.apple.com" => "17.141.128.135",
        "apple-pay-gateway-pr-pod4.apple.com" => "17.141.128.199",
        "apple-pay-gateway-pr-pod5.apple.com" => "17.141.129.12",
        "apple-pay-gateway-nc-pod1-dr.apple.com" => "17.171.78.9",
        "apple-pay-gateway-nc-pod2-dr.apple.com" => "17.171.78.73",
        "apple-pay-gateway-nc-pod3-dr.apple.com" => "17.171.78.137",
        "apple-pay-gateway-nc-pod4-dr.apple.com" => "17.171.78.201",
        "apple-pay-gateway-nc-pod5-dr.apple.com" => "17.171.79.13",
        "apple-pay-gateway-pr-pod1-dr.apple.com" => "17.141.128.9",
        "apple-pay-gateway-pr-pod2-dr.apple.com" => "17.141.128.73",
        "apple-pay-gateway-pr-pod3-dr.apple.com" => "17.141.128.137",
        "apple-pay-gateway-pr-pod4-dr.apple.com" => "17.141.128.201",
        "apple-pay-gateway-pr-pod5-dr.apple.com" => "17.141.129.13",
        "cn-apple-pay-gateway-sh-pod1.apple.com" => "101.230.204.232",
        "cn-apple-pay-gateway-sh-pod1-dr.apple.com" => "101.230.204.233",
        "cn-apple-pay-gateway-sh-pod2.apple.com" => "101.230.204.242",
        "cn-apple-pay-gateway-sh-pod2-dr.apple.com" => "101.230.204.243",
        "cn-apple-pay-gateway-sh-pod3.apple.com" => "101.230.204.240",
        "cn-apple-pay-gateway-sh-pod3-dr.apple.com" => "101.230.204.241",
        "cn-apple-pay-gateway-tj-pod1.apple.com" => "60.29.205.104",
        "cn-apple-pay-gateway-tj-pod1-dr.apple.com" => "60.29.205.105",
        "cn-apple-pay-gateway-tj-pod2.apple.com" => "60.29.205.106",
        "cn-apple-pay-gateway-tj-pod2-dr.apple.com" => "60.29.205.107",
        "cn-apple-pay-gateway-tj-pod3.apple.com" => "60.29.205.108",
        "cn-apple-pay-gateway-tj-pod3-dr.apple.com" => "60.29.205.109",
      ];
    }

    $host = gethostbyname($validateUrl);
    if ($accessList[$validateUrl] == $host) {
      $isPass = TRUE;
    }
    elseif ($validateUrl == 'apple-pay-gateway.apple.com' && in_array($host, $accessList)) {
      $isPass = TRUE;
    }

    return $isPass;
  }

  /**
   * Get the sync data URL for manual synchronization.
   *
   * @param int $contributionId contribution ID
   *
   * @return string|null sync URL or NULL if not supported
   */
  public static function getSyncDataUrl($contributionId) {
    $payment_instrument_id = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $contributionId, 'payment_instrument_id');
    $instrument_options = CRM_Core_OptionGroup::values('payment_instrument', FALSE);
    $instrument = $instrument_options[$payment_instrument_id];
    if (strtolower($instrument) == 'line pay') {
      $get = $_GET;
      unset($get['q']);
      $query = http_build_query($get);
      $sync_url = CRM_Utils_System::url("civicrm/linepay/query", $query);
    }
    else {
      $sync_url = NULL;
    }
    return $sync_url;
  }

  /**
   * Get the recurring cancelation message.
   *
   * @param int $recurID recurring ID
   *
   * @return string HTML message and JS
   */
  public function cancelRecuringMessage($recurID) {
    $text = '<p>'.ts("Please edit recurring and change status to 'Cancelled'.").'</p>';
    $js = '<script>cj(".ui-dialog-buttonset button").hide();</script>';
    return $text . $js;
  }

}
