<?php
/**
 * LINE Pay business logic for initiating and confirming transactions.
 *
 * This class only holds the business logic (build request payload, run the
 * IPN flow, write notes). The actual HTTP calls live in
 * CRM_Core_Payment_LinePayAPI; this class decides which API type to call at
 * the moment it needs it.
 *
 * @package CiviCRM_PaymentProcessor
 */

class CRM_Core_Payment_LinePay {

  /**
   * Sequence name used to lock the recurring batch (refs #45587).
   */
  public const QUEUE_NAME = 'linepay_recurring';

  /**
   * Days a preapproved regKey stays valid without being used (refs #45587).
   *
   * After this window LINE Pay invalidates the regKey, so the recurring is
   * expired locally and the key discarded.
   */
  public const REGKEY_VALID_DAYS = 180;

  /**
   * Recurring contribution_status_id values that are terminal for a LINE Pay
   * preapproved recurring (refs #45587).
   *
   * Completed (1) and Cancelled (3) are reached by choice (with a warning,
   * see updateRecur()); Failed (4) and Overdue/Expired (6) are only ever set
   * by the system. Once any of these is reached the Recurring contribution
   * edit form is fully locked.
   */
  public const LOCKED_RECUR_STATUSES = [1, 3, 4, 6];

  /**
   * Fields editable on the Recurring contribution edit form for a LINE Pay
   * preapproved recurring, while not locked (refs #45587).
   */
  public const EDITABLE_RECUR_FIELDS = [
    'amount', 'cycle_day', 'installments', 'end_date',
    'contribution_status_id', 'note_title', 'note_body',
  ];

  /**
   * @var string the mode of operation: live or test
   */
  protected $_mode;

  /**
   * @var array payment processor parameters (holds LINE Pay credentials)
   */
  protected $_paymentProcessor;

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
   * Build a LINE Pay API client for a given API type.
   *
   * The API type is decided at call time so a single business object can talk
   * to whichever endpoint it needs (request / confirm / query).
   *
   * @param string $apiType API type
   *
   * @return CRM_Core_Payment_LinePayAPI
   */
  private function getAPI($apiType) {
    return CRM_Core_Payment_LinePayAPI::create($this->_paymentProcessor, $apiType);
  }

  /**
   * Initiate a LINE Pay payment request.
   *
   * @param array &$params contribution and form parameters
   *
   * @return void
   */
  public function doRequest(&$params) {

    // prepare confirm url
    $qfKey = $params['qfKey'];
    $contributionId = $params['contributionID'];
    $paymentProcessorId = $params['payment_processor'];
    $confirmQuery = "qfKey={$qfKey}&cid={$contributionId}&ppid={$paymentProcessorId}";
    $path = CRM_Utils_System::currentPath();

    if (!empty($params['participantID'])) {
      $confirmQuery .= "&pid={$params['participantID']}";
    }
    if (!empty($params['eventID'])) {
      $confirmQuery .= "&eid={$params['eventID']}";
    }
    if (!empty($params['membershipID'])) {
      $confirmQuery .= "&mid={$params['membershipID']}";
    }

    $confirmUrl = CRM_Utils_System::url('civicrm/linepay/confirm', $confirmQuery, TRUE, NULL, FALSE);

    $cancelUrl = self::prepareThankYouUrl($path, $qfKey, TRUE);

    // page title, description
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

    // reserve (v4: nested packages[] / redirectUrls / options)
    $config = CRM_Core_Config::singleton();
    $amount = (int)$params['amount'];
    $productName = strip_tags($description);
    $requestParams = [
      'amount' => $amount,
      'currency' => $config->defaultCurrency,
      'orderId' => (string)$params['contributionID'],
      'packages' => [
        [
          'id' => '1',
          'amount' => $amount,
          'products' => [
            [
              'name' => $productName,
              'imageUrl' => $config->userFrameworkResourceURL . 'i/whiteBg.png',
              'quantity' => 1,
              'price' => $amount,
            ],
          ],
        ],
      ],
      'redirectUrls' => [
        'confirmUrl' => $confirmUrl,
        'cancelUrl' => $cancelUrl,
        // v4: confirmUrlType belongs to redirectUrls, default 'CLIENT'
        'confirmUrlType' => 'CLIENT',
      ],
      'options' => [
        'payment' => [
          'capture' => TRUE,
        ],
        'display' => [
          'locale' => CRM_Core_Payment_LinePayAPI::displayLocale(),
          'checkConfirmUrlBrowser' => TRUE,
        ],
      ],
    ];

    // refs #45587, switch to preapproved when this is a recurring
    // donation and the processor has enabled LINE Pay preapproved (subject flag).
    $isPreapproved = !empty($params['contributionRecurID']);
    if ($isPreapproved) {
      $requestParams['options']['payment']['payType'] = 'PREAPPROVED';
      $requestParams['options']['regPayRequest'] = self::buildRegPayRequest($params['contributionRecurID'], $amount);
    }

    CRM_Core_Error::debug_var('mobile_payment_linepay', $requestParams);
    $api = $this->getAPI('request');
    $response = $api->request($requestParams);
    if ($response && $response->returnCode === '0000') {
      $transactionId = $response->info->transactionId;
      if (!empty($transactionId)) {
        $contribution = self::prepareContribution($contributionId);
        $contribution->trxn_id = $transactionId;
        $contribution->save();
      }
      if ($isPreapproved) {
        CRM_Core_DAO::executeQuery("UPDATE civicrm_contribution_linepay SET contribution_recur_id = %1 WHERE trxn_id = %2", [
          1 => [$params['contributionRecurID'], 'Integer'],
          2 => [(string) $contributionId, 'String'],
        ]);
      }
      CRM_Utils_System::redirect($response->info->paymentUrl->web);
    }
    else {
      self::addResponseMessageToNote($contributionId, $api);
      $returnMessage = !empty($api->_response->returnMessage) ? $api->_response->returnMessage : ts('LINE Pay request failed.');
      throw new CRM_Core_Exception($returnMessage);
    }
  }

