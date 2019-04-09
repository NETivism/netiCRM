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
    $trxn_id = 'testing_'.substr($now, -5);
    $amount = 111;

    // create contribution
    $contrib = array(
      'trxn_id' => $trxn_id,
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
   "rec_trade_id":"sample_trade_id",
   "bank_transaction_id":"sample_bank_id",
   "order_number":"'.$trxn_id.'",
   "auth_code":"123456",
   "card_info":{ 
      "issuer":"",
      "funding":0,
      "type":1,
      "level":"",
      "country":"UNITED KINGDOM",
      "last_four":"1357",
      "bin_code":"246824",
      "country_code":"GB"
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

    $this->assertEquals($trxn_id, $dao->order_number,  "In line " . __LINE__);
    $this->assertEquals('sample_trade_id', $dao->rec_trade_id, "In line " . __LINE__);
    $this->assertEquals('1357', $dao->last_four, "In line " . __LINE__);
    $this->assertEquals('246824', $dao->bin_code, "In line " . __LINE__);
    $this->assertNotEmpty($dao->data, "In line " . __LINE__);
    
    // these data should be null when one-time payment
    $this->assertEquals(NULL, $dao->card_token, "In line " . __LINE__);
    $this->assertEquals(NULL, $dao->card_key, "In line " . __LINE__);
    $this->assertEquals(NULL, $dao->expiry_date, "In line " . __LINE__);
  }

  function testRecurringPaymentNotify(){
  }

  function testNonCreditNotify(){
  }
}
