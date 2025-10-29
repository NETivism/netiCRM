civicrm_initialize();
$params = array(
  'version' => 3,
  'class_name' => 'Payment_Backer',
  'is_test' => 0,
);
$result = civicrm_api('PaymentProcessor', 'get', $params);
$pp = reset($result['values']);
$processor = CRM_Core_Payment::singleton('live', $pp);
$contributionResult = array();

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

$processor->processContribution($json, $contributionResult);
print_r($contributionResult);