  /**
   * Static entry point for confirming a LINE Pay transaction.
   *
   * Parameters are read straight from the request with typed validation rather
   * than trusting whatever the router/LINE Pay redirect hands us.
   *
   * @return void
   */
  public static function confirm() {
    $params = [
      'qfKey' => CRM_Utils_Request::retrieve('qfKey', 'String', CRM_Core_DAO::$_nullObject, TRUE, NULL, 'GET'),
      'cid' => CRM_Utils_Request::retrieve('cid', 'Positive', CRM_Core_DAO::$_nullObject, TRUE, NULL, 'GET'),
      'ppid' => CRM_Utils_Request::retrieve('ppid', 'Positive', CRM_Core_DAO::$_nullObject, TRUE, NULL, 'GET'),
      'pid' => CRM_Utils_Request::retrieve('pid', 'Positive', CRM_Core_DAO::$_nullObject, FALSE, NULL, 'GET'),
      'eid' => CRM_Utils_Request::retrieve('eid', 'Positive', CRM_Core_DAO::$_nullObject, FALSE, NULL, 'GET'),
      'mid' => CRM_Utils_Request::retrieve('mid', 'Positive', CRM_Core_DAO::$_nullObject, FALSE, NULL, 'GET'),
      'transactionId' => CRM_Utils_Request::retrieve('transactionId', 'String', CRM_Core_DAO::$_nullObject, TRUE, NULL, 'GET'),
    ];
    $paymentProcessor = self::getPaymentProcessor($params['ppid']);
    $mode = !empty($paymentProcessor['is_test']) ? 'test' : 'live';
    $linePay = new self($mode, $paymentProcessor);
    $thankyouUrl = $linePay->doConfirm($params);
    CRM_Utils_System::redirect($thankyouUrl);
  }

  /**
   * Handle the confirmation of a LINE Pay transaction.
   *
   * @param array $params confirmation parameters (including transactionId)
   *
   * @return string thank you URL the caller should redirect to
   */
  public function doConfirm($params) {
    $config = CRM_Core_Config::singleton();
    $contribution = self::prepareContribution($params['cid']);

    // confirm (v4: POST /v4/payments/{transactionId}/confirm, body amount + currency)
    $confirmParams = [
      'transactionId' => $params['transactionId'],
      'amount' => (int)$contribution->total_amount,
      'currency' => $config->defaultCurrency,
    ];
    $api = $this->getAPI('confirm');
    $api->request($confirmParams);

    // refs #45587, store the preapproved regKey returned on a PREAPPROVED confirm.
    if (!empty($api->_response->info->regKey)) {
      CRM_Core_DAO::executeQuery("UPDATE civicrm_contribution_linepay SET reg_key = %1 WHERE trxn_id = %2 OR transaction_id = %3", [
        1 => [$api->_response->info->regKey, 'String'],
        2 => [(string) $params['cid'], 'String'],
        3 => [(string) $params['transactionId'], 'String'],
      ]);
    }

    // Timeout condition: confirm timed out (curl error 28). Fall back to query
    // to find out the real transaction status.
    $triedTimes = 0;
    while (!empty($api->_curlError[28]) && $triedTimes < 2) {
      sleep(10);
      $api = $this->getAPI('query');
      $api->request([
        'transactionId' => $params['transactionId'],
        'orderId' => $params['cid'],
      ]);
      $triedTimes++;
    }
    $is_success = $api->_success;
    $thankYouPath = 'civicrm/contribute/transact';

    // ipn transact
    $ipn = new CRM_Core_Payment_BaseIPN();
    $input = $ids = $objects = [];
    if (!empty($params['pid']) && !empty($params['eid'])) {
      $input['component'] = 'event';
      $ids['participant'] = $params['pid'];
      $ids['event'] = $params['eid'];
      $thankYouPath = 'civicrm/event/register';
    }
    else {
      if (!empty($params['mid'])) {
        $ids['membership'] = $params['mid'];
      }
      $input['component'] = 'contribute';
    }
    $ids['contribution'] = $contribution->id;
    $ids['contact'] = $contribution->contact_id;
    $validate_result = $ipn->validateData($input, $ids, $objects, FALSE);
    // Refs #31598, 1172 means duplicated order, often means trigger twice.
    // Refs #41790, 1198 means duplicate API requests.
    if ($validate_result && ($api->_response->returnCode != '1172' && $api->_response->returnCode != '1198')) {
      $transaction = new CRM_Core_Transaction();
      if ($is_success) {
        $input['payment_instrument_id'] = $contribution->payment_instrument_id;
        $input['amount'] = $contribution->total_amount;
        $objects['contribution']->receive_date = date('YmdHis');
        $ipn->completeTransaction($input, $ids, $objects, $transaction);
        // refs #45587, the first successful charge of a preapproved recurring
        // activates it: move Pending (2) to In Progress (5) so the recurring
        // batch (which only picks In Progress) starts charging it.
        if (!empty($contribution->contribution_recur_id)) {
          $recurStatusId = (int) CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionRecur', $contribution->contribution_recur_id, 'contribution_status_id');
          if ($recurStatusId === 2) {
            $recurParams = [
              'id' => $contribution->contribution_recur_id,
              'contribution_status_id' => 5,
            ];
            $null = NULL;
            CRM_Contribute_BAO_ContributionRecur::add($recurParams, $null);
          }
        }
        $thankyou_url = self::prepareThankYouUrl($thankYouPath, $params['qfKey']);
      }
      else {
        $error = '';
        $ipn->failed($objects, $transaction, $error);
        self::addResponseMessageToNote($contribution, $api);
        $thankyou_url = self::prepareThankYouUrl($thankYouPath, $params['qfKey'], TRUE);
      }
      $transaction->commit();
    }
    else {
      $thankyou_url = self::prepareThankYouUrl($thankYouPath, $params['qfKey'], TRUE);
    }

    // refs #45587, this confirm carries the first preapproved charge. If the
    // gateway voided the just-issued regKey, drop it and fail the recurring so
    // it never tries to charge again.
    if (!empty($contribution->contribution_recur_id)) {
      $returnCode = $api->_response->returnCode ?? '';
      if (self::isRegKeyVoidedCode($returnCode)) {
        self::voidRegKey($contribution->contribution_recur_id, $returnCode, $api->_response->returnMessage ?? '');
      }
    }

    return $thankyou_url;
  }

