{include file="CRM/common/WizardHeader.tpl"}
<div class="crm-block crm-form-block crm-admin-options-form-block">
  <table class="form-layout-compressed">
    <tr class="crm-admin-options-form-block-is_active">
      <td class="label">{$form.is_active.label}</td>
      <td>{$form.is_active.html}</td>
    </tr>
    <tr class="crm-admin-options-form-block-is_default">
      <td class="label">{$form.is_default.label}</td>
      <td>{$form.is_default.html}</td>
    </tr>
  </table>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
<script>{literal}
cj(document).ready(function($){
  if ($('#is_default').length) {
    $('#is_default').click(function() {
      if ($(this).attr('checked')) {
        $('#is_active').attr('checked', true);
      }
    });
  }
});
{/literal}</script>