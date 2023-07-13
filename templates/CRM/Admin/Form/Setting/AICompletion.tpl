<div class="crm-block crm-form-block crm-aicompletion-setting-form-block">
  <table class="form-layout">
    <tr class="crm-aiOrganizationIntro-form-block-orgintro">
      <td class="label">{$form.aiOrganizationIntro.label}</td>
      <td>
        {$form.aiOrganizationIntro.html|crmReplace:class:'huge40'}
        <div class="description">
          {ts}Set up the organization intro, so that when using AI to generate text later, it can be quickly incorporated to let the AI understand the context of your organization.{/ts}
        </div>
      </td>
    </tr>
  </table>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