  /**
   * Static entry point for querying LINE Pay transaction status.
   *
   * This is a pure sync operation, so it talks to the LINE Pay API directly
   * instead of going through the business object. Parameters are read straight
   * from the request with typed validation.
   *
   * @return CRM_Core_Error|void status bounce or error on failure
   */
  public static function query() {
    $contributionId = CRM_Utils_Request::retrieve('id', 'Positive', CRM_Core_DAO::$_nullObject, TRUE, NULL, 'GET');
    $contactId = CRM_Utils_Request::retrieve('cid', 'Positive', CRM_Core_DAO::$_nullObject, FALSE, NULL, 'GET');
    $action = CRM_Utils_Request::retrieve('action', 'String', CRM_Core_DAO::$_nullObject, FALSE, NULL, 'GET');
    $context = CRM_Utils_Request::retrieve('context', 'String', CRM_Core_DAO::$_nullObject, FALSE, NULL, 'GET');
    $selectedChild = CRM_Utils_Request::retrieve('selectedChild', 'String', CRM_Core_DAO::$_nullObject, FALSE, NULL, 'GET');

    $contribution = new CRM_Contribute_DAO_Contribution();
    $contribution->id = $contributionId;
    if ($contribution->find(TRUE)) {
      $result_note = ts('Update the contribution manually.');

      // Sync to linepay server: call the API directly.
      $paymentProcessor = self::getPaymentProcessor($contribution->payment_processor_id);
      $api = CRM_Core_Payment_LinePayAPI::create($paymentProcessor, 'query');
      $api->request([
        'orderId' => $contribution->id,
        'transactionId' => $contribution->trxn_id,
      ]);
      if (!empty($api->_response->info)) {
        $info = $api->_response->info;
        if (is_array($info)) {
          foreach ($info as $transaction) {
            if ($transaction->transactionId == $contribution->trxn_id) {
              break;
            }
          }
        }

        // record original cancel_date, status_id data.
        $origin_cancel_date = (string) $contribution->cancel_date;
        $origin_cancel_date = date('YmdHis', strtotime($origin_cancel_date));
        $origin_status_id = $contribution->contribution_status_id;

        // check info
        $result_note .= "\n".ts('Sync to Linepay server success.');

        // check refundList
        if (!empty($transaction->refundList)) {
          // find refund, check original status
          $refund = $transaction->refundList[0];
          $cancel_date = $refund->refundTransactionDate;
          $cancel_date = date('YmdHis', strtotime($cancel_date));
          $contribution->cancel_date = $cancel_date;
          $contribution->contribution_status_id = 3;
          if ($origin_cancel_date == $contribution->cancel_date && $origin_status_id == $contribution->contribution_status_id) {
            $result_note .= "\n".ts('There are no any change.');
          }
          else {
            $contribution->save();
            $result_note .= "\n".ts('The contribution has been canceled.');
          }
        }
        else {
          $result_note .= "\n".ts('There are no any change.');
        }
        // finish check info
        CRM_Core_Payment_Mobile::addNote($result_note, $contribution);

      }
      else {
        $result_note = ts('The response has errors, please check the note.');
        self::addResponseMessageToNote($contribution, $api);

      }
      // finish sync to linepay server
      unset($contribution);

      $get = ['reset' => 1, 'id' => $contributionId];
      if (!empty($contactId)) {
        $get['cid'] = $contactId;
      }
      if (!empty($action)) {
        $get['action'] = $action;
      }
      if (!empty($context)) {
        $get['context'] = $context;
      }
      if (!empty($selectedChild)) {
        $get['selectedChild'] = $selectedChild;
      }
      $query = http_build_query($get);
      $redirect = CRM_Utils_System::url('civicrm/contact/view/contribution', $query);
      CRM_Core_Session::setStatus($result_note);
      CRM_Utils_System::redirect($redirect);
    }
    else {
      throw new CRM_Core_Exception(ts('Wrong contribution ID in url query'));
    }
  }

  /**
   * Add a LINE Pay error response message to the contribution note.
   *
   * @param CRM_Contribute_DAO_Contribution|int $contribution contribution object or ID
   * @param CRM_Core_Payment_LinePayAPI $api the API client that holds the response
   *
   * @return void
   */
  private static function addResponseMessageToNote($contribution, $api) {
    if (is_numeric($contribution)) {
      $contribution = self::prepareContribution($contribution);
    }
    $returnCode = $api->_response->returnCode;
    $errorMessage = CRM_Core_Payment_LinePayAPI::errorMessage($returnCode);
    $note = "Error, return code is ".$returnCode.": ".$errorMessage;
    CRM_Core_Payment_Mobile::addNote($note, $contribution);
  }

  /**
   * Prepare a contribution object from a contribution ID.
   *
   * @param int $contributionId contribution ID
   *
   * @return CRM_Contribute_DAO_Contribution contribution object
   */
  private static function prepareContribution($contributionId) {
    $contribution = new CRM_Contribute_DAO_Contribution();
    $contribution->id = $contributionId;
    $contribution->find(TRUE);
    return $contribution;
  }

  /**
   * Load a payment processor configuration array by ID.
   *
   * @param int $paymentProcessorId payment processor ID
   *
   * @return array payment processor parameters
   */
  private static function getPaymentProcessor($paymentProcessorId) {
    $dao = new CRM_Core_DAO_PaymentProcessor();
    $dao->id = $paymentProcessorId;
    if (!$dao->find(TRUE)) {
      throw new CRM_Core_Exception(ts('Could not find payment processor meta information'));
    }
    return CRM_Core_BAO_PaymentProcessor::buildPayment($dao);
  }

  /**
   * Prepare the thank you page URL.
   *
   * @param string $path page path
   * @param string $qfKey quickform key
   * @param bool $failed whether the payment failed
   *
   * @return string full thank you URL
   */
  public static function prepareThankYouUrl($path, $qfKey, $failed = FALSE) {
    $query = "_qf_ThankYou_display=1&qfKey={$qfKey}";
    $query .= $failed ? '&payment_result_type=4' : '&payment_result_type=1';
    $url = CRM_Utils_System::url($path, $query, TRUE, NULL, FALSE);
    return $url;
  }

  /**
   * Build the options.regPayRequest payload for a preapproved request.
   *
   * refs #45587. This is display-only for the customer; netiCRM still drives
   * the actual charging via regKey, so the schedule fields are informative.
   *
   * @param int $recurId contribution recur ID
   * @param int $amount charge amount
   *
   * @return array regPayRequest payload
   */
  private static function buildRegPayRequest($recurId, $amount) {
    $recur = new CRM_Contribute_DAO_ContributionRecur();
    $recur->id = $recurId;
    $recur->find(TRUE);
    $regPayRequest = [
      'regPayPeriodType' => 'RECURRING',
      'recurringPeriod' => CRM_Core_Payment_LinePayAPI::recurringPeriod($recur->frequency_unit),
      'productPrice' => $amount,
    ];
    if ($recur->frequency_unit === 'week') {
      $regPayRequest['recurringDayOfWeek'] = NULL;
    }
    elseif ($recur->frequency_unit === 'year') {
      $regPayRequest['recurringMonth'] = (int) date('n');
      $regPayRequest['recurringDay'] = !empty($recur->cycle_day) ? (int) $recur->cycle_day : (int) date('j');
    }
    else {
      $regPayRequest['recurringDay'] = !empty($recur->cycle_day) ? (int) $recur->cycle_day : NULL;
    }
    return $regPayRequest;
  }

