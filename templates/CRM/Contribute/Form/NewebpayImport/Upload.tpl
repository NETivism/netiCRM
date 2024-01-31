{include file="CRM/common/WizardHeader.tpl"}

<table class="form-layout-compressed">
  <tr>
    <td class="label">{$form.uploadFile.label}</td>
    <td>{$form.uploadFile.html}</td>
  </tr>
  <tr>
    <td class="label">{$form.disbursementDate.label}</td>
    <td>{$form.disbursementDate.html}</td>
  </tr>
</table>

<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
