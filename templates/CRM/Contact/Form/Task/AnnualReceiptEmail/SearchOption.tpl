
<div class="crm-block crm-contact-form-task-annual_receipt_email search_option">
  <div class="help">
    The help description here{ts}{/ts}
  </div>
  {include file="CRM/common/WizardHeader.tpl"}
  <table class="form-layout-compressed">
    <tr>
      <td class="label">{$form.year.label}</td>
      <td>{$form.year.html}</td>
    </tr>
    <tr>
      <td class="label">{$form.contribution_type_id.label}</td>
      <td>{$form.contribution_type_id.html}</td>
    </tr>
    <tr>
      <td class="label">{$form.is_recur.label}</td>
      <td>{$form.is_recur.html}</td>
    </tr>
  </table>
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl"}
  </div>
</div>