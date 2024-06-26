<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
 <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
 <title></title>
</head>
<body>

{capture assign=headerStyle}colspan="2" style="text-align: left; padding: 4px; border-bottom: 1px solid #999; background-color: #eee;"{/capture}
{capture assign=labelStyle }style="padding: 4px; border-bottom: 1px solid #999; background-color: #f7f7f7;"{/capture}
{capture assign=valueStyle }style="padding: 4px; border-bottom: 1px solid #999;"{/capture}

<center>
 <table width="620" border="0" cellpadding="0" cellspacing="0" id="crm-event_receipt" style="font-family: Arial, Verdana, sans-serif; text-align: left;">

  <!-- BEGIN HEADER -->
  <!-- You can add table row(s) here with logo or other header elements -->
  <!-- END HEADER -->

  <!-- BEGIN CONTENT -->

  <tr>
   <td>

    {if $receipt_text}
     <div>{$receipt_text}</div>
    {/if}

    {if $is_pay_later}
     <p>{$pay_later_receipt}</p> {* FIXME: this might be text rather than HTML *}
    {else}
     <p>{if $pdf_receipt_decrypt_info}{$pdf_receipt_decrypt_info|escape:'html'} {/if}{ts}Please print this confirmation for your records.{/ts}</p>
    {/if}

   </td>
  </tr>
  <tr>
   <td>
    <table style="border: 1px solid #999; margin: 1em 0em 1em; border-collapse: collapse; width:100%;">

     {if $amount}


      <tr>
       <th {$headerStyle}>
        {ts}Contribution Information{/ts}
       </th>
      </tr>

      {if $lineItem and $priceSetID}

       {foreach from=$lineItem item=value key=priceset}
        <tr>
         <td colspan="2" {$valueStyle}>
          <table> {* FIXME: style this table so that it looks like the text version (justification, etc.) *}
           <tr>
            <th>{ts}Item{/ts}</th>
            <th>{ts}Qty{/ts}</th>
            <th>{ts}Each{/ts}</th>
            <th>{ts}Total{/ts}</th>
           </tr>
           {foreach from=$value item=line}
            <tr>
             <td>
             {if $line.html_type eq 'Text'}{$line.label}{else}{$line.field_title} - {$line.label}{/if} {if $line.description}<div>{$line.description|truncate:30:"..."}</div>{/if}
             </td>
             <td>
              {$line.qty}
             </td>
             <td>
              {$line.unit_price|crmMoney:$currency}
             </td>
             <td>
              {$line.line_total|crmMoney:$currency}
             </td>
            </tr>
           {/foreach}
          </table>
         </td>
        </tr>
       {/foreach}
       <tr>
        <td {$labelStyle}>
         {ts}Total Amount{/ts}
        </td>
        <td {$valueStyle}>
         {$amount|crmMoney:$currency}
        </td>
       </tr>

      {else}

       <tr>
        <td {$labelStyle}>
         {ts}Amount{/ts}
        </td>
        <td {$valueStyle}>
         {$amount|crmMoney:$currency} {if $amount_level} - {$amount_level}{/if}
        </td>
       </tr>

      {/if}


     {/if}


     {if $receive_date}
      <tr>
       <td {$labelStyle}>
        {ts}Date{/ts}
       </td>
       <td {$valueStyle}>
        {$receive_date|crmDate}
       </td>
      </tr>
     {/if}

     {if $is_monetary and $trxn_id}
      <tr>
       <td {$labelStyle}>
        {ts}Transaction #{/ts}
       </td>
       <td {$valueStyle}>
        {$trxn_id}
       </td>
      </tr>
     {/if}

     {if $is_recur}
      {if $recur.end_date}
        {capture assign="recur_date"}{ts 1=$recur.start_date 2=$recur.end_date}Between %1 and %2{/ts}{/capture}
      {else}
        {capture assign="recur_date"}{ts}From{/ts} {$recur.start_date}{/capture}
      {/if}
      {capture assign="recur_frequency_unit"}{ts}{$recur.frequency_unit}{/ts}{/capture}
       <tr>
        <td colspan="2" {$labelStyle}>
         {ts 1=$recur.frequency_interval 2=$recur_frequency_unit 3=$paidBy 4=$recur_date}This is a recurring contribution. %4, every %1 %2 will charge from %3 payment.{/ts}<br />
         {if $receiptFromEmail}
           {ts 1=$receiptFromEmail}To modify or cancel future contributions please contact %1{/ts}
         {else}
           {ts}To modify or cancel future contributions please contact us{/ts}
         {/if}
        </td>
       </tr>
     {/if}

     {if $honor_block_is_active}
      <tr>
       <th {$headerStyle}>
        {$honor_type}
       </th>
      </tr>
      <tr>
       <td colspan="2" {$labelStyle}>
        {$honor_prefix} {$honor_first_name} {$honor_last_name}
       </td>
      </tr>
      {if $honor_email}
       <tr>
        <td {$labelStyle}>
         {ts}Honoree Email{/ts}
        </td>
        <td {$valueStyle}>
         {$honor_email}
        </td>
       </tr>
      {/if}
     {/if}

     {if $pcpBlock}
      <tr>
       <th {$headerStyle}>
        {ts}Personal Campaign Page{/ts}
       </th>
      </tr>
      <tr>
       <td {$labelStyle}>
        {ts}Display In Honor Roll{/ts}
       </td>
       <td {$valueStyle}>
        {if $pcp_display_in_roll}{ts}Yes{/ts}{else}{ts}No{/ts}{/if}
       </td>
      </tr>
      {if $pcp_roll_nickname}
       <tr>
        <td {$labelStyle}>
         {ts}Nick Name{/ts}
        </td>
        <td {$valueStyle}>
         {$pcp_roll_nickname}
        </td>
       </tr>
      {/if}
      {if $pcp_personal_note}
       <tr>
        <td {$labelStyle}>
         {ts}Personal Note{/ts}
        </td>
        <td {$valueStyle}>
         {$pcp_personal_note}
        </td>
       </tr>
      {/if}
     {/if}

     {if $onBehalfName}
      <tr>
       <th {$headerStyle}>
        {ts}On Behalf Of{/ts}
       </th>
      </tr>
      <tr>
       <td colspan="2" {$valueStyle}>
        {$onBehalfName}<br />
        {$onBehalfAddress|nl2br}<br />
        {$onBehalfEmail}
       </td>
      </tr>
     {/if}

     {if ! ($contributeMode eq 'notify' OR $contributeMode eq 'directIPN') and $is_monetary}
      {if $is_pay_later}
       <tr>
        <th {$headerStyle}>
         {ts}Registered Email{/ts}
        </th>
       </tr>
       <tr>
        <td colspan="2" {$valueStyle}>
         {$email}
        </td>
       </tr>
      {elseif $amount GT 0}
       <tr>
        <th {$headerStyle}>
         {ts}Billing Name and Address{/ts}
        </th>
       </tr>
       <tr>
        <td colspan="2" {$valueStyle}>
         {$billingName}<br />
         {$address|nl2br}<br />
         {$email}
        </td>
       </tr>
      {/if}
     {/if}

     {if $contributeMode eq 'direct' AND !$is_pay_later AND $amount GT 0}
      <tr>
       <th {$headerStyle}>
        {ts}Credit Card Information{/ts}
       </th>
      </tr>
      <tr>
       <td colspan="2" {$valueStyle}>
        {$credit_card_type}<br />
        {$credit_card_number}<br />
        {ts}Expires{/ts}: {$credit_card_exp_date|truncate:7:''|crmDate}<br />
       </td>
      </tr>
     {/if}

     {if $selectPremium}
      <tr>
       <th {$headerStyle}>
        {ts}Premium Information{/ts}
       </th>
      </tr>
      <tr>
       <td colspan="2" {$labelStyle}>
        {$product_name}
       </td>
      </tr>
      {if $option}
       <tr>
        <td {$labelStyle}>
         {ts}Option{/ts}
        </td>
        <td {$valueStyle}>
         {$option}
        </td>
       </tr>
      {/if}
      {if $sku}
       <tr>
        <td {$labelStyle}>
         {ts}SKU{/ts}
        </td>
        <td {$valueStyle}>
         {$sku}
        </td>
       </tr>
      {/if}
      {if $start_date}
       <tr>
        <td {$labelStyle}>
         {ts}Start Date{/ts}
        </td>
        <td {$valueStyle}>
         {$start_date|crmDate}
        </td>
       </tr>
      {/if}
      {if $end_date}
       <tr>
        <td {$labelStyle}>
         {ts}End Date{/ts}
        </td>
        <td {$valueStyle}>
         {$end_date|crmDate}
        </td>
       </tr>
      {/if}
      {if $contact_email OR $contact_phone}
       <tr>
        <td colspan="2" {$valueStyle}>
         <p>{ts}For information about this premium, contact:{/ts}</p>
         {if $contact_email}
          <p>{$contact_email}</p>
         {/if}
         {if $contact_phone}
          <p>{$contact_phone}</p>
         {/if}
        </td>
       </tr>
      {/if}
     {/if}

     {if $customPre}
      <tr>
       <th {$headerStyle}>
        {$customPre_grouptitle}
       </th>
      </tr>
      {foreach from=$customPre item=customValue key=customName}
       {if ($trackingFields and ! in_array($customName, $trackingFields)) or ! $trackingFields}
        <tr>
         <td {$labelStyle}>
          {$customName}
         </td>
         <td {$valueStyle}>
          {$customValue}
         </td>
        </tr>
       {/if}
      {/foreach}
     {/if}

     {if $customPost}
      <tr>
       <th {$headerStyle}>
        {$customPost_grouptitle}
       </th>
      </tr>
      {foreach from=$customPost item=customValue key=customName}
       {if ($trackingFields and ! in_array($customName, $trackingFields)) or ! $trackingFields}
        <tr>
         <td {$labelStyle}>
          {$customName}
         </td>
         <td {$valueStyle}>
          {$customValue}
         </td>
        </tr>
       {/if}
      {/foreach}
     {/if}

    </table>
   </td>
  </tr>

 </table>
</center>

</body>
</html>
