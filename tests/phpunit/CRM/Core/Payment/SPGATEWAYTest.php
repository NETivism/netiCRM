<?php

require_once 'CiviTest/CiviUnitTestCase.php';

class CRM_Core_Payment_SPGATEWAYTest extends CiviUnitTestCase {
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
    if(!module_exists('civicrm_spgateway')){
      die("You must enable civicrm_spgateway module first before test.");
    }
    $payment_page = variable_get('civicrm_demo_payment_page', array());
    $class_name = 'Payment_SPGATEWAY';
    if(isset($payment_page[$class_name])){
      $this->_page_id = $payment_page[$class_name];
    }
    parent::__construct();
  }

  function get_info() {
    return array(
     'name' => 'SPGATEWAY payment processor',
     'description' => 'Test SPGATEWAY payment processor.',
     'group' => 'Payment Processor Tests',
    );
  }

  function setUp() {
    parent::setUp();

    $this->_is_test = 1;

    // get processor
    $params = array(
      'version' => 3,
      'class_name' => 'Payment_SPGATEWAY',
      'is_test' => $this->_is_test,
    );
    $result = civicrm_api('PaymentProcessor', 'get', $params);
    $this->assertAPISuccess($result);
    if(empty($result['count'])){
      $payment_processors = array();
      $params = array(
        'version' => 3,
        'class_name' => 'Payment_SPGATEWAY',
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
            'user_name' => !empty($p['user_name_label']) ? 'abcd' : NULL,
            // 'password' => !empty($p['password_label']) ? 'abcd' : NULL,
            // 'signature' => !empty($p['signature_label']) ? 'abcd' : NULL,
            'password' => 'abcd',
            'signature' => 'abcd',
            'url_site' => !empty($p['url_site_default']) ? $p['url_site_default'] : NULL,
            'url_api' => !empty($p['url_api_default']) ? $p['url_api_default'] : NULL,
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
          $payment_processor['url_site'] = !empty($p['url_site_test_default']) ? $p['url_site_test_default'] : NULL;
          $payment_processor['url_api'] = !empty($p['url_api_test_default']) ? $p['url_api_test_default'] : NULL;
          $result = civicrm_api('PaymentProcessor', 'create', $payment_processor);
          $this->assertAPISuccess($result);
        }
      }
    }
    $params = array(
      'version' => 3,
      'class_name' => 'Payment_SPGATEWAY',
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

    // load drupal module file
    $loaded = module_load_include('inc', 'civicrm_spgateway', 'civicrm_spgateway.ipn');
  }

  function tearDown() {
    $this->_processor = NULL;
  }

  /**
  function testSinglePaymentNotify(){
    $now = time();
    $trxn_id = 'ut'.substr($now, -5);
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

    // manually trigger ipn
    $get = $post = $ids = array();
    $ids = CRM_Contribute_BAO_Contribution::buildIds($contribution->id);
    $query = CRM_Contribute_BAO_Contribution::makeNotifyUrl($ids, NULL, $return_query = TRUE);
    parse_str($query, $get);
    $data = array(
      'MerchantID' => 'abcd',// have to modify
      'Amt' => $amount,
      'TradeNo' => '16112117153757079',
      'MerchantOrderNo' => $trxn_id,
      'RespondType' => 'JSON',
      // 'CheckCode' => NULL,
      'PaymentType' => 'CREDIT',
      'IP' => NULL,
      'EscrowBank' => 'KGI',
      'ItemDesc' => 'This is description.',
      'Gateway' => 'MPG',
      'IsLogin' => FALSE,
      'LangType' => 'zh-Tw',
      'PayTime' => date('Y-m-d H:i:s',$now),
      'RespondCode' => '00',
      'Exp' => '2112',
      'TokenUseStatus' => 0,
      'InstFirst' => 100,
      'InstEach' => 0,
      'Inst' => 0,
      'ECI' => '',
    );
    $json = json_encode($data);
    $jsonData = json_encode(array(
      'Status' => 'SUCCESS',
      'Message' => '',
      'Result' => $json,
      ));
    $post = array('JSONData' => $jsonData);
    civicrm_spgateway_ipn('Credit', $post, $get);

    // verify contribution status after trigger
    $this->assertDBCompareValue(
      'CRM_Contribute_DAO_Contribution',
      $searchValue = $contribution->id,
      $returnColumn = 'contribution_status_id',
      $searchColumn = 'id',
      $expectedValue = 1,
      "In line " . __LINE__
    );

    // verify data in drupal module
    $cid = CRM_Core_DAO::singleValueQuery("SELECT cid FROM civicrm_contribution_spgateway WHERE cid = $contribution->id");
    $this->assertNotEmpty($cid, "In line " . __LINE__);
  }
  */

  function testRecurringPaymentNotify(){

    $now = time()+60;
    $trxn_id = 'ut'.substr($now, -5);
    $amount = 222;

    // create recurring
    $date = date('YmdHis', $now);
    $recur = array(
      'contact_id' => $this->_cid,
      'amount' => $amount,
      'frequency_unit' => 'month',
      'frequency_interval' => 1,
      'installments' => 3,
      'is_test' => $this->_is_test,
      'start_date' => $date,
      'create_date' => $date,
      'modified_date' => $date,
      'invoice_id' => md5($now),
      'contribution_status_id' => 2,
      'trxn_id' => CRM_Utils_Array::value('trxn_id', $params),
    );
    $ids = array();
    $recurring = &CRM_Contribute_BAO_ContributionRecur::add($recur, $ids);

    // verify recurring status
    $params = array(
      'id' => $recurring->id,
      'contribution_status_id' => 2,
    );
    $this->assertDBState('CRM_Contribute_DAO_ContributionRecur', $recurring->id, $params);

    /**
     * create contribution (first recurring)
     */
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
      'contribution_recur_id' => $recurring->id,
    );
    $contribution = CRM_Contribute_BAO_Contribution::create($contrib, CRM_Core_DAO::$_nullArray);
    $this->assertNotEmpty($contribution->id, "In line " . __LINE__);
    $params = array(
      'is_test' => $this->_is_test,
      'id' => $contribution->id,
    );
    $this->assertDBState('CRM_Contribute_DAO_Contribution', $contribution->id, $params);

    // manually trigger ipn
    $get = $post = $ids = array();
    $ids = CRM_Contribute_BAO_Contribution::buildIds($contribution->id);
    // $query = CRM_Contribute_BAO_Contribution::makeNotifyUrl($ids, NULL, $return_query = TRUE);
    $vars = array(
      'contactID' => $this->_cid,
      'contributionID' => $contribution->id,
      'is_recur' => 1,
      'contributionRecurID' => $contribution->contribution_recur_id,
      'contributionPageID' => $this->_page_id,
      'payment_processor' => $this->_processor['id'],
    );
    $instrument_code = $contrib['payment_instrument_id'];
    module_load_include('inc', 'civicrm_spgateway', 'civicrm_spgateway.checkout');
    $query = _civicrm_spgateway_notify_url($vars, 'spgateway/ipn/'.$instrument_code, 'contribute');
    parse_str($query, $get);
    $post = array(
      "Status" => "SUCCESS",
      "Message" => "委託單成立，且首次授權成功",
      "Result" => array(
        "MerchantID" => 'abcd',
        "MerchantOrderNo" => $trxn_id,
        "PeriodType" => "M",
        "PeriodAmt" => $amount,
        "AuthTimes" => "4",
        "DateArray" => "2016-11-24,2016-12-24",
        "TradeNo" => "16112415263934243",
        "AuthCode" => "930637",
        "RespondCode" => "00",
        "AuthTime" => date("Ymdhis",$now),
        "CardNo" => "400022******1111",
        "EscrowBank" => "KGI",
        "AuthBank" => "KGI",
      ),
    );
    $ppid = $this->_processor['id'];
    $get['ppid'] = $ppid;
    $PaymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($ppid, $this->_is_test?'test':'live');
    $post = array('Period' => _civicrm_spgateway_recur_encrypt(json_encode($post), $PaymentProcessor));
    civicrm_spgateway_ipn('Credit', $post, $get);

    // verify contribution status after trigger
    $this->assertDBCompareValue(
      'CRM_Contribute_DAO_Contribution',
      $searchValue = $contribution->id,
      $returnColumn = 'contribution_status_id',
      $searchColumn = 'id',
      $expectedValue = 1,
      "In line " . __LINE__
    );

    // verify data in drupal module
    $cid = CRM_Core_DAO::singleValueQuery("SELECT cid FROM civicrm_contribution_spgateway WHERE cid = $contribution->id");
    $this->assertNotEmpty($cid, "In line " . __LINE__);

    // record first time data to check after second
    $first_data = CRM_Core_DAO::singleValueQuery("SELECT data FROM civicrm_contribution_spgateway WHERE cid = $contribution->id");

    // verify recurring status
    $params = array(
      'id' => $recurring->id,
      'contribution_status_id' => 5,
    );
    $this->assertDBState('CRM_Contribute_DAO_ContributionRecur', $recurring->id, $params);

    /**
     * second payment
     */

    $now = time()+120;
    $trxn_id2 = $trxn_id . "_2";
    $get = $post = $ids = array();
    $ids = CRM_Contribute_BAO_Contribution::buildIds($contribution->id);

    // $query = CRM_Contribute_BAO_Contribution::makeNotifyUrl($ids, NULL, $return_query = TRUE);
    parse_str($query, $get);
    // $get['is_recur'] = 1;
    $post = array(
      "Status" => "SUCCESS",
      "Message" => "委託單成立，且首次授權成功",
      "Result" => array(
        "RespondCode" => "00",
        "MerchantID" => 'abcd',
        "MerOrderNo" => $trxn_id,
        "OrderNo" => $trxn_id2,
        "TradeNo" => "16112415263934243",
        "AuthDate" => date("Y-m-d h:i:s",$now),
        "TotalTimes" => "4",
        "AlreadyTimes" => 2,
        "AuthAmt" => $amount,
        "AuthCode" => "930637",
        "EscrowBank" => "KGI",
        "AuthBank" => "KGI",
        "NextAuthDate" => "",
      ),
    );
    $post = array('Period' => _civicrm_spgateway_recur_encrypt(json_encode($post), $PaymentProcessor));
    civicrm_spgateway_ipn('Credit', $post, $get);
    // $trxn_id2 = _civicrm_spgateway_recur_trxn($trxn_id, $gwsr1);
    // $trxn_id2 = "testdev500302T368_2";

    // check second payment contribution exists
    $params = array(
      'id' => $recurring->id,
      'contribution_status_id' => 5,
    );
    $this->assertDBState('CRM_Contribute_DAO_ContributionRecur', $recurring->id, $params);

    $params = array(
      1 => array($recurring->id, 'Integer'),
    );
    $this->assertDBQuery(2, "SELECT count(*) FROM civicrm_contribution WHERE contribution_recur_id = %1", $params);

    $params = array(
      1 => array($trxn_id2, 'String'),
    );

    $this->assertDBQuery(1, "SELECT contribution_status_id FROM civicrm_contribution WHERE trxn_id = %1", $params);
    $cid2 = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_contribution WHERE trxn_id = %1", $params);

    $data = CRM_Core_DAO::singleValueQuery("SELECT data FROM civicrm_contribution_spgateway WHERE cid = $cid2");
    $this->assertNotEmpty($data, "In line " . __LINE__);

    // Check if first time data is recovered by second post.
    $this->assertDBQuery($first_data, "SELECT data FROM civicrm_contribution_spgateway WHERE cid = $contribution->id");


    /**
     * Failed
     */
    $now = time()+180;
    $trxn_id3 = $trxn_id . "_3";
    $get = $post = $ids = array();
    $ids = CRM_Contribute_BAO_Contribution::buildIds($contribution->id);

    // $query = CRM_Contribute_BAO_Contribution::makeNotifyUrl($ids, NULL, $return_query = TRUE);
    parse_str($query, $get);
    // $get['is_recur'] = 1;
    $post = array(
      "Status" => "FAILED",
      "Message" => "授權失敗",
      "Result" => array(
        "RespondCode" => "99",
        "MerchantID" => 'abcd',
        "MerOrderNo" => $trxn_id,
        "OrderNo" => $trxn_id3,
        "TradeNo" => "16112415263934243",
        "AuthDate" => date("Y-m-d h:i:s",$now),
        "TotalTimes" => "4",
        "AlreadyTimes" => 3,
        "AuthAmt" => $amount,
        "AuthCode" => "930637",
        "EscrowBank" => "KGI",
        "AuthBank" => "KGI",
        "NextAuthDate" => "",
      ),
    );
    $post = array('Period' => _civicrm_spgateway_recur_encrypt(json_encode($post), $PaymentProcessor));
    civicrm_spgateway_ipn('Credit', $post, $get);
    // $trxn_id2 = _civicrm_spgateway_recur_trxn($trxn_id, $gwsr1);
    // $trxn_id2 = "testdev500302T368_2";

    $params = array(
      'id' => $recurring->id,
      'contribution_status_id' => 5,
    );
    $this->assertDBState('CRM_Contribute_DAO_ContributionRecur', $recurring->id, $params);

    $params = array(
      1 => array($recurring->id, 'Integer'),
    );
    $this->assertDBQuery(3, "SELECT count(*) FROM civicrm_contribution WHERE contribution_recur_id = %1", $params);

    $params = array(
      1 => array($trxn_id3, 'String'),
    );

    $this->assertDBQuery(4, "SELECT contribution_status_id FROM civicrm_contribution WHERE trxn_id = %1", $params);
    $this->assertDBQuery(1, "SELECT count(*) FROM civicrm_contribution WHERE trxn_id = %1 AND receive_date IS NULL AND cancel_date IS NOT NULL AND cancel_reason IS NOT NULL", $params);
    $cid3 = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_contribution WHERE trxn_id = %1", $params);
    $data = CRM_Core_DAO::singleValueQuery("SELECT data FROM civicrm_contribution_spgateway WHERE cid = $cid3");
    $this->assertNotEmpty($data, "In line " . __LINE__);


    /**
     * Forth pay, finish recur.
     */

    $now = time()+180;
    $trxn_id4 = $trxn_id . "_4";
    $get = $post = $ids = array();
    $ids = CRM_Contribute_BAO_Contribution::buildIds($contribution->id);

    // $query = CRM_Contribute_BAO_Contribution::makeNotifyUrl($ids, NULL, $return_query = TRUE);
    parse_str($query, $get);
    // $get['is_recur'] = 1;
    $post = array(
      "Status" => "SUCCESS",
      "Message" => "委託單成立，且首次授權成功",
      "Result" => array(
        "RespondCode" => "00",
        "MerchantID" => 'abcd',
        "MerOrderNo" => $trxn_id,
        "OrderNo" => $trxn_id4,
        "TradeNo" => "16112415263934243",
        "AuthDate" => date("Y-m-d h:i:s",$now),
        "TotalTimes" => "4",
        "AlreadyTimes" => 4,
        "AuthAmt" => $amount,
        "AuthCode" => "930637",
        "EscrowBank" => "KGI",
        "AuthBank" => "KGI",
        "NextAuthDate" => "",
      ),
    );
    $post = array('Period' => _civicrm_spgateway_recur_encrypt(json_encode($post), $PaymentProcessor));
    civicrm_spgateway_ipn('Credit', $post, $get);
    // $trxn_id2 = _civicrm_spgateway_recur_trxn($trxn_id, $gwsr1);
    // $trxn_id2 = "testdev500302T368_2";

    $params = array(
      'id' => $recurring->id,
      'contribution_status_id' => 1,
    );
    $this->assertDBState('CRM_Contribute_DAO_ContributionRecur', $recurring->id, $params);

    $params = array(
      1 => array($recurring->id, 'Integer'),
    );
    $this->assertDBQuery(4, "SELECT count(*) FROM civicrm_contribution WHERE contribution_recur_id = %1", $params);

    $params = array(
      1 => array($trxn_id4, 'String'),
    );

    $this->assertDBQuery(1, "SELECT contribution_status_id FROM civicrm_contribution WHERE trxn_id = %1", $params);
    $this->assertDBQuery(1, "SELECT count(*) FROM civicrm_contribution WHERE trxn_id = %1 AND receive_date IS NOT NULL AND receive_date >= '".date('Y-m-d H:i:s', $now-3600)."'", $params);
    $cid4 = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_contribution WHERE trxn_id = %1", $params);

    $data = CRM_Core_DAO::singleValueQuery("SELECT data FROM civicrm_contribution_spgateway WHERE cid = $cid4");
    $this->assertNotEmpty($data, "In line " . __LINE__);
  }


  function testNonCreditNotify(){
    // BARCODE : 11
    $now = time()+300;
    $trxn_id = 'ut'.substr($now, -5);
    $amount = 111;

    $instrument_id = 11;
    $payment_type = 'BARCODE';

    $return_params = array(
      'Barcode_1' => 'test1',
      'Barcode_2' => 'test2',
      'Barcode_3' => 'test3',
      );

    $this->doSingleNonCreditTest(
      $now,
      $trxn_id,
      $amount,
      $instrument_id,
      $payment_type,
      $return_params
    );

    // CVS : 12
    $now = $now + 60;
    $trxn_id = 'ut'.substr($now, -5);
    $amount = 111;

    $instrument_id = 12;
    $payment_type = 'CVS';

    $return_params = array(
      'CodeNo' => '12345',
      );

    $this->doSingleNonCreditTest(
      $now,
      $trxn_id,
      $amount,
      $instrument_id,
      $payment_type,
      $return_params
    );

    // ATM : 13
    $now = $now + 60;
    $trxn_id = 'ut'.substr($now, -5);
    $amount = 111;

    $instrument_id = 13;
    $payment_type = 'VACC';

    $return_params = array(
      'PayBankCode' => '111',
      'PayerAccount5Code' => '12345',
      );

    $this->doSingleNonCreditTest(
      $now,
      $trxn_id,
      $amount,
      $instrument_id,
      $payment_type,
      $return_params
    );

    // WebATM : 14




    // update
    /*
    $cid = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_contribution_spgateway ORDER BY id DESC LIMIT 0,1");
    $_POST = array(
      'MerchantID' => $cid,
      'TEST1' => 'AAA',
      'TEST2' => 'BBB',
    );
    $_GET['q'] = 'spgateway/record';
    civicrm_spgateway_record($cid);
    $this->assertDBQuery($cid, "SELECT cid FROM civicrm_contribution_spgateway WHERE data LIKE '%#info%TEST1%' AND cid = $cid");
    */
  }

  function doSingleNonCreditTest($now, $trxn_id, $amount, $instrument_id, $payment_type, $return_params){
    // create contribution
    $contrib = array(
      'trxn_id' => $trxn_id,
      'contact_id' => $this->_cid,
      'contribution_contact_id' => $this->_cid,
      'contribution_type_id' => 1,
      'contribution_page_id' => $this->_page_id,
      'payment_processor_id' => $this->_processor['id'],
      'payment_instrument_id' => $instrument_id,
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

    // manually trigger ipn
    $afteweek = $now + ( 7 * 86400 );
    $get = $post = $ids = array();
    $ids = CRM_Contribute_BAO_Contribution::buildIds($contribution->id);
    $query = CRM_Contribute_BAO_Contribution::makeNotifyUrl($ids, NULL, $return_query = TRUE);
    parse_str($query, $get);
    $data = array(
      'MerchantID' => 'abcd',// have to modify
      'Amt' => $amount,
      'TradeNo' => '16112117153757079',
      'MerchantOrderNo' => $trxn_id,
      'PaymentType' => $payment_type,
      'ExpireDate' => date('Y-m-d',$afteweek),
    );
    $data = array_merge($data, $return_params);
    $json = json_encode($data);
    $jsonData = json_encode(array(
      'Status' => 'SUCCESS',
      'Message' => '',
      'Result' => $json,
      ));
    $post = array('JSONData' => $jsonData);
    civicrm_spgateway_ipn('Credit', $post, $get);

    // verify contribution status after trigger
    $this->assertDBCompareValue(
      'CRM_Contribute_DAO_Contribution',
      $searchValue = $contribution->id,
      $returnColumn = 'contribution_status_id',
      $searchColumn = 'id',
      $expectedValue = 1,
      "In line " . __LINE__
    );

    // verify data in drupal module
    $cid = CRM_Core_DAO::singleValueQuery("SELECT cid FROM civicrm_contribution_spgateway WHERE cid = $contribution->id");
    $this->assertNotEmpty($cid, "In line " . __LINE__);
  }

}

