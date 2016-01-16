<?php

require_once 'CiviTest/CiviUnitTestCase.php';

class CRM_Core_Payment_NewebTest extends CiviUnitTestCase {
  public $DBResetRequired = FALSE;
  protected $_apiversion;
  protected $_processor;
  protected $_is_test;
  protected $_page_id;
  protected $_merchant_no;
  protected $_merchant_pass;

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
    $payment_page = variable_get('civicrm_demo_payment_page', array('Payment_Neweb' => 1));
    $class_name = 'Payment_Neweb';
    if(isset($payment_page[$class_name])){
      $this->_page_id = $payment_page[$class_name];
    }

    $this->_merchant_no = "758200";
    $this->_merchant_pass = "abcd1234";    
    $this->_is_test = 1;

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
            'user_name' => !empty($p['user_name_label']) ? $this->_merchant_no : NULL,
            'password' => !empty($p['password_label']) ? $this->_merchant_no : NULL,
            'signature' => !empty($p['signature_label']) ? $this->_merchant_pass : NULL,
            'subject' => !empty($p['subject_label']) ? $this->_merchant_pass : NULL,
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
            $ftp['ftp_password'] = $this->_merchant_pass;
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
    if(substr(VERSION,0,1)=='6' || !defined('VERSION')){
      $loaded = module_load_include('inc', 'civicrm_neweb', 'civicrm_neweb.extern');  
    }elseif(substr(VERSION,0,1)=='7'){
      $loaded = module_load_include('inc', 'civicrm_neweb', 'civicrm_neweb.ipn');  
    }
    
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
    $ids = CRM_Contribute_BAO_Contribution::buildIds($contribution->id);
    $_POST = array(
      'MerchantNumber'=>$this->_merchant_no,
      'OrderNumber'=>$contribution->id,
      'Amount'=>$amount,
      'CheckSum'=>md5($this->_merchant_no.$contribution->id.'0'.'0'.$this->_merchant_pass.$amount),
      'PRC'=>'0',
      'SRC'=>'0',
      'BankResponseCode'=>'0/00',
      'BatchNumber'=>''
    );
    $_GET = array(
      "module"=>"contribute",
      "contact_id" => 1,
      "cid" => $contribution->id,
      );

