<?php

require_once 'CiviTest/CiviUnitTestCase.php';

class CRM_Core_Payment_TapPayTest extends CiviUnitTestCase {
  public $DBResetRequired = FALSE;
  protected $_apiversion;
  protected $_processor;
  protected $_is_test;
  protected $_page_id;

  /**
   *  Constructor
   *
   *  Initialize configuration
   */
  function __construct() {
    // test if drupal bootstraped
    if(!defined('DRUPAL_ROOT')){
      die("You must exprot DRUPAL_ROOT for bootstrap drupal before test.");
    }
    
    parent::__construct();
    $this->_page_id = 1;
    $this->prepareMailLog();
  }

  function get_info() {
    return array(
     'name' => 'TapPay payment processor',
     'description' => 'Test TapPay payment processor.',
     'group' => 'Payment Processor Tests',
    );
  }

  function setUp() {
    parent::setUp();

    $this->_is_test = 1;

    // get processor
    $params = array(
      'version' => 3,
      'class_name' => 'Payment_TapPay',
      'is_test' => $this->_is_test,
    );
    $result = civicrm_api('PaymentProcessor', 'get', $params);
    $this->assertAPISuccess($result);
    if(empty($result['count'])){
      $payment_processors = array();
      $params = array(
        'version' => 3,
        'class_name' => 'Payment_TapPay',
      );
      $result = civicrm_api('PaymentProcessorType', 'get', $params);
      $this->assertAPISuccess($result);
      if(!empty($result['count'])){
        $domain_id = CRM_Core_Config::domainID();
        foreach($result['values'] as $type_id => $p){
          $payment_processor = array(
            'version' => 3,
            'domain_id' => $domain_id,
            'name' => 'AUTO payment '.$p['name'],
            'payment_processor_type_id' => $type_id,
            'payment_processor_type' => $p['name'],
            'is_active' => 1,
            'is_default' => 0,

            'is_test' => 0,
            'user_name' => !empty($p['user_name_label']) ? '11111111' : NULL, // Merchant ID  
            'password' => !empty($p['password_label']) ? '11111111' : NULL,   // Partner Key
            'signature' => !empty($p['signature_label']) ? '11111111' : NULL, // APP ID
            'subject' => !empty($p['subject_label']) ? '11111111' : NULL,     // APP Key
            'url_site' => NULL,
            'url_api' => NULL,
            'class_name' => $p['class_name'],
            'billing_mode' => $p['billing_mode'],
            'is_recur' => $p['is_recur'],
            'payment_type' => $p['payment_type'],
          );
          $result = civicrm_api('PaymentProcessor', 'create', $payment_processor);
          $this->assertAPISuccess($result);
          if(is_numeric($result['id'])){
            $payment_processors[] = $result['id'];
          }

          $payment_processor['is_test'] = 1;
          $result = civicrm_api('PaymentProcessor', 'create', $payment_processor);
          $this->assertAPISuccess($result);
        }
      }
    }
    $params = array(
      'version' => 3,
      'class_name' => 'Payment_TapPay',
      'is_test' => $this->_is_test,
    );
    $result = civicrm_api('PaymentProcessor', 'get', $params);
    $this->assertAPISuccess($result);
    $pp = reset($result['values']);
    $this->_processor = $pp;

    // get cid
    $params = array(
      'version' => 3,
      'options' => array(
        'limit' => 1,
      ),
    );
    $result = civicrm_api('Contact', 'get', $params);
    $this->assertAPISuccess($result);
    if(!empty($result['count'])){
      $this->_cid = $result['id'];
    }
  }

  function tearDown() {
    $this->_processor = NULL;
  }

