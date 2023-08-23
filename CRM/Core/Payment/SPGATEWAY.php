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
    1 => 'suspend',
    5 => 'restart',
    7 => 'suspend',
  );

  public static $_unitMap = array(
    'year' => 'Y',
    'month' => 'M',
  );

  private static $_recurEditAPIVersion = '1.1';

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

  static function getEditableFields($paymentProcessor = NULL, $form = NULL) {
    if (empty($paymentProcessor)) {
      $returnArray = array();
    }
    else {
      if ($paymentProcessor['url_recur'] == 1) {
        // $returnArray = array('contribution_status_id', 'amount', 'cycle_day', 'frequency_unit', 'recurring', 'installments', 'note_title', 'note_body');
        // Enable Installments field after spgateway update.
        $returnArray = array('contribution_status_id', 'amount', 'cycle_day', 'frequency_unit', 'recurring', 'installments', 'note_title', 'note_body');
      }
    }
    if (!empty($form)) {
      $recur_id = $form->get('id');
      if ($recur_id) {
        $sql = "SELECT LENGTH(trxn_id) FROM civicrm_contribution_recur WHERE id = %1";
        $params = array( 1 => array($recur_id, 'Positive'));
        $length = CRM_Core_DAO::singleValueQuery($sql, $params);
        if ($length >= 30 || empty($length)) {
          $returnArray[] = 'trxn_id';
        }
        // Refs 35835, recur should switch to in_process as canceled, and no use neweb recur IPN.
        if ($paymentProcessor['url_recur'] != 1) {
          $sql = "SELECT contribution_status_id FROM civicrm_contribution_recur WHERE id = %1";
          $statusId = CRM_Core_DAO::singleValueQuery($sql, $params);
          if ($statusId == 3) {
            $returnArray[] = 'contribution_status_id';
            $form->assign('set_active_only', 1);
          }
        }
      }
    }

    return $returnArray;
  }

  static function postBuildForm($form) {
    $form->addDate('cycle_day_date', FALSE, FALSE, array('formatType' => 'custom', 'format' => 'mm-dd'));
    $cycleDay = &$form->getElement('cycle_day');
    unset($cycleDay->_attributes['max']);
    unset($cycleDay->_attributes['min']);
    if (!empty($form->get('id'))) {
      $installment = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionRecur', $form->get('id'), 'installments');
      if (!empty($installment)) {
        $form->set('original_installments', $installment);
        $form->addFormRule(array('CRM_Core_Payment_SPGATEWAY', 'validateInstallments'), $form);
      }
    }
  }

  static function validateInstallments($fields, $ignore, $form) {
    $errors = array();
    $pass = TRUE;
    $contribution_status_id = $fields['contribution_status_id'];
    $installments = $fields['installments'];
    $original_installments = $form->get('original_installments');
    if ($contribution_status_id == 5 && !empty($original_installments) && $installments <= 0) {
      $pass = FALSE;
    }
    if (!$pass) {
      $errors['installments'] = ts('Installments should be greater than zero.');
    }
    return $errors;
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
      if (isset($this->_paymentForm) && get_class($this->_paymentForm) == 'CRM_Contribute_Form_Payment_Main') {
        if (empty($params['email-5'])) {
          // Retrieve email of billing type or primary.
          $locationTypes = CRM_Core_PseudoConstant::locationType(FALSE, 'name');
          $bltID = array_search('Billing', $locationTypes);
          if (!$bltID) {
            return CRM_Core_Error::statusBounce(ts('Please set a location type of %1', array(1 => 'Billing')));
          }
          $fields = array();
          $fields['email-'.$bltID] = 1;
          $fields['email-Primary'] = 1;
          $default = array();

          CRM_Core_BAO_UFGroup::setProfileDefaults($params['contactID'], $fields, $default);
          if (!empty($default['email-'.$bltID])) {
            $params['email-5'] = $default['email-'.$bltID];
          }
          elseif (!empty($default['email-Primary'])) {
            $params['email-5'] = $default['email-Primary'];
          }
        }
        $params['item_name'] = $params['description'];
      }
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
    if ($debug) {
      CRM_Core_error::debug('SPGATEWAY doUpdateRecur $params', $params);
    }
    // For no use neweb recur API condition, return original parameters.
    if ($this->_paymentProcessor['url_recur'] != 1) {
      return $params;
    }
    if (module_load_include('inc', 'civicrm_spgateway', 'civicrm_spgateway.api') === FALSE) {
      CRM_Core_Error::fatal('Module civicrm_spgateway doesn\'t exists.');
    }
    else if (empty($params['contribution_recur_id'])) {
      CRM_Core_Error::fatal('Missing contribution recur ID in params');
    }
    else {
      // Prepare params
      $recurResult = array();

      if (preg_match('/^[a-f0-9]{32}$/', $params['trxn_id']) || empty($params['trxn_id'])) {
        // trxn_id is hash, equal to the situation without trxn_id
        $recurResult['is_error'] = 1;
        $recurResult['msg'] = ts('Transaction ID must equal to the Order serial of NewebPay.');
        $recurResult['msg'] .= ts('There are no any change.');
        return $recurResult;
      }

      $apiConstructParams = array(
        'paymentProcessor' => $this->_paymentProcessor,
        'isTest' => $this->_mode == 'test' ? 1 : 0,
      );

      $sql = "SELECT r.trxn_id AS period_no, c.trxn_id AS merchant_id FROM civicrm_contribution_recur r INNER JOIN civicrm_contribution c ON r.id = c.contribution_recur_id WHERE r.id = %1";
      $sqlParams = array( 1 => array($params['contribution_recur_id'], 'Positive'));
      $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
      while ($dao->fetch()) {
        if (substr($dao->merchant_id, 0, 2) == 'r_') {
          // Condition for old neweb transfer to current.
          list($ignore1, $merchantId, $ignore2) = explode('_', $dao->merchant_id);
        }
        else {
          list($merchantId, $ignore) = explode('_', $dao->merchant_id);
        }
      }

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
          'Version' => self::$_recurEditAPIVersion,
          'MerOrderNo' => $merchantId,
          'PeriodNo' => $params['trxn_id'],
          'AlterType' => self::$_statusMap[$newStatusId],
        );
        $apiAlterStatus = clone $spgatewayAPI;
        $recurResult = $apiAlterStatus->request($requestParams);
        if ($debug) {
          $recurResult['API']['AlterType'] = $apiAlterStatus;
        }

        if (!empty($recurResult['response_status'])) {
          if (in_array($recurResult['response_status'], array('PER10062', 'PER10064'))) {
            // Neweb is canceled. Set finished if status is setting to finished.
            if ($newStatusId == 1) {
              $recurResult['contribution_status_id'] = $newStatusId;
            }
            else {
              $recurResult['msg'] .=  "\n". ts('The contribution has been canceled.');
              $recurResult['note_body'] = $recurResult['msg'];
              $recurResult['contribution_status_id'] = 3;
            }
          }
          else {
            // Status is 'PER10061', 'PER10063'. Set to which admin is selected.
            $recurResult['contribution_status_id'] = $newStatusId;
          }
        }
        if (!empty($recurResult['is_error'])) {
          // There are error msg in $recurResult['msg']
          $errResult = $recurResult;
          return $errResult;
        }
      }

      // Send alter other property API.

      $apiConstructParams['apiType'] = 'alter-amt';
      $spgatewayAPI = new spgateway_spgateway_api($apiConstructParams);
      $isChangeRecur = FALSE;
      $requestParams = array(
        'Version' => self::$_recurEditAPIVersion,
        'MerOrderNo' => $merchantId,
        'PeriodNo' => $params['trxn_id'],
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

        $requestParams['PeriodType'] = self::$_unitMap[$params['frequency_unit']];
        $isChangeRecur = TRUE;
      }

      if (!empty($params['cycle_day'])) {
        if (empty($requestParams['PeriodType'])) {
          $unit = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionRecur', $params['contribution_recur_id'], 'frequency_unit');
          $requestParams['PeriodType'] = self::$_unitMap[$unit];
        }
        $isChangeRecur = TRUE;
      }
      if (!empty($requestParams['PeriodType'])) {
        if ($requestParams['PeriodType'] == 'M') {
          $requestParams['PeriodPoint'] = sprintf('%02d', $params['cycle_day']);
        }
        elseif ($requestParams['PeriodType'] == 'Y') {
          $requestParams['PeriodPoint'] = sprintf('%04d', $params['cycle_day']);
        }
      }
      if (!empty($params['amount'])) {
        $requestParams['AlterAmt'] = $params['amount'];
        $isChangeRecur = TRUE;
      }
      if (!empty($params['installments'])) {
        $requestParams['PeriodTimes'] = $params['installments'];
        $isChangeRecur = TRUE;
      }

      if ($debug) {
        CRM_Core_error::debug('SPGATEWAY doUpdateRecur $requestParams', $requestParams);
      }

      /**
       * Send Request.
       */
      if ($isChangeRecur) {
        $apiOthers = clone $spgatewayAPI;
        $recurResult2 = $apiOthers->request($requestParams);
        if ($debug) {
          $recurResult['API']['AlterMnt'] = $apiOthers;
          CRM_Core_error::debug('SPGATEWAY doUpdateRecur $apiOthers', $apiOthers);
        }
        if (is_array($recurResult2)) {
          $recurResult += $recurResult2;
        }
      }

      if (!empty($recurResult['is_error'])) {
        // There are error msg in $recurResult['msg']
        $errResult = $recurResult;
        return $errResult;
      }
      CRM_Core_Error::debug('SPGATEWAY doUpdateRecur $recurResult', $recurResult);
      if (!empty($recurResult['installments'] && $recurResult['installments'] != $requestParams['PeriodTimes'])) {
        $recurResult['note_body'] .= ts('Selected installments is %1.', array(1 => $requestParams['PeriodTimes'])).ts('Modify installments by Newebpay data.');
      }
    }

    if ($debug) {
      CRM_Core_Error::debug('Payment Spgateway doUpdateRecur $recurResult', $recurResult);
    }
    return $recurResult;
  }

  function cancelRecuringMessage($recurID){
    $sql = "SELECT p.payment_processor_type, p.url_recur FROM civicrm_payment_processor p INNER JOIN civicrm_contribution_recur r ON p.id = r.processor_id WHERE r.id = %1";
    $params = array( 1 => array($recurID, 'Positive'));
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    while ($dao->fetch()) {
      if ($dao->payment_processor_type == 'SPGATEWAY' && $dao->url_recur == 1 ) {
        $msg = '<p>'.ts("You have enable NewebPay recurring API. Please use edit page to cancel recurring contribution.").'</p><script>cj(".ui-dialog-buttonset button").hide();</script>';
        return $msg;
      }
    }
    if (function_exists("_civicrm_spgateway_cancel_recuring_message")) {
      return _civicrm_spgateway_cancel_recuring_message(); 
    }else{
      CRM_Core_Error::fatal('Module civicrm_spgateway doesn\'t exists.');
    }
  }

  /**
   * return array(
   *   // All instrument:
   *   'status' => contribuion_status
   *   'msg' => return message
   * 
   *   // Not Credit Card:
   *   'payment_instrument' => civicrm_spgateway_notify_display() return value
   * )
   */
  function doGetResultFromIPNNotify($contributionId, $submitValues = array()) {
    // First, check if it is redirect payment.
    $instruments = CRM_Contribute_PseudoConstant::paymentInstrument('Name');
    $cDao = new CRM_Contribute_DAO_Contribution();
    $cDao->id = $contributionId;
    $cDao->fetch(TRUE);
    if (strstr($instruments[$cDao->payment_instrument_id], 'Credit')) {
      // If contribution status id == 2, wait 3 second for IPN trigger
      if ($cDao->contribution_status_id == 2) {
        sleep(3);
        $contribution_status_id = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $contributionId, 'contribution_status_id');
        if ($contribution_status_id == 2) {
          $ids = CRM_Contribute_BAO_Contribution::buildIds($contributionId);
          $query = CRM_Contribute_BAO_Contribution::makeNotifyUrl($ids, NULL, TRUE);
          parse_str($query, $get);
          $result = civicrm_spgateway_ipn('Credit', $submitValues, $get, FALSE);
          if(strstr($result, 'OK')){
            $status = 1;
          }
          else{
            $status = 2;
          }
        }
      }
      else {
        $status = $cDao->contribution_status_id;
        if (!empty($submitValues['JSONData'])) {
          $return_params = _civicrm_spgateway_post_decode($submitValues['JSONData']);
        }
        if(!empty($submitValues['Period']) && empty($return_params)){
          $payment_processors = CRM_Core_BAO_PaymentProcessor::getPayment($cDao->payment_processor_id, $cDao->is_test?'test':'live');
          $return_params = _civicrm_spgateway_post_decode(_civicrm_spgateway_recur_decrypt($submitValues['Period'], $payment_processors));
        }
        $msg = _civicrm_spgateway_error_msg($return_params['RtnCode']);
      }
    }
    else {

    }

  }

  
  /**
   * Function called from contributionRecur page to show tappay detail information
   * 
   * @param int @contributionId the contribution id
   * 
   * @return array The label as the key to value.
   */
  public static function getRecordDetail($contributionId) {
    require_once 'CRM/Core/Smarty/resources/String.php';
    $smarty = CRM_Core_Smarty::singleton();
    civicrm_smarty_register_string_resource();
    $returnTables[ts("Manually Synchronize")] = $smarty->fetch('string: {$form.$update_notify.html}');
    return $returnTables;
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
    if (!empty($form)) {
      $contributionId = $form->get('contributionId');
      $contribution = new CRM_Contribute_DAO_Contribution();
      $contribution->id = $contributionId;
      $contribution->find(TRUE);
      $trxn_id = $contribution->trxn_id;
      $explodedTrxnId = explode('_', $trxn_id);
      $isAddedNewContribution = FALSE;
      // If current contribution status is waited, solved current contribution.
      if ($contribution->contribution_status_id == 2) {
        if (count($explodedTrxnId) == 1) {
          $trxn_id .= '_1';
        }
      }
      // If current contribution status is completed, find next contribution.
      if ($contribution->contribution_status_id == 1) {
        $isAddedNewContribution = TRUE;
        $trxn_id = $explodedTrxnId[0] . '_' . ($explodedTrxnId[1] + 1);
      }

      $result = civicrm_spgateway_single_check($trxn_id, TRUE);
      $session = CRM_Core_Session::singleton();
      if (!empty($result)) {
        if ($isAddedNewContribution) {
          if (!empty($result->Result->OrderNo)) {
            $orderNo = $result->Result->OrderNo;
          }
          $session->setStatus(ts("The contribution with transaction ID: %1 has been created and updated.", array(
            1 => $orderNo,
          )));
        }
        else {
          $session->setStatus(ts("%1 status has been updated to %2.", array(
            1 => ts("Recurring Contribution"),
            2 => ts("In Progress"),
          )));
        }
      }
      else {
        if ($isAddedNewContribution) {
          $session->setStatus(ts("The contribution with transaction ID: %1 can't find from Newebpay API.", array(
            1 => $trxn_id,
          )));
        }
        else {
          $session->setStatus(ts("There are no any change."));
        }
      }
    }
  }

  static function doSingleQueryRecord($contributionId = NULL) {
    $get = $_GET;
    unset($get['q']);
    if (!is_numeric($contributionId) || empty($contributionId)) {
      $cid = $get['id'];
    }
    else {
      $cid = $contributionId;
    }
    $origDAO = new CRM_Contribute_DAO_Contribution();
    $origDAO->id = $cid;
    $origDAO->find(TRUE);
    $trxnId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $cid, 'trxn_id');
    if (empty($trxnId)) {
      $resultMessage = ts("The contribution with transaction ID: %1 can't find from Newebpay API.", array(1 => $cid));
    }
    else {
      if (module_load_include('inc', 'civicrm_spgateway', 'civicrm_spgateway.checkout') === FALSE) {
        $resultMessage = ts('Module %1 doesn\'t exists.', array(1 => 'civicrm_spgateway'));
      }
      else {
        if (!function_exists('civicrm_spgateway_single_contribution_sync')) {
          $resultMessage = ts("Sync single contribution function doesn't exist.");
        }
        else {
          civicrm_spgateway_single_contribution_sync($trxnId);
          $resultMessage = ts("Synchronizing to %1 server success.", array(1 => ts("NewebPay")));
          $updatedDAO = new CRM_Contribute_DAO_Contribution();
          $updatedDAO->id = $cid;
          $updatedDAO->find(TRUE);
          $diffContribution = array();
          if ($updatedDAO->contribution_status_id != $origDAO->contribution_status_id) {
            $status = CRM_Contribute_PseudoConstant::contributionStatus();
            $diffContribution[ts('Contribution Status')] = array($status[$origDAO->contribution_status_id], $status[$updatedDAO->contribution_status_id]);

            // Check it will send Email.
            $components = CRM_Contribute_BAO_Contribution::getComponentDetails(array($cid));
            $contributeComponent = $components[$cid];
            $componentName = $contributeComponent['component'];
            $pageId = $contributeComponent['page_id'];
            if ($componentName == 'contribute' && !empty($pageId)) {
              $pageParams = array(1 => array( $pageId, 'Positive'));
              $isEmailReceipt = CRM_Core_DAO::singleValueQuery("SELECT is_email_receipt FROM civicrm_contribution_page WHERE id = %1", $pageParams);
              if ($isEmailReceipt) {
                $diffContribution[] = ts('A notification email has been sent to the supporter.');
              }
            }

            // Check if the SMS is sent.
            $activityType = CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'name', TRUE);
            $activitySMSParams = array(
              'source_record_id' => $cid,
              'activity_type_id' => CRM_Utils_Array::key('Contribution SMS', $activityType),
            );
            $smsActivity = new CRM_Activity_DAO_Activity();
            $smsActivity->copyValues($activitySMSParams);
            if ($smsActivity->find(TRUE)) {
              $diffContribution[] = ts('SMS Sent');
            }
          }
          if ($updatedDAO->receive_date != $origDAO->receive_date) {
            $diffContribution[ts('Received Date')] = array($origDAO->receive_date, $updatedDAO->receive_date);
          }
          if ($updatedDAO->cancel_date != $origDAO->cancel_date) {
            $diffContribution[ts('Cancel Date')] = array($origDAO->cancel_date, $updatedDAO->cancel_date);
          }
          if ($updatedDAO->cancel_reason != $origDAO->cancel_reason) {
            $diffContribution[ts('Cancel Reason')] = array($origDAO->cancel_reason, $updatedDAO->cancel_reason);
          }
          if ($updatedDAO->receipt_id != $origDAO->receipt_id) {
            $diffContribution[ts('Receipt ID')] = array($origDAO->receipt_id, $updatedDAO->receipt_id);
          }
          if ($updatedDAO->receipt_date != $origDAO->receipt_date) {
            $diffContribution[ts('Receipt Date')] = array($origDAO->receipt_date, $updatedDAO->receipt_date);
          }
          if (empty($diffContribution)) {
            $diffContribution[] = ts("There are no any change.");
          }
        }
      }
    }
    // Redirect to contribution view page.
    $query = http_build_query($get);
    $redirect = CRM_Utils_System::url('civicrm/contact/view/contribution', $query);
    if (!empty($diffContribution)) {
      $resultMessage."<ul>";
      foreach ($diffContribution as $key => $value) {
        if ($key && is_array($value)) {
          $resultMessage .= "<li><span>{$key}: </span>".implode(' ==> ', $value)."</li>";
        }
        else {
          $resultMessage .= "<li>{$value}</li>";
        }
      }
      $resultMessage.="</ul>";
    }
    CRM_Core_Session::setStatus($resultMessage);
    CRM_Utils_System::redirect($redirect);
  }

  static function getSyncDataUrl($contributionId, &$form = NULL) {
    $get = $_GET;
    unset($get['q']);
    $query = http_build_query($get);
    $sync_url = CRM_Utils_System::url("civicrm/spgateway/query", $query);
    $params = array( 1 => array( $contributionId, 'Positive'));
    $statusId = CRM_Core_DAO::singleValueQuery("SELECT contribution_status_id FROM civicrm_contribution WHERE id = %1", $params);
    if ($statusId == 2) {
      $updateDataArray = array(ts('Contribution Status'), ts('Receive Date'), );
      $components = CRM_Contribute_BAO_Contribution::getComponentDetails(array($contributionId));
      $contributeComponent = $components[$contributionId];
      $componentName = $contributeComponent['component'];
      $pageId = $contributeComponent['page_id'];
      if ($componentName == 'contribute' && !empty($pageId)) {
        $pageParams = array(1 => array( $pageId, 'Positive'));
        $isEmailReceipt = CRM_Core_DAO::singleValueQuery("SELECT is_email_receipt FROM civicrm_contribution_page WHERE id = %1", $pageParams);
        if ($isEmailReceipt) {
          $updateDataArray[] = ts('Receipt Date');
          $updateDataArray[] = ts('Receipt ID');
          $updateDataArray[] = ts('Payment Notification');
        }
        $isSendSMS = CRM_Core_DAO::singleValueQuery("SELECT is_send_sms FROM civicrm_contribution_page WHERE id = %1", $pageParams);
        if ($isSendSMS) {
          $updateDataArray[] = ts('Send SMS');
        }
      }
      $updateData = implode(', ', $updateDataArray);
      $form->set('sync_data_hint', ts('If the transaction is finished, it will update the follow data by this action: %1', $updateData));
    }

    return $sync_url;
  }
}

