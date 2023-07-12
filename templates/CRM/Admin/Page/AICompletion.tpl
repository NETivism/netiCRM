<div class="crm-container">
  <div class="crm-actions-ribbon">
    <li class="action-link-button">
      <a href="{crmURL p='civicrm/admin/setting/aicompletion' q="reset=1&destination=`$destination`"}" class="edit button" title="{ts}Set up the organization profile, so that when using AI to generate text later, it can be quickly incorporated to let the AI understand the context of your organization.{/ts}">
        <i class="zmdi zmdi-edit"></i>{ts}Edit Organization profile{/ts}
      </a>
		</li>
  </div>
  <table id="aicomplete-items" class="crm-aicomplete-items">
    <thead>
    <tr>
      <th class="crm-aicomplete-items-id">{ts}ID{/ts}</th>
      <th class="crm-aicomplete-items-name">{ts}Name{/ts}</th>
      <th class="crm-aicomplete-items-created">{ts}Created Date{/ts}</th>
      <th class="crm-aicomplete-items-component">{ts}Component{/ts}</th>
      <th class="crm-aicomplete-items-airole">{ts}AI Role{/ts}</th>
      <th class="crm-aicomplete-items-tonestyle">{ts}Tone Style{/ts}</th>
      <th class="crm-aicomplete-items-prompt">{ts}Prompt{/ts}</th>
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
      <th class="crm-aicomplete-items-tonstyle">{$row.ton_style}</th>
      <td class="crm-aicomplete-items-prompt truncate-container">{$row.prompt}</td>
      <th class="crm-aicomplete-items-operation">{$row.operation}</th>
    </tr>
    {/foreach}
    </tbody>
  </table>
  {literal}
  <style>
    .truncate-container {
      max-width: 18em;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
  </style>
  {/literal}
</div>
