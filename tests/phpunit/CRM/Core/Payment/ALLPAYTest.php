<?php

require_once 'CiviTest/CiviUnitTestCase.php';

class CRM_Core_Payment_ALLPAYTest extends CiviUnitTestCase {
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
    if(!module_exists('civicrm_allpay')){
      die("You must enable civicrm_allpay module first before test.");
    }
    $payment_page = variable_get('civicrm_demo_payment_page', array());
    $class_name = 'Payment_ALLPAY';
    if(isset($payment_page[$class_name])){
      $this->_page_id = $payment_page[$class_name];
    }
    parent::__construct();
  }

  function get_info() {
    return array(
     'name' => 'ALLPAY payment processor',
     'description' => 'Test ALLPAY payment processor.',
     'group' => 'Payment Processor Tests',
    );
  }

  function setUp() {
    parent::setUp();

    $this->_is_test = 1;

    // get processor
    $params = array(
      'version' => 3,
      'class_name' => 'Payment_ALLPAY',
      'is_test' => $this->_is_test,
    );
    $result = civicrm_api('PaymentProcessor', 'get', $params);
    $this->assertAPISuccess($result);
    if(empty($result['count'])){
      $payment_processors = array();
      $params = array(
        'version' => 3,
        'class_name' => 'Payment_ALLPAY',
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
            'password' => !empty($p['password_label']) ? 'abcd' : NULL,
            'signature' => !empty($p['signature_label']) ? 'abcd' : NULL,
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
      'class_name' => 'Payment_ALLPAY',
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
    $loaded = module_load_include('inc', 'civicrm_allpay', 'civicrm_allpay.ipn');
  }

  function tearDown() {
    $this->_processor = NULL;
  }

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
    $post = array(
      'MerchantID' => '2000132',
      'MerchantTradeNo' => $trxn_id,
      'RtnCode' => '1',
      'RtnMsg' => 'success',
      'TradeNo' => '201203151740582564',
      'TradeAmt' => $amount,
      'PaymentDate' => date('Y-m-d H:i:s', $now),
      'PaymentType' => 'Credit',
      'PaymentTypeChargeFee' => '10',
      'TradeDate' => date('Y-m-d H:i:s', $now),
      'SimulatePaid' => '1',
    );
    civicrm_allpay_ipn('Credit', $post, $get);

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
    $cid = CRM_Core_DAO::singleValueQuery("SELECT cid FROM civicrm_contribution_allpay WHERE cid = $contribution->id");
    $this->assertNotEmpty($cid, "In line " . __LINE__);
  }

  function testRecurringPaymentNotify(){
    $now = time()+60;
    $trxn_id = 'ut'.substr($now, -5);
    $amount = 111;

    // create recurring
    $date = date('YmdHis', $now);
    $recur = array(
      'contact_id' => $this->_cid,
      'amount' => $amount,
      'frequency_unit' => 'month',
      'frequency_interval' => 1,
      'installments' => 12,
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

    // create contribution (first recurring)
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
    $query = CRM_Contribute_BAO_Contribution::makeNotifyUrl($ids, NULL, $return_query = TRUE);
    parse_str($query, $get);
    $post = array(
      'MerchantID' => '2000132',
      'MerchantTradeNo' => $trxn_id,
      'RtnCode' => '1',
      'RtnMsg' => 'success',
      'TradeNo' => '201203151740582564',
      'TradeAmt' => $amount,
      'PaymentDate' => date('Y-m-d H:i:s', $now),
      'PaymentType' => 'Credit',
      'PaymentTypeChargeFee' => '10',
      'TradeDate' => date('Y-m-d H:i:s', $now),
      'SimulatePaid' => '1',
    );
    civicrm_allpay_ipn('Credit', $post, $get);

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
    $cid = CRM_Core_DAO::singleValueQuery("SELECT cid FROM civicrm_contribution_allpay WHERE cid = $contribution->id");
    $this->assertNotEmpty($cid, "In line " . __LINE__);

    // verify recurring status
    $params = array(
      'id' => $recurring->id,
      'contribution_status_id' => 5,
    );
    $this->assertDBState('CRM_Contribute_DAO_ContributionRecur', $recurring->id, $params);

    // second payment
    $now = time()+120;
    $gwsr1 = 111111;
    $get = $post = $ids = array();
    $ids = CRM_Contribute_BAO_Contribution::buildIds($contribution->id);
    $query = CRM_Contribute_BAO_Contribution::makeNotifyUrl($ids, NULL, $return_query = TRUE);
    parse_str($query, $get);
    $get['is_recur'] = 1;
    $post = array(
      'MerchantID' => '2000132',
      'MerchantTradeNo' => $trxn_id,
      'RtnCode' => '1',
      'RtnMsg' => 'success',
      'PeriodType' => 'M',
      'Frequency' => '1',
      'ExecTimes' => '12',
      'Amount' => $amount,
      'Gwsr' => $gwsr1,
      'ProcessDate' => date('Y-m-d H:i:s', $now+3600),
      'AuthCode' => '777777',
      'FirstAuthAmount' => $amount,
      'TotalSuccessTimes' => 2,
      'SimulatePaid' => '1',
    );
    civicrm_allpay_ipn('Credit', $post, $get);
    $trxn_id2 = _civicrm_allpay_recur_trxn($trxn_id, $gwsr1);

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

    $data = CRM_Core_DAO::singleValueQuery("SELECT data FROM civicrm_contribution_allpay WHERE cid = $cid2");
    $this->assertNotEmpty($data, "In line " . __LINE__);

    // use civicrm_allpay_recur_check to insert third Payment_ALLPAY
    $gwsr2 = 222222;
    $get = $post = $ids = array();
    $order_base = (object)(array(
      'ExecStatus' => '1',
      'MerchantID' => '2000132',
      'MerchantTradeNo' => $trxn_id,
      'TradeNo' => '1501101152542267',
      'RtnCode' => 1,
      'PeriodType' => 'M',
      'Frequency' => 1,
      'ExecTimes' => 99,
      'PeriodAmount' => $amount,
      'amount' => $amount,
      'gwsr' => '000000',
      'process_date' => date('Y-m-d H:i:s', $now+86400*2),
      'auth_code' => '777777',
      'card4no' => '1234',
      'card6no' => '123456',
      'TotalSuccessTimes' => 3,
      'TotalSuccessAmount' => $amount*3,
      'ExecLog' => array (
        0 => (object)(array(
           'RtnCode' => 1,
           'amount' => $amount,
           'gwsr' => '000000',
           'process_date' => date('Y-m-d H:i:s', $now),
           'auth_code' => '777777',
        )),
        1 => (object)(array(
           'RtnCode' => 1,
           'amount' => $amount,
           'gwsr' => $gwsr1,
           'process_date' => date('Y-m-d H:i:s', $now+3600),
           'auth_code' => '777777',
        )),
        2 => (object)(array(
           'RtnCode' => 1,
           'amount' => $amount,
           'gwsr' => $gwsr2,
           'process_date' => date('Y-m-d H:i:s', $now+86400*2),
           'auth_code' => '777777',
        )),
        // fail contribution from recurring
        3 => (object)(array(
           'RtnCode' => '',
           'amount' => '',
           'gwsr' => '',
           'process_date' => date('Y-m-d H:i:s', $now+86400*3),
           'auth_code' => '',
        )),
        4 => (object)(array(
           'RtnCode' => '0',
           'amount' => $amount,
           'gwsr' => '',
           'process_date' => date('Y-m-d H:i:s', $now+86400*4),
           'auth_code' => '',
        )),
        // normal contribution but empty gwsr
        5 => (object)(array(
           'RtnCode' => '1',
           'amount' => $amount,
           'gwsr' => 0,
           'process_date' => date('Y-m-d H:i:s', $now+86400*5),
           'auth_code' => '',
        )),
        // failed contribution and have gwsr
        6 => (object)(array(
           'RtnCode' => '0',
           'amount' => $amount,
           'gwsr' => '11223344',
           'process_date' => date('Y-m-d H:i:s', $now+86400*6),
           'auth_code' => '',
        )),
        // #21187 failed contribution and no gwsr, but we won't generate duplicate record
        // because we have same process date
        7 => (object)(array(
           'RtnCode' => '0',
           'amount' => $amount,
           'gwsr' => '',
           'process_date' => date('Y-m-d H:i:s', $now+86400*6),
           'auth_code' => '',
        )),
      ),
    ));
    $trxn_id3 = _civicrm_allpay_recur_trxn($trxn_id, $gwsr2);
    $order_json = json_encode($order_base);
    $order_sample = json_decode($order_json);

    // add new payment from recurring notification
    civicrm_allpay_recur_check($recurring->id, $order_sample);
    $params = array(
      'id' => $recurring->id,
      'contribution_status_id' => 5,
    );
    $this->assertDBState('CRM_Contribute_DAO_ContributionRecur', $recurring->id, $params);

    $params = array(
      1 => array($trxn_id3, 'String'),
    );
    $this->assertDBQuery(1, "SELECT contribution_status_id FROM civicrm_contribution WHERE trxn_id = %1", $params);
    $this->assertDBQuery(1, "SELECT count(*) FROM civicrm_contribution WHERE trxn_id = %1 AND receive_date IS NOT NULL AND receive_date >= '".date('Y-m-d H:i:s', $now)."'", $params);
    $cid3 = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_contribution WHERE trxn_id = %1", $params);

    $data = CRM_Core_DAO::singleValueQuery("SELECT data FROM civicrm_contribution_allpay WHERE cid = $cid3");
    $this->assertNotEmpty($data, "In line " . __LINE__);

    // fail contribution from recurring
    $hash = substr(md5(implode('', (array)$order_base->ExecLog[3])), 0, 8);
    $trxn_id4 = _civicrm_allpay_recur_trxn($trxn_id, $hash);
    $params = array(
      1 => array($trxn_id4, 'String'),
    );
    $this->assertDBQuery(4, "SELECT contribution_status_id FROM civicrm_contribution WHERE trxn_id = %1", $params);
    $this->assertDBQuery(1, "SELECT count(*) FROM civicrm_contribution WHERE trxn_id = %1 AND receive_date IS NULL AND cancel_date IS NOT NULL AND cancel_reason IS NOT NULL", $params);
    $cid4 = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_contribution WHERE trxn_id = %1", $params);
    $data = CRM_Core_DAO::singleValueQuery("SELECT data FROM civicrm_contribution_allpay WHERE cid = $cid4");
    $this->assertNotEmpty($data, "In line " . __LINE__);

    // fail contribution from recurring (new version)
    $hash = substr(md5(implode('', (array)$order_base->ExecLog[4])), 0, 8);
    $trxn_id5 = _civicrm_allpay_recur_trxn($trxn_id, $hash);
    $params = array(
      1 => array($trxn_id5, 'String'),
    );
    $this->assertDBQuery(4, "SELECT contribution_status_id FROM civicrm_contribution WHERE trxn_id = %1", $params);
    $this->assertDBQuery(1, "SELECT count(*) FROM civicrm_contribution WHERE trxn_id = %1 AND receive_date IS NULL AND cancel_date IS NOT NULL AND cancel_reason IS NOT NULL", $params);
    $cid5 = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_contribution WHERE trxn_id = %1", $params);
    $data = CRM_Core_DAO::singleValueQuery("SELECT data FROM civicrm_contribution_allpay WHERE cid = $cid5");

    $trxn_id6 = _civicrm_allpay_recur_trxn($trxn_id, '11223344');
    $params = array(
      1 => array($trxn_id6, 'String'),
    );
    $this->assertDBQuery(4, "SELECT contribution_status_id FROM civicrm_contribution WHERE trxn_id = %1", $params);

    $trxn_id7 = _civicrm_allpay_recur_trxn($trxn_id, _civicrm_allpay_noid_hash($order_base->ExecLog[7]));
    $params = array(
      1 => array($trxn_id7, 'String'),
    );
    $this->assertDBQuery(0, "SELECT count(*) FROM civicrm_contribution WHERE trxn_id = %1", $params);

    // normal contribution but empty gwsr
    // execlog 5, 7 will be skipped, so total number is 6 not 7
    $params = array(
      1 => array($recurring->id, 'Integer'),
    );
    $this->assertDBQuery(6, "SELECT count(*) FROM civicrm_contribution WHERE contribution_recur_id = %1", $params);

    // completed recurring
    $order_base->ExecStatus = 2;
    civicrm_allpay_recur_check($recurring->id, $order_base);
    $params = array(
      'id' => $recurring->id,
      'contribution_status_id' => 1,
    );
    $this->assertDBState('CRM_Contribute_DAO_ContributionRecur', $recurring->id, $params);

    // cancelled recurring
    $order_base->ExecStatus = 0;
    civicrm_allpay_recur_check($recurring->id, $order_base);
    $params = array(
      'id' => $recurring->id,
      'contribution_status_id' => 3,
    );
    $this->assertDBState('CRM_Contribute_DAO_ContributionRecur', $recurring->id, $params);
  }

  function testNonCreditNotify(){
    // update
    $cid = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_contribution_allpay ORDER BY id DESC LIMIT 0,1");
    $_POST = array(
      'MerchantID' => $cid,
      'TEST1' => 'AAA',
      'TEST2' => 'BBB',
    );
    $_GET['q'] = 'allpay/record';
    civicrm_allpay_record($cid);
    $this->assertDBQuery($cid, "SELECT cid FROM civicrm_contribution_allpay WHERE data LIKE '%#info%TEST1%' AND cid = $cid");
  }
}
