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
{include file="CRM/common/WizardHeader.tpl"}
{include file="CRM/Mailing/Form/Count.tpl"}
<div id="help">
{ts}These settings control tracking and responses to recipient actions. The number of recipients selected to receive this mailing is shown in the box to the right. If this count doesn't match your expectations, click <strong>Previous</strong> to review your selection(s).{/ts} 
</div>
<div class="form-item">
  <fieldset><legend>{ts}Tracking{/ts}</legend> 
    <dl>
    <dt class="label">{$form.url_tracking.label}</dt>
        <dd>{$form.url_tracking.html}
            <span class="description">{ts}Track the number of times recipients click each link in this mailing. NOTE: When this feature is enabled, all links in the message body will be automaticallly re-written to route through your CiviCRM server prior to redirecting to the target page.{/ts}</span>
        </dd>
    <dt class="label">{$form.open_tracking.label}</dt>
        <dd>{$form.open_tracking.html}
            <span class="description">{ts}Track the number of times recipients open this mailing in their email software.{/ts}</span>
        </dd>
    </dl>
  </fieldset>
  <fieldset><legend>{ts}Responding{/ts}</legend> 
    <dl>
        <dt class="label ">{$form.forward_replies.label}</dt>
            <dd>{$form.forward_replies.html}
                <span class="description">{ts}If a recipient replies to this mailing, forward the reply to the FROM Email address specified for the mailing.{/ts}</span>
            </dd>
    <dt class="label">{$form.auto_responder.label}</dt>
        <dd>{$form.auto_responder.html} &nbsp; {$form.reply_id.html}
            <span class="description">{ts}If a recipient replies to this mailing, send an automated reply using the selected message.{/ts}</span>
        </dd>
    <dt class="label">{$form.unsubscribe_id.label}</dt>
        <dd>{$form.unsubscribe_id.html}
            <span class="description">{ts}Select the automated message to be sent when a recipient unsubscribes from this mailing.{/ts}</span>
        </dd>
    <dt class="label">{$form.resubscribe_id.label}</dt>
        <dd>{$form.resubscribe_id.html}
            <span class="description">{ts}Select the automated message to be sent when a recipient resubscribes to this mailing.{/ts}</span>
        </dd>
    <dt class="label ">{$form.optout_id.label}</dt>
        <dd>{$form.optout_id.html}
            <span class="description">{ts}Select the automated message to be sent when a recipient opts out of all mailings from your site.{/ts}</span>
        </dd>
   </dl>
  </fieldset>
  <dl>
    <dt>&nbsp;</dt><dd>{$form.buttons.html}</dd>
  </dl>
</div>

{* include jscript to warn if unsaved form field changes *}
{include file="CRM/common/formNavigate.tpl"}


