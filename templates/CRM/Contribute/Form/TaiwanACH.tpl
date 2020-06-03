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
    <td class="label">{$form.ach_amount.label}</td>
    <td>{$form.currency.html} {$form.ach_amount.html}</td>
  </tr>
  <tr>
    <td class="label">{$form.ach_processor_id.label}</td>
    <td>{$form.ach_processor_id.html}</td>
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
  doCheckACHPaymentType();
  $("select#ach_payment_type").change(doCheckACHPaymentType);
  $("#ach_identifier_number").keyup(doCheckTWorOrgID).blur(doCheckTWorOrgID);

  function doCheckACHPaymentType() {
    $("tr.ach-bank-code, tr.ach-postoffice-acc-type, tr.ach-bank-branch").hide();
    if ($("select#ach_payment_type").val()) {
      if ($("select#ach_payment_type").val() == 'ACH Bank') {
        $("tr.ach-bank-code").show();
        $("tr.ach-bank-branch").show();
      }
      else {
        $("tr.ach-postoffice-acc-type").show();
      }
    }
  }

  /**
   * Valid receipt id field
   * @return boolean  passed or not
   */
  function doCheckTWorOrgID(){
    while($('#ach_identifier_number').parent().find('.error-twid').length>=1){
      $('#ach_identifier_number').parent().find('.error-twid').remove();
    }
    var value = $('#ach_identifier_number').val();
    if(validTWID(value) || validOrgID(value) || validResidentID(value)){
      $('#ach_identifier_number').removeClass('error');
      return true;
    }else{
      $('#ach_identifier_number').addClass('error').parent().append('<label for="ach_identifier_number" class="error-twid" style="padding-left: 10px;color: #e55;">{/literal}{ts}Please enter correct Data ( in valid format ).{/ts}{literal}</label>');
      return false;
    }
  }
});

/**
 * Validate TW ID, Should match TW ID formula.
 * @param  String value
 * @return boolean
 */
function validTWID(value){
  if(value=='')return true;
  value = value.toUpperCase();
  var tab = "ABCDEFGHJKLMNPQRSTUVXYWZIO";
  var A1 = new Array (1,1,1,1,1,1,1,1,1,1,2,2,2,2,2,2,2,2,2,2,3,3,3,3,3,3 );
  var A2 = new Array (0,1,2,3,4,5,6,7,8,9,0,1,2,3,4,5,6,7,8,9,0,1,2,3,4,5 );
  var Mx = new Array (9,8,7,6,5,4,3,2,1,1);

  if ( value.length != 10 ){
    return false;
  }
  var i = tab.indexOf( value.charAt(0) );
  if ( i == -1 ){
    return false;
  }
  var sum = A1[i] + A2[i]*9;

  for( i=1; i<10; i++ ){
    var v = parseInt( value.charAt(i) );
    if ( isNaN(v) ){
      return false;
    }
    sum = sum + v * Mx[i];
  }
  if ( sum % 10 != 0 ){
    return false;
  }
  return true;
}

/**
 * Validate Organize ID. Should be 8 numbers.
 * @param  String value
 * @return boolean
 */
function validOrgID(value){
  if(value=='')return true;
  var checkRegex = RegExp("^[0-9]{8}$");
  if(checkRegex.test(value)){
    return true;
  }
  return false;
}

/**
 * Validate Resident Permit ID, Should match Resident Permit ID formula.
 * @param  String value
 * @return boolean
 */
function validResidentID(value) {
  if (value == '') return true;
  value = value.toUpperCase();
  var tab = "ABCDEFGHJKLMNPQRSTUVXYWZIO";
  var c = (tab.indexOf(value.substr(0, 1)) + 10) + '' + (tab.indexOf(value.substr(1, 1)) % 10) + value.substr(2, 8);
  var checkCode = parseInt(c.substr(0, 1));
  for (var i = 1; i <= 9; i++) {
    checkCode += (parseInt(c.substr(i, 1)) * (10 - i)) % 10;
  }
  checkCode += parseInt(c.substr(10, 1));
  if (checkCode % 10 == 0) {
    return true;
  }
  return false;
}
</script>
{/literal}
{include file="CRM/common/chosen.tpl" selector="select#ach_bank_code" select_width="300"}
