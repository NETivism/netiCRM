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
{include file="CRM/common/WizardHeader.tpl"}
<div id="pcp-form" class="crm-block crm-form-block crm-contribution-contributionpage-pcp-form-block">
{if !$profile}
	{capture assign=pUrl}{crmURL p='civicrm/admin/uf/group' q="reset=1"}{/capture}
	<div class="status message">
	{ts 1=$pUrl}No Profile with a user account registration option has been configured / enabled for your site. You need to <a href='%1'>configure a Supporter profile</a> first. It will be used to collect or update basic information from users while they are creating a Personal Campaign Page.{/ts}
	</div>
{/if}
<div id="help">
{ts}Allow constituents to create their own personal fundraising pages linked to this contribution page.{/ts}
</div>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
<table class="form-layout">
	<tr  class="crm-contribution-contributionpage-pcp-form-block-is_active">
	    <td class="label">&nbsp;</td>
	    <td>{$form.is_active.html} {$form.is_active.label}</td>
	</tr>
</table>

<div class="spacer"></div>

<div id="pcpFields">
<table class="form-layout">
   <tr class="crm-contribution-contributionpage-pcp-form-block-is_approval_needed">
	    <td class="label">{$form.is_approval_needed.label}</td>
	    <td>{$form.is_approval_needed.html} {help id="id-approval_needed"}</td>
   </tr>
   <tr class="crm-contribution-contributionpage-pcp-form-block-notify_email">
	    <td class="label">{$form.notify_email.label}</td>
	    <td>{$form.notify_email.html} {help id="id-notify"}</td>
   </tr>       
   <tr class="crm-contribution-contributionpage-pcp-form-block-supporter_profile_id">
	    <td class="label">{$form.supporter_profile_id.label} <span class="marker"> *</span></td>
	    <td>{$form.supporter_profile_id.html} {help id="id-supporter_profile"}</td>
   </tr>
   <tr class="crm-contribution-contributionpage-pcp-form-block-link_text">
	    <td class="label">{$form.link_text.label}</td>
	    <td>
        {$form.link_text.html|crmReplace:class:huge}
        <div class="description">
          {ts}Text for the link inviting constituents to create a Personal Contribution Page. This link will appear on the Contribution Thank-you page as well as on each Personal Campaign Page.{/ts}{ts}Create links to this contribution page by copying and pasting the following URL into any web page.{/ts}<br />
          {ts}URL{/ts}: <a href="{crmURL a=true p='civicrm/contribute/campaign' q="action=add&reset=1&pageId=`$pageId`"}">{crmURL a=true p='civicrm/contribute/campaign' q="action=add&reset=1&pageId=`$pageId`"}</a><br />
        
        </div>
      </td>
   </tr>
</table>
</div>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
{include file="CRM/common/showHideByFieldValue.tpl" 
    trigger_field_id    = "is_active"
    trigger_value       = "true"
    target_element_id   = "pcpFields" 
    target_element_type = "block"
    field_type          = "radio"
    invert              = "false"
}

{* include jscript to warn if unsaved form field changes *}
{include file="CRM/common/formNavigate.tpl"}
