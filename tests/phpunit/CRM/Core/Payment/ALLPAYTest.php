<?php

require_once 'CiviTest/CiviUnitTestCase.php';

class CRM_Core_Payment_ALLPAYTest extends CiviUnitTestCase {
  public $DBResetRequired = FALSE;
  protected $_apiversion;
  protected $_processor;

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

    // get processor
    $params = array(
      'version' => 3,
      'class_name' => 'Payment_ALLPAY',
      'is_test' => 1,
    );
    $result = civicrm_api('PaymentProcessor', 'get', $params);
    if(empty($result['count'])){
      $payment_processors = array();
      $params = array(
        'version' => 3,
        'class_name' => 'Payment_ALLPAY',
      );
      $result = civicrm_api('PaymentProcessorType', 'get', $params);
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
          if(is_numeric($result['id'])){
            $payment_processors[] = $result['id']; 
          }

          $payment_processor['is_test'] = 1;
          $payment_processor['url_site'] = !empty($p['url_site_test_default']) ? $p['url_site_test_default'] : NULL;
          $payment_processor['url_api'] = !empty($p['url_api_test_default']) ? $p['url_api_test_default'] : NULL;
          $result = civicrm_api('PaymentProcessor', 'create', $payment_processor);
        }
      }
    }
    $params = array(
      'version' => 3,
      'class_name' => 'Payment_ALLPAY',
      'is_test' => 1,
    );
    $result = civicrm_api('PaymentProcessor', 'get', $params);
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
    if(!empty($result['count'])){
      $this->_cid = $result['id'];
    }

    // load drupal module file
    $loaded = module_load_include('inc', 'civicrm_allpay', 'civicrm_allpay.ipn');
  }

  function tearDown() {
    $this->_processor = NULL;
  }

  function testSinglePayment(){
    $now = time();
    $trxn_id = 'ut'.substr($now, -5);

    // create contribution
    $contrib = array(
      'trxn_id' => $trxn_id, 
      'contact_id' => $this->_cid,
      'contribution_contact_id' => $this->_cid,
      'contribution_type_id' => 1,
      'contribution_page_id' => 1,
      'payment_processor_id' => $this->_processor['id'],
      'payment_instrument_id' => 1,
      'created_date' => date('YmdHis', $now),
      'non_deductible_amount' => 0,
      'total_amount' => 111,
      'currency' => 'TWD',
      'cancel_reason' => '0',
      'source' => 'AUTO: unit test',
      'contribution_source' => 'AUTO: unit test',
      'amount_level' => '',
      'is_test' => 1,
      'is_pay_later' => 0,
      'contribution_status_id' => 2,
    );
    $contribution = CRM_Contribute_BAO_Contribution::create($contrib, CRM_Core_DAO::$_nullArray);
    $this->assertNotEmpty($contribution->id, "In line " . __LINE__);
    $params = array(
      'is_test' => 1,
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
      'TradeAmt' => $contrib['total_amount'],
      'PaymentDate' => date('Y-m-d H:i:s', $now),
      'ExecTimes' => '4',
      'PaymentType' => 'Credit',
      'PaymentTypeChargeFee' => '10',
      'ProcessDate' => date('Y-m-d H:i:s', $now),
      'TradeDate' => date('Y-m-d H:i:s', $now),
      'SimulatePaid' => '1',
    );
    civicrm_allpay_ipn('Credit', $post, $get);
    $this->expectOutputString('1|OK', "In line " . __LINE__);

    $cid = db_query("SELECT cid FROM {civicrm_contribution_allpay} WHERE cid = :cid", array(':cid' => $contribution->id))->fetchField();
    $this->assertNotEmpty($cid, "In line " . __LINE__);

    // verify database record, allpay table and contribution table should have data.
    // create contirbution and payment
    // url notify to trigger payment fail
    // verify database record
  }
}
