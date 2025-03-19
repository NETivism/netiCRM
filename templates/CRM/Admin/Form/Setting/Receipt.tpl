{*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2010                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}
<div class="crm-block crm-form-block crm-form-block">
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>                         
      <table class="form-layout-compressed">
        <tr class="crm-form-block-receiptLogo">
            <td class="label">{$form.receiptLogo.label}</td>
            <td class="value">
                {if $receiptLogoUrl}
                <img style="max-height: 103px;" src="{$receiptLogoUrl}">
                <a class="delete-image" href="javascript:void(0);" data-field="deleteReceiptLogo">{ts}Delete{/ts}</a>
                <br/>
                {/if}
                {$form.receiptLogo.html}<br />
                <span class="description">{ts}Please upload the logo image to be displayed on the receipt.{/ts}</span>
            </td>
        </tr>
        <tr class="crm-form-block-receiptPrefix">
            <td class="label">{$form.receiptPrefix.label}</td><td>{$form.receiptPrefix.html}<br />    
            <span class="description">
            {ts}The prefix always append 'A' for online payment and 'M' for manual payment for prevent serial issue when date change.{/ts}<br>
            {ts}You can have different prefix of each contribution type by filling token '!acc' into this field. And fill the 'Accounting Code' in contribution type setting page.{/ts}<br>
            {ts}Receipt ID prefix. Can be numberic or alphabetic.{/ts}<br>
            {ts}Use this screen to configure formats for date display and date input fields. Defaults are provided for standard United States formats. Settings use standard POSIX specifiers.{/ts} {help id='date-format'}
            </span>
            </td>
        </tr>
        <tr class="crm-form-block-receiptDescription">
            <td class="label">{$form.receiptDescription.label}</td><td>{$form.receiptDescription.html}<br />    
            <span class="description">{ts}Description will appear at the end of receipt.{/ts}</span></td>
        </tr>
        <tr class="crm-form-block-receiptOrgInfo">
            <td class="label">{$form.receiptOrgInfo.label}</td><td>{$form.receiptOrgInfo.html}<br />
            <span class="description">{ts}Organization info will appear at the end of receipt.{/ts}</span></td>
        </tr>
        <tr class="crm-form-block-receiptYesNo">
            <td class="label">{$form.receiptYesNo.label}</td><td>{$form.receiptYesNo.html}<br />
            <span class="description">{ts}Choose a Checkbox or a Radio field. If the field is selected to 'Yes' by contributor, then the receipt title and serial field will be required. On the contrary, the receipt title and serial field will hide.{/ts}</span></td>
        </tr>
        <tr class="crm-form-block-receiptTitle">
            <td class="label">{$form.receiptTitle.label}</td><td>{$form.receiptTitle.html}<br />
            <span class="description">{ts}When your receipt title save in another field, use this to select the field.{/ts}</span></td>
        </tr>
        <tr class="crm-form-block-receiptSerial">
            <td class="label">{$form.receiptSerial.label}</td><td>{$form.receiptSerial.html}<br />
            <span class="description">{ts}When your serial code save in another field, use this to select the field.{/ts}</span></td>
        </tr>
        <tr class="crm-form-block-receiptDonorCredit">
            <td class="label">{$form.receiptDonorCredit.label}</td><td>{$form.receiptDonorCredit.html}<br />
            <span class="description">{ts}When use custom field to record donor credit, use this to select the field.{/ts}</span></td>
        </tr>
        <tr class="crm-form-block-customDonorCredit">
            <td class="label">{$form.customDonorCredit.label}</td>
            <td>{$form.customDonorCredit.html}<br />
              <span class="description">{ts}Select which options to show to donors when they make contributions.{/ts}</span>
            </td>
        </tr>
        <tr class="crm-form-block-anonymousDonorCreditDefault" id="anonymousDonorCreditDefault-tr">
            <td class="label">{$form.anonymousDonorCreditDefault.label} <span class="crm-marker">*</span></td>
            <td>{$form.anonymousDonorCreditDefault.html}<br />
              <span class="description">{ts}This name will be used when donor selects "I don't agree to disclose name". Examples: "Anonymous", "Kind-hearted Person", etc.{/ts}</span>
            </td>
        </tr>
        <script type="text/javascript">
        {literal}
          cj(function($){
            function toggleAnonymousField() {
              if ($('input[name="customDonorCredit[anonymous]"]').is(':checked')) {
                $('#anonymousDonorCreditDefault-tr').show();
                $('#anonymousDonorCreditDefault').addClass('required');
              } else {
                $('#anonymousDonorCreditDefault-tr').hide();
                $('#anonymousDonorCreditDefault').removeClass('required');
              }
            }

            toggleAnonymousField();
            $('input[name="customDonorCredit[anonymous]"]').change(toggleAnonymousField);

            $('form').submit(function() {
              if ($('input[name="customDonorCredit[anonymous]"]').is(':checked') && 
                  !$('#anonymousDonorCreditDefault').val()) {
                alert("{/literal}{ts escape='js'}Please enter a default name for anonymous donors.{/ts}{literal}");
                $('#anonymousDonorCreditDefault').focus();
                return false;
              }
              return true;
            });
          });
        {/literal}
        </script>
        <tr class="crm-form-block-receiptAddrType">
            <td class="label">{$form.receiptAddrType.label}</td>
            <td>{$form.receiptAddrType.html}</td>
        </tr>
        <tr class="crm-form-block-receiptTypeDefault">
            <td class="label">{$form.receiptTypeDefault.label}</td>
            <td>{$form.receiptTypeDefault.html}</td>
        </tr>
        <tr class="crm-form-block-uploadBigStamp">
            <td class="label">{$form.uploadBigStamp.label}</td>
            <td class="value">
                {if $imageBigStampUrl}
                <img style="max-height: 103px;" src="{$imageBigStampUrl}">
                <a class="delete-image" href="javascript:void(0);" data-field="deleteBigStamp">{ts}Delete{/ts}</a>
                <br/>
                {/if}
                {$form.uploadBigStamp.html}<br />
            <span class="description">{ts 1=$stampDocUrl}This image will show on receipt.The position please click <a href='%1' target='_blank'>here</a> to get more information.{/ts}</span></td>
        </tr>
        <tr class="crm-form-block-uploadSmallStamp">
            <td class="label">{$form.uploadSmallStamp.label}</td>
            <td class="value">
                {if $imageSmallStampUrl}
                <img style="max-height: 43px;" src="{$imageSmallStampUrl}">
                <a class="delete-image" href="javascript:void(0);" data-field="deleteSmallStamp">{ts}Delete{/ts}</a>
                <br/>
                {/if}
                {$form.uploadSmallStamp.html}<br />
            <span class="description">{ts 1=$stampDocUrl}This image will show on receipt.The position please click <a href='%1' target='_blank'>here</a> to get more information.{/ts}</span></td>
        </tr>
        {if $form.receiptEmailAuto}
        <tr class="crm-form-block-receiptEmailAuto">
            <td class="label">{$form.receiptEmailAuto.label}</td><td>{$form.receiptEmailAuto.html}<br />    
            <span class="description">{ts}Check to attach formal receipt PDF on notification email after every complete transaction.{/ts}</span>
            <span class="description font-red">{ts}This option only effect transaction from contribution page and belonged page enable notification confirmation and the contribution type is tax-deductible.{/ts}</span>
            </td>
        </tr>
        {/if}
        {if $form.receiptEmailEncryption}
        <tr class="crm-form-block-receiptEmailEncryption">
            <td class="label">{$form.receiptEmailEncryption.label}</td><td>{$form.receiptEmailEncryption.html}<br />
            <span class="description">{ts}After activation, a password will be added to the email receipt. If the receipt includes tax filing credentials (such as ID number or Uniform Number), encryption will be done using these credentials. If these fields are not present, the recipient's email address will be used for encryption.{/ts}</span>
            </td>
        </tr>
        {/if}
        {if $form.receiptEmailEncryptionText}
        <tr class="crm-form-block-receiptEmailEncryptionText">
            <td class="label">{$form.receiptEmailEncryptionText.label}</td><td>{$form.receiptEmailEncryptionText.html}<br />
            <span class="description">{ts}If no specific text is set, the default text will be: Your PDF receipt is encrypted. The password is either your tax certificate number or, if not provided, your email address.{/ts}</span>
            </td>
        </tr>
        {/if}
        {if $form.receiptDisplayLegalID}
        <tr class="crm-form-block-receiptDisplayLegalID">
            <td class="label">{$form.receiptDisplayLegalID.label}</td><td>{$form.receiptDisplayLegalID.html}<br />    
            <span class="description">{ts}How to display the legal ID in receipt file. (The sic code of an organization will always completely display.){/ts}</span>
            </td>
        </tr>
        {/if}
      </table>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>     
<div class="spacer"></div>
{literal}
<script type="text/javascript">
    cj(function($){
        $('.delete-image').click(function(){
            deleteFieldName = $(this).attr('data-field');
            $('[name='+deleteFieldName+']').val(1);
            $(this).parent().find('img').css('filter','brightness(50%)');
        });
        $('input[name^="receiptDisplayLegalID"]').click(function() {
            const selectedValue = $('input[name^="receiptDisplayLegalID"]:checked').val();
            $('.crm-form-block-receiptEmailEncryption .warning-message').remove();
            if (selectedValue !== 'complete') {
                $('input[name="receiptEmailEncryption"]').prop('checked', false);
                $('.crm-form-block-receiptEmailEncryption .description').after(
                    '<span class="description warning-message font-red"; display: block; margin-top: 5px;">' +
                    '{/literal}{ts}When the legal ID display option is not set to complete display, email receipt encryption cannot be enabled.{/ts}{literal}' +
                    '</span>'
                );
            }
        });
    })
</script>
<style type="text/css">
    .label-test{
        vertical-align: top;
        padding-left: 5px;
        font-size: 11px;
        color: #f44336;
        display: inline;
    }
</style>
{/literal}
</div>