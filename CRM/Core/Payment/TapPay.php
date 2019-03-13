<?php

class CRM_Core_Payment_TapPay extends CRM_Core_Payment {
  
  protected $_mode = NULL;

  protected $_api = NULL;

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
  public static function &singleton($mode, &$paymentProcessor) {
    $processorName = $paymentProcessor['name'];
    if (self::$_singleton[$processorName] === NULL) {
      self::$_singleton[$processorName] = new CRM_Core_Payment_TapPay($mode, $paymentProcessor);
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
    $currentPath = CRM_Utils_System::currentPath();
    $thankyou = CRM_Utils_System::url($currentPath, '_qf_ThankYou_display=1&qfKey='.$params['qfKey']);
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext($thankyou);
    $url = CRM_Utils_System::url("civicrm/tappay/directpay", "id={$params['contributionID']}&qfKey={$params['qfKey']}&component={$component}");
    CRM_Utils_System::redirect($url);
  }

  public static function payByPrime() {
    // validate sessions
    $id = CRM_Utils_Request::retrieve('id', 'Positive', CRM_Core_DAO::$_nullObject, TRUE, NULL, 'REQUEST');
    $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', CRM_Core_DAO::$_nullObject, TRUE, NULL, 'REQUEST');
    $class = CRM_Utils_Request::retrieve('class', 'String', CRM_Core_DAO::$_nullObject, TRUE, NULL, 'REQUEST');
    $payment = CRM_Core_Payment_TapPay::getAssociatedSession($qfKey, $class);

    if ($payment && !empty($payment['paymentProcessor'])) {
      $contribution = $ids = array();
      $params = array('id' => $id);
      CRM_Contribute_BAO_Contribution::getValues($params, $contribution, $ids);
      list($sortName, $email) = CRM_Contact_BAO_Contact::getContactDetails($contribution['contact_id']);
      $paymentProcessor = $payment['paymentProcessor'];
      $prime = CRM_Utils_Request::retrieve('prime', 'String', CRM_Core_DAO::$_nullObject, TRUE, NULL, 'REQUEST');
			$tappayParams = array(
				'apiType' => 'pay_by_prime',
				'partnerKey' => $paymentProcessor['password'],
			);
			$api = new CRM_Core_Payment_TapPayAPI($tappayParams);
			$data = array(
				'prime' => $prime,
				'partner_key' => $paymentProcessor['password'],
				'merchant_id' => $paymentProcessor['user_name'],
				'amount' => $contribution['currency'] == 'TWD' ? (int)$contribution['total_amount'] : $contribution['total_amount'],
				'currency' => $contribution['currency'],
				'details' => $contribution['amount_level'], // item name
				'cardholder'=> array(
          'phone_number'=> '+886900000000', #required #TODO
          'name' => $sortName, # required
          'email' => $email, #required
          'zip_code' => '',    //optional
          'address' => '',     //optional
          'national_id' => '', //optional
        ),
				'remember' => $contribution['contribution_recur_id'] ? TRUE : FALSE,
        'contribution_id' => $id,
			);
      $result = $api->request($data);
      $response = array('status' => $result->status, 'msg' => $result->message);
      echo json_encode($response);
      CRM_Utils_System::civiExit();
    }
    return CRM_Utils_System::notFound();
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
    CRM_Core_Session::registerAndRetrieveSessionObjects(array($name, array('CiviCRM', $scope)));
    $session = CRM_Core_Session::singleton();
    $payment = $session->get($scope);
    return $payment;
  }
}
