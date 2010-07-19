{*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.1                                                |
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
{* WizardHeader.tpl provides visual display of steps thru the wizard as well as title for current step *}
{include file="CRM/common/WizardHeader.tpl"}
{capture assign='reqMark'}<span class="marker"  title="{ts}This field is required.{/ts}">*</span>{/capture}
<div id="help">
    <p>{ts}Use this form to configure the thank-you message and receipting options. Contributors will see a confirmation and thank-you page after whenever an online contribution is successfully processed. You provide the content and layout of the thank-you section below. You also control whether an electronic receipt is automatically emailed to each contributor - and can add a custom message to that receipt.{/ts}</p>
</div>
 
<div class="form-item">
    <fieldset><legend>{ts}Thank-you Message and Receipting{/ts}</legend>
    <dl class="html-adjust">
    <dt>{$form.thankyou_title.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contribution_page' field='thankyou_title' id=$id}{/if}</dt><dd>{$form.thankyou_title.html}</dd>
    <dt>&nbsp;</dt><dd class="description">{ts}This title will be displayed at the top of the thank-you / transaction confirmation page.{/ts}</dd>
    <dt>{$form.thankyou_text.label}</dt><dd>{$form.thankyou_text.html}</dd>
    <dt>&nbsp;</dt><dd class="description">{ts}Enter text (and optional HTML layout tags) for the thank-you message that will appear at the top of the confirmation page.{/ts}</dd>
    <dt>{$form.thankyou_footer.label}</dt><dd>{$form.thankyou_footer.html}</dd>
    <dt>&nbsp;</dt><dd class="description">{ts}Enter link(s) and/or text that you want to appear at the bottom of the thank-you page. You can use this content area to encourage contributors to visit a tell-a-friend page or take some other action.{/ts}</dd>
    <dt></dt><dd>{$form.is_email_receipt.html} {$form.is_email_receipt.label}</dd>
    <dt>&nbsp;</dt><dd class="description">{ts}Check this box if you want an electronic receipt to be sent automatically.{/ts}</dd>
    </dl>
    <div id="receiptDetails">
    <dl class="html-adjust">
    <dt>{$form.receipt_from_name.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contribution_page' field='receipt_from_name' id=$id}{/if}</dt><dd>{$form.receipt_from_name.html}</dd>
    <dt>&nbsp;</dt><dd class="description">{ts}Enter the FROM name to be used when receipts are emailed to contributors.{/ts}</dd>
    <dt>{$form.receipt_from_email.label}{$reqMark}</dt><dd>{$form.receipt_from_email.html}</dd>
    <dt>&nbsp;</dt><dd class="description">{ts}Enter the FROM email address to be used when receipts are emailed to contributors.{/ts}</dd>
    <dt>{$form.receipt_text.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contribution_page' field='receipt_text' id=$id}{/if}</dt><dd>{$form.receipt_text.html}</dd>
    <dt>&nbsp;</dt><dd class="description">{ts}Enter a message you want included at the beginning of emailed receipts. NOTE: This text will be included in both TEXT and HTML versions of receipt emails so we do not recommend including HTML tags / formatting here.{/ts}</dd>
    <dt>{$form.cc_receipt.label}</dt><dd>{$form.cc_receipt.html}</dd>
    <dt>&nbsp;</dt><dd class="description">{ts}If you want member(s) of your organization to receive a carbon copy of each emailed receipt, enter one or more email addresses here. Multiple email addresses should be separated by a comma (e.g. jane@example.org, paula@example.org).{/ts}</dd>
    <dt>{$form.bcc_receipt.label}</dt><dd>{$form.bcc_receipt.html}</dd> 
    <dt>&nbsp;</dt><dd class="description">{ts}If you want member(s) of your organization to receive a BLIND carbon copy of each emailed receipt, enter one or more email addresses here. Multiple email addresses should be separated by a comma (e.g. jane@example.org, paula@example.org).{/ts}</dd>
    </dl>
    </div>
    <div id="crm-submit-buttons">	
    <div class="spacer"></div>   
        <dl><dt></dt><dd >{$form.buttons.html}</dd></dl>  
    </div>
    
    </fieldset>
</div>
	 
<script type="text/javascript">
 showReceipt();
 {literal}
     function showReceipt() {
        var checkbox = document.getElementsByName("is_email_receipt");
        if (checkbox[0].checked) {
            document.getElementById("receiptDetails").style.display = "block";
        } else {
            document.getElementById("receiptDetails").style.display = "none";
        }  
     } 
 {/literal} 
</script>

{* include jscript to warn if unsaved form field changes *}
{include file="CRM/common/formNavigate.tpl"}

