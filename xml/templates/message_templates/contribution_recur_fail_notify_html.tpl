<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
 <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
 <title></title>
</head>
<body>

<p>{ts 1=$amount 2=$display_name 3=$receive_date 4=$url}The recurring contribution %1 by contributor %2 is failed at %3.( Link: %4 ){/ts}{if $detail}{ts}Additional Details:{/ts}{/if}</p>

{if $detail}

<ul>
{foreach from=$detail item=item}
<li>{$item}</li>
{/foreach}
</ul>

{/if}

{if $trxn_id}
<p>{ts 1=$trxn_id}If you need more infomation, please check source from payment processor service page by transaction ID: %1{/ts}</p>
{/if}

<p>{ts}You can evaluate whether to notify the contributor for the relevant treatment (for example, due to the credit card expires and need to make online contribution again), or wait for the next time the transaction executing (if this is due to insufficient balance can not pay).{/ts}</p>

<p>{ts}Or you can contact us by our support on https://neticrm.tw/support .{/ts}</p>


</body>
</html>