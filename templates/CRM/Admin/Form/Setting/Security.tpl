<div class="crm-block crm-form-block crm-map-form-block">
<div id="help">
    {ts}{/ts}
</div>
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
    {if $form.decryptExcelOption}
    <table class="form-layout">
        <tr class="crm-miscellaneous-form-block-decryptExcelOption">
            <td class="label">{$form.decryptExcelOption.label}</td>
            <td>{$form.decryptExcelOption.html}</td>
        </tr>
        <tr class="crm-miscellaneous-form-block-decryptExcelPwd">
            <td class="label">{$form.decryptExcelPwd.label}</td>
            <td>{$form.decryptExcelPwd.html}</td>
        </tr>
    </table>
    {/if}
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
<script>{literal}
cj(document).ready(function($){
    var decryptSelectExcelOption = ".crm-miscellaneous-form-block-decryptExcelOption input[type=radio]:checked";
    var decryptExcelOption = ".crm-miscellaneous-form-block-decryptExcelOption input[type=radio]";
    var decryptExcelPwd = ".crm-miscellaneous-form-block-decryptExcelPwd";
    if ($(decryptSelectExcelOption).val() != 2) {
        $(decryptExcelPwd).hide();
        $("#decryptExcelPwd").prop('required', false);
    }
    else {
        $(decryptExcelPwd).show();
         $("#decryptExcelPwd").prop('required', true);
    }
    $(decryptExcelOption).click( function() {
        if ( $(this).val() == "2" ) {
            $(decryptExcelPwd).show();
            $("#decryptExcelPwd").prop('required', true);
        } else {
            $(decryptExcelPwd).hide();
            $("#decryptExcelPwd").prop('required', false);
        }
    });
});
{/literal}</script>