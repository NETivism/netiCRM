<?php

class CRM_Core_Payment_TapPay extends CRM_Core_Payment {

  const QUEUE_NAME = 'tappay_batch_all_recur';

  protected $_mode = NULL;

  protected $_api = NULL;

  protected $_apiType = NULL;

  // Used for contribution recurring form ( /CRM/Contribute/Form/ContributionRecur.php ).
  public static $_editableFields = ['amount', 'installments', 'end_date', 'cycle_day', 'contribution_status_id', 'note_title', 'note_body'];

  public static $_hideFields = ['invoice_id', 'trxn_id'];

  public static $_cardType = [
    1 => 'VISA',
    2 => 'MasterCard',
    3 => 'JCB',
    4 => 'Union Pay',
    5 => 'AMEX',
  ];

  public static $_cardCategory = [
    -1 => 'Unknown',
    0 => 'Credit Card',
    1 => 'Debit Card',
    2 => 'Prepaid Card',
  ];

  public static $_allowRecurUnit = ['month'];

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   * @static
   */
  private static $_singleton = NULL;

  function __construct($mode, &$paymentProcessor, &$paymentForm, $apiType) {
    $this->_mode = $mode;
    $this->_paymentProcessor = $paymentProcessor;
    $this->_apiType = $apiType;
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
    $args = func_get_args();
    if (isset($args[3])) {
      $apiType = $args[3];
    }
    $processorName = $paymentProcessor['name'];
    if (self::$_singleton[$processorName] === NULL) {
      self::$_singleton[$processorName] = new CRM_Core_Payment_TapPay($mode, $paymentProcessor, $paymentForm, $apiType);
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

  static function getAdminFields($ppDAO, $form){
    $fields = [
      [
        'name' => 'user_name',
        'label' => $ppDAO->user_name_label,
      ],
      [
        'name' => 'password',
        'label' => $ppDAO->password_label,
      ],
      [
        'name' => 'signature',
        'label' => $ppDAO->signature_label,
      ],
      [
        'name' => 'subject',
        'label' => $ppDAO->subject_label,
      ],
      [
        'name' => 'url_site',
        'label' => ts('啟用3D驗證'),
        'msg' => ts(''),
      ],
    ];
    $nullObj = NULL;
    $ppid = CRM_Utils_Request::retrieve('id', 'Positive', $nullObj);
    if ($ppid) {
      $params = [
        1 => [$ppid, 'Positive'],
        2 => [0, 'Integer'],
      ];
      $paramsTest = [
        1 => [$ppid+1, 'Positive'],
        2 => [1, 'Integer'],
      ];
      $sql = 'SELECT count(id) FROM civicrm_contribution WHERE payment_processor_id = %1 AND is_test = %2';
      $isHavingContribution = CRM_Core_DAO::singleValueQuery($sql, $params);
      $isHavingContributionTest = CRM_Core_DAO::singleValueQuery($sql, $paramsTest);
      $smarty = CRM_Core_Smarty::singleton();
      $smarty->assign('having_contribution', $isHavingContribution);
      $smarty->assign('having_contribution_test', $isHavingContributionTest);
    }
    return $fields;
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

  function getPaymentFrame() {
    if (!empty($this->_paymentProcessor)) {
      $this->_paymentForm->add('hidden', 'prime', '');
      $this->_paymentForm->assign('button_name', $this->_paymentForm->getButtonName('next'));
      $this->_paymentForm->assign('back_button_name', $this->_paymentForm->getButtonName('back'));
      $this->_paymentForm->assign('payment_processor', $this->_paymentProcessor);
      $className = get_class($this->_paymentForm);
      $qfKey = $this->_paymentForm->get('qfKey');
      // needs payment processor keys
      // we needs these to process payByPrime
      $this->_paymentForm->assign('contribution_id', $payment['contributionID']);
      $this->_paymentForm->assign('class_name', $className);
      $this->_paymentForm->assign('qfKey', $qfKey);

      // get template and render some element
      require_once 'CRM/Core/Smarty/resources/String.php';
      civicrm_smarty_register_string_resource();
      $config = CRM_Core_Config::singleton();
      $tplFile = $config->templateDir[0].'CRM/Core/Page/Payment/TapPay.tpl';
      $tplContent = 'string:'.file_get_contents($tplFile);
      $smarty = CRM_Core_Smarty::singleton();
      $html = $smarty->fetch($tplContent);
      return $html;
    }
  }

  function doTransferCheckout(&$params, $component) {
    $currentPath = CRM_Utils_System::currentPath();
    $params['prime'] = CRM_Utils_Type::escape($_POST['prime'], 'String');
    $params['mode'] = $this->_mode;
    if (!empty($params['isPayByBindCard'])) {
      $paymentResult = self::payByBindCard($params);
    }
    else {
      $paymentResult = self::payByPrime($params);
    }
    if ($paymentResult['redirect']) {
      // 3D secure validation.
      CRM_Utils_System::redirect($paymentResult['redirect']);
    }
    elseif ($paymentResult['status'] == "0") {
      $thankyou = CRM_Utils_System::url($currentPath, '_qf_ThankYou_display=1&qfKey='.$params['qfKey'].'&payment_result_type=1');
    }
    else {
      $thankyou = CRM_Utils_System::url($currentPath, '_qf_ThankYou_display=1&qfKey='.$params['qfKey'].'&payment_result_type=4&payment_result_message='.$paymentResult['msg']);
    }
    CRM_Utils_System::redirect($thankyou);
  }

  function cancelRecuringMessage($recurID) {
    $text = '<p>'.ts("Please edit recurring and change status to 'Completed'.").'</p>';
    $js = '<script>cj(".ui-dialog-buttonset button").hide();</script>';
    return $text . $js;
  }

  public static function payByPrime($payment) {
    if ($payment && !empty($payment['payment_processor_id'])) {
      $trxn_id = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $payment['contributionID'], 'trxn_id');
      if(empty($trxn_id)){
        $trxn_id = self::getContributionTrxnID($payment['contributionID']);
        CRM_Core_DAO::setFieldValue('CRM_Contribute_DAO_Contribution', $payment['contributionID'], 'trxn_id', $trxn_id);
      }

      $contribution = $ids = [];
      $params = ['id' => $payment['contributionID']];
      CRM_Contribute_BAO_Contribution::getValues($params, $contribution, $ids);
      $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($payment['payment_processor_id'], $payment['mode']);
      $prime = $payment['prime'];
      $tappayParams = [
        'apiType' => 'pay_by_prime',
        'partnerKey' => $paymentProcessor['password'],
        'isTest' => $contribution['is_test'],
      ];
      $api = new CRM_Core_Payment_TapPayAPI($tappayParams);
      $details = !empty($contribution['amount_level']) ? $contribution['source'].'-'.$contribution['amount_level'] : $contribution['source'];
      if (empty($details)) {
        $details = (string) $contribution['total_amount'];
      }
      $data = [
        'prime' => $prime,
        'partner_key' => $paymentProcessor['password'],
        'merchant_id' => $paymentProcessor['user_name'],
        'amount' => $contribution['currency'] == 'TWD' ? (int)$contribution['total_amount'] : $contribution['total_amount'],
        'currency' => $contribution['currency'],
        'order_number' => $contribution['trxn_id'],
        'details' => mb_substr($details, 0, 98), // item name
        'cardholder'=> [
          'phone_number'=> '', #required #TODO
          'name' => '', #required but use empty
          'email' => '', #required but use empty
          'zip_code' => '',    //optional
          'address' => '',     //optional
          'national_id' => '', //optional
        ],
        'remember' => TRUE,
        'contribution_id' => $payment['contributionID'],
      ];
      // third domain secure
      $thankYouQuery = http_build_query([
        '_qf_ThankYou_display' => "1",
        'qfKey' => $payment['qfKey'],
      ], '', '&');
      $ids = CRM_Contribute_BAO_Contribution::buildIds($payment['contributionID']);
      $ipnQuery = CRM_Contribute_BAO_Contribution::makeNotifyUrl($ids, NULL, $return_query = TRUE);
      $goBackQuery = http_build_query([
        '_qf_ThankYou_display' => "1",
        'qfKey' => $payment['qfKey'],
        'payment_result_type' => '4',
      ], '', '&');
      CRM_Core_Error::debug_var('paymentProcessor', $paymentProcessor);
      if ($paymentProcessor['url_site']) {
        $data['three_domain_secure'] = TRUE;
        $data['result_url'] = [
          'frontend_redirect_url' => CRM_Utils_System::url(CRM_Utils_System::currentPath(), $thankYouQuery, TRUE, NULL, FALSE),
          'backend_notify_url' => CRM_Utils_System::url('tappay/ipn', $ipnQuery, TRUE, NULL, FALSE),
          'go_back_url' => CRM_Utils_System::url(CRM_Utils_System::currentPath(), $goBackQuery, TRUE, NULL, FALSE),
        ];
      }

      // Allow further manipulation of the arguments via custom hooks ..
      $mode = $paymentProcessor['is_test'] ? 'test' : 'live';
      $null = NULL;
      
      $paymentClass = self::singleton($mode, $paymentProcessor, $null);
      CRM_Utils_Hook::alterPaymentProcessorParams($paymentClass, $payment, $data);

      $result = $api->request($data);

      // If 3D Secure Validation is on, don't do transaction.
      if (!empty($result->transaction_method_details) && $result->transaction_method_details->transaction_method == 'THREE_DOMAIN_SECURE') {
        $isPass = TRUE;
        if (empty($result->payment_url)) {
          // validate url has no special character
          $url = CRM_Core_DAO::escapeString($result->payment_url);
          if (!$url == $result->payment_url) {
            $isPass = FALSE;
          }
        }
        if ($isPass) {
          $response = ['redirect' => $result->payment_url];
          return $response;
        }
      }
      self::doTransaction($result, $payment['contributionID']);

      // update token status after transaction completed
      if ($result->status === 0 && !empty($contribution['contribution_recur_id'])) {
        self::cardMetadata($payment['contributionID']);
      }

      $response = ['status' => $result->status, 'msg' => $result->msg];
      return $response;
    }
    return FALSE;
  }

  public static function payByToken($recurringId = NULL, $referContributionId = NULL, $sendMail = TRUE) {
    if(empty($recurringId)){
      $recurringId = CRM_Utils_Request::retrieve('crid', 'Positive', CRM_Core_DAO::$_nullObject, TRUE, $recurringId, 'REQUEST');
    }

    $contributionRecur = new CRM_Contribute_DAO_ContributionRecur();
    $contributionRecur->id = $recurringId;
    $contributionRecur->find(TRUE);

    // $contribution -> first contribution
    // $c -> current editable contribution
    // Find the contribution
    $config = CRM_Core_Config::singleton();
    if (!empty($config->recurringCopySetting) && $config->recurringCopySetting == 'latest') {
      $order = 'DESC';
    }
    else {
      $order = 'ASC';
    }
    $sql = "SELECT id FROM civicrm_contribution WHERE contribution_recur_id = %1 ORDER BY created_date $order";
    $params = [1 => [$recurringId, 'Positive']];
    $firstContributionId = CRM_Core_DAO::singleValueQuery($sql, $params);

    // Find FirstContribution
    $firstContribution = new CRM_Contribute_DAO_Contribution();
    $firstContribution->id = $firstContributionId;
    $firstContribution->find(TRUE);

    if (empty($referContributionId)) {
      // Clone Contribution
      // trxn_id will update after copy contribution.
      $hash = hash('sha256', $firstContributionId);
      $c = CRM_Core_Payment_BaseIPN::copyContribution($firstContribution, $recurringId, $hash);
      $c->total_amount = $contributionRecur->amount;
    }
    else {
      $c = new CRM_Contribute_DAO_Contribution();
      $c->id = $referContributionId;
      if (!$c->find(TRUE)) {
        CRM_Core_Error::fatal(ts('Could not find the contribution.'));
      }
    }

    // Sync Recurring Custom fields.
    if ($c->contribution_recur_id == $recurringId) {
      CRM_Contribute_BAO_ContributionRecur::syncContribute($recurringId, $c->id);
    }

    // Update new trxn_id
    $c->trxn_id = self::getContributionTrxnID($c->id);
    $c->save();


    $ppid = $firstContribution->payment_processor_id;
    $mode = $firstContribution->is_test ? 'test' : 'live';
    $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($ppid, $mode);

    if ($paymentProcessor) {
      //Prepare tappay api
      $contribution = $ids = [];
      $params = ['id' => $c->id];
      CRM_Contribute_BAO_Contribution::getValues($params, $contribution, $ids);
      list($sortName, $email) = CRM_Contact_BAO_Contact::getContactDetails($contribution['contact_id']);
      $tappayParams = [
        'apiType' => 'pay_by_token',
        'partnerKey' => $paymentProcessor['password'],
        'isTest' => $firstContribution->is_test,
      ];
      $api = new CRM_Core_Payment_TapPayAPI($tappayParams);

      // Prepare tappay api post data
      $details = !empty($contribution['amount_level']) ? $contribution['source'].'-'.$contribution['amount_level'] : $contribution['source'];
      $tappayData = new CRM_Contribute_DAO_TapPay();
      $tappayData->contribution_recur_id = $recurringId;
      $tappayData->find(TRUE);
      if (!empty($tappayData->card_key) && !empty($tappayData->card_token)) {
        if ($contributionRecur->currency == 'TWD') {
          $amount = (int)$contributionRecur->amount;
        }
        else {
          $amount = (float)$contributionRecur->amount;
        }
        if (empty($details)) {
          $details = (string) $amount;
        }
        $data = [
          'card_key' => $tappayData->card_key,
          'card_token' => $tappayData->card_token,
          'partner_key' => $paymentProcessor['password'],
          // #39372, #42445: special case for 3d verify, change merchant id when match rule
          'merchant_id' => preg_match('/5808001$/', $paymentProcessor['user_name']) ? str_replace('5808001', '5808002', $paymentProcessor['user_name']) : $paymentProcessor['user_name'],
          'amount' => $amount,
          'currency' => $contributionRecur->currency,
          'order_number' => $contribution['trxn_id'],
          'details' => mb_substr($details, 0, 98), // item name
        ];
      }
      else {
        CRM_Core_Error::fatal(ts('Missing required fields').': card_key, card_token');
      }

      // Allow further manipulation of the arguments via custom hooks ..
      $null = NULL;
      $paymentClass = self::singleton($mode, $paymentProcessor, $null);
      CRM_Utils_Hook::alterPaymentProcessorParams($paymentClass, $payment, $data);

      // Send tappay pay_by_token post
      $result = $api->request($data);

      // Validate the result.
      self::doTransaction($result, $c->id, $sendMail);

      $response = ['status' => $result->status, 'msg' => $result->msg];
    }
    return $response;
  }

  /**
   * @param $paymentProcessorId Payment Processor ID
   * @param $contribId Which contribution be updated
   * @param $tokenParams Array, must contain 'card_toke', 'card_key'
   * @param $sendMail Boolean, Send mail after finished transaction or not.
   * return array('status' => $result->status, 'msg' => $result->msg);
   */
  public static function payByTokenForNonRecur($paymentProcessorId, $contribId, $tokenParams, $sendMail = FALSE) {
    // Check required parameters
    // Check token.
    $requiredTokenParams = ['card_key', 'card_token'];
    foreach($requiredTokenParams as $paramsKey) {
      if (empty($tokenParams[$paramsKey])) {
        CRM_Core_Error::fatal(ts('Missing required field: %1', [1 => $paramsKey]));
      }
    }

    if (empty($contribId)) {
      CRM_Core_Error::fatal(ts('Missing required field: %1', [1 => 'contribution ID']));
    }
    if (empty($paymentProcessorId)) {
      CRM_Core_Error::fatal(ts('Missing required field: %1', [1 => '$paymentProcessorId']));
    }

    $isTest = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $contribId, 'is_test');
    $mode = $isTest?'test':'live';
    $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($paymentProcessorId, $mode);
    if (empty($paymentProcessor)) {
      CRM_Core_Error::fatal(ts('Missing input parameters').':getPayment');
    }

    $config = CRM_Core_Config::singleton();
    $debug = $config->debug;

    $contribution = $ids = [];
    $params = ['id' => $contribId];
    CRM_Contribute_BAO_Contribution::getValues($params, $contribution, $ids);
    $tappayParams = [
      'apiType' => 'pay_by_token',
      'partnerKey' => $paymentProcessor['password'],
      'isTest' => $isTest,
    ];
    $api = new CRM_Core_Payment_TapPayAPI($tappayParams);

    // Prepare tappay api post data
    $details = !empty($contribution['amount_level']) ? $contribution['source'].'-'.$contribution['amount_level'] : $contribution['source'];
    $tappayData = new CRM_Contribute_DAO_TapPay();
    $tappayData->contribution_id =$contribId;
    $tappayData->card_key = $tokenParams['card_key'];
    $tappayData->card_token = $tokenParams['card_token'];
    $tappayData->save();
    if ($contribution['currency'] == 'TWD') {
      $amount = (int)$contribution['total_amount'];
    }
    else {
      $amount = (float)$contribution['total_amount'];
    }
    $data = [
      'card_key' => $tokenParams['card_key'],
      'card_token' => $tokenParams['card_token'],
      'partner_key' => $paymentProcessor['password'],
      'merchant_id' => $paymentProcessor['user_name'],
      'amount' => $amount,
      'currency' => $contribution['currency'],
      'order_number' => $contribution['trxn_id'],
      'details' => mb_substr($details, 0, 98), // item name
    ];

    // Allow further manipulation of the arguments via custom hooks ..
    $null = NULL;
    $paymentClass = self::singleton($mode, $paymentProcessor, $null);
    CRM_Utils_Hook::alterPaymentProcessorParams($paymentClass, $payment, $data);
    if ($debug) {
      CRM_Core_Error::debug('TapPay::payByTokenForNonRecur $data', $data);
    }

    // Send tappay pay_by_token post
    $result = $api->request($data);

    if ($debug) {
      CRM_Core_Error::debug('TapPay::payByTokenForNonRecur $result', $result);
    }

    // Validate the result.
    self::doTransaction($result, $contribId, $sendMail);

    $response = ['status' => $result->status, 'msg' => $result->msg];
    if ($debug) {
      CRM_Contribute_BAO_Contribution::getValues($params, $resultContribution, $ids);
      $response['result'] = $result;
      $response['data'] = $data;
      $response['originContribution'] = $contribution;
      $response['resultContribution'] = $resultContribution;
    }
    return $response;
  }

  public static function payByBindCard($payment, $isSendMail = FALSE) {
    if ($payment && !empty($payment['payment_processor_id'])) {
      $trxn_id = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $payment['contributionID'], 'trxn_id');
      $recurringId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $payment['contributionID'], 'contribution_recur_id');
      if(empty($trxn_id)){
        $rand = base_convert(strval(rand(16, 255)), 10, 16);
        $recurringId = 
        $trxn_id = 'b_'.$recurringId.'_'.$payment['contributionID'].'_'.$rand;
        CRM_Core_DAO::setFieldValue('CRM_Contribute_DAO_Contribution', $payment['contributionID'], 'trxn_id', $trxn_id);
      }

      CRM_Core_Error::debug_var('payment', $payment);
      $contribution = $ids = [];
      $params = ['id' => $payment['contributionID']];
      CRM_Contribute_BAO_Contribution::getValues($params, $contribution, $ids);
      $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($payment['payment_processor_id'], $payment['mode']);
      $prime = $payment['prime'];
      $tappayParams = [
        'apiType' => 'bind_card',
        'partnerKey' => $paymentProcessor['password'],
        'isTest' => $contribution['is_test'],
      ];
      $api = new CRM_Core_Payment_TapPayAPI($tappayParams);
      $details = !empty($contribution['amount_level']) ? $contribution['source'].'-'.$contribution['amount_level'] : $contribution['source'];
      $data = [
        'prime' => $prime,
        'partner_key' => $paymentProcessor['password'],
        'merchant_id' => $paymentProcessor['user_name'],
        'currency' => $contribution['currency'],
        'cardholder'=> [
          'phone_number'=> '', #required #TODO
          'name' => '', #required but use empty
          'email' => '', #required but use empty
          'zip_code' => '',    //optional
          'address' => '',     //optional
          'national_id' => '', //optional
        ],
        'contribution_id' => $payment['contributionID'],
      ];

      CRM_Core_Error::debug_var('data', $data);
      // Allow further manipulation of the arguments via custom hooks ..
      $mode = $paymentProcessor['is_test'] ? 'test' : 'live';
      $paymentClass = self::singleton($mode, $paymentProcessor, CRM_Core_DAO::$_nullObject);
      CRM_Utils_Hook::alterPaymentProcessorParams($paymentClass, $payment, $data);

      $result = $api->request($data);
      CRM_Core_Error::debug_var('result', $result);
      self::doTransaction($result, $payment['contributionID'], $isSendMail);

      CRM_Contribute_BAO_Contribution::getValues($params, $contribution, $ids);
      if ($contribution['contribution_status_id'] == 1) {
        $contribution['contribution_status_id'] = 3;
        $contribution['cancel_date'] = date('Y-m-d H:i:s');
        $contribution['total_amount'] = 0;
        $contribution['cancel_reason'] = ts('This record is only used to make authorization.');
        $contributionDAO = new CRM_Contribute_DAO_Contribution();
        $contributionDAO->copyValues($contribution);
        $contributionDAO->save();
      }

      // update token status after transaction completed
      if ($result->status === 0 && !empty($contribution['contribution_recur_id'])) {
        self::cardMetadata($payment['contributionID']); 
      }

      $response = ['status' => $result->status, 'msg' => $result->msg];
      return $response;
    }
    return FALSE;
  }

  public static function cardMetadata($contributionId, $data = NULL) {
    if (empty($contributionId))  {
      return FALSE;
    }
    $tappayData = new CRM_Contribute_DAO_TapPay();
    $tappayData->contribution_id = $contributionId;
    if ($tappayData->find(TRUE)) {
      // 1. check card_token and card_key
      // 2. get payment processor infomation (partner key)
      if (!empty($tappayData->card_token) && !empty($tappayData->card_key)) {
        $contribution = new CRM_Contribute_DAO_Contribution();
        $contribution->id = $contributionId;
        if($contribution->find(TRUE)) {
          $ppid = $contribution->payment_processor_id;
          $mode = $contribution->is_test ? 'test' : 'live';
          if ($ppid) {
            $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($ppid, $mode);
            $tappayParams = [
              'apiType' => 'card_metadata',
              'partnerKey' => $paymentProcessor['password'],
              'contribution_id' => $contributionId,
              'isTest' => $contribution->is_test,
            ];
            $api = new CRM_Core_Payment_TapPayAPI($tappayParams);
            if (empty($data)) {
              $result = $api->request([
                'partner_key' => $paymentProcessor['password'],
                'card_key' => $tappayData->card_key,
                'card_token' => $tappayData->card_token,
              ]);
            }
            else {
              $result = $data;
            }

            // only set auto renew when contribution has recurring
            if ($result->status == 0 && $contribution->contribution_recur_id) {
              // set card status.
              $cardStatus = $result->card_info->token_status;
              $tappayData->token_status = $cardStatus;
              $tappayData->save();

              // set auto_renew to contribution recur
              if (!empty($cardStatus) && ($cardStatus == 'ACTIVE' || $cardStatus == 'SUSPENDED')) {
                CRM_Core_DAO::setFieldValue('CRM_Contribute_DAO_ContributionRecur', $contribution->contribution_recur_id, 'auto_renew', 1);
              }
              else {
                CRM_Core_DAO::setFieldValue('CRM_Contribute_DAO_ContributionRecur', $contribution->contribution_recur_id, 'auto_renew', 0);
              }
            }
            return $result;
          }
        }
      } 
    }
    return FALSE;
  }

  /**
   * Make Transaction for certainly contribution.
   * 
   * @param Object $result The result object.
   * @param Integer $contributionId The ID of contribution.
   * @param Boolean $sendMail If TRUE, send mail for contact after finished.
   * @return Boolean Is success or not.
   */
  public static function doTransaction($result, $contributionId = NULL, $sendMail = TRUE) {
    $input = $ids = $objects = [];

    // prepare ids
    if (empty($contributionId)) {
      if (!empty($result['order_number'])) {
        $contributionId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $result->order_number, 'id', 'trxn_id');
      }
    }
    if (empty($contributionId)) {
      return FALSE;
    }
    $ids = CRM_Contribute_BAO_Contribution::buildIds($contributionId, FALSE);

    // prepare input
    $input = (array)$result;
    if(!empty($ids['event'])){
      $input['component'] = 'event';
    }
    else{
      $input['component'] = 'contribute';
    }
    /* TODO: remove this because this should be done in TapPayAPI
    if (!empty($input['currency']) && $input['currency'] != 'TWD') {
      $input['amount'] = $input['amount'] / 100;
    }
    */

    // ipn transact
    $ipn = new CRM_Core_Payment_BaseIPN();

    // First use ipn validate
    $validate_result = $ipn->validateData($input, $ids, $objects, FALSE);
    if (!$validate_result) {
      return FALSE;
    }
    else {
      $pass = TRUE;
      $contribution = $objects['contribution'];

      // check trxn_id when pay_by_prime
      // result->order_id is only used in payByBindCard
      if ( empty($result->order_id) && !empty($result->card_secret) && !strstr($contribution->trxn_id, $result->order_number)) {
        $msgText = ts("Failuare: OrderNumber values doesn't match between database and IPN request.").$contribution->trxn_id.": ".$result->order_number."\n";
        CRM_Core_Error::debug_log_message($msgText);
        $note .= $msgText;
        $pass = FALSE;
      }

      if (!empty($objects['contributionRecur'])) {
        $amount = round($objects['contributionRecur']->amount);
      }
      else {
        $amount = round($contribution->total_amount);
      }

      // check amount
      if (!empty($result->amount) && $amount != $result->amount ) {
        $msgText = ts("Failuare: Amount values dont match between database and IPN request. Trxn_id is %1, Data from payment : %2, Data in CRM : %3", [1 => $contribution->trxn_id, 2 => $result->amount, 3 => $amount])."\n";
        CRM_Core_Error::debug_log_message($msgText);
        $note .= $msgText;
        $pass = FALSE;
      }

      $note .= ts("Response Code").': '.$result->status."\n".ts("Response Message").': '.$result->msg."\n";

      // recurring validation

      $transaction = new CRM_Core_Transaction();

      if (isset($result->status)) {
        $status = $result->status;
      }
      else if (!empty($result->record_status)) {
        // recordAPI use record_status
        $status = $result->record_status;
      }
      if ($pass && in_array($status, ["0", "1"])) {
        $input['payment_instrument_id'] = $objects['contribution']->payment_instrument_id;
        $input['amount'] = $objects['contribution']->total_amount;
        $receiveTime = empty($result->transaction_time_millis) ? time() : ($result->transaction_time_millis / 1000);
        $objects['contribution']->receive_date = date('YmdHis', $receiveTime);
        $transaction_result = $ipn->completeTransaction($input, $ids, $objects, $transaction, NULL, $sendMail);
        if (!empty($ids['contributionRecur'])) {
          $sql = "SELECT count(*) FROM civicrm_contribution WHERE contribution_recur_id = %1";
          $params = [ 1 => [$ids['contributionRecur'], 'Positive']];
          $recurTimes = CRM_Core_DAO::singleValueQuery($sql, $params);
          if ($recurTimes == 1) {
            $recur_params = [
              'id' => $ids['contributionRecur'],
              'contribution_status_id' => 5,
            ];
            $null = [];
            CRM_Contribute_BAO_ContributionRecur::add($recur_params, $null);
          }

        }
      }
      else{
        // Failed
        $ipn->failed($objects, $transaction, $note);
      }
      self::addNote($note, $objects['contribution']);
    }

  }


  public static function doExecuteAllRecur($time = NULL) {
    // Check sequence;
    $seq = new CRM_Core_DAO_Sequence();
    $seq->name = self::QUEUE_NAME;

    if ($seq->find(TRUE)) {
      if ( $seq->value && (CRM_REQUEST_TIME - $seq->timestamp) < 1800) {
        // last process is executing.
        $error = "Last process is still executing. Interupt now.";
        CRM_Core_Error::debug_log_message($error, TRUE);
        return $error;
      }
      else {
        // no last process or last process is overdue.
        // delete last sequence if it exist
        $error = "There are a overdue process in DB, delete it.";
        CRM_Core_Error::debug_log_message($error, TRUE);
        $seq->delete();
      }
    }
    // insert new sequence
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
      $cycleDayFilter = 'r.cycle_day = '.$today.' ';
    }
    else {
      for($i = $today; $i <= 31 ; $i++) {
        $days[] = $i;
      }
      $cycleDayFilter = 'r.cycle_day IN ('.CRM_Utils_Array::implode(',', $days).')';
    }

    $currentDate = date('Y-m-01 00:00:00', $time);

    // #25443, only trigger when current month doesn't have any contribution yet
    $sql = "
