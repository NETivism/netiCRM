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
{* WizardHeader.tpl provides visual display of steps thru the wizard as well as title for current step *}
{include file="CRM/common/WizardHeader.tpl"}
{capture assign='reqMark'}<span class="marker"  title="{ts}This field is required.{/ts}">*</span>{/capture}
<div class="crm-block crm-form-block crm-contribution-contributionpage-thankyou-form-block">
<div id="help">
    <p>{ts}Use this form to configure the thank-you message and payment notification options. Contributors will see a confirmation and thank-you page after whenever an online contribution is successfully processed. You provide the content and layout of the thank-you section below. You also control whether an electronic payment notification is automatically emailed to each contributor - and can add a custom message to that notification.{/ts}</p>
</div>
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>				    
    <table class="form-layout-compressed">
    <tr class="crm-contribution-contributionpage-thankyou-form-block-thankyou_title">
       <td class="label">{$form.thankyou_title.label}{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contribution_page' field='thankyou_title' id=$id}{/if}</td>
       <td class="html-adjust">{$form.thankyou_title.html}<br />
           <span class="description">{ts}This title will be displayed at the top of the thank-you / transaction confirmation page.{/ts}</span>
       </td>
    </tr>
    <tr class="crm-contribution-contributionpage-thankyou-form-block-thankyou_text">
       <td class="label">{$form.thankyou_text.label}</td>
       <td class="html-adjust">{$form.thankyou_text.html}<br />
       	   <span class="description">{ts}Enter text (and optional HTML layout tags) for the thank-you message that will appear at the top of the confirmation page.{/ts}</span>
       </td>
    </tr>
    <tr class="crm-contribution-contributionpage-thankyou-form-block-thankyou_footer">
       <td class="label">{$form.thankyou_footer.label}</td>
       <td class="html-adjust">{$form.thankyou_footer.html}<br />
       	   <span class="description">{ts}Enter link(s) and/or text that you want to appear at the bottom of the thank-you page. You can use this content area to encourage contributors to visit a tell-a-friend page or take some other action.{/ts}</span>
       </td>
    </tr>
    <tr class="crm-contribution-contributionpage-thankyou-form-block-is_email_receipt">
       <td class="label"></td>
       <td class="html-adjust">{$form.is_email_receipt.html}{$form.is_email_receipt.label}<br />
       	   <span class="description">{ts}Check this box if you want an electronic payment notification to be sent automatically.{/ts}</span>
       </td>
    </tr>
    </table>
    <table id="receiptDetails" class="form-layout-compressed">
    <tr class="crm-contribution-contributionpage-thankyou-form-block-receipt_from_name">
	<td class="label">{$form.receipt_from_name.label}{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contribution_page' field='receipt_from_name' id=$id}{/if}
    	</td>
    	<td class="html-adjust">{$form.receipt_from_name.html}<br />
    	    <span class="description">{ts}Enter the FROM name to be used when payment notifications are emailed to contributors.{/ts}</span>
	</td>
    </tr>
    <tr class="crm-contribution-contributionpage-thankyou-form-block-receipt_from_email">
	<td class="label">{$form.receipt_from_email.label}{$reqMark}
	</td>
	<td class="html-adjust">{$form.receipt_from_email.html}
      {capture assign="from_email_text"}{ts}FROM Email Address{/ts}{/capture}
      <a href="{crmURL p='civicrm/admin/from_email' q='reset=1'}" target="_blank">{ts 1=$from_email_text}Add %1{/ts}</a><br>
      {if $show_spf_dkim_notice}
        <span class="description font-red">
          {ts}All{/ts} {ts 1='SPF / DKIM' 2=''}%1 for your email %2 is not verified.{/ts}
          {docURL page="Configure SPF Record" text="Learn how to config settings"}
        </span>
      {/if}
      <span class="description font-red">
        {ts}Only verified domain of email can be set as sender.{/ts} {ts}Otherwise, the email will be hidden on above select list.{/ts}<br>
        {capture assign="from_email_admin_path"}{crmURL p="civicrm/admin/from_email" q="reset=1"}{/capture}
        {ts 1=$from_email_admin_path}Make sure at least one of your email domain verified in <a href="%1">FROM email address</a> list.{/ts}
      </span>
	    <span class="description">
        {ts 1=`$mail_providers`}Do not use free mail address as mail sender. (eg. %1){/ts}
      </span>
	</td>
    </tr>
    <tr class="crm-contribution-contributionpage-thankyou-form-block-receipt_text">
	<td class="label">{$form.receipt_text.label}{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contribution_page' field='receipt_text' id=$id}{/if}
	</td>
	<td class="html-adjust">
        <span class="helpIcon" id="helphtml">
            <a class="token-trigger" href="#" onClick="return showToken('Html', 2);">{$form.token2.label}</a>
            {help id="id-token-html" file="CRM/Contact/Form/Task/Email.hlp"}
            <div id="tokenHtml" style="display:none;">
                <input style="border:1px solid #999999;" type="text" id="filter2" size="20" name="filter2" onkeyup="filter(this, 2)"/><br />
                <span class="description">{ts}Begin typing to filter list of tokens{/ts}</span><br/>
                {$form.token2.html}
            </div>
        </span>
        <br>
        <br>
        {$form.receipt_text.html}<br />
	    <span class="description">{ts}Enter a message you want included at the beginning of emailed payment notifiction. NOTE: This text will be included in both TEXT and HTML versions of payment notificationsemails so we do not recommend including HTML tags / formatting here.{/ts}<br /></span>
	</td>
    </tr>
    <tr class="crm-contribution-contributionpage-thankyou-form-block-cc_receipt">
	<td class="label">{$form.cc_receipt.label}
    	</td>
	<td class="html-adjust">{$form.cc_receipt.html}<br />
	    <span  class="description">{ts}If you want member(s) of your organization to receive a carbon copy of each emailed payment notification, enter one or more email addresses here. Multiple email addresses should be separated by a comma (e.g. jane@example.org, paula@example.org).{/ts} <span class="font-red">{ts}Do not use same email address with from address.{/ts}</span></span>
	</td>
    </tr>
    <tr class="crm-contribution-contributionpage-thankyou-form-block-bcc_receipt">
	<td class="label">{$form.bcc_receipt.label}
	</td>
	<td class="html-adjust">{$form.bcc_receipt.html}<br />
	    <span class="description">{ts}If you want member(s) of your organization to receive a BLIND carbon copy of each emailed payment notification, enter one or more email addresses here. Multiple email addresses should be separated by a comma (e.g. jane@example.org, paula@example.org).{/ts} <span class="font-red">{ts}Do not use same email address with from address.{/ts}</span></span>
	</td>
    </tr>
    <tr class="crm-contribution-contributionpage-thankyou-form-block-recur_fail_notify">
  <td class="label">{$form.recur_fail_notify.label}
  </td>
  <td class="html-adjust">{$form.recur_fail_notify.html}<br />
      <span class="description">{ts}If you want member(s) of your organization to receive a message of each recurring failed notification, enter one or more email addresses here. Multiple email addresses should be separated by a comma (e.g. jane@example.org, paula@example.org).{/ts} <span class="font-red">{ts}Do not use same email address with from address.{/ts}</span></span>
  </td>
    </tr>
    </table>
    {if $form.is_send_sms}
        <table class="form-layout-compressed">
            <tr class="crm-contribution-contributionpage-thankyou-form-block-is_send_sms">
                <td class="label"></td>
                <td class="html-adjust">{$form.is_send_sms.html}{$form.is_send_sms.label}<br />
                    <span class="description">{ts}Check this box if you want an SMS message to be sent automatically.{/ts}</span>
                </td>
            </tr>
        </table>
        <table id="smsDetails" class="form-layout-compressed">
            <tr class="crm-contribution-contributionpage-thankyou-form-block-sms_text">
                <td class="label">{$form.sms_text.label}</td>
                <td class="html-adjust">
                    <span class="helpIcon" id="helphtml">
                        <a class="token-trigger" href="#" onClick="return showToken('Text', 1);">{$form.token1.label}</a>
                        <div id="tokenText" style="display:none;">
                            <input style="border:1px solid #999999;" type="text" id="filter1" size="20" name="filter1" onkeyup="filter(this, 1)"/><br />
                            <span class="description">{ts}Begin typing to filter list of tokens{/ts}</span><br/>
                            {$form.token1.html}
                        </div>
                    </span>
                {$form.sms_text.html}
                </td>
            </tr>
        </table>
    {/if}
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
	 
<script type="text/javascript">
 showReceipt();
 showSMS();
 {literal}
     function showReceipt() {
        var checkbox = document.getElementsByName("is_email_receipt");
        if (checkbox[0].checked) {
            cj(".notice").remove();
            document.getElementById("receiptDetails").style.display = "block";
            showMessage();
        } else {
            cj(".alter").remove();
            document.getElementById("receiptDetails").style.display = "none";
            showMessage();
        }  
     } 
     function showSMS() {
        var checkbox = document.getElementsByName("is_send_sms");
        if (checkbox.length) {
            if (checkbox[0].checked) {
                document.getElementById("smsDetails").style.display = "block";
            } else {
                document.getElementById("smsDetails").style.display = "none";
            }
        }
     }
    function showMessage() {
        var isChecked = cj('#is_email_receipt:checked').length;
        var isNoticeShow = cj(".crm-contribution-contributionpage-thankyou-form-block-is_email_receipt span.notice").length;
        var isAlterShow = cj(".crm-contribution-contributionpage-thankyou-form-block-is_email_receipt span.alter").length;
        if (isChecked == 1) {
            if (isNoticeShow == 0) {
                cj(".crm-contribution-contributionpage-thankyou-form-block-is_email_receipt .description").after( "<span class='notice'>{/literal}{ts}If you check Email Payment Notification To User, System will automatically generate receipt for contribution and mark Receipt Date as Payments Date after the payment is completed.{/ts}{literal}</span>" );
            } else {
                cj(".notice").remove();
            }
            if (isAlterShow != 0) {
                cj(".alter").remove();
            }
        } else {
            if (isAlterShow == 0) {
                cj(".crm-contribution-contributionpage-thankyou-form-block-is_email_receipt .description").after( "<span class='alter' style='color:red'>{/literal}{ts}If you uncheck Email Payment Notification To User, System will not automatically generate receipt for contribution and would not have Receipt Date and Receipt ID, administrator needs generate the receipt by himself.{/ts}{literal}</span>");
            } else {
                cj(".alter").remove();
            }
            if (isNoticeShow != 0) {
                cj(".notice").remove();
            }
        }
    }
 {/literal}
</script>

{* This for token popup *}
{include file="CRM/Mailing/Form/InsertTokens.tpl"}
{* include jscript to warn if unsaved form field changes *}
{include file="CRM/common/formNavigate.tpl"}
