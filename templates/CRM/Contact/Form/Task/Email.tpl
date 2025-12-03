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
<div class="crm-block crm-form-block crm-contactEmail-form-block">
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
{if $suppressedEmails > 0}
    <div class="status">
        <p>{ts count=$suppressedEmails plural='Email will NOT be sent to %count contacts - (no email address on file, or communication preferences specify DO NOT EMAIL, or contact is deceased).'}Email will NOT be sent to %count contact - (no email address on file, or communication preferences specify DO NOT EMAIL, or contact is deceased).{/ts}</p>
    </div>
{/if}
<table class="form-layout-compressed">
    <tr class="crm-contactEmail-form-block-fromEmailAddress">
      <td class="label">{$form.fromEmailAddress.label}</td>
      <td>
        {$form.fromEmailAddress.html} {help id ="id-from_email" file="CRM/Contact/Form/Task/Email.hlp"}
        {if $show_spf_dkim_notice}
        <span class="description font-red">
          {ts}Only verified domain of email can be set as sender.{/ts} {ts}Otherwise, the email will be hidden on above select list.{/ts}<br>
          {capture assign="from_email_admin_path"}{crmURL p="civicrm/admin/from_email" q="reset=1"}{/capture}
          {ts 1=$from_email_admin_path}Make sure at least one of your email domain verified in <a href="%1">FROM email address</a> list.{/ts}
        </span>
        {/if}
      </td>
    </tr>
    <tr class="crm-contactEmail-form-block-recipient">
       <td class="label">{if $single eq false}{ts}Recipient(s){/ts}{else}{$form.to.label}{/if}</td>
       <td>{$form.to.html}{if $noEmails eq true}&nbsp;&nbsp;{$form.emailAddress.html}{/if}
       <div class="description">{ts}Send email to contacts one by one listed above. Your won't leak email of contacts.{/ts}</div>
       <!--
    <div class="spacer"></div>
       <span class="bold"><a href="#" id="addcc">{ts}Add CC{/ts}</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="#" id="addbcc">{ts}Add BCC{/ts}</a></span>
       -->
       </td>
    </tr>
    <tr class="crm-contactEmail-form-block-cc_id" id="cc" {if ! $form.cc_id.value}style="display:none;"{/if}>
        <td class="label">{$form.cc_id.label}</td><td>{$form.cc_id.html}</td>
    </tr>
    <tr class="crm-contactEmail-form-block-bcc_id" id="bcc" {if ! $form.bcc_id.value}style="display:none;"{/if}>
        <td class="label">{$form.bcc_id.label}</td><td>{$form.bcc_id.html}</td>
    </tr>

{if $emailTask}
    <tr class="crm-contactEmail-form-block-template">
        <td class="label">{$form.template.label}</td>
        <td>
          {$form.template.html}
          {if $templateDefault}
          <script>{literal}
            cj(document).ready(function($){
              var templateId = "{/literal}{$templateDefault}{literal}";
              console.log(templateId);
              if ($("#template").find("option[value="+templateId+"]").length > 0){
                $("#template").val(templateId);
                $("#template").change();
              }
            });
          {/literal}</script>
          {/if}
        </td>
    </tr>
{/if}
    <tr class="crm-contactEmail-form-block-subject">
       <td class="label">{$form.subject.label}</td>
       <td>{$form.subject.html|crmReplace:class:huge}&nbsp;
        <a href="#" onClick="return showToken('Subject', 3);">{$form.token3.label}</a>
	    {help id="id-token-subject" file="CRM/Contact/Form/Task/Email.hlp"}
        <div id='tokenSubject' style="display:none">
	      <input style="border:1px solid #999999;" type="text" id="filter3" size="20" name="filter3" onkeyup="filter(this, 3)"/><br />
	      <span class="description">{ts}Begin typing to filter list of tokens{/ts}</span><br/>
	      {$form.token3.html}
        </div>
       </td>
    </tr>
</table>

{include file="CRM/Contact/Form/Task/EmailCommon.tpl"}

<div class="spacer"> </div>

{if $single eq false}
  {include file="CRM/Contact/Form/Task.tpl"}
{/if}
{if $suppressedEmails > 0}
   {ts count=$suppressedEmails plural='Email will NOT be sent to %count contacts.'}Email will NOT be sent to %count contact.{/ts}
{/if}
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
<script type="text/javascript">
var toContact = ccContact = bccContact = '';