    civicrm_neweb_ipn();

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
    $cid = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_contribution WHERE id = $contribution->id");
    $this->assertNotEmpty($cid, "In line " . __LINE__);
    
  }


  function testRecurringPaymentNotify(){
    $this->_is_test = 1;
    // Can't use this code to get id. so use static variable : " CRM_Core_Payment_NewebTest::$current_cid "
    $id =  CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_contribution ORDER BY id DESC LIMIT 1");
    $id = $id ? $id + 1 : 1;

    // create_date is last 15th day in month.
    $now = time();
    if(date('d',$now)>15){
      $create_date = strtotime(date('Y-m-15 H:i:s',$now));
    }else{
      $last_month = $now - 28 * 86400;
      $create_date = strtotime(date('Y-m-15 H:i:s',$last_month));
    }
    // $before_yesterday = $now - 86400 * 2;
    $trxn_id = 990000000 + $id;
    $amount = 222;

    if(date('d',$create_date) < 25){
      $cycle_day = date('d', $create_date+86400);  
    }else{
      $cycle_day = 5;  
    }
    

    // create recurring
    $date = date('YmdHis', $now);
    $by_date = date('YmdHis', $create_date);
    $recur = array(
      'contact_id' => $this->_cid,
      'amount' => $amount,
      'frequency_unit' => 'month',
      'frequency_interval' => 1,
      'installments' => 12,
      'is_test' => $this->_is_test,
      'start_date' => $by_date,
      'create_date' => $by_date,
      'modified_date' => $by_date,
      'cycle_day' => $cycle_day,
      'invoice_id' => md5($create_date),
      'contribution_status_id' => 2,
      'trxn_id' => CRM_Utils_Array::value('trxn_id', $params),
    );
    $ids = array();
    $recurring = &CRM_Contribute_BAO_ContributionRecur::add($recur, $ids);
    $params = array(
      'is_test' => $this->_is_test,
      'id' => $recurring->id,
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
      'created_date' => date('YmdHis', $create_date),
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
    $this->assertEquals($id,$contribution->id);

    // manually trigger ipn
    $get = $post = $ids = array();
    $ids = CRM_Contribute_BAO_Contribution::buildIds($contribution->id);
    $_POST = array(
      'MerchantNumber'=>$this->_merchant_no,
      'OrderNumber'=>$trxn_id,
      'Amount'=>$amount,
      'CheckSum'=>md5($this->_merchant_no.$trxn_id.'0'.'0'.$this->_merchant_pass.$amount),
      'PRC'=>'0',
      'SRC'=>'0',
      'BankResponseCode'=>'0/00',
      'BatchNumber'=>''
    );
    $_GET = array(
      "module"=>"contribute",
      "contact_id" => 1,
      "cid" => $contribution->id,
      "crid" => $recurring->id,
    );

    civicrm_neweb_ipn();

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
    $cid = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_contribution WHERE id = $contribution->id");
    $this->assertNotEmpty($cid, "In line " . __LINE__);

    $today = date('Ymd', $now);
    $_test = $this->_is_test? "_test":"";

    // Create recuring .dat file.
    $loaded = module_load_include('inc', 'civicrm_neweb', 'civicrm_neweb.cron');
    $upload_result = civicrm_neweb_process_upload($this->_is_test, $this->_processor['id']);
    $this->assertNotEmpty($upload_result, "In line " . __LINE__);

    // Check the last line is correct.
    $file_path = DRUPAL_ROOT . "/sites/default/files/neweb" . $_test ."/RP_" . $this->_merchant_no . "_" . $today . ".dat";

    $file = fopen($file_path,"r");
    while($line = fgets($file)){
      if(strpos($line,strval($trxn_id))){
        $last_line = $line;
        break;
      }
    }
    $this->assertNotEmpty($last_line, "In line " . __LINE__);
    $results = explode(",",$last_line);
    fclose($file);
    $this->assertFileExists($file_path);

    $expects = array(
      $this->_merchant_no,
      strval($recurring->id), 
      strval($trxn_id), 
      "", 
      "", 
      strval($amount), 
      strval($cycle_day), 
      "New",
      "01",
    );

    $this->assertArraySubset($expects, $results);

    // Update recuring by .out file.
    $file_path = DRUPAL_ROOT . "/sites/default/files/neweb" . $_test . "/RP_" . $this->_merchant_no . "_" . $today . ".out";
    
    $line = array("$this->_merchant_no,$recurring->id,1234********4321,VISA,202212,$amount,$cycle_day,New,01,0,0");
    write_file($file_path,$line);
    
    civicrm_neweb_process_response($this->_is_test,$now, $this->_processor['id']);


    // verify start_dat, end_date and status
    // TO DO : check if date is over 25th each month. 
    $this->assertDBCompareValue(
      'CRM_Contribute_DAO_ContributionRecur',
      $searchValue = $recurring->id,
      $returnColumn = 'start_date',
      $searchColumn = 'id',
      $expectedValue = date('Y-m-d 00:00:00', $create_date + 86400),
      "In line " . __LINE__
    );

    $after_year = strtotime(date('Y-m-d', $create_date + 86400) . " +11 month");
    $date_end_date = date('Y-m-d 00:00:00', $after_year);
    $this->assertDBCompareValue(
      'CRM_Contribute_DAO_ContributionRecur',
      $searchValue = $recurring->id,
      $returnColumn = 'end_date',
      $searchColumn = 'id',
      $expectedValue = $date_end_date,
      "In line " . __LINE__
    );

    // verify contribution status after trigger
    $this->assertDBCompareValue(
      'CRM_Contribute_DAO_ContributionRecur',
      $searchValue = $recurring->id,
      $returnColumn = 'contribution_status_id',
      $searchColumn = 'id',
      $expectedValue = 5,
      "In line " . __LINE__
    );

    // update contribution by .log files
    $next_id = $id + 1;
    $next_trxn_id = 900000000 + $next_id;
    
    $file_path = DRUPAL_ROOT . "/sites/default/files/neweb" . $_test . "/RP_Trans_" . $this->_merchant_no . "_" . $today . ".log";
    $line = array(
      "#Merchantnumber,Refnumber,Ordernumber,Httpcode,Prc,Src,Bankresponsecode,Approvalcode,Batchnumber,Orgordernumber,Mode\n",
      "$this->_merchant_no,$recurring->id,$next_trxn_id,200,0,0,0/00,654321,$today01,$today$next_trxn_id,0"
      );
    write_file($file_path,$line);

    civicrm_neweb_process_transaction($this->_is_test,$now, $this->_processor['id']);

    // verify new contribution status
    $params = array(
      'is_test' => $this->_is_test,
      'trxn_id' => $next_trxn_id,
      'contribution_status_id' => 1, 
    );
    $this->assertDBState('CRM_Contribute_DAO_Contribution', $next_id, $params);
    
  }
}

function write_file($file_path, $line_array){
  $file = fopen($file_path,"w");
  foreach ($line_array as $key => $value) {
    fwrite($file,$value);
  }
  fclose($file);
}

