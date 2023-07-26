<div class="row">
  <div class="col-xs-12 col-md-4">
    <div class="box mdl-shadow--2dp">
      <div class="box-content">
        <div class="kpi-box">
          <h4 class="kpi-box-title">{ts}Current Month-To-Date{/ts} - {ts}Used{/ts}</h4>
          <div class="kpi-box-value">{$usage.used}<span class="kpi-unit">{ts}times{/ts}</span><span class="kpi-total-txt">{ts}Quota{/ts} {$usage.max} {ts}times{/ts}</span></div>
          {include file="CRM/common/chartist.tpl" chartist=$chartAICompletionQuota}
        </div>
      </div>
    </div>
  </div>
  <div class="col-xs-12 col-md-4">
    <div class="box mdl-shadow--2dp">
      <div class="box-content">
        <h5 class="box-title">{ts}Organization intro{/ts}</h5>
        <div class="box-detail">
          {$organization_intro|nl2br}
          <div>
            <a href="{crmURL p='civicrm/admin/setting/aicompletion' q="reset=1&destination=`$destination`"}" class="edit button""><i class="zmdi zmdi-edit"></i>{ts}Edit{/ts}</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{include file="CRM/common/pager.tpl" location="top"}
<table id="aicomplete-items" class="crm-aicomplete-items">
<thead>
<tr>
  <th class="crm-aicomplete-items-id">{ts}ID{/ts}</th>
  <th class="crm-aicomplete-items-name">{ts}Name{/ts}</th>
  <th class="crm-aicomplete-items-created">{ts}Created Date{/ts}</th>
  <th class="crm-aicomplete-items-component">{ts}Component{/ts}</th>
  <th class="crm-aicomplete-items-airole">{ts}Copywriting Role{/ts}</th>
  <th class="crm-aicomplete-items-tonestyle">{ts}Tone Style{/ts}</th>
  <th class="crm-aicomplete-items-content overflow-safe">{ts}Content{/ts}</th>
  <th class="crm-aicomplete-items-operation">{ts}Operation{/ts}</th>
</tr>
</thead>
<tbody>
{foreach from=$rows item=row}
<tr class="{cycle values="odd-row,even-row"} {$row.class}">
  <td class="crm-aicomplete-items-id">{$row.id}</td>
  <td class="crm-aicomplete-items-displayname"><a href="{crmURL a=true p='civicrm/contact/view' q="reset=1&cid=`$row.contact_id`"}">{$row.display_name}</a></td>
  <td class="crm-aicomplete-items-created">{$row.created_date|crmDate}</td>
  <td class="crm-aicomplete-items-component">{$row.component}</td>
  <td class="crm-aicomplete-items-airole">{$row.ai_role}</td>
  <td class="crm-aicomplete-items-tonstyle">{$row.ton_style}</td>
  <td class="crm-aicomplete-items-content overflow-safe">{$row.content}</td>
  <td class="crm-aicomplete-items-operation">{$row.operation}</td>
</tr>
{/foreach}
</tbody>
</table>
{include file="CRM/common/pager.tpl" location="bottom"}
