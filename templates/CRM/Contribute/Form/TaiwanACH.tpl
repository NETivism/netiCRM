<table class="form-layout-compressed">
  {if !$contact_id && $action eq 1}
    {include file="CRM/Contact/Form/NewContact.tpl"}
  {else}
    <td class="font-size12pt label"><strong>{ts}Contributor{/ts}</strong></td><td class="font-size12pt"><strong>{$displayName}</strong></td>
  {/if}
  <tr>
    <td class="label">{$form.ach_contribution_page_id.label}</td>
    <td>{$form.ach_contribution_page_id.html}</td>
  </tr>
  <tr>
    <td class="label">{$form.ach_total_amount.label}</td>
    <td>{$form.currency.html} {$form.ach_total_amount.html}</td>
  </tr>
  <tr>
    <td class="label">{$form.ach_payment_type.label}</td>
    <td>{$form.ach_payment_type.html}</td>
  </tr>
  <tr>
    <td></td>
    <td><table class="form-layout"><tbody>
      <tr class="ach-bank-code">
        <td class="label">{$form.ach_bank_code.label} <span class="marker" title="This field is required.">*</span></td>
        <td>{$form.ach_bank_code.html}</td>
      </tr>
      <tr class="ach-bank-branch">
        <td class="label">{$form.ach_bank_branch.label}</td>
        <td>{$form.ach_bank_branch.html}</td>
      </tr>
      <tr class="ach-postoffice-acc-type">
        <td class="label">{$form.ach_postoffice_acc_type.label} <span class="marker" title="This field is required.">*</span></td>
        <td>{$form.ach_postoffice_acc_type.html}</td>
      </tr>
    </tbody></table></td>
  </tr>
  <tr>
    <td class="label">{$form.ach_bank_account.label}</td>
    <td>{$form.ach_bank_account.html}</td>
  </tr>
  <tr>
    <td class="label">{$form.ach_identifier_number.label}</td>
    <td>{$form.ach_identifier_number.html}</td>
  </tr>
  <tr>
    <td class="label">{$form.ach_stamp_verification.label}</td>
    <td>{$form.ach_stamp_verification.html}</td>
  </tr>
</table>

{*Custom Data*}
{include file="CRM/Custom/Form/CustomData.tpl"}

<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>

{literal}
<script type="text/javascript">
cj(document).ready( function($) {
  $("tr.ach-bank-code, tr.ach-postoffice-acc-type, tr.ach-bank-branch").hide();
  $("select#ach_payment_type").change(function(){
    $("tr.ach-bank-code, tr.ach-postoffice-acc-type, tr.ach-bank-branch").hide();
    if ($(this).val()) {
      if ($(this).val() == 'bank') {
        $("tr.ach-bank-code").show();
        $("tr.ach-bank-branch").show();
      }
      else {
        $("tr.ach-postoffice-acc-type").show();
      }
    }
  });
});
</script>
{/literal}
{include file="CRM/common/chosen.tpl" selector="select#ach_bank_code" select_width="300"}
