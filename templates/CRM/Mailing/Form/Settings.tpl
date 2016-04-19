{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.1                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2011                                |
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

<div class="crm-block crm-form-block crm-mailing-settings-form-block">
{include file="CRM/common/WizardHeader.tpl"}
<div id="help">
    {ts}These settings control tracking and responses to recipient actions. The number of recipients selected to receive this mailing is shown in the box to the right. If this count doesn't match your expectations, click <strong>Previous</strong> to review your selection(s).{/ts} 
</div>
{include file="CRM/Mailing/Form/Count.tpl"}
<div class="crm-block crm-form-block crm-mailing-settings-form-block">
  <fieldset><legend>{ts}Tracking{/ts}</legend> 
    <table class="form-layout"><tr class="crm-mailing-settings-form-block-url_tracking">
    <td class="label">{$form.url_tracking.label}</td>
        <td>{$form.url_tracking.html}
            <span class="description">{ts}Track the number of times recipients click each link in this mailing. NOTE: When this feature is enabled, all links in the message body will be automaticallly re-written to route through your CiviCRM server prior to redirecting to the target page.{/ts}</span>
        </td></tr><tr class="crm-mailing-settings-form-block-open_tracking">
    <td class="label">{$form.open_tracking.label}</td>
        <td>{$form.open_tracking.html}
            <span class="description">{ts}Track the number of times recipients open this mailing in their email software.{/ts}</span>
        </td></tr>
    </table>
  </fieldset>
  <fieldset><legend>{ts}Responding{/ts}</legend> 
    <table class="form-layout">
    <tr class="crm-mailing-settings-form-block-unsubscribe_id crm-message-select"><td class="label">{$form.unsubscribe_id.label}</td>
        <td>{$form.unsubscribe_id.html}
            <div class="description">{ts}Select the automated message to be sent when a recipient unsubscribes from this mailing.{/ts}</div>
        </td>
    <tr>
    <tr class="crm-mailing-settings-form-block-resubscribe_id crm-message-select"><td class="label">{$form.resubscribe_id.label}</td>
        <td>{$form.resubscribe_id.html}
            <div class="description">{ts}Select the automated message to be sent when a recipient resubscribes to this mailing.{/ts}</div>
        </td>
    </tr>
    <tr class="crm-mailing-settings-form-block-optout_id crm-message-select"><td class="label ">{$form.optout_id.label}</td>
        <td>{$form.optout_id.html}
            <div class="description">{ts}Select the automated message to be sent when a recipient opts out of all mailings from your site.{/ts}</div>
        </td>
    </tr>
   </table>
  </fieldset>
  <fieldset><legend>{ts}Online Publication{/ts}</legend>
  	<table class="form-layout">
		<tr class="crm-mailing-group-form-block-visibility">
   		<td class="label">{ts}{$form.visibility.label}{/ts}</td><td>{$form.visibility.html} {help id="mailing-visibility"}
   		</td>
   		</tr>
  	</table>
  </fieldset>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl"}</div>
</div>

{* include jscript to warn if unsaved form field changes *}
{include file="CRM/common/formNavigate.tpl"}
</div>

