<?php

require_once 'CiviTest/CiviUnitTestCase.php';

class CRM_Core_Payment_BackerTest extends CiviUnitTestCase {
  public $DBResetRequired = FALSE;
  protected $_processor;
  protected $_isTest;
  protected $_pageId;
  protected $_payment;
  protected $_trxnId;
  protected $_signature;
  protected $_json;
  static $_rtypeId;

  function get_info() {
    return [
      'name' => 'Backer payment processor',
      'description' => 'Test Backer payment processor.',
      'group' => 'Payment Processor Tests',
    ];
  }

  /**
   * @before
   */
  function setUpTest() {
    // login by user 1 to get customfield
    CRM_Utils_System::loadUser(['uid' => 1]);
    parent::setUp();
    $pages = CRM_Contribute_PseudoConstant::contributionPage();
    $this->_pageId = key($pages);
    $this->assertNotEmpty($this->_pageId, "In line " . __LINE__);

    $this->_isTest = 1;

    // get processor
    $params = [
      'version' => 3,
      'class_name' => 'Payment_Backer',
      'is_test' => $this->_isTest,
    ];
    $result = civicrm_api('PaymentProcessor', 'get', $params);
    $this->assertAPISuccess($result);
    if(empty($result['count'])){
      $payment_processors = [];
      $params = [
        'version' => 3,
        'class_name' => 'Payment_Backer',
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
            'user_name' => !empty($p['user_name_label']) ? $this->_pageId : NULL,
            'password' => !empty($p['password_label']) ? '1234' : NULL,
            'signature' => NULL,
            'subject' => NULL,
            'url_site' => NULL,
            'url_api' => NULL,
            'url_recur' => NULL,
            'class_name' => $p['class_name'],
            'billing_mode' => $p['billing_mode'],
            'is_recur' => $p['is_recur'],
            'payment_type' => $p['payment_type'],
          ];
          $result = civicrm_api('PaymentProcessor', 'create', $payment_processor);
          $this->assertAPISuccess($result);

          $payment_processor['is_test'] = 1;
          $result = civicrm_api('PaymentProcessor', 'create', $payment_processor);
          $this->assertAPISuccess($result);
        }
      }
    }
    $params = [
      'version' => 3,
      'class_name' => 'Payment_Backer',
      'is_test' => $this->_isTest,
    ];
    $result = civicrm_api('PaymentProcessor', 'get', $params);
    $this->assertAPISuccess($result);
    $pp = reset($result['values']);
    $this->_payment = $pp;
    $this->_processor = CRM_Core_Payment::singleton('live', $this->_payment);

    $json = <<< EOT
{
  "transaction": {
    "trade_no": "REG4829201627459123",
    "money": "8000.0",
    "created_at": "2021-07-28T16:06:27.063+08:00",
    "updated_at": "2021-07-28T16:06:27.063+08:00",
    "quantity": 1,
    "render_status": "success",
    "type": "normal",
    "items": {
      "id": 3347123,
      "reward_id": 123,
      "reward_name": "OOOO",
      "quantity": 1,
      "money": "7490.0",
      "note": "",
      "custom_fields": [
        {
          "id": 42082,
          "field_type": "checkbox",
          "name": "詳細介紹",
          "is_required": false,
          "value": "yes"
        },
        {
          "id": 42566,
          "field_type": "checkbox",
          "name": "加贈手機殼！",
          "is_required": false,
          "value": "yes"
        },
        {
          "id": 42118,
          "field_type": "checkbox",
          "name": "方案總覽",
          "is_required": false,
          "value": "yes"
        },
        {
          "id": 42093,
          "field_type": "select_box",
          "name": "是否需要收據",
          "is_required": true,
          "value": "需要（請寄給我紙本收據）"
        },
        {
          "id": 42083,
          "field_type": "select_box",
          "name": "請選擇Tshirt尺寸",
          "is_required": false,
          "value": "M"
        },
        {
          "id": 42094,
          "field_type": "text",
          "name": "收據抬頭",
          "is_required": false,
          "value": "王測試"
        },
        {
          "id": 42095,
          "field_type": "text",
          "name": "報稅憑證",
          "is_required": false,
          "value": "A123123120"
        },
        {
          "id": 42096,
          "field_type": "text",
          "name": "捐款徵信名稱",
          "is_required": false,
          "value": "ABC"
        }
      ]
    }
  },
  "payment": {
    "type": "credit",
    "paid_at": "2021-07-28T16:06:27.063+08:00",
    "next_paid_time": "",
    "next_paid_amount": "",
    "log": "",
    "refund_at": null
  },
  "user": {
    "id": 482920,
    "email": "admintest@example.com",
    "name": "王測試",
    "cellphone": "+886900111222"
  },
  "recipient": {
    "recipient_name": "王測試",
    "recipient_contact_email": "admintest@eaxmple.com",
    "recipient_cellphone": "0900222333",
    "recipient_address": "泉州路2之xxx號",
    "recipient_postal_code": "421",
    "recipient_country": "TW",
    "recipient_subdivision": "TXG",
    "recipient_cityarea": "后里區"
  }
}
EOT;
    $jsonArray = json_decode($json, TRUE);
    // randomize trade_no that test can run again
    $jsonArray['transaction']['trade_no'] = CRM_Utils_String::createRandom(16, CRM_Utils_String::ALPHANUMERIC);
    $this->_trxnId[1] = $jsonArray['transaction']['trade_no'];
    $this->_json[1] = json_encode($jsonArray);
    $this->_signature[1] = hash_hmac('sha1', $this->_json[1], '1234');

    // json object 2
    $json = <<< EOT
{
  "transaction": {
    "trade_no": "SUB367927166994321",
    "money": "230.0",
    "created_at": "2022-12-02T10:18:46.256+08:00",
    "updated_at": "2022-12-02T10:18:46.507+08:00",
    "quantity": 1,
    "flag": null,
    "render_status": "success",
    "type": "child",
    "items": {
      "id": 4246283,
      "reward_id": 23431,
      "reward_name": "test reward name",
      "quantity": 1,
      "money": "100.0",
      "note": "",
      "custom_fields": [

      ]
    }
  },
  "payment": {
    "type": "credit",
    "paid_at": "2022-12-02T10:18:46.256+08:00",
    "next_paid_time": "2023-01-02T10:18:00.995+08:00",
    "next_paid_amount": "100.0",
    "log": "",
    "refund_at": null
  },
  "user": {
    "id": 982928,
    "email": "admintest2@example.com",
    "name": "陳測試",
    "cellphone": "+886900111333"
  },
  "recipient": {
    "recipient_name": "陳先生",
    "recipient_contact_email": "admintest3@example.com",
    "recipient_cellphone": "+886900111333",
    "recipient_address": "三重路一段3號5樓",
    "recipient_postal_code": "302",
    "recipient_country": "TW",
    "recipient_subdivision": "HSQ",
    "recipient_cityarea": "竹北市"
  },
  "receipt": {
    "receipt_type": "紙本收據",
    "choice": "單次寄送紙本收據",
    "contact_name": "稅捐收據抬頭",
    "identity_card_number": "1234567890",
    "country": "TW",
    "subdivision": "HSQ",
    "city_area": "竹北市",
    "postal_code": "302",
    "address": "三重路一段3號5樓"
  }
}
EOT;
    $jsonArray = json_decode($json, TRUE);
    // randomize trade_no that test can run again
    $jsonArray['transaction']['trade_no'] = CRM_Utils_String::createRandom(16, CRM_Utils_String::ALPHANUMERIC);
    $this->_trxnId[2] = $jsonArray['transaction']['trade_no'];
    $this->_json[2] = json_encode($jsonArray);
    $this->_signature[2] = hash_hmac('sha1', $this->_json[2], '1234');

    // json object 3 for recurring main data
    $json = <<< EOT
{
  "transaction": {
  "trade_no": "SUB3679271678955153",
  "money": "20.0",
  "created_at": "2023-03-16T16:24:57.060+08:00",
  "updated_at": "2023-03-16T16:25:54.100+08:00",
  "quantity": 1,
  "flag": null,
  "render_status": "recurring",
  "type": "parent",
  "parent_trade_no": null,
  "items": {
  "id": 4425501,
  "reward_id": 25048,
  "reward_name": "test reward name",
  "quantity": 1,
  "money": "20.0",
  "note": "",
  "custom_fields": [
    {
      "id": 74879,
      "field_type": "select_box",
      "name": "下拉選單",
      "is_required": false,
      "value": "選項 2"
    },
    {
      "id": 74880,
      "field_type": "text",
      "name": "捐款徵信名稱",
      "is_required": false,
      "value": "捐款徵信名稱捐款徵信名稱"
    },
    {
      "id": 74878,
      "field_type": "checkbox",
      "name": "核取方塊",
      "is_required": false,
      "value": "yes"
    }
    ]
  }
  },
  "payment": {
    "type": "period",
    "paid_at": "2023-03-16T16:24:57.060+08:00",
    "next_paid_time": "",
    "next_paid_amount": "",
    "log": "",
    "refund_at": null
  },
  "user": {
    "id": 482920,
    "email": "admintest@example.com",
    "name": "王測試",
    "cellphone": "+886900111222"
  },
  "recipient": {
    "recipient_name": "王測試",
    "recipient_contact_email": "admintest@eaxmple.com",
    "recipient_cellphone": "0900222333",
    "recipient_address": "泉州路2之xxx號",
    "recipient_postal_code": "421",
    "recipient_country": "TW",
    "recipient_subdivision": "TXG",
    "recipient_cityarea": "后里區"
  },
  "receipt": {
    "receipt_type": "紙本收據",
    "choice": "單次寄送紙本收據",
    "contact_name": "稅捐收據抬頭",
    "identity_card_number": "1234567890",
    "country": "TW",
    "subdivision": "HSQ",
    "city_area": "竹北市",
    "postal_code": "302",
    "address": "三重路一段3號5樓"
  }
}
EOT;
    $jsonArray = json_decode($json, TRUE);
    $this->_json[3] = json_encode($jsonArray);
    $this->_signature[3] = hash_hmac('sha1', $this->_json[3], '1234');

    // json object 4 for recurring sub data
    $json = <<< EOT
{
  "transaction": {
  "trade_no": "SUB3679271678955097",
  "money": "20.0",
  "created_at": "2023-03-16T16:25:54.276+08:00",
  "updated_at": "2023-03-16T16:25:54.431+08:00",
  "quantity": 1,
  "flag": null,
  "render_status": "success",
  "type": "child",
  "parent_trade_no": "SUB3679271678955153",
  "items": {
    "id": 4425502,
    "reward_id": 25048,
    "reward_name": "test reward name",
    "quantity": 1,
    "money": "20.0",
    "note": "",
    "custom_fields": [ {
      "id": 74879,
      "field_type": "select_box",
      "name": "下拉選單",
      "is_required": false,
      "value": "選項 2"
    }, {
      "id": 74880,
      "field_type": "text",
      "name": "捐款徵信名稱",
      "is_required": false,
      "value": "捐款徵信名稱捐款徵信名稱"
    }, {
      "id": 74878,
      "field_type": "checkbox",
      "name": "核取方塊",
      "is_required": false,
      "value": "yes"
    }
    ]}
  },
  "payment": {
    "type": "credit",
    "paid_at": "2023-03-16T16:25:54.276+08:00",
    "next_paid_time": "2023-03-18T16:24:57.060+08:00",
    "next_paid_amount": "20.0",
    "log": "",
    "refund_at": null
  },
  "user": {
    "id": 482920,
    "email": "admintest@example.com",
    "name": "王測試",
    "cellphone": "+886900111222"
  },
  "recipient": {
    "recipient_name": "王測試",
    "recipient_contact_email": "admintest@eaxmple.com",
    "recipient_cellphone": "0900222333",
    "recipient_address": "泉州路2之xxx號",
    "recipient_postal_code": "421",
    "recipient_country": "TW",
    "recipient_subdivision": "TXG",
    "recipient_cityarea": "后里區"
  },
  "receipt": {
    "receipt_type": "紙本收據",
    "choice": "單次寄送紙本收據",
    "contact_name": "稅捐收據抬頭",
    "identity_card_number": "1234567890",
    "country": "TW",
    "subdivision": "HSQ",
    "city_area": "竹北市",
    "postal_code": "302",
    "address": "三重路一段3號5樓"
  }
}
EOT;
    $jsonArray = json_decode($json, TRUE);
    $this->_json[4] = json_encode($jsonArray);
    $this->_signature[4] = hash_hmac('sha1', $this->_json[4], '1234');

    // relationship type
    if (!$this->_rtypeId) {
      $rtypeId = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_relationship_type WHERE label_a_b = 'Orderer' AND label_b_a = 'Recipient'");
      if (!$rtypeId) {
        $params = [
          'label_a_b' => 'Orderer',
          'label_b_a' => 'Recipient',
          'description' => "Used for".': '.'Backer Auto Import',
          'is_active' => 1,
          'is_reserved' => 1,
          'contact_type_a' => '',
          'contact_type_b' => '',
          'contact_types_a' => '',
          'contact_types_b' => '',
          'contact_sub_type_a' => '',
          'contact_sub_type_b' => '',
        ];
        $ids = [];
        $saved = CRM_Contact_BAO_RelationshipType::add($params, $ids);
        $rtypeId = $saved->id;
      }
      $params = [
        'backerFounderRelationship' => $rtypeId,
      ];
      CRM_Core_BAO_ConfigSetting::add($params);
      $config =& CRM_Core_Config::singleton();
      $config->backerFounderRelationship = $rtypeId;
    }
    $this->_rtypeId = $rtypeId;
  }

  /**
   * @after
   */
  function tearDownTest() {
  }

  function testBackerIPN(){
    $contributionResult = [];
    $now = time();
    $hash = hash_hmac('sha1', $this->_json[1], $this->_payment['password']);
    $this->assertEquals($hash, $this->_signature[1]);

    $formatted = CRM_Core_Payment_Backer::formatParams($this->_json[1]);
    $createdContributionId = $this->_processor->processContribution($this->_json[1], $contributionResult);
    $createdContributionId = $contributionResult['contributionId'];
    $this->assertNotEmpty($createdContributionId, "In line " . __LINE__);

    // verify all contribution saved data
    $params = [
      'trxn_id' => $this->_trxnId[1],
      'payment_instrument_id' => $formatted['contribution']['payment_instrument_id'],
      'total_amount' => $formatted['contribution']['total_amount'],
      'contribution_status_id' => $formatted['contribution']['contribution_status_id'],
      'currency' => $formatted['contribution']['currency'],
      'payment_processor_id' => $this->_payment['id'],
    ];
    $this->assertDBState('CRM_Contribute_DAO_Contribution', $createdContributionId, $params);

    // verify all custom fields saved correctly
    $params = [
      'version' => 3,
      'entity_table' => 'civicrm_contribution',
      'entity_id' => $createdContributionId,
    ];
    $result = civicrm_api('CustomValue', 'get', $params);
    $this->assertAPISuccess($result);
    foreach($formatted['contribution'] as $key => $value) {
      if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($key)) {
        $this->assertEquals($value, $result['values'][$customFieldID][0]);
      }
    }

    // verify contact data
    $contactId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $createdContributionId, 'contact_id');
    $params = [
      'id' => $contactId,
      'last_name' => $formatted['contact']['last_name'],
      'first_name' => $formatted['contact']['first_name'],
      'external_identifier' => $formatted['contact']['external_identifier'],
    ];
    $this->assertDBState('CRM_Contact_DAO_Contact', $contactId, $params);

    $params = [
      'version' => 3,
      'id' => $contactId,
    ];
    $result = civicrm_api('contact', 'get', $params);
    $this->assertAPISuccess($result);

    // address, email, phone
    $this->assertEquals($formatted['address'][0]['street_address'], $result['values'][$result['id']]['street_address']);
    $this->assertEquals($formatted['email'][0]['email'], $result['values'][$result['id']]['email']);
    $this->assertEquals($formatted['phone'][0]['phone'], $result['values'][$result['id']]['phone']);
  }

  function testBackerAdditionalContact(){
    $now = time();
    $contributionResult = NULL;

    // case 1, this should create additional contact
    $json = json_decode($this->_json[1], TRUE);
    $json['transaction']['trade_no'] = CRM_Utils_String::createRandom(15);
    $json['user']['id'] = mt_rand(100000, 999999);
    $json['user']['email'] = 'admintest.main1@eaxmple.com';
    $json['user']['name'] = '張小弟';
    $json['user']['cellphone'] = '+886912445667';
    $json['recipient']['recipient_name'] = '張大媽';
    $json['recipient']['recipient_contact_email'] = 'admintest.other1@eaxmple.com';
    $json['recipient']['recipient_cellphone'] = '0933444555';
    $json = json_encode($json);
    $formatted = CRM_Core_Payment_Backer::formatParams($json);

    $this->_processor->processContribution($json, $contributionResult);
    $createdContributionId = $contributionResult['contributionId'];
    $this->assertNotEmpty($createdContributionId, "Contribution not created. In line " . __LINE__);
    $contactId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $createdContributionId, 'contact_id');

    $params = [
      'version' => 3,
      'last_name' => $formatted['additional']['last_name'],
      'first_name' => $formatted['additional']['first_name'],
    ];
    $result = civicrm_api('contact', 'get', $params);
    $this->assertAPISuccess($result);
    $this->assertGreaterThan(0 , $result['count'], 'Additional contact count should greater than zero. In line '.__LINE__);
    $additionalContact = reset($result['values']);
    $additionalContactId1 = $additionalContact['contact_id'];
    $this->assertEquals($formatted['additional']['address'][0]['street_address'], $additionalContact['street_address']);
    $this->assertEquals($formatted['additional']['phone'][0]['phone'], $additionalContact['phone']);
    $this->assertEquals($formatted['additional']['email'][0]['email'], $additionalContact['email']);

    $params = [
      'version' => 3,
      'contact_id_a' => $contactId,
      'contact_id_b' => $additionalContactId1,
      'relationship_type_id' => $this->_rtypeId,
    ];
    $relation = civicrm_api('contact', 'get', $params);
    $this->assertAPISuccess($relation);
    $this->assertGreaterThan(0 , $relation['count'], 'Relationship result should greater than zero. In line '.__LINE__);

    // duplicate case 1, won't add another contact
    $formatted = CRM_Core_Payment_Backer::formatParams($json);
    $this->_processor->processContribution($json, $contributionResult);
    $params = [
      'version' => 3,
      'last_name' => $formatted['additional']['last_name'],
      'first_name' => $formatted['additional']['first_name'],
    ];
    $result = civicrm_api('contact', 'get', $params);
    $this->assertAPISuccess($result);
    $this->assertEquals(1 , $result['count'], 'Additional contact count should be 1. In line '.__LINE__);

    $params = [
      'version' => 3,
      'last_name' => $formatted['additional']['last_name'],
      'first_name' => $formatted['additional']['first_name'],
    ];
    $result = civicrm_api('contact', 'get', $params);
    $this->assertAPISuccess($result);
    $this->assertGreaterThan(0 , $result['count'], 'Additional contact count should greater than zero. In line '.__LINE__);
    $additionalContact = reset($result['values']);
    $additionalContactId1 = $additionalContact['contact_id'];
    $this->assertEquals($formatted['additional']['address'][0]['street_address'], $additionalContact['street_address']);
    $this->assertEquals($formatted['additional']['phone'][0]['phone'], $additionalContact['phone']);
    $this->assertEquals($formatted['additional']['email'][0]['email'], $additionalContact['email']);

    // case 2, additional contact shouldn't be create again when missing email
    $json = json_decode($this->_json[1], TRUE);
    $json['transaction']['trade_no'] = CRM_Utils_String::createRandom(15);
    $json['user']['id'] = mt_rand(100000, 999999);
    $json['user']['email'] = 'admintest.main2@eaxmple.com';
    $json['user']['name'] = '陳小刀';
    $json['user']['cellphone'] = '+886900111222';
    $json['recipient']['recipient_name'] = '陳小刀';
    unset($json['recipient']['recipient_contact_email']);
    $json['recipient']['recipient_cellphone'] = '0900111222';
    $json = json_encode($json);
    $formatted = CRM_Core_Payment_Backer::formatParams($json);

    $contributionResult = NULL;
    $this->_processor->processContribution($json, $contributionResult);
    $createdContributionId = $contributionResult['contributionId'];
    $this->assertNotEmpty($createdContributionId, "Contribution not created. In line " . __LINE__);
    $contactId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $createdContributionId, 'contact_id');

    $params = [
      'version' => 3,
      'last_name' => $formatted['additional']['last_name'],
      'first_name' => $formatted['additional']['first_name'],
    ];
    $result = civicrm_api('contact', 'get', $params);
    $this->assertAPISuccess($result);
    $this->assertEquals(1 , $result['count'], 'Additional contact count should be only 1. In line '.__LINE__);
    $additionalContact = reset($result['values']);
    $additionalContactId2 = $additionalContact['contact_id'];
    $this->assertEquals($additionalContactId2, $contactId, 'Additional contact should be same contact as previous after dedupe. In line '.__LINE__);
    $blockValue = $formatted['additional']['address'][0];
    $blockValue['contact_id'] = $contactId;
    CRM_Core_BAO_Address::valueExists($blockValue);
    $this->assertNotEmpty($blockValue['id'], "Additional address should be added to same contact. In line " . __LINE__);

    // case 3, this should dupe original contact, no additional contact should be created
    $json = json_decode($this->_json[1], TRUE);
    $json['transaction']['trade_no'] = CRM_Utils_String::createRandom(15);
    $json['user']['id'] = mt_rand(100000, 999999);
    $json['user']['email'] = 'admintest.noadd@eaxmple.com';
    $json['user']['name'] = '陳未增';
    $json['user']['cellphone'] = '+886966777888';
    $json['recipient']['recipient_name'] = '陳未增';
    $json['recipient']['recipient_contact_email'] = 'admintest.noadd@eaxmple.com';
    $json['recipient']['recipient_cellphone'] = '0977777888';
    $json = json_encode($json);
    $formatted = CRM_Core_Payment_Backer::formatParams($json);

    $contributionResult = NULL;
    $createdContributionId = $this->_processor->processContribution($json, $contributionResult);
    $createdContributionId = $contributionResult['contributionId'];
    $this->assertNotEmpty($createdContributionId, "Contribution not created. In line " . __LINE__);
    $contactId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $createdContributionId, 'contact_id');

    $blockValue = $formatted['additional']['address'][0];
    $blockValue['contact_id'] = $contactId;
    CRM_Core_BAO_Address::valueExists($blockValue);
    $this->assertNotEmpty($blockValue['id'], "Additional address should be added to same contact. In line " . __LINE__);

    $blockValue = $formatted['additional']['phone'][0];
    $blockValue['contact_id'] = $contactId;
    CRM_Core_BAO_Phone::valueExists($blockValue);
    $this->assertNotEmpty($blockValue['id'], "Additional phone should be added to same contact. In line " . __LINE__);
  }

  function testBackerReceiptNew(){
    $contributionResult = NULL;
    // prepare data
    $formatted = CRM_Core_Payment_Backer::formatParams($this->_json[2]);
    $createdContributionId = $this->_processor->processContribution($this->_json[2] ,$contributionResult);
    $createdContributionId = $contributionResult['contributionId'];
    $this->assertNotEmpty($createdContributionId, "In line " . __LINE__);

    // verify all contribution saved data
    $params = [
      'trxn_id' => $this->_trxnId[2],
      'payment_instrument_id' => $formatted['contribution']['payment_instrument_id'],
      'total_amount' => $formatted['contribution']['total_amount'],
      'contribution_status_id' => $formatted['contribution']['contribution_status_id'],
      'currency' => $formatted['contribution']['currency'],
      'payment_processor_id' => $this->_payment['id'],
    ];
    $this->assertDBState('CRM_Contribute_DAO_Contribution', $createdContributionId, $params);

    // verify all custom fields saved correctly
    $params = [
      'version' => 3,
      'entity_table' => 'civicrm_contribution',
      'entity_id' => $createdContributionId,
    ];
    $result = civicrm_api('CustomValue', 'get', $params);
    $this->assertAPISuccess($result);
    foreach($formatted['contribution'] as $key => $value) {
      if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($key)) {
        $this->assertEquals($value, $result['values'][$customFieldID][0]);
      }
    }

    // verify contact data
    $contactId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $createdContributionId, 'contact_id');
    $params = [
      'id' => $contactId,
      'last_name' => $formatted['contact']['last_name'],
      'first_name' => $formatted['contact']['first_name'],
      'external_identifier' => $formatted['contact']['external_identifier'],
    ];
    $this->assertDBState('CRM_Contact_DAO_Contact', $contactId, $params);

    $params = [
      'version' => 3,
      'id' => $contactId,
    ];
    $result = civicrm_api('contact', 'get', $params);
    $this->assertAPISuccess($result);

    // address, email, phone
    $this->assertEquals($formatted['address'][0]['street_address'], $result['values'][$result['id']]['street_address']);
    $this->assertEquals($formatted['email'][0]['email'], $result['values'][$result['id']]['email']);
    $this->assertEquals($formatted['phone'][0]['phone'], $result['values'][$result['id']]['phone']);
  }

  function testBackerRecurring(){
    //main
    $contributionResult = NULL;
    $formatted = CRM_Core_Payment_Backer::formatParams($this->_json[3]);
    $this->_processor->processContribution($this->_json[3], $contributionResult);

    // verify recur contribution saved data
    $params = [
      'trxn_id' => $formatted['recurring']['trxn_id'],
      'contribution_status_id' => $formatted['recurring']['contribution_status_id'],
    ];
    $this->assertDBState('CRM_Contribute_DAO_ContributionRecur', $contributionResult['recur_contribution_id'], $params);

    //sub contribution
    if ($contributionResult['recur_contribution_id']) {
      $formatted = CRM_Core_Payment_Backer::formatParams($this->_json[4]);
      $this->_processor->processContribution($this->_json[4], $contributionResult);
      $params = [
        'trxn_id' => $formatted['contribution']['trxn_id'],
        'payment_instrument_id' => $formatted['contribution']['payment_instrument_id'],
        'total_amount' => $formatted['contribution']['total_amount'],
        'contribution_status_id' => $formatted['contribution']['contribution_status_id'],
        'currency' => $formatted['contribution']['currency'],
        'payment_processor_id' => $this->_payment['id'],
      ];
      $this->assertDBState('CRM_Contribute_DAO_Contribution', $contributionResult['contributionId'], $params);
    }
  }
}