  /**
   * Guard: confirm a recurring is a LINE Pay preapproved recurring.
   *
   * refs #45587. payByRegKey() / doCheckRecur() can be reached directly (drush,
   * the crid request param, cron), so before charging we verify the recurring's
   * gateway. The processor id is resolved from
   * civicrm_contribution_recur.processor_id, falling back to
   * civicrm_contribution.payment_processor_id, then loaded for the matching
   * test/live mode. It qualifies only when the gateway is a Mobile processor
   * that carries LINE Pay credentials and has LINE Pay Recurring enabled (the
   * repurposed `subject` flag = '1').
   *
   * @param int $recurId contribution recur ID
   *
   * @return bool TRUE when this recurring may be charged through LINE Pay preapproved
   */
  private static function isLinePayRecur($recurId) {
    if (empty($recurId)) {
      return FALSE;
    }
    $paymentProcessor = self::getRecurPaymentProcessor($recurId);
    if (empty($paymentProcessor) || $paymentProcessor['payment_processor_type'] !== 'Mobile') {
      return FALSE;
    }
    return self::isLinePayPreapprovedProcessor($paymentProcessor);
  }

  /**
   * Whether a payment processor is a LINE Pay preapproved (recurring) gateway.
   *
   * refs #45587. The gateway must carry LINE Pay credentials and have the
   * repurposed `subject` flag (LINE Pay Recurring) enabled. Shared with
   * CRM_Core_Payment_Mobile so the Recurring contribution edit form can decide
   * which fields are editable.
   *
   * @param array $paymentProcessor payment processor params
   *
   * @return bool TRUE when this processor is a LINE Pay preapproved gateway
   */
  public static function isLinePayPreapprovedProcessor($paymentProcessor) {
    if (empty($paymentProcessor['url_site']) || empty($paymentProcessor['url_api'])) {
      return FALSE;
    }
    return (string) ($paymentProcessor['subject'] ?? '') === '1';
  }

  /**
   * Resolve the LINE Pay payment processor backing a recurring.
   *
   * refs #45587. The processor id is taken from
   * civicrm_contribution_recur.processor_id, falling back to
   * civicrm_contribution.payment_processor_id of the first contribution, then
   * loaded for the matching test/live mode.
   *
   * @param int $recurId contribution recur ID
   *
   * @return array|null payment processor params, or NULL when unresolved
   */
  private static function getRecurPaymentProcessor($recurId) {
    $sql = "
SELECT
  COALESCE(r.processor_id, c.payment_processor_id) AS payment_processor_id,
  c.is_test AS is_test
FROM civicrm_contribution_recur r
LEFT JOIN civicrm_contribution c ON c.contribution_recur_id = r.id AND c.payment_processor_id IS NOT NULL
WHERE r.id = %1
ORDER BY c.id ASC
LIMIT 1";
    $dao = CRM_Core_DAO::executeQuery($sql, [1 => [$recurId, 'Positive']]);
    if (!$dao->fetch() || empty($dao->payment_processor_id)) {
      return NULL;
    }
    $mode = !empty($dao->is_test) ? 'test' : 'live';
    return CRM_Core_BAO_PaymentProcessor::getPayment($dao->payment_processor_id, $mode);
  }

  /**
   * Whether a LINE Pay result code voids the preapproved regKey.
   *
   * refs #45587. Per the v4 docs, return codes 1141, 1282-1287 and 1290-1295
   * revoke the regKey; once revoked it can never be recovered and the donor has
   * to subscribe again.
   *
   * @param string $returnCode LINE Pay result code
   *
   * @return bool TRUE when the regKey is voided by this code
   */
  private static function isRegKeyVoidedCode($returnCode) {
    $code = (int) $returnCode;
    return $code === 1141
      || ($code >= 1282 && $code <= 1287)
      || ($code >= 1290 && $code <= 1295);
  }

  /**
   * Timestamp of the most recent successful charge on a recurring.
   *
   * refs #45587. Used to gate the 180-day regKey validity window; falls back to
   * the recurring's start/create date when nothing has been charged yet.
   *
   * @param int $recurId contribution recur ID
   *
   * @return int|null unix timestamp, or NULL when undeterminable
   */
  /**
   * Get the active preapproved regKey for a recurring, if any.
   *
   * @param int $recurId contribution recur ID
   *
   * @return string|null the regKey, or NULL when none is stored
   */
  private static function getRegKey($recurId) {
    return CRM_Core_DAO::singleValueQuery("SELECT reg_key FROM civicrm_contribution_linepay WHERE contribution_recur_id = %1 AND reg_key IS NOT NULL AND reg_key != '' LIMIT 1", [
      1 => [$recurId, 'Positive'],
    ]);
  }

  /**
   * Whether a recurring still has a usable preapproved regKey.
   *
   * refs #45587. Used to decide whether a Pending recurring is still editable
   * on the Recurring contribution edit form.
   *
   * @param int $recurId contribution recur ID
   *
   * @return bool TRUE when a regKey is stored for this recurring
   */
  public static function hasRegKey($recurId) {
    return !empty(self::getRegKey($recurId));
  }

  private static function lastChargeTime($recurId) {
    $date = CRM_Core_DAO::singleValueQuery("SELECT MAX(receive_date) FROM civicrm_contribution WHERE contribution_recur_id = %1 AND contribution_status_id = 1", [
      1 => [$recurId, 'Positive'],
    ]);
    if (empty($date)) {
      $date = CRM_Core_DAO::singleValueQuery("SELECT COALESCE(start_date, create_date) FROM civicrm_contribution_recur WHERE id = %1", [
        1 => [$recurId, 'Positive'],
      ]);
    }
    return !empty($date) ? strtotime($date) : NULL;
  }

  /**
   * Change a recurring's status and leave a payment-gateway note.
   *
   * @param int $recurId contribution recur ID
   * @param int $statusId target contribution_status_id
   * @param string $note human readable reason recorded on the recurring
   *
   * @return void
   */
  private static function setRecurStatus($recurId, $statusId, $note) {
    // add() takes both arguments by reference, so literals cannot be passed.
    $recurParams = [
      'id' => $recurId,
      'contribution_status_id' => $statusId,
      'message' => $note,
    ];
    $null = NULL;
    CRM_Contribute_BAO_ContributionRecur::add($recurParams, $null);
    $title = ts("Change status to %1", [1 => CRM_Contribute_PseudoConstant::contributionStatus($statusId)]);
    CRM_Contribute_BAO_ContributionRecur::addNote($recurId, ts('【Payment Gateway】') . ' ' . $title, $note);
  }