  function testSinglePaymentNotify(){
    $now = time();
    $trxnId = 'testing_'.substr($now, -5);
    $amount = 111;

    // create contribution
    $contrib = array(
      'trxn_id' => $trxnId,
      'contact_id' => $this->_cid,
      'contribution_contact_id' => $this->_cid,
      'contribution_type_id' => 1,
      'contribution_page_id' => $this->_page_id,
      'payment_processor_id' => $this->_processor['id'],
      'payment_instrument_id' => 1,
      'created_date' => date('YmdHis', $now),
      'non_deductible_amount' => 0,
      'total_amount' => $amount,
      'currency' => 'TWD',
      'cancel_reason' => '0',
      'source' => 'AUTO: unit test',
      'contribution_source' => 'AUTO: unit test',
      'amount_level' => '',
      'is_test' => $this->_is_test,
      'is_pay_later' => 0,
      'contribution_status_id' => 2,
    );
    $contribution = CRM_Contribute_BAO_Contribution::create($contrib, CRM_Core_DAO::$_nullArray);
    $this->assertNotEmpty($contribution->id, "In line " . __LINE__);
    $params = array(
      'is_test' => $this->_is_test,
      'id' => $contribution->id,
    );
    $this->assertDBState('CRM_Contribute_DAO_Contribution', $contribution->id, $params);

    // manually trigger pay by prime api
    $microtime = round(microtime(true) * 1000);

    // simulate response
    $primeJson = '{
   "status":0,
   "msg":"Success",
   "amount":111,
   "acquirer":"TW_ESUN",
   "currency":"TWD",
   "card_secret":{
      "card_token":"29af4a1404217bcfc229353e804a1684d7172bed714950f140bb214e1c56262a",
      "card_key":"7de322cc09fd95b3db3637743d16b2c5fe85607393d38c2e8b92d407f63250c9"
   },
   "rec_trade_id":"sample_trade_id",
   "bank_transaction_id":"sample_bank_id",
   "order_number":"'.$trxnId.'",
   "auth_code":"123456",
   "card_info":{ 
      "issuer":"",
      "funding":0,
      "type":1,
      "level":"",
      "country":"UNITED KINGDOM",
      "last_four":"1357",
      "bin_code":"246824",
      "country_code":"GB",
			"expiry_date":"202211"
   },
   "transaction_time_millis":"'.$microtime.'",
   "bank_transaction_time":{  
      "start_time_millis":"'.$microtime.'",
      "end_time_millis":"'.$microtime.'"
   },
   "bank_result_code":"000",
   "bank_result_msg":"ABCDEFG"
}    
';
    $primeResponse = json_decode($primeJson);

    $this->assertNotEmpty($primeResponse, "In line " . __LINE__);
    // because response not through TapPayAPI, we need save data manually
    CRM_Core_Payment_TapPayAPI::saveTapPayData($contribution->id, $primeResponse);
    CRM_Core_Payment_TapPay::validateData($primeResponse, $contribution->id);

    // verify contribution status after trigger
    $this->assertDBCompareValue(
      'CRM_Contribute_DAO_Contribution',
      $searchValue = $contribution->id,
      $returnColumn = 'contribution_status_id',
      $searchColumn = 'id',
      $expectedValue = 1,
      "In line " . __LINE__
    );

    // verify data in civicrm_contribution_tappay
    $dao = new CRM_Contribute_DAO_TapPay();
    $dao->contribution_id = $contribution->id;
    $dao->find(TRUE);

    $this->assertEquals($trxnId, $dao->order_number,  "In line " . __LINE__);
    $this->assertEquals('sample_trade_id', $dao->rec_trade_id, "In line " . __LINE__);
    $this->assertEquals('1357', $dao->last_four, "In line " . __LINE__);
    $this->assertEquals('246824', $dao->bin_code, "In line " . __LINE__);
    $this->assertNotEmpty($dao->data, "In line " . __LINE__);
    
    // these data should be null when one-time payment
    $this->assertEquals(NULL, $dao->card_token, "In line " . __LINE__);
    $this->assertEquals(NULL, $dao->card_key, "In line " . __LINE__);