SELECT
  r.id recur_id,
  r.last_execute_date last_execute_date,
  c.payment_processor_id payment_processor_id,
  c.is_test is_test,
  (SELECT MAX(created_date) FROM civicrm_contribution WHERE contribution_recur_id = r.id GROUP BY r.id) AS last_created_date,
  '$currentDate' as current_month_start
FROM
  civicrm_contribution_recur r
INNER JOIN
  civicrm_contribution c
ON
  r.id = c.contribution_recur_id
INNER JOIN
  civicrm_payment_processor p
ON
  c.payment_processor_id = p.id
WHERE
  $cycleDayFilter AND
  (SELECT MAX(created_date) FROM civicrm_contribution WHERE contribution_recur_id = r.id GROUP BY r.id) < '$currentDate'
AND r.contribution_status_id = 5
AND r.frequency_unit = 'month'
AND p.payment_processor_type = 'TapPay'
GROUP BY r.id
ORDER BY r.id
LIMIT 0, 100
";
    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      // Check payment processor
      $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($dao->payment_processor_id, $dao->is_test ? 'test': 'live');
      if (strtolower($paymentProcessor['payment_processor_type']) != 'tappay') {
        CRM_Core_Error::debug_log_message(ts("Payment processor of recur is not %1.", [1 => 'TapPay']));
        continue;
      }

      // Check last execute date.
      $currentDayTime = strtotime(date('Y-m-d', $time));
      $lastExecuteDayTime = strtotime(date('Y-m-d', strtotime($dao->last_execute_date)));
      if (!empty($dao->last_execute_date) && $currentDayTime <= $lastExecuteDayTime) {
        CRM_Core_Error::debug_log_message(ts("Last execute date of recur is over the date."));
        continue;
      }

      $command = 'drush neticrm-process-recurring --payment-processor=tappay --time='.$time.' --contribution-recur-id='.$dao->recur_id.'&';
      popen($command, 'w');
      // wait for 1 second.
      usleep(1000000);
    }

    // Delete the sequence data of this process.
    $checkSeq = new CRM_Core_DAO_Sequence();
    unset($seq->timestamp);
    $seqArray = (array) $seq;
    $checkSeq->copyValues($seqArray);
    if ($checkSeq->find(TRUE)) {
      $checkSeq->delete();
    }
  }

  public static function doCheckRecur($recurId, $time = NULL) {
    CRM_Core_Error::debug_log_message("TapPay synchronize execute: ".$recurId);
    if (empty($time)) {
      $time = time();
    }
    // Update last_execute_date
    CRM_Core_DAO::setFieldValue('CRM_Contribute_DAO_ContributionRecur', $recurId, 'last_execute_date', date('Y-m-d H:i:s'));
    // Get same cycle_day recur.
    $sql = "SELECT c.id contribution_id, r.id recur_id, r.contribution_status_id recur_status_id, r.end_date end_date, r.installments, r.frequency_unit, c.is_test FROM civicrm_contribution c INNER JOIN civicrm_contribution_recur r ON c.contribution_recur_id = r.id WHERE c.contribution_recur_id = %1 ORDER BY c.id ASC LIMIT 1";
    $params = [
      1 => [$recurId, 'Positive'],
    ];
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    $dao->fetch();
    $resultNote = "Syncing recurring $recurId ";
    $changeStatus = FALSE;
    $goPayment = $donePayment = FALSE;
    $sqlContribution = "SELECT COUNT(*) FROM civicrm_contribution WHERE contribution_recur_id = %1 AND contribution_status_id = 1 AND is_test = %2";
    $paramsContribution = [
      1 => [$dao->recur_id, 'Positive'],
      2 => [$dao->is_test, 'Integer'],
    ];
    $successCount = CRM_Core_DAO::singleValueQuery($sqlContribution, $paramsContribution);

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
      if ($successCount < $dao->installments) {
        $goPayment = TRUE;
        $reason = 'by installments not full ...';
      }
      else {
        $resultNote .= "Payment doesn't be executed cause the installments was full.";
      }
    }
    else {
      // Obviously, the condition is empty($dao->installments) && empty($dao->end_date)
      $goPayment = TRUE;
      $reason = 'by no end_date and installments set ...';
    }

    $tappay = new CRM_Contribute_DAO_TapPay();
    $tappay->contribution_recur_id = $recurId;
    $tappay->orderBy("expiry_date DESC");
    $tappay->find(TRUE);
    $expiry_date = $tappay->expiry_date;
    if ($goPayment) {
      // Check if Credit card over date.
      if ($time <= strtotime($expiry_date)) {
        $resultNote .= $reason;
        $resultNote .= ts("Finish synchronizing recurring.");
        self::payByToken($dao->recur_id);
        $donePayment = TRUE;
        // Count again for new contribution.
        $successCount = CRM_Core_DAO::singleValueQuery($sqlContribution, $paramsContribution);
      }
      else {
        $resultNote .= $reason;
        $resultNote .= ', but card expiry date due.';
      }
    }

    // check recurring status change and reason
    // no else for make sure every rule checked
    // and get latest tappay check
    $tappay->free();
    $tappay = new CRM_Contribute_DAO_TapPay();
    $tappay->contribution_recur_id = $recurId;
    $tappay->orderBy("expiry_date DESC");
    $tappay->find(TRUE);
    $new_expiry_date = $tappay->expiry_date;
    if ($donePayment && $dao->frequency_unit == 'month' && !empty($dao->end_date) && date('Ym', $time) == date('Ym', strtotime($dao->end_date))) {
      $statusNote = ts("This is lastest contribution of this recurring (end date is %1).", [1 => date('Y-m-d', strtotime($dao->end_date))]);
      $resultNote .= "\n" . $statusNote;
      $changeStatus = TRUE;
    }
    elseif ($donePayment && $dao->frequency_unit == 'month' && !empty($new_expiry_date) && date('Ym', $time) == date('Ym', strtotime($new_expiry_date))) {
      $statusNote = ts("This is lastest contribution of this recurring (expiry date is %1).", [1 => date('Y/m',strtotime($new_expiry_date))]);
      $resultNote .= "\n" . $statusNote;
      $changeStatus = TRUE;
    }
    elseif (!empty($dao->end_date) && $time > strtotime($dao->end_date)) {
      $statusNote = ts("End date is due.");
      $resultNote .= "\n".$statusNote;
      $changeStatus = TRUE;
    }
    elseif (!empty($dao->installments) && $successCount >= $dao->installments) {
      $statusNote = ts("Installments is full.");
      $resultNote .= "\n".$statusNote;
      $changeStatus = TRUE;
    }
    elseif (!empty($new_expiry_date) && $time > strtotime($new_expiry_date)) {
      $statusNote = ts("Card expiry date is due.");
      $resultNote .= "\n".$statusNote;
      $changeStatus = TRUE;
    }

    if ( $changeStatus ) {
      $statusNoteTitle = ts("Change status to %1", [1 => CRM_Contribute_PseudoConstant::contributionStatus(1)]);
      $statusNote .= ' '.ts("Auto renews status");
      $resultNote .= "\n".$statusNoteTitle;
      $recurParams = [];
      $recurParams['id'] = $dao->recur_id;
      $recurParams['contribution_status_id'] = 1;
      $recurParams['message'] = $resultNote;
      CRM_Contribute_BAO_ContributionRecur::add($recurParams, CRM_Core_DAO::$_nullObject);
      CRM_Contribute_BAO_ContributionRecur::addNote($dao->recur_id, $statusNoteTitle, $statusNote);
    }

    CRM_Core_Error::debug_log_message($resultNote);
    CRM_Core_Error::debug_log_message("TapPay synchronize finished: ".$recurId);
    return $resultNote;
  }

  public static function queryRecord($url_params, $get = []) {
    // apply $_GET to $get , and filted params 'q'
    if(empty($get)){
      foreach ($_GET as $key => $value) {
        if($key == 'q')continue;
        $get[$key] = $value;
      }
    }

    // retrieve contribution_id from $_GET
    if(empty($contributionId)){
      $contributionId = CRM_Utils_Request::retrieve('id', 'Positive', CRM_Core_DAO::$_nullObject, TRUE, NULL, 'REQUEST');
    }

    $resultNote = self::doSyncRecord($contributionId);
    
    // redirect to contribution view page
    $query = http_build_query($get);
    $redirect = CRM_Utils_System::url('civicrm/contact/view/contribution', $query);
     return CRM_Core_Error::statusBounce($resultNote, $redirect);
  }

  public static function doSyncRecord($contributionId, $data = NULL) {
    // retrieve contribution object
    $contribution = new CRM_Contribute_DAO_Contribution();
    $contribution->id = $contributionId;
    $contribution->find(TRUE);

    // retrieve tappay data object
    $tappay = new CRM_Contribute_DAO_TapPay();
    $tappay->contribution_id = $contributionId;
    $tappay->find(TRUE);

    // retrieve payment processor object
    $ppid = $contribution->payment_processor_id;
    $mode = $contribution->is_test ? 'tset' : 'live';
    $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($ppid, $mode);

    // setup tappay api
    $tappayParams = [
      'apiType' => 'record',
      'partnerKey' => $paymentProcessor['password'],
      'isTest' => $contribution->is_test,
    ];
    $api = new CRM_Core_Payment_TapPayAPI($tappayParams);

    // retrieve record data
    if (empty($data)) {
      $params = [
        'contribution_id' => $contributionId,
        'partner_key' => $paymentProcessor['password'],
        'filters' => [
          'rec_trade_id' => $tappay->rec_trade_id,
        ],
      ];

      // Allow further manipulation of the arguments via custom hooks ..
      $null = NULL;
      $mode = $paymentProcessor['is_test'] ? 'test' : 'live';
      $paymentClass = self::singleton($mode, $paymentProcessor, $null);
      CRM_Utils_Hook::alterPaymentProcessorParams($paymentClass, CRM_Core_DAO::$_nullObject, $params);

      $result = $api->request($params);
      $record = !empty($result->trade_records[0]) ? $result->trade_records[0] : NULL;
    }
    else {
      $record = $data;
    }

    // check there are record in return list
    if(!empty($record)){

      // The status means refund
      if($record->record_status == 3) {

        // Call trade history api to get refund date.
        $tappayParams['apiType'] = 'trade_history';

        if (empty($data)) {
          $api_history = new CRM_Core_Payment_TapPayAPI($tappayParams);
          $params = [
            'contribution_id' => $contributionId,
            'partner_key' => $paymentProcessor['password'],
            'rec_trade_id' => $tappay->rec_trade_id,
          ];

          // Allow further manipulation of the arguments via custom hooks ..
          $null = NULL;
          $mode = $paymentProcessor['is_test'] ? 'test' : 'live';
          $paymentClass = self::singleton($mode, $paymentProcessor, $null);
          CRM_Utils_Hook::alterPaymentProcessorParams($paymentClass, CRM_Core_DAO::$_nullObject, $params);

          $result = $api_history->request($params);
          // Get the refund type history from history list.
          foreach ($result->trade_history as $history) {
            if($history->action == 3){
              break;
            }
          }
          $record->refund_date = $history->millis;
        }
      }

      $resultNote .= "\n".ts('Synchronizing to Tappay server success.');

      // Sync contribution status in CRM
      if (in_array($record->record_status, ["1", "0"]) && $contribution->contribution_status_id != 1) {
        self::doTransaction($record, $contributionId);
      }
      else if($record->record_status == 3 && $contribution->contribution_status_id != 3) {
        // record original cancel_date, status_id data.
        $origin_cancel_date = $contribution->cancel_date;
        $origin_cancel_date = date('YmdHis', strtotime($origin_cancel_date));
        $origin_status_id = $contribution->contribution_status_id;

        // check data
        $pass = TRUE;
        if($record->order_number != $contribution->trxn_id) {
          // order number is not correct.
          $msgText = ts("Failuare: OrderNumber values doesn't match between database and IPN request.").$contribution->trxn_id.": ".$result->order_number."\n";
          $resultNote .= $msgText;
          $pass = FALSE;
        }

        // check refund
        if($record->refunded_amount == $contribution->total_amount && $pass) {
          // find refund, check original status
          $cancelDate = date('Y-m-d H:i:s', $record->refund_date / 1000);
          $contribution->cancel_date = $cancelDate;
          $contribution->contribution_status_id = 3;
          $contribution->save();
          $resultNote .= "\n".ts('The contribution has been canceled.');
        }
      }
      else if ($record->record_status == 2) {

        // record original cancel_date, status_id data.
        $origin_cancel_date = $contribution->cancel_date;
        $origin_cancel_date = date('YmdHis', strtotime($origin_cancel_date));
        $origin_status_id = $contribution->contribution_status_id;

        // check data
        $pass = TRUE;
        if($record->order_number != $contribution->trxn_id) {
          // order number is not correct.
          $msgText = ts("Failuare: OrderNumber values doesn't match between database and IPN request.").$contribution->trxn_id.": ".$result->order_number."\n";
          $resultNote .= $msgText;
          $pass = FALSE;
        }

        // check refund
        if($record->amount != $contribution->total_amount && $pass) {
          // find refund, check original status
          $contribution->total_amount = $record->amount;
          $contribution->contribution_status_id = 1;
          $contribution->save();
          $resultNote .= "\n".ts('The transaction has already been refunded.')." {$record->refund_amount}";
        }
        else {
          $resultNote .= "\n".ts('There are no any change.');
        }

      }
      else{
        $resultNote .= "\n".ts('There are no any change.');
      }
    }
    else {
      $resultNote .= "\n".ts('There are no valid record back.');
    }

    if (!empty($resultNote)) {
      // CRM_Core_Error::debug_log_message($resultNote);
      self::addNote($resultNote, $contribution);
    }
    return $resultNote;
  }

  public static function doSyncLastDaysRecords() {
    $last2Day = date('Y-m-d 00:00:00', time() - (86400 * 2));
    $currentDay = date('Y-m-d 23:59:59');
    $sql = "SELECT c.id, payment_processor_id, c.is_test FROM civicrm_contribution c INNER JOIN civicrm_payment_processor p ON p.id = payment_processor_id WHERE receive_date >= '$last2Day' AND receive_date <= '$currentDay' AND p.name = 'Tappay' ";
    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      // Check payment processor
      $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($dao->payment_processor_id, $dao->is_test ? 'test': 'live');
      if (strtolower($paymentProcessor['payment_processor_type']) != 'tappay') {
        CRM_Core_Error::debug_log_message($resultNote.ts("Payment processor of recur is not %1.", [1 => 'TapPay']));
        continue;
      }

      self::doSyncRecord($dao->id);
    }
  }

  public static function doStatusCheck() {
    // update recurring status when end date is due
    $currentDay = date('Y-m-d 00:00:00');
    $sql = "SELECT r.id, r.end_date, r.contribution_status_id, c.payment_processor_id, c.is_test FROM civicrm_contribution_recur r
 INNER JOIN civicrm_contribution c ON c.contribution_recur_id = r.id
 WHERE r.end_date IS NOT NULL AND r.end_date < %1 AND r.contribution_status_id = 5 GROUP BY r.id";
    $dao = CRM_Core_DAO::executeQuery($sql, [
      1 => [$currentDay, 'String'],
    ]);
    while ($dao->fetch()) {
      $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($dao->payment_processor_id, $dao->is_test ? 'test': 'live');
      if ($dao->id && strtolower($paymentProcessor['payment_processor_type']) == 'tappay') {
        $params = [
          'id' => $dao->id,
          'contribution_status_id' => 1,
          'message' => ts("End date is due."),
        ];
        CRM_Contribute_BAO_ContributionRecur::add($params, CRM_Core_DAO::$_nullObject);
        $statusNoteTitle = ts("Change status to %1", [1 => CRM_Contribute_PseudoConstant::contributionStatus(1)]);
        $statusNote = $params['message'] . ts("Auto renews status");
        CRM_Contribute_BAO_ContributionRecur::addNote($dao->id, $statusNoteTitle, $statusNote);

      }
    }

    // update recurring status when card expiry date is due
    $sql = "SELECT r.id, MAX(t.expiry_date) as expiry_date, r.contribution_status_id FROM civicrm_contribution_recur r
 INNER JOIN civicrm_contribution_tappay t ON t.contribution_recur_id = r.id
 WHERE t.expiry_date IS NOT NULL AND r.contribution_status_id = 5 GROUP BY r.id HAVING MAX(t.expiry_date) < %1";
    $dao = CRM_Core_DAO::executeQuery($sql, [
      1 => [$currentDay, 'String'],
    ]);
    while ($dao->fetch()) {
      if ($dao->id) {
        $params = [
          'id' => $dao->id,
          'contribution_status_id' => 1,
          'message' => ts("Card expiry date is due."),
        ];
        CRM_Contribute_BAO_ContributionRecur::add($params, CRM_Core_DAO::$_nullObject);
        $statusNoteTitle = ts("Change status to %1", [1 => CRM_Contribute_PseudoConstant::contributionStatus(1)]);
        $statusNote = $params['message'] . ts("Auto renews status");
        CRM_Contribute_BAO_ContributionRecur::addNote($dao->id, $statusNoteTitle, $statusNote);
      }
    }
  }

  public static function getAssociatedSession($qfKey, $class) {
    if(!$qfKey){
      return FALSE;
    }
    if(empty($class)){
      return FALSE;
    }

    // validate if key is permit by this session
    $key = CRM_Core_Key::validate($qfKey, $class, TRUE);
    if (empty($key)) {
      return FALSE;
    }

    // handling session and validating key
    $name = "_{$class}_".$key.'_container';
    $scope = "{$class}_".$key;
    CRM_Core_Session::registerAndRetrieveSessionObjects([$name, ['CiviCRM', $scope]]);
    $session = CRM_Core_Session::singleton();
    $payment = $session->get($scope);
    return $payment;
  }

  public static function cardNotify($url_params, $request = NULL) {
    // Get Input
    if (empty($request)) {
      $input = file_get_contents('php://input');
      $data = json_decode($input);
    }
    elseif (is_string($request)){
      $input = $request;
      $data = json_decode($request);
    }
    else {
      $data = $request;
    }

    if (empty($data)) {
      return 0;
    }
    $requestURL = CRM_Utils_System::isSSL() ? 'https://' : 'http://';
    $requestURL .= $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

    // Get contribution ids by token
    foreach ($data->card_token as $token) {
      if (empty($token)) {
        continue;
      }
      $sql = "SELECT contribution_id, contribution_recur_id, expiry_date FROM civicrm_contribution_tappay WHERE card_token = %1 ORDER BY contribution_id DESC";
      $params = [
        1 => [$token, 'String'],
      ];
      $dao = CRM_Core_DAO::executeQuery($sql, $params);
      $written = FALSE;
      while ($dao->fetch()) {
        if (!$written) {
          $recordData = [
            'contribution_id' => $dao->contribution_id,
            'url' => $requestURL,
            'date' => date('Y-m-d H:i:s'),
            'post_data' => $input,
          ];
          CRM_Core_Payment_TapPayAPI::writeRecord(NULL, $recordData);
          $written = TRUE;
        }
        // Update contribution_tappay for new expiry_date only. *DO NOT* touch other fields
        $year = $month = $expiryDate = NULL;
        if ($data->card_info->expiry_date) {
          $year = substr($data->card_info->expiry_date, 0, 4);
          $month = substr($data->card_info->expiry_date, 4, 2);
					$expiryDate = date('Y-m-d', strtotime('last day of this month', strtotime($year.'-'.$month.'-01')));

          // update status
          if (strtolower($data->card_info->token_status) !== 'active') {
            $tokenStatus = strtolower($data->card_info->token_status);
            $params = [
              'id' => $dao->contribution_recur_id,
              'contribution_status_id' => 7, // suspend
              'auto_renew' => 9,
              'message' => ts("The recurring have been suspended due to TapPay Notify token status is %1.", [1 => $tokenStatus]),
            ];
            CRM_Contribute_BAO_ContributionRecur::add($params, CRM_Core_DAO::$_nullObject);
            $noteTitle = ts('TapPay Payment').': '.ts('Credit Card Information').' '.ts('updated');
            CRM_Contribute_BAO_ContributionRecur::addNote($dao->contribution_recur_id, $noteTitle, $params['message']);
            break;
          }
          elseif ($expiryDate != $dao->expiry_date  && strtotime($expiryDate ) > strtotime($dao->expiry_date)) {
            // check token_status
            $sql = "UPDATE civicrm_contribution_tappay SET expiry_date = %1 WHERE card_token = %2";
            CRM_Core_DAO::executeQuery($sql, [
              1 => [$expiryDate, 'String'],
              2 => [$token, 'String'],
            ]);
            $params = [
              'id' => $dao->contribution_recur_id,
              'auto_renew' => 2,
              'contribution_status_id' => 5,
              'message' => ts("Expiry date updated from %1 to %2", [
                1 => $dao->expiry_date,
                2 => $expiryDate
              ]),
            ];
            CRM_Contribute_BAO_ContributionRecur::add($params, CRM_Core_DAO::$_nullObject);
            $noteTitle = ts('TapPay Payment').': '.ts('Card Expiry Date').' '.ts('updated');
            CRM_Contribute_BAO_ContributionRecur::addNote($dao->contribution_recur_id, $noteTitle, ts("From").': '.$dao->expiry_date);

            // Add status note
            $statusNoteTitle = ts("Change status to %1", [1 => CRM_Contribute_PseudoConstant::contributionStatus(5)]);
            $statusNote = ts('Card expiry date has been updated.').' '.ts("Auto renews status");
            CRM_Contribute_BAO_ContributionRecur::addNote($dao->contribution_recur_id, $statusNoteTitle, $statusNote);
            break;
          }
        }
      }
    }
    return 1;
  }

  /**
   * Execute ipn when called by tappay 3d validation.
   *
   * @param array $urlParams Default params in CiviCRM Router, Must be array('civicrm', 'mypay', 'ipn')
   * @param string $request Bring post variables if you need test, format json.
   * @param array $get Bring get variables if you need test.
   * @param boolean $sendMail TRUE mean need sendmail after finished..
   * @return void
   */
  public static function doIPN($params, $request = NULL, $get = NULL, $sendMail = TRUE) {
    // Get Input
    if (empty($request)) {
      $input = file_get_contents('php://input');
      $data = json_decode($input);
    }
    elseif (is_string($request)){
      $input = $request;
      $data = json_decode($request);
    }
    else {
      $data = $request;
    }

    if (empty($data)) {
      return 0;
    }
    $requestURL = CRM_Utils_System::isSSL() ? 'https://' : 'http://';
    $requestURL .= $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

    $get = empty($get) ? $_GET : $get;
    $contributionID = CRM_Utils_Type::escape($get['cid'], 'Integer', TRUE);
    $module = $get['module'];

    if (!empty($contributionID) && in_array($module, ['contribute', 'event'])) {
      // Get contribution ids by token
      $recordData = [
        'contribution_id' => $contributionID,
        'url' => CRM_Core_DAO::escapeString($requestURL),
        'date' => date('Y-m-d H:i:s'),
        'post_data' => $input,
      ];
      CRM_Core_Payment_TapPayAPI::writeRecord(NULL, $recordData);

      // Execute original trasaction.
      self::doTransaction($data, $contributionID, $sendMail);
    }

    return;
  }

  /**
   * Trigger when click transaction button.
   */
  public static function doRecurTransact($recurId = NULL, $sendMail = FALSE) {
    // Get current user
    $session = CRM_Core_Session::singleton();
    $contactId = $session->get('userID');

    $resultNote = self::payByToken($recurId, NULL, $sendMail);

    $sql = "SELECT count(*) FROM civicrm_contribution WHERE contribution_recur_id = %1";
    $params = [1 => [$recurId, 'Positive']];
    $sql = "SELECT id FROM civicrm_contribution_tappay
    WHERE contribution_recur_id = %1
    ORDER BY id DESC LIMIT 1";
    $params = [1 => [$recurId, 'Positive']];
    $tappayId = CRM_Core_DAO::singleValueQuery($sql, $params);
    if ($tappayId) {
      $tappayData = new CRM_Contribute_DAO_TapPay();
      $tappayData->id = $tappayId;
      $tappayData->find(TRUE);
      $tappayData->created_id = $contactId;
      $tappayData->save();
    }
    return $resultNote;
  }

  /**
   * Get the message as pressing "Sync Now" button.
   * Called by MakingTransaction form.
   * 
   * @param int $contributionId The contribution id of the page.
   * @param int $recurId The recurring id of the page.
   * @return string The message
   */
  public static function getSyncNowMessage($contributionId, $recurId = NULL) {
    return ts("Are you sure you want to sync all expiry dates of this token?", [1 => $recurId]);
  }

  /**
   * Behavior after pressed "Sync now" button.
   * 
   * @param int $id The contribution recurring ID
   * @param string $idType Means the type of the ID, value as "Contribution" or "recur"
   * @param object $form The MakingTransaction form object
   * @return void
   */
  public static function doRecurUpdate($id, $idType = 'contribution', $form = NULL) {
    if (strstr('recur', $idType)) {
      $contribution_recur_id = $id;
    }
    else {
      // $id is contribution ID.
      $contribution_recur_id = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $id, 'contribution_recur_id');
    }

    $card_token = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_TapPay', $contribution_recur_id, 'card_token', 'contribution_recur_id');
    $paramsToken = [1 => [$card_token, 'String']];
    $sqlGroupExpiryDates = "SELECT GROUP_CONCAT(expiry_date) FROM civicrm_contribution_tappay WHERE card_token = %1;";
    $originExpiryDates = CRM_Core_DAO::singleValueQuery($sqlGroupExpiryDates, $paramsToken);

    $returnMessage =  ts("There are no any change.");

    $paramsRecurId = [1 => [$contribution_recur_id, 'Positive']];
    $sql = "SELECT id FROM civicrm_contribution WHERE contribution_recur_id = %1 ORDER BY id DESC LIMIT 1;";
    $contributionId = CRM_Core_DAO::singleValueQuery($sql, $paramsRecurId);
    $contribution = new CRM_Contribute_DAO_Contribution();
    $contribution->id = $contributionId;
    if($contribution->find(TRUE)) {
      $ppid = $contribution->payment_processor_id;
      $mode = $contribution->is_test ? 'test' : 'live';
      if ($ppid) {
        $tappayData = new CRM_Contribute_DAO_TapPay();
        $tappayData->contribution_id = $contributionId;
        $tappayData->find(TRUE);

        $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($ppid, $mode);
        $tappayParams = [
          'apiType' => 'card_metadata',
          'partnerKey' => $paymentProcessor['password'],
          'contribution_id' => $contributionId,
          'isTest' => $contribution->is_test,
        ];
        $api = new CRM_Core_Payment_TapPayAPI($tappayParams);
        $result = $api->request([
          'partner_key' => $paymentProcessor['password'],
          'card_key' => $tappayData->card_key,
          'card_token' => $tappayData->card_token,
        ]);

        if ($result->card_info->expiry_date) {
          // Update expiry date`
          $year = substr($result->card_info->expiry_date, 0, 4);
          $month = substr($result->card_info->expiry_date, 4, 2);
          $expiryTimestamp = strtotime('last day of this month', strtotime($year.'-'.$month.'-01'));

          // check expiry_date is over current time.
          if ($expiryTimestamp >= time()) {
            $newExpiryDate = date('Y-m-d', $expiryTimestamp);

            $sql = "UPDATE civicrm_contribution_tappay SET expiry_date = %1 WHERE card_token = %2";
            $params = [
              1 => [$newExpiryDate, 'String'],
              2 => [$card_token, 'String'],
            ];
            CRM_Core_DAO::executeQuery($sql, $params);

            $newExpiryDates = CRM_Core_DAO::singleValueQuery($sqlGroupExpiryDates, $paramsToken);

            if ($newExpiryDates != $originExpiryDates) {
              $returnMessage = ts("Card expiry date has been updated.");
            }
            else {
              $returnMessage = ts("Card expiry date has been already newest.");
            }
          }
          else {
            $returnMessage = ts("Card expiry date on the server is still not up-to-date.").ts("If there has any problem, please contact payment provider.");
          }
        }
        else {
          $returnMessage = ts("There are some problem on API.").ts("If there has any problem, please contact payment provider.");
        }
      }
    }

    return $returnMessage;
  }

  /**
   * Function called from contributionRecur page to show tappay detail information
   * 
   * @param int @contributionId the contribution id
   * 
   * @return array The label as the key to value.
   */
  public static function getRecordDetail($contributionId) {
    $tappayDAO = new CRM_Contribute_DAO_TapPay();
    $tappayDAO->contribution_id = $contributionId;
    $tappayDAO->find(TRUE);
    $tappayObject = json_decode($tappayDAO->data);

    $contribution = new CRM_Contribute_DAO_Contribution();
    $contribution->id = $contributionId;
    $contribution->find(TRUE);
    $is_test = $contribution->is_test ? 'test' : '';
    $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($contribution->payment_processor_id, $is_test);
    if (empty($contribution->payment_processor_id)) {
      return NULL;
    }
    $returnData = [];
    $returnData[ts('Payment Processor')] = $paymentProcessor['name']." - ".ts('ID').$paymentProcessor['id'];
    $returnData[ts('Record Trade ID')] = $tappayDAO->rec_trade_id;
    $returnData[ts('Card Number')] = $tappayDAO->bin_code."**********".$tappayDAO->last_four;
    require_once 'CRM/Core/Smarty/resources/String.php';
    $smarty = CRM_Core_Smarty::singleton();
    civicrm_smarty_register_string_resource();
    $updateCardmetaButton = $smarty->fetch('string: {$form.$update_notify.html}');
    if (!empty($tappayDAO->contribution_recur_id)) {
      $params = [ 1 => [$tappayDAO->contribution_recur_id, 'Positive']];
      $newestExpiryDate = CRM_Core_DAO::singleValueQuery("SELECT MAX(expiry_date) FROM civicrm_contribution_tappay WHERE contribution_recur_id = %1 GROUP BY contribution_recur_id", $params);
    }
    else {
      $newestExpiryDate = $tappayDAO->expiry_date;
    }
    $returnData[ts('Card Expiry Date')] = date('Y/m',strtotime($newestExpiryDate)).$updateCardmetaButton;
    $returnData[ts('Response Code')] = $tappayObject->status;
    $returnData[ts('Response Message')] = $tappayObject->msg;

    $contact_id = $tappayDAO->created_id;
    if ($contact_id) {
      $contactName = CRM_Contact_BAO_Contact::displayName($contact_id);
      $returnData[ts('Added By')] = ['id' => $contact_id, 'name' => $contactName];
    }
    if (!empty($tappayObject->card_info)) {
      $cardInfo = $tappayObject->card_info;
      $returnData[ts('Card Issuer')] = $cardInfo->issuer;
      $returnData[ts('Card Type')] = self::$_cardType[$cardInfo->type];
      $returnData[ts('Card Category')] = ts(self::$_cardCategory[$cardInfo->funding]);
    }
    if (!empty($tappayDAO->contribution_recur_id)) {
      $autoRenew = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionRecur', $tappayDAO->contribution_recur_id, 'auto_renew');
      if (empty($autoRenew)) {
        $returnData[ts('Support 3JTSP')] = ts("No");
      }
      else if($autoRenew == 1) {
        $returnData[ts('Support 3JTSP')] = ts("Yes");
      }
      else if($autoRenew == 2) {
        $sql = "SELECT MAX(tl.date) FROM civicrm_contribution_tappay_log tl INNER JOIN civicrm_contribution c ON tl.contribution_id = c.id WHERE c.contribution_recur_id = %1 AND tl.url LIKE '%civicrm/tappay/cardnotify' GROUP BY tl.contribution_id";
        $params = [1 => [$tappayDAO->contribution_recur_id, 'Positive']];
        $updatedDate = CRM_Core_DAO::singleValueQuery($sql, $params);
        $returnData[ts('Support 3JTSP')] = $updatedDate . ' ' . ts("updated");
      }
    }
    return $returnData;
  }

  public static function getContributionAllRecordData($contributionId) {
    $logs = [];
    $tappayLog = new CRM_Contribute_DAO_TapPayLog();
    $tappayLog->contribution_id = $contributionId;
    $tappayLog->find();
    while ($tappayLog->fetch()) {
      $logs[$tappayLog->id] = (array) $tappayLog;
    }
    return $logs;
  }

  static function getContributionTrxnID($contributionId, $recurringId = NULL) {
    $rand = base_convert(strval(rand(16, 255)), 10, 16);
    if(empty($recurringId)){
      $recurringId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $contributionId, 'contribution_recur_id');
    }

    if(!empty($recurringId)){
      $trxnId = 'r_'.$recurringId.'_'.$contributionId.'_'.$rand;
    }else{
      $trxnId = 'c_'.$contributionId.'_'.$rand;
    }
    return $trxnId;
  }

  static function getSyncDataUrl($contributionId) {
    $get = $_GET;
    unset($get['q']);
    $query = http_build_query($get);
    $sync_url = CRM_Utils_System::url("civicrm/tappay/query", $query);
    return $sync_url;
  }

  static function addNote($note, &$contribution){

    $note = date("Y/m/d H:i:s "). ts("Transaction record")."Trxn ID: {$contribution->trxn_id} \n\n".$note;
    CRM_Core_Error::debug_log_message( $note );
  }
}
