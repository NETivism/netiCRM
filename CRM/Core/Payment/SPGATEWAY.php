<?php
date_default_timezone_set('Asia/Taipei');
require_once 'CRM/Core/Payment.php';
class CRM_Core_Payment_SPGATEWAY extends CRM_Core_Payment {

  /**
   * mode of operation: live or test
   *
   * @var object
   * @static
   */
  static protected $_mode = NULL;

  public static $_hideFields = array('invoice_id');

  // Used for contribution recurring form ( /CRM/Contribute/Form/ContributionRecur.php ).
  public static $_editableFields = NULL;

  public static $_statusMap = array(
    // 3 => 'terminate',   // Can't undod. Don't Use
    5 => 'restart',
    7 => 'suspend',
  );

  public static $_unitMap = array(
    'year' => 'Y',
    'month' => 'M',
  );

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   * @static
   */
  static private $_singleton = NULL;

  /**
   * Constructor
   *
   * @param string $mode the mode of operation: live or test
   *
   * @return void
   */
  function __construct($mode, &$paymentProcessor) {
    $this->_mode = $mode;
    $this->_paymentProcessor = $paymentProcessor;
    $this->_processorName = ts('Spgateway');
    $config = &CRM_Core_Config::singleton();
    $this->_config = $config;
  }

