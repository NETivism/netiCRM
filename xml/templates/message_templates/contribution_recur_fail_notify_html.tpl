<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
 <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
 <title></title>
</head>
<body>

<p>捐款人 {$display_name} 的定期定額捐款 {$amount}，於 {$receive_date}因故請款失敗（ {$url} ）。{if $detail}原因如下：{/if}</p>

{if $detail}

<ul>
{foreach from=$detail item=item}
<li>{$item}</li>
{/foreach}
</ul>

{/if}

{if $trxn_id}
<p>若有不清楚的地方，可再到金流服務後台以交易編號 {$trxn_id} 進行查詢</p>
{/if}

<p>您可評估是否要通知捐款人進行相關處理（例如因為信用卡過期而需重新上網進行捐款），或是等待下個月的定期定額自動請款（假如本次是因為餘額不足導致無法請款）。</p>

<p>若有需要，也可以透過 https://neticrm.tw/support 的線上客服與我們聯絡。謝謝。</p>


</body>
</html>