<div class="crm-block crm-form-block crm-aicompletion-setting-form-block">
  <table class="form-layout">
    <tr class="crm-aiOrganizationIntro-form-block-orgintro">
      <td class="label">{$form.aiOrganizationIntro.label}</td>
      <td>
        {$form.aiOrganizationIntro.html|crmReplace:class:'huge40'}
        <div class="description">
          {ts}Establish an introduction for your organization so that when later generating text using AI, it can be swiftly integrated, enabling the AI to grasp the context of your organization.{/ts}<br>
          {ts 1=200}Suggested word count: %1 words.{/ts}
        </div>
      </td>
    </tr>
  </table>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
