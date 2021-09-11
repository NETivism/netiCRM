<div class="crm-container">
  <div class="messages">
    {ts 1=30}This page displays the user data has been changed in last %1 days.{/ts}
  </div>
  <table id="audit-records" class="crm-audit-records">
    <thead>
    <tr>
      <th class="crm-audit-records-date">{ts}Time{/ts}</th>
      <th class="crm-audit-records-status">{ts}Actions{/ts}</th>
      <th class="crm-audit-records-modified_user">{ts}Modified By{/ts}</th>
      <th class="crm-audit-records-user">{ts}Modified User{/ts}</th>
      <th class="crm-audit-records-relative_data">{ts}Relative Data{/ts}</th>
    </tr>
    </thead>
    <tbody>
    {foreach from=$rows item=row}
    <tr class="{cycle values="odd-row,even-row"} {$row.class}">
      <td class="crm-audit-records-date">{$row.time|crmDate}</td>
      <td class="crm-audit-records-state">{$row.state}</td>
      <td class="crm-audit-records-modified_user">
        {capture assign=modified_user_link}{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.modified_id`" h=0 a=1 fe=1}{/capture}
        <a href="{$modified_user_link}" target="_blank">{$row.modified_name}</a>
      </td>
      <td class="crm-audit-records-user">
        {if $row.user_id}
          {capture assign=user_link}{crmURL p="user/`$row.user_id`" h=0 a=1 fe=1}{/capture}
          {capture assign=user_name}{if $row.user_contact_name}{$row.user_contact_name} ({ts}User ID{/ts}: {$row.user_id}){else}{ts}User ID{/ts}:{ts}User ID{/ts}: {$row.user_id}{/if}{/capture}
          <a href="{$user_link}" target="_blank">{$user_name}</a>
        {/if}
      </td>
      <td class="crm-audit-records-relative_data">
        {if $row.audit_type eq 'audit.civicrm.export'}
          {ts}File name{/ts}: {$row.data.File}<br>
          {ts}Rows count{/ts}: {$row.data.Data}<br>
          <input type="checkbox" class="export-fields" id="export-fields-{$row.entity_id}">
          <label for="export-fields-{$row.entity_id}">{ts}Fields{/ts}</label>
            <ul>
              {foreach from=$row.data.Fields item=field}
                <li>{$field}</li>
              {/foreach}
            </ul>
        {else}
          {$row.data}
        {/if}
      </td>
    </tr>
    {/foreach}
    </tbody>
  </table>
  {literal}
  <style>
    input.export-fields {
      display: none;
    }
    input.export-fields+label {
      position: relative;
    }
    input.export-fields+label:after {
      content: "\f2f6";
      font-family: 'Material-Design-Iconic-Font';
      margin-left: 5px;
    }
    input.export-fields:checked+label:after {
      content: "\f2f2";
    }
    input.export-fields+label+ul {
      display: none;
    }
    input.export-fields:checked+label+ul {
      display: block;
    }
  </style>
  {/literal}
</div>