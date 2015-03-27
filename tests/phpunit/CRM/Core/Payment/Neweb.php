<?php
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


/**
 * $signature 來自金流的設定
 * 但必須要知道金流機制的 ID 才行
 */
// require_once 'CRM/Core/BAO/PaymentProcessor.php';
// $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment( $paymentProcessorID, $contribution->is_test ? 'test' : 'live' );


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

?>

<script>

/**
 * Enable this to jump back to ReturnURL
 */
// window.location.href = "<?php echo $_POST['ReturnURL'];?>";

</script>

