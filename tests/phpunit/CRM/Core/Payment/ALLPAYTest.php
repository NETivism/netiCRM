<?php

require_once 'CiviTest/CiviUnitTestCase.php';

class CRM_Core_Payment_ALLPAYTest extends CiviUnitTestCase {
  public $_cid;
  public $DBResetRequired = FALSE;
  protected $_apiversion;
  protected $_processor;
  protected $_is_test;
  protected $_page_id;

  function get_info() {
    return [
      'name' => 'ALLPAY payment processor',
      'description' => 'Test ALLPAY payment processor.',
      'group' => 'Payment Processor Tests',
    ];
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
    $params = [
      'version' => 3,
      'class_name' => 'Payment_ALLPAY',
      'is_test' => $this->_is_test,
    ];
    $result = civicrm_api('PaymentProcessor', 'get', $params);
    $this->assertAPISuccess($result);
    if(empty($result['count'])){
      $payment_processors = [];
      $params = [
        'version' => 3,
        'class_name' => 'Payment_ALLPAY',
      ];
      $result = civicrm_api('PaymentProcessorType', 'get', $params);
      $this->assertAPISuccess($result);
      if(!empty($result['count'])){
        $domain_id = CRM_Core_Config::domainID();
        foreach($result['values'] as $type_id => $p){
          $payment_processor = [
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
          ];
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
    $params = [
      'version' => 3,
      'payment_processor_type' => 'ALLPAY',
      'is_test' => $this->_is_test,
    ];
    $result = civicrm_api('PaymentProcessor', 'get', $params);
    $this->assertAPISuccess($result);
    $pp = reset($result['values']);
    $this->_processor = $pp;

    // get cid
    $params = [
      'version' => 3,
      'options' => [
        'limit' => 1,
      ],
    ];
    $result = civicrm_api('Contact', 'get', $params);
    $this->assertAPISuccess($result);
    if(!empty($result['count'])){
      $this->_cid = $result['id'];
    }

    // load drupal module file
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
    $contrib = [
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
    ];
    $contribution = CRM_Contribute_BAO_Contribution::create($contrib, CRM_Core_DAO::$_nullArray);
    $this->assertNotEmpty($contribution->id, "In line " . __LINE__);
    $params = [
      'is_test' => $this->_is_test,
      'id' => $contribution->id,
    ];
    $this->assertDBState('CRM_Contribute_DAO_Contribution', $contribution->id, $params);

    // manually trigger ipn
    $get = $post = $ids = [];
    $ids = CRM_Contribute_BAO_Contribution::buildIds($contribution->id);
    $query = CRM_Contribute_BAO_Contribution::makeNotifyUrl($ids, NULL, $return_query = TRUE);
    parse_str($query, $get);
    $post = [
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
    ];
    $this->doIPN(['allpay', 'ipn', 'Credit'], $post, $get, __LINE__);

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
    $now = time();
    $trxn_id = 'ut'.substr($now, -5);
    $amount = 111;

    // create recurring
    $date = date('YmdHis', $now);
    $recur = [
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
      'trxn_id' => $trxn_id,
    ];
    $ids = [];
    $recurring = &CRM_Contribute_BAO_ContributionRecur::add($recur, $ids);

    // verify recurring status
    $params = [
      'id' => $recurring->id,
      'contribution_status_id' => 2,
    ];
    $this->assertDBState('CRM_Contribute_DAO_ContributionRecur', $recurring->id, $params);

    // create contribution (first recurring)
    $contrib = [
      'trxn_id' => $trxn_id,
      'contact_id' => $this->_cid,
      'contribution_contact_id' => $this->_cid,
      'contribution_type_id' => 1,
      'contribution_page_id' => $this->_page_id,
      'payment_processor_id' => $this->_processor['id'],
      'payment_instrument_id' => 1,
      'created_date' => date('YmdHis', $now-60),
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
    ];
    $customValues = $this->customValueGenerate('Contribution', 'postProcess');
    if (!empty($customValues)) {
      $contrib['custom'] = $customValues;
    }
    $contribution = CRM_Contribute_BAO_Contribution::create($contrib, CRM_Core_DAO::$_nullArray);
    $this->assertNotEmpty($contribution->id, "In line " . __LINE__);
    $params = [
      'is_test' => $this->_is_test,
      'id' => $contribution->id,
    ];
    $this->assertDBState('CRM_Contribute_DAO_Contribution', $contribution->id, $params);

    // manually trigger ipn
    $get = $post = $ids = [];
    $ids = CRM_Contribute_BAO_Contribution::buildIds($contribution->id);
    $query = CRM_Contribute_BAO_Contribution::makeNotifyUrl($ids, NULL, $return_query = TRUE);
    parse_str($query, $get);
    $post = [
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
    ];
    $this->doIPN(['allpay', 'ipn', 'Credit'], $post, $get, __LINE__);

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
    $params = [
      'id' => $recurring->id,
      'contribution_status_id' => 5,
    ];
    $this->assertDBState('CRM_Contribute_DAO_ContributionRecur', $recurring->id, $params);

    // second payment
    $now = time()+120;
    $gwsr1 = 111111;
    $get = $post = $ids = [];
    $ids = CRM_Contribute_BAO_Contribution::buildIds($contribution->id);
    $query = CRM_Contribute_BAO_Contribution::makeNotifyUrl($ids, NULL, $return_query = TRUE);
    parse_str($query, $get);
    $get['is_recur'] = 1;
    $post = [
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
    ];
    $this->doIPN(['allpay', 'ipn', 'Credit'], $post, $get, __LINE__);
    $trxn_id2 = CRM_Core_Payment_ALLPAY::generateRecurTrxn($trxn_id, $gwsr1);

    // check second payment contribution exists
    $params = [
      'id' => $recurring->id,
      'contribution_status_id' => 5,
    ];
    $this->assertDBState('CRM_Contribute_DAO_ContributionRecur', $recurring->id, $params);

    $params = [
      1 => [$recurring->id, 'Integer'],
    ];
    $this->assertDBQuery(2, "SELECT count(*) FROM civicrm_contribution WHERE contribution_recur_id = %1", $params);

    $params = [
      1 => [$trxn_id2, 'String'],
    ];
    $this->assertDBQuery(1, "SELECT contribution_status_id FROM civicrm_contribution WHERE trxn_id = %1", $params);
    $cid2 = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_contribution WHERE trxn_id = %1", $params);

    $data = CRM_Core_DAO::singleValueQuery("SELECT data FROM civicrm_contribution_allpay WHERE cid = $cid2");
    $this->assertNotEmpty($data, "In line " . __LINE__);

    // before doing third payment, change second payment custom field
    $updatedCustomValues = $this->customValueGenerate('Contribution');
    $updatedCustomValues['entityID'] = $cid2;
    $updatedResult = $this->customValueUpdate($cid2, $updatedCustomValues);
    $this->assertEquals(0, $updatedResult['is_error'], 'Simulate update custom values on second contribution of recurring in line '.__LINE__.'. Error message: '. $updatedResult['error_message']);
    $this->updateConfig('recurringCopySetting', 'latest');
    $config = CRM_Core_Config::singleton();
    $this->assertEquals($config->recurringCopySetting, 'latest', 'Make sure the config updated to recurringCopySetting=latest in line '.__LINE__);

    // use CRM_Core_Payment_ALLPAY::recurCheck to insert third Payment_ALLPAY
    $gwsr2 = 222222;
    $get = $post = $ids = [];
    $order_base = (object)([
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
      'ExecLog' =>  [
        0 => (object)([
          'RtnCode' => 1,
          'amount' => $amount,
          'gwsr' => '000000',
          'process_date' => date('Y-m-d H:i:s', $now),
          'auth_code' => '777777',
        ]),
        1 => (object)([
          'RtnCode' => 1,
          'amount' => $amount,
          'gwsr' => $gwsr1,
          'process_date' => date('Y-m-d H:i:s', $now+3600),
          'auth_code' => '777777',
        ]),
        2 => (object)([
          'RtnCode' => 1,
          'amount' => $amount,
          'gwsr' => $gwsr2,
          'process_date' => date('Y-m-d H:i:s', $now+86400*2),
          'auth_code' => '777777',
        ]),
        // fail contribution from recurring
        3 => (object)([
          'RtnCode' => '',
          'amount' => '',
          'gwsr' => '',
          'process_date' => date('Y-m-d H:i:s', $now+86400*3),
          'auth_code' => '',
        ]),
        4 => (object)([
          'RtnCode' => '0',
          'amount' => $amount,
          'gwsr' => '',
          'process_date' => date('Y-m-d H:i:s', $now+86400*4),
          'auth_code' => '',
        ]),
        // normal contribution but empty gwsr
        5 => (object)([
          'RtnCode' => '1',
          'amount' => $amount,
          'gwsr' => 0,
          'process_date' => date('Y-m-d H:i:s', $now+86400*5),
          'auth_code' => '',
        ]),
        // failed contribution and have gwsr
        6 => (object)([
          'RtnCode' => '0',
          'amount' => $amount,
          'gwsr' => '11223344',
          'process_date' => date('Y-m-d H:i:s', $now+86400*6),
          'auth_code' => '',
        ]),
      ],
    ]);
    $trxn_id3 = CRM_Core_Payment_ALLPAY::generateRecurTrxn($trxn_id, $gwsr2);
    $order_json = json_encode($order_base);
    $order_sample = json_decode($order_json);

    // add new payment from recurring notification
    CRM_Core_Payment_ALLPAY::recurCheck($recurring->id, $order_sample);
    $params = [
      'id' => $recurring->id,
      'contribution_status_id' => 5,
    ];
    $this->assertDBState('CRM_Contribute_DAO_ContributionRecur', $recurring->id, $params);

    $params = [
      1 => [$trxn_id3, 'String'],
    ];
    $this->assertDBQuery(1, "SELECT contribution_status_id FROM civicrm_contribution WHERE trxn_id = %1", $params);
    $this->assertDBQuery(1, "SELECT count(*) FROM civicrm_contribution WHERE trxn_id = %1 AND receive_date IS NOT NULL AND receive_date >= '".date('Y-m-d H:i:s', $now)."'", $params);
    $cid3 = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_contribution WHERE trxn_id = %1", $params);

    $data = CRM_Core_DAO::singleValueQuery("SELECT data FROM civicrm_contribution_allpay WHERE cid = $cid3");
    $this->assertNotEmpty($data, "In line " . __LINE__);

    $this->updateConfig('recurringCopySetting', 'earliest');
    $this->assertCustomValues('Contribution', $cid3, $updatedCustomValues, 'Make sure custom values follow the config recurringCopySetting=latest in line '.__LINE__);

    // fail contribution from recurring
    $hash = substr(md5(implode('', (array)$order_base->ExecLog[3])), 0, 8);
    $trxn_id4 = CRM_Core_Payment_ALLPAY::generateRecurTrxn($trxn_id, $hash);
    $params = [
      1 => [$trxn_id4, 'String'],
    ];
    $this->assertDBQuery(4, "SELECT contribution_status_id FROM civicrm_contribution WHERE trxn_id = %1", $params);
    $this->assertDBQuery(1, "SELECT count(*) FROM civicrm_contribution WHERE trxn_id = %1 AND receive_date IS NULL AND cancel_date IS NOT NULL AND cancel_reason IS NOT NULL", $params);
    $cid4 = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_contribution WHERE trxn_id = %1", $params);
    $data = CRM_Core_DAO::singleValueQuery("SELECT data FROM civicrm_contribution_allpay WHERE cid = $cid4");
    $this->assertNotEmpty($data, "In line " . __LINE__);

    // fail contribution from recurring (new version)
    $hash = substr(md5(implode('', (array)$order_base->ExecLog[4])), 0, 8);
    $trxn_id5 = CRM_Core_Payment_ALLPAY::generateRecurTrxn($trxn_id, $hash);
    $params = [
      1 => [$trxn_id5, 'String'],
    ];
    $this->assertDBQuery(4, "SELECT contribution_status_id FROM civicrm_contribution WHERE trxn_id = %1", $params);
    $this->assertDBQuery(1, "SELECT count(*) FROM civicrm_contribution WHERE trxn_id = %1 AND receive_date IS NULL AND cancel_date IS NOT NULL AND cancel_reason IS NOT NULL", $params);
    $cid5 = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_contribution WHERE trxn_id = %1", $params);
    $data = CRM_Core_DAO::singleValueQuery("SELECT data FROM civicrm_contribution_allpay WHERE cid = $cid5");

    $trxn_id6 = CRM_Core_Payment_ALLPAY::generateRecurTrxn($trxn_id, '11223344');
    $params = [
      1 => [$trxn_id6, 'String'],
    ];
    $this->assertDBQuery(4, "SELECT contribution_status_id FROM civicrm_contribution WHERE trxn_id = %1", $params);

    // normal contribution but empty gwsr
    // execlog 5 will be skipped, so total number is 6
    $params = [
      1 => [$recurring->id, 'Integer'],
    ];
    $this->assertDBQuery(6, "SELECT count(*) FROM civicrm_contribution WHERE contribution_recur_id = %1", $params);

    // refs #21187, submit again but change gwsr data (simulate gw bad api)
    // we should still 6 records
    $order_base->ExecLog[6]->gwsr = 0;
    CRM_Core_Payment_ALLPAY::recurCheck($recurring->id, $order_base);
    $params = [
      1 => [$recurring->id, 'Integer'],
    ];
    $this->assertDBQuery(6, "SELECT count(*) FROM civicrm_contribution WHERE contribution_recur_id = %1", $params);

    // completed recurring
    $order_base->ExecStatus = 2;
    CRM_Core_Payment_ALLPAY::recurCheck($recurring->id, $order_base);
    $params = [
      'id' => $recurring->id,
      'contribution_status_id' => 1,
    ];
    $this->assertDBState('CRM_Contribute_DAO_ContributionRecur', $recurring->id, $params);

    // cancelled recurring
    $order_base->ExecStatus = 0;
    CRM_Core_Payment_ALLPAY::recurCheck($recurring->id, $order_base);
    $params = [
      'id' => $recurring->id,
      'contribution_status_id' => 3,
    ];
    $this->assertDBState('CRM_Contribute_DAO_ContributionRecur', $recurring->id, $params);
  }

  function testNonCreditNotify(){
    // update
    $cid = CRM_Core_DAO::singleValueQuery("SELECT cid FROM civicrm_contribution_allpay ORDER BY cid DESC LIMIT 0,1");
    $_POST = [
      'MerchantID' => $cid,
      'TEST1' => 'AAA',
      'TEST2' => 'BBB',
    ];
    $_GET['q'] = 'allpay/record';
    CRM_Core_Payment_ALLPAYIPN::doRecordData(['allpay', 'record', $cid]);
    $this->assertDBQuery($cid, "SELECT cid FROM civicrm_contribution_allpay WHERE data LIKE '%#info%TEST1%' AND cid = $cid");
  }

  function doIPN($args, $post, $get, $line) {
    try {
      CRM_Core_Payment_ALLPAY::doIPN($args, $post, $get);
    }
    catch (CRM_Core_Exception $e) {
      $message = $e->getMessage();
      $data = $e->getErrorData();
      $code = $e->getErrorCode();
      if ($code != CRM_Core_Error::NO_ERROR) {
        throw new Exception($message.' at line '.$line, $code);
      }
    }
  }
}
