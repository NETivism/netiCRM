{if $action eq 8}

<div class="messages status">
  {ts 1=$email}Are you sure you want to delete the selected from email address %1 ?{/ts} {ts}This action cannot be undone.{/ts}
</div>
<div class="crm-block crm-form-block crm-admin-options-form-block">
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
</div>

{/if}