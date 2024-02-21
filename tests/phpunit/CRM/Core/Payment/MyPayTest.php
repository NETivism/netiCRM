<?php

require_once 'CiviTest/CiviUnitTestCase.php';

class CRM_Core_Payment_MyPayTest extends CiviUnitTestCase {
  public $DBResetRequired = FALSE;
  protected $_apiversion;
  protected $_processor;
  protected $_is_test;
  protected $_page_id;

  function get_info() {
    return array(
     'name' => 'MyPay payment processor',
     'description' => 'Test MyPay payment processor.',
     'group' => 'Payment Processor Tests',
    );
  }

  /**
   * @before
   */
  function setUpTest() {
    parent::setUp();
    $pageId = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_contribution_page ORDER BY id");
    $this->assertNotEmpty($pageId, 'You need to have contribution page to procceed.');
    $this->_page_id = $pageId;

    $this->_is_test = 1;

    // get processor
    $params = array(
      'version' => 3,
      'class_name' => 'Payment_MyPay',
      'is_test' => $this->_is_test,
    );
    $result = civicrm_api('PaymentProcessor', 'get', $params);
    $this->assertAPISuccess($result);
    if(empty($result['count'])){
      $payment_processors = array();
      $params = array(
        'version' => 3,
        'class_name' => 'Payment_MyPay',
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
      'payment_processor_type' => 'MyPay',
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

  /**
   * @after
   */
  function tearDownTest() {
    $this->_processor = NULL;
  }
  function testSinglePaymentNotify(){
    $now = time() - 60;
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
    $uid = substr($now, -6);
    $uid_key = md5($trxn_id);
    $transationData = array(
      'uid' => $uid, // serial number of transaction of MyPay
      'uid_key' => $uid_key,
    );
    CRM_Core_Payment_MyPay::doRecordData($contribution->id, $transationData);
    $post = array(
      'uid' => $uid,
      'key' => $uid_key,
      'prc' => '250',
      'finishtime' => date('YmdHis', $now),
      'cardno' => '493817013****003',
      'acode' => 'AA1234',
      'card_type' => '1',
      'issuing_bank' => '國泰世華',
      'issuing_bank_uid' => '013',
      'is_agent_charge' => '0',
      'transaction_mode' => '1',
      'supplier_name' => '高鐵科技',
      'supplier_code' => 'T0',
      'order_id' => $trxn_id,
      'user_id' => $this->_cid,
      'cost' => $amount,
      'currency' => 'TWD',
      'actual_cost' => $amount,
      'actual_currency' => 'TWD',
      'love_cost' => '0',
      'retmsg' => '付款完成',
      'pfn' => 'CREDITCARD',
      'actual_pay_mode' => '',
      'trans_type' => '1',
      'redeem' => '',
      'installment' => '',
      'payment_name' => '',
      'nois' => '',
      'group_id' => '',
      'bank_id' => '',
      'expired_date' => '',
      'result_type' => '4',
      'result_content_type' => 'CREDITCARD',
      'result_content' => '{}',
      'echo_0' => 'contribute',
      'echo_1' => '',
      'echo_2' => '',
      'echo_3' => '',
      'echo_4' => ''
    );
    CRM_Core_Payment_MyPay::doIPN(NULL, 'Credit', $post, $get);

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
    $cid = CRM_Core_DAO::singleValueQuery("SELECT contribution_id FROM civicrm_contribution_mypay  WHERE contribution_id = $contribution->id");
    $this->assertNotEmpty($cid, "In line " . __LINE__);
  }
}

