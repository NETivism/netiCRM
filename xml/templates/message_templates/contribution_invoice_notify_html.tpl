<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
 <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
 <title></title>
</head>
<body>
{capture assign=headerStyle}colspan="2" style="text-align: left; padding: 10px; border-bottom: 1px solid #999; background-color: #eee;font-size:1.1em;"{/capture}
{capture assign=labelStyle}style="padding: 5px; border-bottom: 1px solid #999; background-color: #f7f7f7;font-weight:bold;"{/capture}
{capture assign=valueStyle}style="padding: 5px; border-bottom: 1px solid #999;"{/capture}
{capture assign=highlightStyle}style="color:#red;"{/capture}
{capture assign=expire}#expire{/capture}
<center>
{if $logo}<img src="{$logo}" style="max-height:80px" />{/if}
 <table width="620" border="0" cellpadding="0" cellspacing="0" id="crm-event_receipt" style="font-family: Arial, Verdana, sans-serif; text-align: left;">
  <tr>
    <td>
    <h3>{ts}Invoice{/ts} - {$title}</h3>
    {if $message}
      {$message}
    {else}
      <p>
        {ts 1=$page.url 2=$title}We send this invoice because you have submitted data at <a href="%1">%2</a>.{/ts}<br>
        {ts 1=$payment_info.$expire}You need to follow instruction below to complete transaction before <strong>%1</strong> (expire date).{/ts}<br>
        {ts 1=$page.url}If you can make it before expire date, you can <a href="%1">go page</a> submit again.{/ts}
      </p>
    {/if}
    </td>
  </tr>
  <tr>
    <td>
    <table style="border: 1px solid #999; margin: 1em 0em 1em; border-collapse: collapse; width:100%;">
      <tr>
        <th colspan=2 {$headerStyle}>
          {ts}Payment Information{/ts} <span>{ts}Original Receipts{/ts}</span>
        </th>
      </tr>
      <tr>
        <td {$labelStyle}>
          {ts}Amount{/ts}
        </td>
        <td {$valueStyle}>
          {$contribution.total_amount|crmMoney}
        </td>
      </tr>
      <tr>
        <td {$labelStyle}>
          {ts}Transaction ID{/ts}
        </td>
        <td {$valueStyle}>
          {$contribution.trxn_id}
        </td>
      </tr>
      <tr>
        <td {$labelStyle}>
          {ts}Payment Instrument{/ts}
        </td>
        <td {$valueStyle}>
          {$contribution.payment_instrument}
        </td>
      </tr>
      <tr>
        <td colspan=2 {$valueStyle}>
          {$payment_info.display}
        </td>
      </tr>
{if $payment_info.has_receipt}
      <tr>
        <td colspan=2 {$valueStyle}><hr style="border:1px dashed #AAA;"></td>
      </tr>
      <tr>
        <th colspan=2 {$headerStyle}>
          <span>{ts}Copy Receipts{/ts}</span>
        </th>
      </tr>
      <tr>
        <td {$labelStyle}>
          {ts}Amount{/ts}
        </td>
        <td {$valueStyle}>
          <strong>{$contribution.total_amount|crmMoney}</strong>
        </td>
      </tr>
      <tr>
        <td {$labelStyle}>
          {ts}Transaction ID{/ts}
        </td>
        <td {$valueStyle}>
          {$contribution.trxn_id}
        </td>
      </tr>
      <tr>
        <td {$labelStyle}>
          {ts}Payment Instrument{/ts}
        </td>
        <td {$valueStyle}>
          {$contribution.payment_instrument}
        </td>
      </tr>
      <tr>
        <td colspan=2 {$valueStyle}>
          {$payment_info.display}
        </td>
      </tr>
    </table>
    </td>
  </tr>
{/if}
 </table>
</center>

</body>
</html>