{if $toContact}
    toContact  = {$toContact};
{/if}

{if $ccContact}
    ccContact  = {$ccContact};
{/if}

{if $bccContact}
    bccContact = {$bccContact};
{/if}

{literal}
cj('#addcc').toggle( function() { cj(this).text('Remove CC');
                                  cj('tr#cc').show().find('ul').find('input').focus();
                   },function() { cj(this).text('Add CC');cj('#cc_id').val('');
                                  cj('tr#cc ul li:not(:last)').remove();cj('#cc').hide();
});
cj('#addbcc').toggle( function() { cj(this).text('Remove BCC');
                                   cj('tr#bcc').show().find('ul').find('input').focus();
                    },function() { cj(this).text('Add BCC');cj('#bcc_id').val('');
                                   cj('tr#bcc ul li:not(:last)').remove();cj('#bcc').hide();
});

eval( 'tokenClass = { tokenList: "token-input-list-facebook", token: "token-input-token-facebook", tokenDelete: "token-input-delete-token-facebook", selectedToken: "token-input-selected-token-facebook", highlightedToken: "token-input-highlighted-token-facebook", dropdown: "token-input-dropdown-facebook", dropdownItem: "token-input-dropdown-item-facebook", dropdownItem2: "token-input-dropdown-item2-facebook", selectedDropdownItem: "token-input-selected-dropdown-item-facebook", inputToken: "token-input-input-token-facebook" } ');

var hintText = "{/literal}{ts}Type in a partial or complete name or email address of an existing contact.{/ts}{literal}";
var sourceDataUrl = "{/literal}{crmURL p='civicrm/ajax/getemail' h=0 }{literal}";
var toDataUrl     = "{/literal}{crmURL p='civicrm/ajax/getemail' q='id=1' h=0 }{literal}";

cj( "#to"     ).tokenInput( toDataUrl, { prePopulate: toContact, classes: tokenClass, hintText: hintText });
cj( "#cc_id"  ).tokenInput( sourceDataUrl, { prePopulate: ccContact, classes: tokenClass, hintText: hintText });
cj( "#bcc_id" ).tokenInput( sourceDataUrl, { prePopulate: bccContact, classes: tokenClass, hintText: hintText });
cj( 'ul.token-input-list-facebook, div.token-input-dropdown-facebook' ).css( 'width', '450px' );
</script>
{/literal}
{include file="CRM/common/formNavigate.tpl"}

{if $config->nextEnabled}
<div class="nme-setting-panels">
  <div class="nme-setting-panels-inner">
    <div class="nme-setting-panels-header" id="nme-setting-panels-header">
      <div class="inner">
        <ul data-target-contents="nme-setting-panel" class="nme-setting-panels-tabs">
          <li><a href="#nme-aicompletion" data-target-id="nme-aicompletion">{ts}AI Copywriter{/ts}</a></li>
          <li><a href="#nme-aiimagegeneration" class="is-active" data-target-id="nme-aiimagegeneration">{ts}AI Image Generator{/ts}</a></li>
        </ul>
      </div>
    </div>
    <div class="nme-ai-panels-content" id="nme-setting-panels-content">
      <div id="nme-aicompletion" class="nme-aicompletion nme-setting-panel">
        <div class="nme-setting-panel-inner">
          <h3 class="nme-setting-panel-title">{ts}AI Copywriter{/ts}</h3>
          <div class="nme-setting-panel-content">
            {include file="CRM/AI/AICompletion.tpl"}
          </div>
        </div>
      </div>
      <div id="nme-aiimagegeneration" class="nme-aiimagegeneration nme-setting-panel is-active">
        <div class="nme-setting-panel-inner">
          <h3 class="nme-setting-panel-title">{ts}AI Image Generator{/ts}</h3>
          <div class="nme-setting-panel-content">
            {include file="CRM/AI/AIImageGeneration.tpl"}
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="nme-setting-panels-trigger" data-tooltip data-tooltip-placement="w"><i
 class="zmdi zmdi-settings"></i></div>
</div>
{include file="CRM/common/sidePanel.tpl" type="inline" headerSelector="#nme-setting-panels-header" contentSelector="#nme-setting-panels-content" containerClass="nme-setting-panels" opened="true" userPreference="true" triggerText="AI Assistant" width="500px" fullscreen="true" triggerIcon="packages/AICompletion/images/icon--magic--white.svg"}
{/if}
