{if $receipt_text}
{$receipt_text}
{/if}
{if $is_pay_later}

===========================================================
{$pay_later_receipt}
===========================================================
{else}

{ts}Please print this receipt for your records.{/ts}
{if $pdf_receipt_decrypt_info}{$pdf_receipt_decrypt_info|escape:'html'} {/if}{ts}Please print this confirmation for your records.{/ts}
{/if}

{if $amount}
===========================================================
{ts}Contribution Information{/ts}

===========================================================
{if $lineItem and $priceSetID}
{foreach from=$lineItem item=value key=priceset}
---------------------------------------------------------
{capture assign=ts_item}{ts}Item{/ts}{/capture}
{capture assign=ts_qty}{ts}Qty{/ts}{/capture}
{capture assign=ts_each}{ts}Each{/ts}{/capture}
{capture assign=ts_total}{ts}Total{/ts}{/capture}
{$ts_item|string_format:"%-30s"} {$ts_qty|string_format:"%5s"} {$ts_each|string_format:"%10s"} {$ts_total|string_format:"%10s"}
----------------------------------------------------------
{foreach from=$value item=line}
{capture assign=ts_item}{if $line.html_type eq 'Text'}{$line.label}{else}{$line.field_title} - {$line.label}{/if} {if $line.description} {$line.description}{/if}{/capture}{$ts_item|truncate:30:"..."|string_format:"%-30s"} {$line.qty|string_format:"%5s"} {$line.unit_price|crmMoney:$currency|string_format:"%10s"} {$line.line_total|crmMoney:$currency|string_format:"%10s"}
{/foreach}
{/foreach}

{ts}Total Amount{/ts}: {$amount|crmMoney:$currency}
{else}
{ts}Amount{/ts}: {$amount|crmMoney:$currency} {if $amount_level } - {$amount_level} {/if}
{/if}
{/if}
{if $receive_date}

{ts}Date{/ts}: {$receive_date|crmDate}
{/if}
{if $is_monetary and $trxn_id}
{ts}Transaction #{/ts}: {$trxn_id}
{/if}

{if $is_recur}
{if $recur.end_date}{capture assign="recur_date"}{ts 1=$recur.start_date 2=$recur.end_date}Between %1 and %2{/ts}{/capture}{else}{capture assign="recur_date"}{ts}From{/ts} {$recur.start_date}{/capture}{/if}
{capture assign="recur_frequency_unit"}{ts}{$recur.frequency_unit}{/ts}{/capture}
{ts 1=$recur.frequency_interval 2=$recur_frequency_unit 3=$paidBy 4=$recur_date}This is a recurring contribution. %4, every %1 %2 will charge from %3 payment.{/ts}
{if $receiptFromEmail}
{ts 1=$receiptFromEmail}To modify or cancel future contributions please contact %1{/ts}
{else}
{ts}To modify or cancel future contributions please contact us{/ts}
{/if}
{/if}

{if $honor_block_is_active }
===========================================================
{$honor_type}
===========================================================
{$honor_prefix} {$honor_first_name} {$honor_last_name}
{if $honor_email}
{ts}Honoree Email{/ts}: {$honor_email}
{/if}

{/if}
{if $pcpBlock}
===========================================================
{ts}Personal Campaign Page{/ts}

===========================================================
{ts}Display In Honor Roll{/ts}: {if $pcp_display_in_roll}{ts}Yes{/ts}{else}{ts}No{/ts}{/if}

{if $pcp_roll_nickname}{ts}Nick Name{/ts}: {$pcp_roll_nickname}{/if}

{if $pcp_personal_note}{ts}Personal Note{/ts}: {$pcp_personal_note}{/if}

{/if}
{if $onBehalfName}
===========================================================
{ts}On Behalf Of{/ts}

===========================================================
{$onBehalfName}
{$onBehalfAddress}

{$onBehalfEmail}

{/if}

{if !( $contributeMode eq 'notify' OR $contributeMode eq 'directIPN' ) and $is_monetary}
{if $is_pay_later}
===========================================================
{ts}Registered Email{/ts}

===========================================================
{$email}
{elseif $amount GT 0}
===========================================================
{ts}Billing Name and Address{/ts}

===========================================================
{$billingName}
{$address}

{$email}
{/if} {* End ! is_pay_later condition. *}
{/if}
{if $contributeMode eq 'direct' AND !$is_pay_later AND $amount GT 0}

===========================================================
{ts}Credit Card Information{/ts}

===========================================================
{$credit_card_type}
{$credit_card_number}
{ts}Expires{/ts}: {$credit_card_exp_date|truncate:7:''|crmDate}
{/if}

{if $selectPremium }
===========================================================
{ts}Premium Information{/ts}

===========================================================
{$product_name}
{if $option}
{ts}Option{/ts}: {$option}
{/if}
{if $sku}
{ts}SKU{/ts}: {$sku}
{/if}
{if $start_date}
{ts}Start Date{/ts}: {$start_date|crmDate}
{/if}
{if $end_date}
{ts}End Date{/ts}: {$end_date|crmDate}
{/if}
{if $contact_email OR $contact_phone}

{ts}For information about this premium, contact:{/ts}

{if $contact_email}
  {$contact_email}
{/if}
{if $contact_phone}
  {$contact_phone}
{/if}
{/if}
{if $is_deductible AND $price}
{/if}
{/if}

{if $customPre}
===========================================================
{$customPre_grouptitle}

===========================================================
{foreach from=$customPre item=customValue key=customName}
{if ( $trackingFields and ! in_array( $customName, $trackingFields ) ) or ! $trackingFields}
 {$customName}: {$customValue}
{/if}
{/foreach}
{/if}


{if $customPost}
===========================================================
{$customPost_grouptitle}

===========================================================
{foreach from=$customPost item=customValue key=customName}
{if ( $trackingFields and ! in_array( $customName, $trackingFields ) ) or ! $trackingFields}
 {$customName}: {$customValue}
{/if}
{/foreach}
{/if}