  /**
   * Drop a voided regKey and fail the recurring.
   *
   * refs #45587. When a charge returns a regKey-voiding code the key can never
   * be reused, so we clear it locally and move the recurring to Failed (4).
   *
   * @param int $recurId contribution recur ID
   * @param string $returnCode LINE Pay result code that voided the key
   * @param string $returnMessage LINE Pay result message
   *
   * @return void
   */
  private static function voidRegKey($recurId, $returnCode, $returnMessage = '') {
    CRM_Core_DAO::executeQuery("UPDATE civicrm_contribution_linepay SET reg_key = NULL WHERE contribution_recur_id = %1", [
      1 => [$recurId, 'Positive'],
    ]);
    $note = ts("LINE Pay preapproved key was voided by the gateway (code %1: %2). The donor must subscribe again.", [
      1 => $returnCode,
      2 => $returnMessage,
    ]);
    self::setRecurStatus($recurId, 4, $note);
  }

  /**
   * Check whether a recurring's preapproved regKey is still valid on the LINE
   * Pay server (v4 GET /v4/payments/preapprovedPay/{regKey}/check).
   *
   * refs #45587. Used before discarding a regKey purely on the local
   * 180-day elapsed-time heuristic, in case the key is in fact still valid.
   *
   * @param int $recurId contribution recur ID
   *
   * @return bool TRUE when the regKey is confirmed valid (returnCode 0000)
   */
  private static function isRegKeyValid($recurId) {
    $regKey = self::getRegKey($recurId);
    if (empty($regKey)) {
      return FALSE;
    }
    $paymentProcessor = self::getRecurPaymentProcessor($recurId);
    if (empty($paymentProcessor)) {
      return FALSE;
    }
    $api = CRM_Core_Payment_LinePayAPI::create($paymentProcessor, 'recurring/check');
    $api->request(['regKey' => $regKey]);
    return ($api->_response->returnCode ?? '') === '0000';
  }

  /**
   * Discard a recurring's preapproved regKey on the LINE Pay server.
   *
   * refs #45587 (v4 POST /v4/payments/preapprovedPay/{regKey}/expire). Called
   * when a recurring reaches Completed/Cancelled/Expired so it can never charge
   * again; also clears the local regKey. Best effort — result codes 1190 (key
   * not found) and 1193 (key expired) mean the key is already gone.
   *
   * @param int $recurId contribution recur ID
   *
   * @return void
   */
  public static function expireRegKey($recurId) {
    $regKey = self::getRegKey($recurId);
    if (empty($regKey)) {
      return;
    }
    $paymentProcessor = self::getRecurPaymentProcessor($recurId);
    if (empty($paymentProcessor)) {
      return;
    }
    $api = CRM_Core_Payment_LinePayAPI::create($paymentProcessor, 'recurring/expire');
    $api->request(['regKey' => $regKey]);
    $returnCode = $api->_response->returnCode ?? '';
    if ($api->_success || in_array($returnCode, ['1190', '1193'], TRUE)) {
      CRM_Core_DAO::executeQuery("UPDATE civicrm_contribution_linepay SET reg_key = NULL WHERE contribution_recur_id = %1", [
        1 => [$recurId, 'Positive'],
      ]);
      CRM_Core_Error::debug_log_message("LinePay regKey discarded for recur $recurId (code $returnCode).");
    }
    else {
      CRM_Core_Error::debug_log_message("LinePay regKey discard failed for recur $recurId (code $returnCode).");
    }
  }

  /**
   * Handle recurring transaction trigger.
   *
   * @param int|null $recurId recurring ID
   * @param bool $sendMail whether to send a confirmation email
   *
   * @return array result note
   */
  public static function doRecurTransact($recurId = NULL, $sendMail = FALSE) {
    $resultNote = self::payByRegKey($recurId, NULL, $sendMail);
    return $resultNote;
  }

