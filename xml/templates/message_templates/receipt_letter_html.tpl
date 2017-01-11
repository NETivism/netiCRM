{counter start=1 skip=1 assign="count"}
{if $single_page_letter}
<div class="{if $single_page_letter eq 'single_page_letter'}single-page-header{else}two-sections-header{/if}">
  <div class="info">
    {if $single_page_letter eq 'two_pages_letter'}
    <div class="logo"><img src="{$logo}" height="30" /></div>
    {/if}
    {if $single_page_letter eq 'two_pages_letter'}<div class="address"><div class="address-label">From: </div>{$domain_address}</div>{/if}
    {if $address}<div class="address">{if $single_page_letter eq 'two_pages_letter'}<div class="address-label">To: </div>{/if}{$address}</div>{/if}
    <div><span class="web-name">{$addressee}收</span></div>
  </div>
</div>
{counter print=false}
{/if}



<div class="receipts page-contain-{$print_type|@count}-sections {if $single_page_letter neq ''}receipt-with-address{/if}">
{foreach from=$print_type key=type item=type_label}
{if $count > 1}
<div class="line {$type}"></div>
{/if}
<div class="receipt {$type}">
  <div class="receipt-head">
    <div class="logo"><img src="{$logo}" height="30" /></div>
    <div class="title">收據</div>
    <div class="date"><label>日期：</label>{$receipt_date}</div>
    <div class="serial">
      <label class="type">{$type_label}</label><br />
      <label>收據編號：</label>{$receipt_id}<br />
    </div>
  </div>
  <div class="receipt-body">
    <table>
      <tr>
        <td class="col-1">姓名/抬頭</td>
        <td class="col-2">{$sort_name}</td>
        <td class="col-3 signature">組織簽章</td>
      </tr>
      <tr>
        <td class="col-1">身分證字號/統一編號</td>
        <td class="col-2">{$serial_id}</td>
        <td class="col-3" rowspan="4">&nbsp;</td>
      </tr>
      <tr>
        <td class="col-1">收入用途/類別</td>
        <td class="col-2">{$contributionTypeName}</td>
      </tr>
      <tr>
        <td class="col-1">繳費方式</td>
        <td class="col-2">{$instrument}</td>
      </tr>
      <tr>
        <td class="col-1">金額（大寫）新台幣</td>
        <td class="col-2">{$amount|crmMoney:$currency:"chinese"}</td>
      </tr>
      <tr>
        <td class="col-1">金額（小寫）</td>
        <td class="col-2">{$amount|crmMoney:$currency} 元整</td>
        <td class="col-3">經辦人：</td>
      </tr>
    </table>
  </div>
  <div class="receipt-footer">
    <table>
      <tr>
        <td class="col-1">組織資訊</td>
        <td class="col-2">{$receiptOrgInfo}</td>
        <td class="col-3">備註說明</td>
        <td class="col-4">{$receiptDescription}</td>
      </tr>
    </table>
  </div>
</div>
{if $type eq "address"}
address here
{/if}
{counter print=false}
{/foreach}
</div>