    // when single payment, we also save expire date of card
    $this->assertEquals('2022-11-30', $dao->expiry_date, "In line " . __LINE__);
  }

  function testRecurringPaymentNotify(){
    ### 1st contribution of recurring
    $now = time();
    $amount = 222;

    // create recurring
    $date = date('YmdHis', $now);
    $recur = array(
      'contact_id' => $this->_cid,
      'amount' => $amount,
      'frequency_unit' => 'month',
      'frequency_interval' => 1,
      'installments' => 0,
      'is_test' => $this->_is_test,
      'start_date' => $date,
      'create_date' => $date,
      'modified_date' => $date,
      'invoice_id' => md5($now),
      'contribution_status_id' => 2,
      'cycle_day' => 5,
      'trxn_id' => $trxnId = 'ut'.substr($now, -5),
    );
    $ids = array();
    $recurring = CRM_Contribute_BAO_ContributionRecur::add($recur, $ids);
    $params = array(
      'id' => $recurring->id,
      'contribution_status_id' => 2,
    );
    $this->assertDBState('CRM_Contribute_DAO_ContributionRecur', $recurring->id, $params);

    // create contribution
    $contrib = array(
      'contact_id' => $this->_cid,
      'contribution_contact_id' => $this->_cid,
      'contribution_type_id' => 1,
      'contribution_page_id' => $this->_page_id,
      'payment_processor_id' => $this->_processor['id'],
      'payment_instrument_id' => 1,
      'created_date' => date('YmdHis', $now),
      'non_deductible_amount' => 0,
      'total_amount' => $amount,
      'currency' => 'TWD',
      'cancel_reason' => '0',
      'source' => 'AUTO: unit test',
      'contribution_source' => 'AUTO: unit test - recurring',
      'amount_level' => '',
      'is_test' => $this->_is_test,
      'is_pay_later' => 0,
      'contribution_status_id' => 2,
      'contribution_recur_id' => $recurring->id,
    );
    $contribution = CRM_Contribute_BAO_Contribution::create($contrib, CRM_Core_DAO::$_nullArray);
    $trxnId = CRM_Core_Payment_TapPay::getContributionTrxnID($contribution->id, $recurring->id);
    CRM_Core_DAO::setFieldValue('CRM_Contribute_DAO_Contribution', $contribution->id, 'trxn_id', $trxnId);
    $this->assertNotEmpty($contribution->id, "In line " . __LINE__);
    $params = array(
      'is_test' => $this->_is_test,
      'id' => $contribution->id,
      'trxn_id' => $trxnId,
    );
    $this->assertDBState('CRM_Contribute_DAO_Contribution', $contribution->id, $params);

    // manually trigger pay by prime api
    $microtime = round(microtime(true) * 1000);
    $plusmonth = strtotime('+4 month');
    $expiryDate = date('Ym', $plusmonth);
    $lastDayOfMonth = date('Y-m-d', strtotime('last day of this month', $plusmonth));

    // simulate response
    $primeJson = '{
   "status":0,
   "msg":"Success",
   "amount":'.$amount.',
   "acquirer":"TW_ESUN",
   "currency":"TWD",
   "card_secret":{
      "card_token":"a1",
      "card_key":"b1"
   },
   "rec_trade_id":"sample_trade_id",
   "bank_transaction_id":"sample_bank_id",
   "order_number":"'.$trxnId.'",
   "auth_code":"123456",
   "card_info":{ 
      "issuer":"",
      "funding":0,
      "type":1,
      "level":"",
      "country":"UNITED KINGDOM",
      "last_four":"1357",
      "bin_code":"246824",
      "country_code":"GB",
			"expiry_date":"'.$expiryDate.'"
   },
   "transaction_time_millis":"'.$microtime.'",
   "bank_transaction_time":{  
      "start_time_millis":"'.$microtime.'",
      "end_time_millis":"'.$microtime.'"
   },
   "bank_result_code":"000",
   "bank_result_msg":"ABCDEFG"
}    
';
    $primeResponse = json_decode($primeJson);
    $this->assertNotEmpty($primeResponse, "In line " . __LINE__);
    // because response not through TapPayAPI, we need save data manually
    CRM_Core_Payment_TapPayAPI::saveTapPayData($contribution->id, $primeResponse);
    CRM_Core_Payment_TapPay::validateData($primeResponse, $contribution->id);

    // verify contribution status after trigger
    $this->assertDBCompareValue(
      'CRM_Contribute_DAO_Contribution',
      $searchValue = $contribution->id,
      $returnColumn = 'contribution_status_id',
      $searchColumn = 'id',
      $expectedValue = 1,
      "In line " . __LINE__
    );

    // verify data in civicrm_contribution_tappay
    $dao = new CRM_Contribute_DAO_TapPay();
    $dao->contribution_id = $contribution->id;
    $dao->find(TRUE);

    $this->assertEquals($trxnId, $dao->order_number,  "In line " . __LINE__);
    $this->assertEquals('sample_trade_id', $dao->rec_trade_id, "In line " . __LINE__);
    $this->assertEquals('1357', $dao->last_four, "In line " . __LINE__);
    $this->assertEquals('246824', $dao->bin_code, "In line " . __LINE__);
    $this->assertNotEmpty($dao->data, "In line " . __LINE__);
    $this->assertEquals('a1', $dao->card_token, "In line " . __LINE__);
    $this->assertEquals('b1', $dao->card_key, "In line " . __LINE__);
    $this->assertEquals($lastDayOfMonth, $dao->expiry_date, "In line " . __LINE__);

    ### 2nd contribution of recurring
    $now = strtotime(date('Y-m-5', strtotime('+1 month'))) + 80000; // later of that 5th of month
    $microtime = ($now + 5)*1000;
    CRM_Core_Payment_TapPay::doExecuteAllRecur($now);

    $recurParams = array(
      1 => array("r_{$recurring->id}_%", 'String')
    );
    $contribution2nd = CRM_Core_DAO::executeQuery("SELECT id, trxn_id FROM civicrm_contribution WHERE trxn_id LIKE %1 ORDER BY id DESC LIMIT 1", $recurParams);
    $contribution2nd->fetch();
    $trxnId2 = $contribution2nd->trxn_id;
    // when no correct info to use api, this shoule be failed first
    // simulate card token response and validate payment process
    // simulate response
    $tokenJson = '{
   "status":0,
   "msg":"Success",
   "amount":'.$amount.',
   "acquirer":"TW_ESUN",
   "currency":"TWD",
   "card_secret":{
      "card_token":"a2",
      "card_key":"b2"
   },
   "rec_trade_id":"sample_trade_id2",
   "bank_transaction_id":"sample_bank_id2",
   "order_number":"'.$trxnId2.'",
   "auth_code":"123456",
   "card_info":{ 
      "issuer":"",
      "funding":0,
      "type":1,
      "level":"",
      "country":"UNITED KINGDOM",
      "last_four":"1357",
      "bin_code":"246824",
      "country_code":"GB",
			"expiry_date":"'.$expiryDate.'"
   },
   "transaction_time_millis":"'.$microtime.'",
   "bank_transaction_time":{  
      "start_time_millis":"'.$microtime.'",
      "end_time_millis":"'.$microtime.'"
   },
   "bank_result_code":"000",
   "bank_result_msg":"ABCDEFG"
}
';
    $tokenResponse = json_decode($tokenJson);
    $this->assertNotEmpty($tokenResponse, "In line " . __LINE__);
    // because response not through TapPayAPI, we need save data manually
    CRM_Core_Payment_TapPayAPI::saveTapPayData($contribution2nd->id, $tokenResponse);
    CRM_Core_Payment_TapPay::validateData($tokenResponse, $contribution2nd->id);
    $this->assertDBQuery(1, "SELECT contribution_status_id FROM civicrm_contribution WHERE trxn_id LIKE %1", array(1 => array($trxnId2, 'String')));
    $this->assertDBQuery(2, "SELECT count(*) FROM civicrm_contribution WHERE trxn_id LIKE %1 ORDER BY id DESC", $recurParams);
    $this->assertDBQuery(2, "SELECT count(*) FROM civicrm_contribution_tappay WHERE order_number LIKE %1 ORDER BY id DESC", $recurParams);

    $dao = new CRM_Contribute_DAO_TapPay();
    $dao->contribution_id = $contribution2nd->id;
    $dao->find(TRUE);

    $this->assertEquals($trxnId2, $dao->order_number,  "In line " . __LINE__);
    $this->assertEquals('1357', $dao->last_four, "In line " . __LINE__);
    $this->assertEquals('246824', $dao->bin_code, "In line " . __LINE__);
    $this->assertNotEmpty($dao->data, "In line " . __LINE__);
    $this->assertEquals('a2', $dao->card_token, "In line " . __LINE__);
    $this->assertEquals('b2', $dao->card_key, "In line " . __LINE__);
    $this->assertEquals($lastDayOfMonth, $dao->expiry_date, "In line " . __LINE__);
    
    ### 3rd contribution, change amount
    $amount = 333;
    $now = strtotime(date('Y-m-5', strtotime('+2 month'))) + 80000; // later of that 5th of month
    $microtime = ($now + 5)*1000;
    CRM_Core_DAO::setFieldValue("CRM_Contribute_DAO_ContributionRecur", $recurring->id, 'amount', $amount);
    CRM_Core_Payment_TapPay::doExecuteAllRecur($now);

    $recurParams = array(
      1 => array("r_{$recurring->id}_%", 'String')
    );
    $contribution3rd = CRM_Core_DAO::executeQuery("SELECT id, trxn_id FROM civicrm_contribution WHERE trxn_id LIKE %1 ORDER BY id DESC LIMIT 1", $recurParams);
    $contribution3rd->fetch();
    $trxnId3 = $contribution3rd->trxn_id;
    // when no correct info to use api, this shoule be failed first
    // simulate card token response and validate payment process
    // simulate response
    $tokenJson = '{
   "status":0,
   "msg":"Success",
   "amount":'.$amount.',
   "acquirer":"TW_ESUN",
   "currency":"TWD",
   "card_secret":{
      "card_token":"a3",
      "card_key":"b3"
   },
   "rec_trade_id":"sample_trade_id3",
   "bank_transaction_id":"sample_bank_id3",
   "order_number":"'.$trxnId3.'",
   "auth_code":"123456",
   "card_info":{ 
      "issuer":"",
      "funding":0,
      "type":1,
      "level":"",
      "country":"UNITED KINGDOM",
      "last_four":"1357",
      "bin_code":"246824",
      "country_code":"GB",
			"expiry_date":"'.$expiryDate.'"
   },
   "transaction_time_millis":"'.$microtime.'",
   "bank_transaction_time":{  
      "start_time_millis":"'.$microtime.'",
      "end_time_millis":"'.$microtime.'"
   },
   "bank_result_code":"000",
   "bank_result_msg":"ABCDEFG"
}
';
    $tokenResponse = json_decode($tokenJson);
    $this->assertNotEmpty($tokenResponse, "In line " . __LINE__);
    // because response not through TapPayAPI, we need save data manually
    CRM_Core_Payment_TapPayAPI::saveTapPayData($contribution3rd->id, $tokenResponse);
    CRM_Core_Payment_TapPay::validateData($tokenResponse, $contribution3rd->id);
    $this->assertDBQuery($amount, "SELECT total_amount FROM civicrm_contribution WHERE trxn_id LIKE %1", array(1 => array($trxnId3, 'String')));
    $this->assertDBQuery(1, "SELECT contribution_status_id FROM civicrm_contribution WHERE trxn_id LIKE %1", array(1 => array($trxnId3, 'String')));
    $this->assertDBQuery(3, "SELECT count(*) FROM civicrm_contribution WHERE trxn_id LIKE %1 ORDER BY id DESC", $recurParams);
    $this->assertDBQuery(3, "SELECT count(*) FROM civicrm_contribution_tappay WHERE order_number LIKE %1 ORDER BY id DESC", $recurParams);

    $dao = new CRM_Contribute_DAO_TapPay();
    $dao->contribution_id = $contribution3rd->id;
    $dao->find(TRUE);
    $this->assertEquals($trxnId3, $dao->order_number,  "In line " . __LINE__);
    $this->assertEquals('1357', $dao->last_four, "In line " . __LINE__);
    $this->assertEquals('246824', $dao->bin_code, "In line " . __LINE__);
    $this->assertNotEmpty($dao->data, "In line " . __LINE__);
    $this->assertEquals('a3', $dao->card_token, "In line " . __LINE__);
    $this->assertEquals('b3', $dao->card_key, "In line " . __LINE__);
    $this->assertEquals($lastDayOfMonth, $dao->expiry_date, "In line " . __LINE__);

    ### TODO 4th contribution, recurring should be end 

    // end, check recurring end
  }
}