  /**
   * Charge a recurring contribution with a stored preapproved regKey.
   *
   * refs #45587. Mirrors CRM_Core_Payment_TapPay::payByToken: it copies the
   * first contribution of the recurring, charges it through the LINE Pay
   * preapproved payment API, and records the result via the IPN flow.
   *
   * @param int $recurringId contribution recur ID
   * @param int $referContributionId optional existing contribution to charge
   * @param bool $sendMail whether to send the receipt email
   *
   * @return array [status => string, msg => string]
   */
  public static function payByRegKey($recurringId = NULL, $referContributionId = NULL, $sendMail = TRUE) {
    if (empty($recurringId)) {
      $recurringId = CRM_Utils_Request::retrieve('crid', 'Positive', CRM_Core_DAO::$_nullObject, TRUE, $recurringId, 'REQUEST');
    }

    // refs #45587, only charge recurrings whose gateway is a LINE Pay
    // preapproved Mobile processor.
    if (!self::isLinePayRecur($recurringId)) {
      $msg = "refs #45587, recur $recurringId is not a LINE Pay preapproved recurring; skip charging.";
      CRM_Core_Error::debug_log_message($msg);
      return ['status' => '', 'msg' => $msg];
    }

    $contributionRecur = new CRM_Contribute_DAO_ContributionRecur();
    $contributionRecur->id = $recurringId;
    $contributionRecur->find(TRUE);

    $config = CRM_Core_Config::singleton();
    $order = (!empty($config->recurringCopySetting) && $config->recurringCopySetting == 'latest') ? 'DESC' : 'ASC';
    $firstContributionId = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_contribution WHERE contribution_recur_id = %1 ORDER BY created_date $order", [
      1 => [$recurringId, 'Positive'],
    ]);
    $firstContribution = new CRM_Contribute_DAO_Contribution();
    $firstContribution->id = $firstContributionId;
    $firstContribution->find(TRUE);

    if (empty($referContributionId)) {
      $hash = hash('sha256', $firstContributionId);
      $c = CRM_Core_Payment_BaseIPN::copyContribution($firstContribution, $recurringId, $hash);
      $c->total_amount = $contributionRecur->amount;
    }
    else {
      $c = new CRM_Contribute_DAO_Contribution();
      $c->id = $referContributionId;
      if (!$c->find(TRUE)) {
        throw new CRM_Core_Exception(ts('Could not find the contribution.'));
      }
    }
    if ($c->contribution_recur_id == $recurringId) {
      CRM_Contribute_BAO_ContributionRecur::syncContribute($recurringId, $c->id);
    }
    $c->save();

    $regKey = self::getRegKey($recurringId);
    if (empty($regKey)) {
      $note = "refs #45587, missing preapproved regKey for recur $recurringId";
      CRM_Core_Payment_Mobile::addNote($note, $c);
      return ['status' => '', 'msg' => $note];
    }

    $mode = $firstContribution->is_test ? 'test' : 'live';
    $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($firstContribution->payment_processor_id, $mode);
    $currency = !empty($contributionRecur->currency) ? $contributionRecur->currency : $config->defaultCurrency;
    $amount = $currency === 'TWD' ? (int) $contributionRecur->amount : (float) $contributionRecur->amount;
    $productName = !empty($c->amount_level) ? $c->source . ' - ' . $c->amount_level : $c->source;

    $api = CRM_Core_Payment_LinePayAPI::create($paymentProcessor, 'recurring/payment');
    $response = $api->request([
      'regKey' => $regKey,
      'amount' => $amount,
      'currency' => $currency,
      'orderId' => (string) $c->id,
      'productName' => strip_tags((string) $productName),
    ]);

    if ($response && $response->returnCode === '0000') {
      if (!empty($response->info->transactionId)) {
        $c->trxn_id = $response->info->transactionId;
        $c->save();
      }
      CRM_Core_DAO::executeQuery("UPDATE civicrm_contribution_linepay SET contribution_recur_id = %1 WHERE trxn_id = %2", [
        1 => [$recurringId, 'Integer'],
        2 => [(string) $c->id, 'String'],
      ]);
    }

    self::doRecurTransaction($c, $api, $sendMail);

    $returnCode = $api->_response->returnCode ?? '';
    $returnMessage = $api->_response->returnMessage ?? '';

    // refs #45587, a voided regKey can never be reused: drop it and fail the
    // recurring so the cron stops retrying. Other failures (e.g. 1142
    // insufficient balance) keep the regKey and retry next cycle.
    if (self::isRegKeyVoidedCode($returnCode)) {
      self::voidRegKey($recurringId, $returnCode, $returnMessage);
    }
    // refs #45587, code 1193 means the preapproved regKey has already expired on
    // the LINE Pay side, so it can never charge again. Drop the key locally and
    // move the recurring to Expired (6).
    elseif ($returnCode === '1193') {
      CRM_Core_DAO::executeQuery("UPDATE civicrm_contribution_linepay SET reg_key = NULL WHERE contribution_recur_id = %1", [
        1 => [$recurringId, 'Positive'],
      ]);
      $note = ts("LINE Pay returned code 1193: the preapproved key has expired, so the recurring contribution is set to Expired.");
      self::setRecurStatus($recurringId, 6, $note);
    }

    return ['status' => $returnCode, 'msg' => $returnMessage];
  }

  /**
   * Record a preapproved charge result through the IPN flow.
   *
   * @param CRM_Contribute_DAO_Contribution $contribution the charged contribution
   * @param CRM_Core_Payment_LinePayAPI $api the API client holding the response
   * @param bool $sendMail whether to send the receipt email
   *
   * @return void
   */
  private static function doRecurTransaction($contribution, $api, $sendMail = TRUE) {
    $ipn = new CRM_Core_Payment_BaseIPN();
    $input = $objects = [];
    $input['component'] = 'contribute';
    $ids = CRM_Contribute_BAO_Contribution::buildIds($contribution->id, FALSE);
    if (!$ipn->validateData($input, $ids, $objects, FALSE)) {
      return;
    }
    $transaction = new CRM_Core_Transaction();
    if ($api->_success) {
      $input['payment_instrument_id'] = $objects['contribution']->payment_instrument_id;
      $input['amount'] = $objects['contribution']->total_amount;
      $objects['contribution']->receive_date = date('YmdHis');
      $ipn->completeTransaction($input, $ids, $objects, $transaction, NULL, $sendMail);
    }
    else {
      $error = '';
      $ipn->failed($objects, $transaction, $error);
      self::addResponseMessageToNote($contribution, $api);
    }
    $transaction->commit();
  }

  /**
   * Queue all due LINE Pay preapproved recurring contributions.
   *
   * refs #45587. Mirrors CRM_Core_Payment_TapPay::doExecuteAllRecur but scopes
   * to recurrings that already hold a preapproved regKey.
   *
   * @param int $time optional current timestamp
   *
   * @return string|void error message when the batch is skipped
   */
  public static function doExecuteAllRecur($time = NULL) {
    $seq = new CRM_Core_DAO_Sequence();
    $seq->name = self::QUEUE_NAME;
    if ($seq->find(TRUE)) {
      if ($seq->value && (CRM_REQUEST_TIME - $seq->timestamp) < 1800) {
        $error = "Last process is still executing. Interupt now.";
        CRM_Core_Error::debug_log_message($error, TRUE);
        return $error;
      }
      else {
        $error = "There are a overdue process in DB, delete it.";
        CRM_Core_Error::debug_log_message($error, TRUE);
        $seq->delete();
      }
    }
    $seq->value = date('YmdHis');
    $seq->timestamp = microtime(TRUE);
    $seq->insert();

    if (empty($time)) {
      $time = time();
    }
    $thisMonth = date('m', $time);
    $theMonthNextDay = date('m', $time + 86400);
    $today = date('j', $time);
    if ($thisMonth == $theMonthNextDay) {
      $cycleDayFilter = 'r.cycle_day = ' . $today . ' ';
    }
    else {
      $days = [];
      for ($i = $today; $i <= 31; $i++) {
        $days[] = $i;
      }
      $cycleDayFilter = 'r.cycle_day IN (' . CRM_Utils_Array::implode(',', $days) . ')';
    }
    $currentDate = date('Y-m-01 00:00:00', $time);
    $currentDay = date('Y-m-d', $time);

    $sql = "
SELECT
  r.id recur_id,
  r.last_execute_date last_execute_date,
  c.payment_processor_id payment_processor_id,
  c.is_test is_test
FROM
  civicrm_contribution_recur r
INNER JOIN
  civicrm_contribution c ON r.id = c.contribution_recur_id
INNER JOIN
  civicrm_payment_processor p ON c.payment_processor_id = p.id
INNER JOIN
  civicrm_contribution_linepay lp ON lp.contribution_recur_id = r.id AND lp.reg_key IS NOT NULL AND lp.reg_key != ''
WHERE
  $cycleDayFilter AND
  (SELECT MAX(created_date) FROM civicrm_contribution WHERE contribution_recur_id = r.id GROUP BY r.id) < '$currentDate'
AND r.contribution_status_id in (5,7)
AND r.frequency_unit = 'month'
AND p.payment_processor_type = 'Mobile'
AND (r.last_execute_date IS NULL OR r.last_execute_date < '$currentDay')
GROUP BY r.id
ORDER BY r.id
LIMIT 0, 100
";
    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      $currentDayTime = strtotime(date('Y-m-d', $time));
      $lastExecuteDayTime = strtotime(date('Y-m-d', strtotime($dao->last_execute_date)));
      if (!empty($dao->last_execute_date) && $currentDayTime <= $lastExecuteDayTime) {
        CRM_Core_Error::debug_log_message(ts("Last execute date of recur is over the date.") . " recur_id: " . $dao->recur_id);
        continue;
      }
      $command = 'drush neticrm-process-recurring --payment-processor=linepay --time=' . $time . ' --contribution-recur-id=' . $dao->recur_id . '&';
      popen($command, 'w');
      usleep(1000000);
    }

    $checkSeq = new CRM_Core_DAO_Sequence();
    unset($seq->timestamp);
    $seqArray = (array) $seq;
    $checkSeq->copyValues($seqArray);
    if ($checkSeq->find(TRUE)) {
      $checkSeq->delete();
    }
  }

  /**
   * Check and charge a single LINE Pay preapproved recurring contribution.
   *
   * refs #45587. Simplified from CRM_Core_Payment_TapPay::doCheckRecur: LINE Pay
   * has no card expiry / token status to verify, so only end_date and
   * installments gate the charge.
   *
   * @param int $recurId contribution recur ID
   * @param int $time optional current timestamp
   *
   * @return string synchronization result note
   */
  public static function doCheckRecur($recurId, $time = NULL) {
    CRM_Core_Error::debug_log_message("LinePay synchronize execute: " . $recurId);

    // refs #45587, only synchronize recurrings whose gateway is a LINE Pay
    // preapproved Mobile processor.
    if (!self::isLinePayRecur($recurId)) {
      $msg = "refs #45587, recur $recurId is not a LINE Pay preapproved recurring; skip synchronizing.";
      CRM_Core_Error::debug_log_message($msg);
      return $msg;
    }

    if (empty($time)) {
      $time = time();
    }
    CRM_Core_DAO::setFieldValue('CRM_Contribute_DAO_ContributionRecur', $recurId, 'last_execute_date', date('Y-m-d H:i:s'));

    $sql = "SELECT c.id contribution_id, r.id recur_id, r.contribution_status_id recur_status_id, r.end_date end_date, r.installments, r.frequency_unit, c.is_test FROM civicrm_contribution c INNER JOIN civicrm_contribution_recur r ON c.contribution_recur_id = r.id WHERE c.contribution_recur_id = %1 ORDER BY c.id ASC LIMIT 1";
    $dao = CRM_Core_DAO::executeQuery($sql, [1 => [$recurId, 'Positive']]);
    $dao->fetch();
    $resultNote = "Syncing recurring $recurId ";
    $recurStatus = (int) $dao->recur_status_id;

    // refs #45597, do not execute payment when pending / complete / failed / expired
    if (in_array($recurId, [1,2,4,6], TRUE)) {
      $resultNote .= ts("Recurring already in a skip status (%s); skipped execute payment.", [1 =>  CRM_Contribute_PseudoConstant::contributionStatus($recurStatus)]);
      CRM_Core_Error::debug_log_message($resultNote);
      return $resultNote;
    }

    // refs #45597, terminal statuses Cancelled (3) must never
    // charge again: discard the regKey and stop.
    if ($recurStatus === 3) {
      self::expireRegKey($recurId);
      $resultNote .= ts("Recurring already in a terminal status (%1); preapproved key discarded.", [1 => CRM_Contribute_PseudoConstant::contributionStatus($recurStatus)]);
      CRM_Core_Error::debug_log_message($resultNote);
      return $resultNote;
    }

    // refs #45597, LINE Pay voids a preapproved key once it has been unused for
    // over REGKEY_VALID_DAYS days.
    $lastChargeTime = self::lastChargeTime($recurId);
    $regKeyExpired = !empty($lastChargeTime) && ($time - $lastChargeTime) > (self::REGKEY_VALID_DAYS * 86400);

    // Paused (7): never charge. Auto-expire once the regKey window has lapsed,
    // otherwise just remind when the last charge happened.
    if ($recurStatus === 7) {
      if ($regKeyExpired) {
        // refs #45587, the 180-day window is a local heuristic; confirm with
        // LINE Pay before discarding a key that might still be valid.
        if (self::isRegKeyValid($recurId)) {
          $note = ts("Paused recurring exceeded the %1-day LINE Pay key window, but the preapproved key is still confirmed valid; keep it.", [1 => self::REGKEY_VALID_DAYS]);
          $resultNote .= "\n" . $note;
        }
        else {
          $note = ts("Paused recurring exceeded the %1-day LINE Pay key window; expiring.", [1 => self::REGKEY_VALID_DAYS]);
          self::expireRegKey($recurId);
          self::setRecurStatus($recurId, 6, $note);
          $resultNote .= "\n" . $note;
        }
      }
      else {
        $lastChargeNote = !empty($lastChargeTime) ? date('Y-m-d', $lastChargeTime) : ts('never');
        $resultNote .= "\n" . ts("Recurring is paused (last charge: %1); skip charging.", [1 => $lastChargeNote]);
      }
      CRM_Core_Error::debug_log_message($resultNote);
      return $resultNote;
    }

    // Active but the regKey window lapsed: expire the recurring (6) and discard.
    if ($regKeyExpired && $recurStatus === 5) {
      $note = ts("LINE Pay preapproved key unused for over %1 days. We will try execute one payment to see if it's really expired.", [1 => self::REGKEY_VALID_DAYS]);
      $resultNote .= "\n" . $note;
      CRM_Core_Error::debug_log_message($resultNote);
    }

    // only "In Progress" recurring (5) enter here
    $reason = '';
    $changeStatus = FALSE;
    $goPayment = FALSE;
    $donePayment = FALSE;

    $sqlContribution = "SELECT COUNT(*) FROM civicrm_contribution WHERE contribution_recur_id = %1 AND contribution_status_id = 1 AND is_test = %2";
    $paramsContribution = [
      1 => [$dao->recur_id, 'Positive'],
      2 => [$dao->is_test, 'Integer'],
    ];
    // Successful charges before this run; decides whether another charge is due.
    $successCountBefore = CRM_Core_DAO::singleValueQuery($sqlContribution, $paramsContribution);
    // Refreshed after a charge; defaults to the pre-charge count when nothing is charged.
    $successCount = $successCountBefore;

    if (!empty($dao->end_date)) {
      if ($time <= strtotime($dao->end_date)) {
        $goPayment = TRUE;
        $reason = 'by end_date not due ...';
      }
      else {
        $resultNote .= "Payment doesn't be executed cause the end_date was dued.";
      }
    }
    elseif (!empty($dao->installments)) {
      if ($successCountBefore < $dao->installments) {
        $goPayment = TRUE;
        $reason = 'by installments not full ...';
      }
      else {
        $resultNote .= "Payment doesn't be executed cause the installments was full.";
      }
    }
    else {
      $goPayment = TRUE;
      $reason = 'by no end_date and installments set ...';
    }

    if ($goPayment) {
      $resultNote .= $reason . ts("Finish synchronizing recurring.");
      $payResult = self::payByRegKey($dao->recur_id);
      $donePayment = TRUE;
      $successCount = CRM_Core_DAO::singleValueQuery($sqlContribution, $paramsContribution);

      // A voided regKey already failed the recurring inside payByRegKey; stop.
      if (self::isRegKeyVoidedCode($payResult['status'] ?? '')) {
        $resultNote .= "\n" . ts("Charge failed and the preapproved key was voided (code %1); recurring set to Failed.", [1 => $payResult['status']]);
        CRM_Core_Error::debug_log_message($resultNote);
        CRM_Core_Error::debug_log_message("LinePay synchronize finished: " . $recurId);
        return $resultNote;
      }

      // refs #45587, code 1193 means the preapproved regKey has expired;
      // payByRegKey already moved the recurring to Expired (6). Stop here.
      if (($payResult['status'] ?? '') === '1193') {
        $resultNote .= "\n" . ts("Charge failed because the preapproved key has expired (code 1193); recurring set to Expired.");
        CRM_Core_Error::debug_log_message($resultNote);
        CRM_Core_Error::debug_log_message("LinePay synchronize finished: " . $recurId);
        return $resultNote;
      }

    }

    if ($donePayment && $dao->frequency_unit == 'month' && !empty($dao->end_date) && date('Ym', $time) == date('Ym', strtotime($dao->end_date))) {
      $statusNote = ts("This is lastest contribution of this recurring (end date is %1).", [1 => date('Y-m-d', strtotime($dao->end_date))]);
      $resultNote .= "\n" . $statusNote;
      $changeStatus = TRUE;
    }
    elseif (!empty($dao->end_date) && $time > strtotime($dao->end_date)) {
      $statusNote = ts("End date is due.");
      $resultNote .= "\n" . $statusNote;
      $changeStatus = TRUE;
    }
    elseif (!empty($dao->installments) && $successCount >= $dao->installments) {
      $statusNote = ts("Installments is full.");
      $resultNote .= "\n" . $statusNote;
      $changeStatus = TRUE;
    }

    // Completion (installments full / end date reached) -> Completed (1), and
    // discard the regKey so it can never charge again (refs #45587).
    if ($changeStatus) {
      self::setRecurStatus($dao->recur_id, 1, $resultNote);
      $statusNoteTitle = ts("Change status to %1", [1 => CRM_Contribute_PseudoConstant::contributionStatus(1)]);
      $resultNote .= "\n" . $statusNoteTitle;
    }

    CRM_Core_Error::debug_log_message($resultNote);
    CRM_Core_Error::debug_log_message("LinePay synchronize finished: " . $recurId);
    return $resultNote;
  }

  /**
   * Fields editable on the Recurring contribution edit form for this recurring.
   *
   * refs #45587. Returns an empty array when the form should be fully locked:
   * - Completed (1) / Cancelled (3) / Failed (4) / Overdue-Expired (6): terminal,
   *   always locked.
   * - Pending (2): locked unless a preapproved regKey can still be found.
   * - In Progress (5) / Suspended-Paused (7): editable.
   *
   * @param int $recurId contribution recur ID
   * @param CRM_Core_Form $form the edit form, used to read the recurring ID
   *
   * @return array editable field names, or [] when the form is locked
   */
  public static function getEditableFields($recurId, $form) {
    $statusId = (int) CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionRecur', $recurId, 'contribution_status_id');
    if (in_array($statusId, self::LOCKED_RECUR_STATUSES, TRUE)) {
      return [];
    }
    if ($statusId === 2 && !self::hasRegKey($recurId)) {
      return [];
    }
    return self::EDITABLE_RECUR_FIELDS;
  }

  /**
   * Compute the LINE Pay preapproved key's 180-day expiry window for a recurring.
   *
   * refs #45587. Used to warn an admin who pauses (Suspended, status 7) a
   * recurring that the regKey will be auto-expired if no charge succeeds
   * before the returned expiry date.
   *
   * @param int $recurId contribution recur ID
   *
   * @return array{last_charge_date: string, expiry_date: string}|null NULL when undeterminable
   */
  public static function getRegKeyExpiryInfo($recurId) {
    $lastChargeTime = self::lastChargeTime($recurId);
    if (empty($lastChargeTime)) {
      return NULL;
    }
    return [
      'last_charge_date' => date('Y-m-d', $lastChargeTime),
      'expiry_date' => date('Y-m-d', $lastChargeTime + self::REGKEY_VALID_DAYS * 86400),
    ];
  }

  /**
   * Apply LINE Pay side-effects when the Recurring contribution edit form is saved.
   *
   * refs #45587. Unlike CRM_Core_Payment_SPGATEWAY::doUpdateRecur, LINE Pay has
   * no remote "alter recurring" API: edits to amount/cycle_day/installments/
   * dates are simply persisted by the form as-is. The only side effect needed
   * here is discarding the preapproved regKey when the recurring is moved to a
   * terminal status, so it can never be charged again. Status changes to
   * Failed/Expired and Pending-without-regKey are rejected defensively, even
   * though the form should already prevent selecting them.
   *
   * @param array $params changed fields, plus contribution_recur_id and trxn_id
   * @param bool $debug debug mode
   *
   * @return array ['is_error' => int, 'msg' => string]
   */
  public static function updateRecur($params, $debug = FALSE) {
    if ($debug) {
      CRM_Core_Error::debug('LinePay updateRecur $params', $params);
    }
    $recurId = $params['contribution_recur_id'];
    $result = ['is_error' => 0, 'msg' => ''];

    if (isset($params['contribution_status_id'])) {
      $newStatus = (int) $params['contribution_status_id'];
      if (in_array($newStatus, [4, 6], TRUE)) {
        $result['is_error'] = 1;
        $result['msg'] = ts('This status can only be set by the system.');
        return $result;
      }
      if ($newStatus === 2 && !self::hasRegKey($recurId)) {
        $result['is_error'] = 1;
        $result['msg'] = ts('This recurring contribution no longer has a usable LINE Pay preapproved key, so its status cannot be changed.');
        return $result;
      }
      if (in_array($newStatus, [3], TRUE)) {
        self::expireRegKey($recurId);
        $result['msg'] = ts('The LINE Pay preapproved key for this recurring contribution has been discarded; it will never be charged again.');
      }
    }
    return $result;
  }
}
