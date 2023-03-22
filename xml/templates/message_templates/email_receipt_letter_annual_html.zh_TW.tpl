{foreach from=$contact_info key=contact_name item=info name=annual}

<div class="wrapper">
<div class="receipt">
  <div class="receipt-head">
    <div class="logo"><img src="{$logo}" height="30" /></div>
    <div class="title">收據</div>
  </div>
  <div class="receipt-body">
    <table>
      <tr>
        <td class="col-1 align-right">姓名/抬頭</td>
        <td class="col-2">{$info.sort_name}</td>
      </tr>
      <tr>
        <td class="col-1 align-right">身份證字號/統一編號</td>
        <td class="col-2">{$info.serial_id}</td>
      </tr>
    </table>
  </div>
  <div class="receipt-signature">
    <table>
      <tr>
        <td>
          <table>
            <tr>
              <td class="label">組織簽章</td>
              <td class="value stamp-wrapper"><img class="big-stamp stamp-img" src="{$imageBigStampUrl}"/></td>
            </tr>
          </table>
        </td>
        <td>
          <table>
            <tr>
              <td class="label">經辦人</td>
              <td class="value stamp-wrapper"><img class="small-stamp stamp-img" src="{$imageSmallStampUrl}"/></td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </div>
  <div class="receipt-record">
    <table>
      <tr>
        {foreach from=$recordHeader item=th}
          <th>{$th}</th>
        {/foreach}
      </tr>
      {foreach from=$record.$contact_name item=row}
      <tr>
         <td class="align-right">{$row.receipt_id}</td>
         <td>{$row.contribution_type}</td>
         <td>{$row.instrument}</td>
         <td>{$row.receipt_date}</td>
         <td class="align-right">{$row.total_amount}</td>
      </tr>
      {/foreach}
    </table>
    <div class="annual-total align-right">總金額： {$info.total}</div>
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

</div><!-- wrapper -->
{if $contact_info|@count gt $smarty.foreach.annual.index + 1 }<div style="page-break-after: always;"></div>{/if}

{/foreach}