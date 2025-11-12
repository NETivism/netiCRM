{if $action ne 1 and $action ne 2 and $action ne 8}
<div id="help">
  {ts}By default, CiviCRM uses the primary email address of the logged in user as the FROM address when sending emails to contacts. However, you can use this page to define one or more general Email Addresses that can be selected as an alternative. EXAMPLE: <em>"Client Services" &lt;clientservices@example.org&gt;</em>{/ts}
</div>
{/if}

<div class="crm-content-block crm-block">
  {if $action ne 1 and $action ne 2 and $action ne 8}
  <div class="action-link-button">
    <a href="{crmURL q='action=add&reset=1'}" class="button"><span><i class="zmdi zmdi-plus-circle-o"></i>{ts}Add{/ts}</span></a>
  </div>
  {/if}
  {if $rows and $action ne 1 and $action ne 2 and $action ne 8}
  {strip}
  {include file="CRM/common/jsortable.tpl"}
  <table id="options" class="display">
    <thead>
      <tr>
      <th>{ts}Label{/ts}</th>
      <th id="nosort">{ts}Description{/ts}</th>
      <th>{ts}Used for{/ts}</th>
      <th id="order" class="sortable">{ts}Order{/ts}</th>
      <th>{ts}Default?{/ts}</th>
      <th>{ts}Enabled?{/ts}</th>
      <th>
        {capture assign=sender}{ts}Sender{/ts}{/capture}
        {ts 1=$sender}%1 Verified{/ts}?
      </th>
      <th>{ts 1=SPF}%1 Verified{/ts}?</th>
      <th>{ts 1=DKIM}%1 Verified{/ts}?</th>
      <th>{ts}Note{/ts}</th>
      <th class="hiddenElement"></th>
      <th></th>
      </tr>
    </thead>
    <tbody>
      {foreach from=$rows item=row}
      <tr id="row_{$row.id}" class="crm-admin-from_email_address crm-admin-options_{$row.id} {cycle values="odd-row,even-row"}{if NOT $row.is_active} disabled{/if}">
        <td class="crm-admin-from_email_address-label">{$row.label}</td>
        <td class="crm-admin-from_email_address-description">{$row.description}</td>  
        <td class="crm-admin-from_email_address-usedfor">
          {ts}Contribution Page{/ts}: {$row.used_for_page} {ts}times{/ts}<br>
          {ts}Event{/ts}: {$row.used_for_event} {ts}times{/ts}
        </td>
        <td class="nowrap crm-admin-from_email_address-order">{$row.order}</td>
        <td class="crm-admin-from_email_address-is_reserved">{if $row.is_default eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
        <td class="crm-admin-from_email_address-is_active" id="row_{$row.id}_status">
          {if $row.is_active eq 1}
            {if ($row.filter & 1) && ($row.filter & 2) && ($row.filter & 4)}
            {ts}Yes{/ts}<i class="zmdi zmdi-shield-check ok"></i>
            {else}
            <span title="{ts}You need to verify SPF and DKIM first to use this from address.{/ts}">{ts}Yes, but not verified.{/ts}</span> <i class="zmdi zmdi-alert-triangle warning" title="{ts}You need to verify SPF and DKIM first to use this from address.{/ts}"></i>
            {/if}
          {else}
            {ts}No{/ts}
          {/if}
        </td>
        <td class="crm-admin-from_email_address-email">{if $row.filter & 1} {ts}Yes{/ts} <i class="zmdi zmdi-check"></i> {else} {ts}No{/ts} <i class="zmdi zmdi-alert-triangle warning"></i>{/if}</td>
        <td class="crm-admin-from_email_address-spf">{if $row.filter & 2} {ts}Yes{/ts} <i class="zmdi zmdi-check"></i> {else} {ts}No{/ts} <i class="zmdi zmdi-alert-triangle warning"></i>{/if}</td>
        <td class="crm-admin-from_email_address-dkim">{if $row.filter & 4} {ts}Yes{/ts} <i class="zmdi zmdi-check"></i> {else} {ts}No{/ts} <i class="zmdi zmdi-alert-triangle warning"></i>{/if}</td>
        <td class="crm-admin-from_email_address-note">{$row.grouping}</td>
        <td>{$row.action|replace:'xx':$row.id}</td>
        <td class="order hiddenElement">{$row.weight}</td>
      </tr>
      {/foreach}
    </tbody>
  </table>
  {/strip}
  {/if}

  {if $action ne 1 and $action ne 2 and $action ne 8}
  <div class="action-link-button">
    <a href="{crmURL q='action=add&reset=1'}" class="button"><span><i class="zmdi zmdi-plus-circle-o"></i>{ts}Add{/ts}</span></a>
  </div>
  {/if}
</div><!--crm-content-block-->