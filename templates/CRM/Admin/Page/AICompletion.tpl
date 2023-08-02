{if $action eq 4 or $action eq 2}{* when action is view or update *}

{include file="CRM/AI/Form/AICompletion.tpl"}

{else}{* when action is browse *}

{if !$show_reset}
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
        <div class="kpi-box">
          <h4 class="kpi-box-title">{ts}Current Month-To-Date{/ts} - {ts}Used for{/ts}</h4>
          {include file="CRM/common/chartist.tpl" chartist=$chartAICompletionUsedfor}
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
            <a href="{crmURL p='civicrm/admin/setting/aicompletion' q="reset=1"}" class="edit button""><i class="zmdi zmdi-edit"></i>{ts}Edit{/ts}</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
{/if}

<div class="action-link-button">
  {if $show_reset}
    <a class="button" href="{crmURL p="civicrm/admin/aicompletion" q="reset=1"}"><i class="zmdi zmdi-undo"></i>{ts}Reset{/ts}</a>
  {else}
    <a class="button" href="{crmURL p="civicrm/admin/aicompletion" q="reset=1&is_template=1"}">
      <i class="zmdi zmdi-filter-list"></i>{ts}Filter{/ts}: {ts}Template List{/ts}
    </a>
    <a class="button" href="{crmURL p="civicrm/admin/aicompletion" q="reset=1&is_shared=1"}">
      <i class="zmdi zmdi-filter-list"></i>{ts}Filter{/ts}: {ts}Recommend a Template to Other Organizations{/ts}
    </a>
  {/if}
</div>

{include file="CRM/common/pager.tpl" location="top"}
<table id="aicomplete-items" class="crm-aicomplete-items">
<thead>
<tr>
  <th class="crm-aicomplete-items-id">{ts}ID{/ts}</th>
  <th class="crm-aicomplete-items-name">{ts}Name{/ts}</th>
  <th class="crm-aicomplete-items-created">{ts}Created Date{/ts}</th>
  <th class="crm-aicomplete-items-component">{ts}Used for{/ts}</th>
  <th class="crm-aicomplete-items-airole">{ts}Copywriting Role{/ts}</th>
  <th class="crm-aicomplete-items-tonestyle">{ts}Tone Style{/ts}</th>
  <th class="crm-aicomplete-items-content overflow-safe">{ts}Content{/ts}</th>
  <th class="crm-aicomplete-items-template">{ts}Template Title{/ts}</th>
  <th class="crm-aicomplete-items-action">{ts}Operation{/ts}</th>
</tr>
</thead>
<tbody>
{foreach from=$rows item=row}
<tr class="{cycle values="odd-row,even-row"} {$row.class}">
  <td class="crm-aicomplete-items-id">{$row.id}</td>
  <td class="crm-aicomplete-items-displayname"><a href="{crmURL a=true p='civicrm/contact/view' q="reset=1&cid=`$row.contact_id`"}">{$row.display_name}</a></td>
  <td class="crm-aicomplete-items-created">{$row.created_date|crmDate}</td>
  <td class="crm-aicomplete-items-component">
    {if $row.component}
      {capture assign=filter_component}{$row.component|escape:'url'}{/capture}
      <a href="{crmURL p="civicrm/admin/aicompletion" q="reset=1&component=`$filter_component`"}">
        {ts}{$row.component}{/ts}
      </a>
    {else}
      {ts}(n/a){/ts}
    {/if}
  </td>
  <td class="crm-aicomplete-items-airole">
    {if $row.ai_role}
      {capture assign=filter_role}{$row.ai_role|escape:'url'}{/capture}
      <a href="{crmURL p="civicrm/admin/aicompletion" q="reset=1&role=`$filter_role`"}">
        {$row.ai_role}
      </a>
    {else}
      {ts}(n/a){/ts}
    {/if}
  </td>
  <td class="crm-aicomplete-items-tonestyle">
    {if $row.tone_style}
      {capture assign=filter_tone}{$row.tone_style|escape:'url'}{/capture}
      <a href="{crmURL p="civicrm/admin/aicompletion" q="reset=1&tone=`$filter_tone`"}">
        {$row.tone_style}
      </a>
    {else}
      {ts}(n/a){/ts}
    {/if}
  </td>
  <td class="crm-aicomplete-items-content">
    <div><i class="zmdi zmdi-comment-alt-text"></i> {$row.content}</div>
    <div class="description"><i class="zmdi zmdi-comments"></i> {$row.output}</div>
  </td>
  <td class="crm-aicomplete-items-template_title">
    {if $row.template_title}
      {$row.template_title}
    {else}
      {ts}(n/a){/ts}
    {/if}
  </td>
  <td class="crm-aicomplete-items-operation">{$row.action}</td>
</tr>
{/foreach}
</tbody>
</table>
{include file="CRM/common/pager.tpl" location="bottom"}

{/if}{* end action condition *}