  static function getEditableFields($paymentProcessor = NULL) {
    if (empty($paymentProcessor)) {
      $returnArray = array();
    }
    else {
      if ($paymentProcessor['url_recur'] == 1) {
        $returnArray = array('contribution_status_id', 'amount', 'cycle_day', 'frequency_unit');
      }
    }
    return $returnArray;
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
  static function &singleton($mode, &$paymentProcessor, &$paymentForm = NULL) {
    $processorName = $paymentProcessor['name'];
    if (self::$_singleton[$processorName] === NULL) {
      self::$_singleton[$processorName] = new CRM_Core_Payment_SPGATEWAY($mode, $paymentProcessor);
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

    if (empty($this->_paymentProcessor['user_name'])) {
      $error[] = ts('User Name is not set in the Administer CiviCRM &raquo; Payment Processor.');
    }

    if (empty($this->_paymentProcessor['password'])) {
      $error[] = ts('Password is not set in the Administer CiviCRM &raquo; Payment Processor.');
    }

    if (!empty($error)) {
      return implode('<p>', $error);
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

  /**
   * Sets appropriate parameters for checking out to google
   *
   * @param array $params  name value pair of contribution datat
   *
   * @return void
   * @access public
   *
   */
  function doTransferCheckout(&$params, $component) {
    $component = strtolower($component);
    if ($component != 'contribute' && $component != 'event') {
      CRM_Core_Error::fatal(ts('Component is invalid'));
    }
    if (module_load_include('inc', 'civicrm_spgateway', 'civicrm_spgateway.checkout') === FALSE) {
      CRM_Core_Error::fatal('Module civicrm_spgateway doesn\'t exists.');
    }
    else {
      $is_test = $this->_mode == 'test' ? 1 : 0;
      civicrm_spgateway_do_transfer_checkout($params, $component, $this->_paymentProcessor, $is_test);
    }
  }


  /*
      * $params = array(
      *    'contribution_recur_id   => Positive,
      *    'contribution_status_id' => Positive(7 => suspend, 3 => terminate, 5 => restart),
      *    'amount'                 => Positive,
      *    'frequency_unit'         => String('year', 'month')
      *    'cycle_day'              => Positive(1 - 31, 101 - 1231)
      *    'end_date'               => Date
      * )
      */
  function doUpdateRecur($params, $debug = FALSE) {
    if (module_load_include('inc', 'civicrm_spgateway', 'civicrm_spgateway.api') === FALSE) {
      CRM_Core_Error::fatal('Module civicrm_spgateway doesn\'t exists.');
    }
    else if (empty($params['contribution_recur_id'])) {
      CRM_Core_Error::fatal('Missing contribution recur ID in params');
    }
    else {
      // Prepare params

      $apiConstructParams = array(
        'paymentProcessor' => $this->_paymentProcessor,
        'isTest' => $this->_mode == 'test' ? 1 : 0,
      );

      $sql = "SELECT r.trxn_id AS period_no, c.trxn_id AS merchant_id FROM civicrm_contribution_recur r INNER JOIN civicrm_contribution c ON r.id = c.contribution_recur_id WHERE r.id = %1";
      $sqlParams = array( 1 => array($params['contribution_recur_id'], 'Positive'));
      $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
      while ($dao->fetch()) {
        list($merchantId, $ignore) = explode('_', $dao->merchant_id);
        $periodNo = $dao->period_no;
      }

      $recurParamsMap = array(
        'contribution_status_id' => array(
          // 3 => 'terminate',   // Can't undod. Don't Use
          5 => 'restart',
          7 => 'suspend',
        ),
        'frequency_unit' => array(
          'year' => 'Y',
          'month' => 'M',
        ),
      );

      // If status is changed, Send request to alter status API.

      if (!empty($params['contribution_status_id'])) {
        $apiConstructParams['apiType'] = 'alter-status';
        $spgatewayAPI = new spgateway_spgateway_api($apiConstructParams);
        $newStatusId = $params['contribution_status_id'];
        
        /*
        * $requestParams = array(
        *    'AlterStatus'          => Positive(7 => suspend, 3 => terminate, 5 => restart),
        * )
        */
        $requestParams = array(
          'MerOrderNo' => $merchantId,
          'PeriodNo' => $dao->period_no,
          'AlterType' => self::$_statusMap[$newStatusId],
        );
        $apiAlterStatus = clone $spgatewayAPI;
        $apiAlterStatus->request($requestParams);
        if ($debug) {
          $result['API']['AlterType'] = $apiAlterStatus;
        }

        if ($apiAlterStatus->_response->Status == 'SUCCESS') {
          $resultType = $apiAlterStatus->_response->Result->AlterType;
          if (!empty($resultType)) {
            $statusReverseMap = array_flip(self::$_statusMap);
            $result['contribution_status_id'] = $statusReverseMap[$resultType];
          }
          if (!empty($result['NewNextTime'])) {
            $result['next_sched_contribution'] = $result['NewNextTime'];
          }
        }
      }

      // Send alter other property API.

      $apiConstructParams['apiType'] = 'alter-amt';
      $spgatewayAPI = new spgateway_spgateway_api($apiConstructParams);
      $isChangeRecur = FALSE;
      $requestParams = array(
        'MerOrderNo' => $merchantId,
        'PeriodNo' => $dao->period_no,
      );

      /*
      * $requestParams = array(
      *    'AlterAmt'             => Positive,
      *    'PeriodType'           => String(D,W,M,Y)
      *    'PeriodPoint'          => Positive(1 - 31, 0101 - 1231)
      *    'PeriodTimes'          => Positive
      * )
      */

      if (!empty($params['frequency_unit'])) {
        $requestParams['PeriodType'] = $recurParamsMap['frequency_unit'][$requestParams['PeriodType']];
        $isChangeRecur = TRUE;
      }
      if (!empty($params['cycle_day'])) {
        $requestParams['PeriodPoint'] = sprintf('%02d', $params['cycle_day']);
        if (empty($requestParams['PeriodType'])) {
          $unit = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionRecur', $params['contribution_recur_id'], 'frequency_unit');
          $requestParams['PeriodType'] = self::$_unitMap[$unit];
        }
        $isChangeRecur = TRUE;
      }
      if (!empty($params['amount'])) {
        $requestParams['AlterAmt'] = $params['amount'];
        $isChangeRecur = TRUE;
      }

      /**
       * Send Request.
       */
      if ($isChangeRecur) {
        $apiOthers = clone $spgatewayAPI;
        $apiOthers->request($requestParams);
        if ($debug) {
          $result['API']['AlterMnt'] = $apiOthers;
        }
        if ($apiOthers->_response->Status == 'SUCCESS') {
          $apiResult = $apiOthers->_response->Result;
          if (!empty($apiResult->PeriodType)) {
            $unitReverseMap = array_flip(self::$_unitMap);
            $result['frequency_unit'] = $unitReverseMap[$apiResult->PeriodType];
          }
          $result['cycle_day'] = $apiResult->PeriodPoint;
          if (!empty($apiResult->NewNextAmt) && $apiResult->NewNextAmt != '-') {
            $result['amount'] = $apiResult->NewNextAmt;
          }
          if (!empty($apiResult->NewNextTime)) {
            $result['next_sched_contribution'] = $apiResult->NewNextTime;
          }
        }
      }
    }
    return $result;
  }

  function cancelRecuringMessage($recurID){
    if (function_exists("_civicrm_spgateway_cancel_recuring_message")) {
      return _civicrm_spgateway_cancel_recuring_message(); 
    }else{
      CRM_Core_Error::fatal('Module civicrm_spgateway doesn\'t exists.');
    }
  }
}

