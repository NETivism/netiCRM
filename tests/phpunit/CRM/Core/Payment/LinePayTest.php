<?php
require_once 'CiviTest/CiviUnitTestCase.php';

class CRM_Core_Payment_LinePayTest extends CiviUnitTestCase {
  public $_cid;
  public $DBResetRequired = FALSE;
  protected $_apiversion;
  protected $_processor;
  protected $_is_test;
  protected $_page_id;

  function get_info() {
    return [
     'name' => 'LinePay Instrument of Mobile Payment Processor',
     'description' => 'Test LinePay instrument of Mobile payment processor.',
     'group' => 'Payment Processor Tests',
    ];
  }

  /**
   * @before
   */
  function setUpTest() {
    parent::setUp();

    $this->_is_test = 1;

    // get processor
    $params = [
      'version' => 3,
      'class_name' => 'Payment_Mobile',
      'is_test' => $this->_is_test,
    ];
    $result = civicrm_api('PaymentProcessor', 'get', $params);
    $this->assertAPISuccess($result);
    if(empty($result['count'])){
      $payment_processors = [];
      $params = [
        'version' => 3,
        'class_name' => 'Payment_Mobile',
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
      'class_name' => 'Payment_Mobile',
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
  }

  /**
   * @after
   */
  function tearDownTest() {
    $this->_processor = NULL;
  }

  function testSinglePaymentNotify(){
    
  }

  function testNonCreditNotify(){
    
  }
}
