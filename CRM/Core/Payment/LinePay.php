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

    $api = $this->getAPI('request');
    $response = $api->request($requestParams);
    if ($response && $response->returnCode === '0000') {
      $transactionId = $response->info->transactionId;
      if (!empty($transactionId)) {
        $contribution = self::prepareContribution($contributionId);
        $contribution->trxn_id = $transactionId;
        $contribution->save();
      }
      CRM_Utils_System::redirect($response->info->paymentUrl->web);
    }
    else {
      self::addResponseMessageToNote($contributionId, $api);
      CRM_Core_Error::fatal($api->_response->returnMessage);
    }
  }

  /**
   * Static entry point for confirming a LINE Pay transaction.
   *
   * @param array $url_params URL parameters from router
   * @param array $get optional GET variables
   *
   * @return void
   */
  public static function confirm($url_params, $get = []) {
    if (empty($get)) {
      foreach ($_GET as $key => $value) {
        if ($key == 'q') {
          continue;
        }
        if ($key == 'qfKey') {
          $value = CRM_Utils_Type::escape($value, 'String', FALSE);
        }
        else {
          $value = CRM_Utils_Type::escape($value, 'Integer', FALSE);
        }
        $params[$key] = $value;
      }
    }
    else {
      $params = $get;
    }
    if (empty($params['ppid'])) {
      CRM_Core_Error::fatal(ts('Could not find payment processor meta information'));
    }
    $paymentProcessor = self::getPaymentProcessor($params['ppid']);
    $mode = !empty($paymentProcessor['is_test']) ? 'test' : 'live';
    $linePay = new self($mode, $paymentProcessor);
    $linePay->doConfirm($params);
  }

  /**
   * Handle the confirmation of a LINE Pay transaction.
   *
   * @param array $params confirmation parameters (including transactionId)
   *
   * @return void
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

    CRM_Utils_System::redirect($thankyou_url);
  }

  /**
   * Static entry point for querying LINE Pay transaction status.
   *
   * This is a pure sync operation, so it talks to the LINE Pay API directly
   * instead of going through the business object.
   *
   * @param array $url_params URL parameters from router
   * @param array $get parameters containing contribution ID
   *
   * @return CRM_Core_Error|void status bounce or error on failure
   */
  public static function query($url_params, $get = []) {
    if (empty($get)) {
      foreach ($_GET as $key => $value) {
        if ($key == 'q') {
          continue;
        }
        $get[$key] = $value;
      }
    }
    $contribution = new CRM_Contribute_DAO_Contribution();
    $contribution->id = $get['id'];
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

      $query = http_build_query($get);
      $redirect = CRM_Utils_System::url('civicrm/contact/view/contribution', $query);
      return CRM_Core_Error::statusBounce($result_note, $redirect);
    }
    else {
      CRM_Core_Error::fatal(ts('Wrong contribution ID in url query'));
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
      CRM_Core_Error::fatal(ts('Could not find payment processor meta information'));
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
  private static function prepareThankYouUrl($path, $qfKey, $failed = FALSE) {
    $query = "_qf_ThankYou_display=1&qfKey={$qfKey}";
    $query .= $failed ? '&payment_result_type=4' : '&payment_result_type=1';
    $url = CRM_Utils_System::url($path, $query, TRUE, NULL, FALSE);
    return $url;
  }
}
