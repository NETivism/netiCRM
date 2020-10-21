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
   *    'contribution_recur_id => Positive,
   *    'AlterStatus'          => String(suspend, terminate, restart),
   *    'AlterAmt'             => Positive,
   *    'PeriodType'           => String(D,W,M,Y)
   *    'PeriodPoint'          => Positive(1 - 31, 0101 - 1231)
   *    'PeriodTimes'          => Positive
   * )
   */
  function doUpdateRecur($params) {
    if (module_load_include('inc', 'civicrm_spgateway', 'civicrm_spgateway.api') === FALSE) {
      CRM_Core_Error::fatal('Module civicrm_spgateway doesn\'t exists.');
    }
    else if (empty($params['contribution_recur_id'])) {
      CRM_Core_Error::fatal('Missing contribution recur ID in params');
    }
    else {
      // Prepare params

      $apiParams = array(
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

      if (empty($merchantId) || empty($periodNo)) {
        CRM_Core_Error::fatal('Merchant ID or Period NO must have.');
      }

      // If status is changed, Send request to alter status API.
      if (!empty($params['AlterStatus'])) {
        $apiParams['apiType'] = 'alter-status';
        $spgatewayAPI = new spgateway_spgateway_api($apiParams);
        $requestParams = array(
          'MerOrderNo' => $merchantId,
          'PeriodNo' => $periodNo,
          'AlterType' => $params['AlterStatus'],
        );
        $apiAlterStatus = clone $spgatewayAPI;
        $apiAlterStatus->request($requestParams);
        if ($apiAlterStatus->_response->Status == 'SUCCESS') {
          $resultType = $apiAlterStatus->_response->Result->AlterType;
          if (!empty($resultType)) {
            switch ($resultType) {
              case 'suspend':
                $result['contribution_status_id'] = 7;
                break;
              case 'suspend':
                  $result['contribution_status_id'] = 3;
                  break;
              case 'restart':
                $result['contribution_status_id'] = 5;
                break;
            }
          }
          $result['next_sched_contribution'] = $result['NewNextTime'];
        }
      }

      // Send alter other property API.
      $apiParams['apiType'] = 'alter-amt';
      $spgatewayAPI = new spgateway_spgateway_api($apiParams);
      $isChangeRecur = FALSE;
      $requestParams = array(
        'MerOrderNo' => $merchantId,
        'PeriodNo' => $periodNo,
      );
      $allowParams = array('AlterAmt', 'PeriodType', 'PeriodPoint', 'PeriodTimes', 'Extday');
      foreach ($allowParams as $paramName) {
        if (!empty($params[$paramName])) {
          $requestParams[$paramName] = $params[$paramName];
          $isChangeRecur = TRUE;
        }
      }
      if (empty($requestParams['PeriodType'])) {
        $unit = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionRecur', $params['contribution_recur_id'], 'frequency_unit');
        $requestParams['PeriodType'] = ($unit == 'month') ? 'M' : 'Y';
      }
      if ($isChangeRecur) {
        $apiOthers = clone $spgatewayAPI;
        $apiOthers->request($requestParams);
        if ($apiOthers->_response->Status == 'SUCCESS') {
          $apiResult = $apiOthers->_response->Result;
          if (!empty($apiResult->PeriodType)) {
            if ($apiResult->PeriodType == 'Y') {
              $result['frequenty_unit'] = 'year';
            }
            elseif ($apiResult->PeriodType == 'M') {
              $result['frequenty_unit'] = 'month';
            }
          }
          $result['cycle_day'] = $apiResult->PeriodPoint;
          $result['amount'] = $apiResult->NewNextAmt;
          $result['next_sched_contribution'] = $apiResult->NewNextTime;
        }
      }
    }

    /**
     * $result = array(
     *   'contribution_status_id'  => Positive,
     *   'frequenty_unit'          => String(year, month),
     *   'cycle_day'               => String, (Integer in DB),
     *   'amount'                  => Positive,
     *   'next_sched_contribution' => Date (ex: 2020-02-28),
     * )
     */
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

