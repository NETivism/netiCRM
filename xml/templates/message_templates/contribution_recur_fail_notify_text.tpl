

===========================================================
{ts}Recurring Failed Notification{/ts}

===========================================================

捐款人 {$display_name} 的定期定額捐款 {$amount}，於 {$receive_date} 因故請款失敗（ {$url} ）。{if $detail}原因如下：
{foreach from=$detail item=item}
- {$item}
{/foreach}
{/if}
{if $trxn_id}
若有不清楚的地方，可再到金流服務後台以交易編號 {$trxn_id} 進行查詢
{/if}
您可評估是否要通知捐款人進行相關處理（例如因為信用卡過期而需重新上網進行捐款），或是等待下個月的定期定額自動請款（假如本次是因為餘額不足導致無法請款）。

若有需要，也可以透過 https://neticrm.tw/support 的線上客服與我們聯絡。謝謝。