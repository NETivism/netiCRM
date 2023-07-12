<div id="help">
  {ts}Set up the organization profile, so that when using AI to generate text later, it can be quickly incorporated to let the AI understand the context of your organization.{/ts}
</div>
<div class="crm-block crm-form-block crm-aiOrganizationProfile-form-block">
  <fieldset>
    <legend>{ts}Organization profile{/ts}</legend>
    <table class="form-layout">
      <tr class="crm-aiOrganizationProfile-form-block-orgprofile">
        <td class="label">{$form.aiOrganizationProfile.label}</td>
        <td>
          {$form.aiOrganizationProfile.html|crmReplace:class:'huge40'}
          <div class="description">
            {ts}Set up the organization profile, so that when using AI to generate text later, it can be quickly incorporated to let the AI understand the context of your organization.{/ts}
          </div>
        </td>
      </tr>
    </table>
  </fieldset>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
