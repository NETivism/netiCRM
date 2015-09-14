<?php

require_once 'CiviTest/CiviUnitTestCase.php';

class CRM_Core_Payment_NewebTest extends CiviUnitTestCase {
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
    if(!module_exists('civicrm_neweb')){
      die("You must enable civicrm_neweb module first before test.");
    }
    $payment_page = variable_get('civicrm_demo_payment_page', array());
    $class_name = 'Payment_Neweb';
    if(isset($payment_page[$class_name])){
      $this->_page_id = $payment_page[$class_name];
    }
    parent::__construct();
  }

  function get_info() {
    return array(
     'name' => 'Neweb payment processor',
     'description' => 'Test Neweb payment processor.',
     'group' => 'Payment Processor Tests',
    );
  }

  function setUp() {
    parent::setUp();

    $this->_is_test = 1;

    // get processor
    $params = array(
      'version' => 3,
      'class_name' => 'Payment_Neweb',
      'is_test' => $this->_is_test,
    );
    $result = civicrm_api('PaymentProcessor', 'get', $params);
    $this->assertAPISuccess($result);
    if(empty($result['count'])){
      $payment_processors = array();
      $params = array(
        'version' => 3,
        'class_name' => 'Payment_Neweb',
      );
      $result = civicrm_api('PaymentProcessorType', 'get', $params);
      // print_r($result, TRUE);
      // var_dump($result, TRUE);
      // var_export($result, TRUE);
      fwrite(STDERR, print_r($result, TRUE));
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
            'user_name' => !empty($p['user_name_label']) ? '123456' : NULL,
            'password' => !empty($p['password_label']) ? '123456' : NULL,
            'signature' => !empty($p['signature_label']) ? 'xxxx' : NULL,
            'subject' => !empty($p['subject_label']) ? 'xxxx' : NULL,
            'url_site' => !empty($p['url_site_default']) ? $p['url_site_default'] : NULL,
            'url_api' => !empty($p['url_api_default']) ? $p['url_api_default'] : NULL,
            'url_recur' => !empty($p['url_site_default']) ? $p['url_site_default'] : NULL,
            'class_name' => $p['class_name'],
            'billing_mode' => $p['billing_mode'],
            'is_recur' => $p['is_recur'],
            'payment_type' => $p['payment_type'],
          );
          $result = civicrm_api('PaymentProcessor', 'create', $payment_processor);
          $this->assertAPISuccess($result);
          if(is_numeric($result['id'])){
            $ftp = array();
            $ftp['ftp_host'] = '127.0.0.1';
            $ftp['ftp_user'] = 'user'; 
            $ftp['ftp_password'] = 'xxxx';
            variable_set("civicrm_neweb_ftp_".$result['id'], $ftp);
            $payment_processors[] = $result['id'];
          }

          $payment_processor['is_test'] = 1;
          $payment_processor['url_site'] = !empty($p['url_site_test_default']) ? $p['url_site_test_default'] : NULL;
          $payment_processor['url_api'] = !empty($p['url_api_test_default']) ? $p['url_api_test_default'] : NULL;
          $payment_processor['url_recur'] = !empty($p['url_site_test_default']) ? $p['url_site_test_default'] : NULL;
          $result = civicrm_api('PaymentProcessor', 'create', $payment_processor);
          if(is_numeric($result['id'])){
            variable_set("civicrm_neweb_ftp_test_".$result['id'], $ftp);
            $payment_processors[] = $result['id'];
          }
          $this->assertAPISuccess($result);
        }
      }
    }
    $params = array(
      'version' => 3,
      'class_name' => 'Payment_Neweb',
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
    $loaded = module_load_include('inc', 'civicrm_neweb', 'civicrm_neweb.ipn');
  }

  function tearDown() {
    $this->_processor = NULL;
  }

  function testSinglePaymentNotify(){
    $now = time();
    $trxn_id = substr($now, -5);
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
    /*
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
    civicrm_neweb_ipn('Credit', $post, $get);

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
    $cid = db_query("SELECT cid FROM {civicrm_contribution_allpay} WHERE cid = :cid", array(':cid' => $contribution->id))->fetchField();
    $this->assertNotEmpty($cid, "In line " . __LINE__);
    */
  }

}







/**
 * $signature 來自金流的設定
 * 但必須要知道金流機制的 ID 才行
 */

/*
function my_curl($url,$post)
{
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  //curl_setopt($ch, CURLOPT_POST,1);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST,  'POST');
  curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
  $result = curl_exec($ch);
  curl_close ($ch);
  return $result;
}

$signature = "abcd1234";

$data = array(
  // 'final_result'=>'1',
  'MerchantNumber'=>$_POST['MerchantNumber'],
  'OrderNumber'=>$_POST['OrderNumber'],
  'Amount'=>$_POST['Amount'],
  'CheckSum'=>md5($_POST['MerchantNumber'].$_POST['OrderNumber'].$_POST['PRC'].$_POST['SRC'].$signature.$_POST['Amount']),
  'PRC'=>'0',
  'SRC'=>'0',
  // 'ApproveCode'=>'ET7373',
  'BankResponseCode'=>'0/00',
  'BatchNumber'=>''
,);

// print_r($data);

$getBody = my_curl($_POST['OrderURL'],$data);
print($getBody);

*/
