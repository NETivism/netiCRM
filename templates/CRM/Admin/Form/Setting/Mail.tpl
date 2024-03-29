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
{capture assign=docLink}{docURL page="CiviMail Admin" text="CiviMail Administration Guide"}{/capture}
<div class="crm-block crm-form-block crm-mail-form-block">
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
      <table class="form-layout-compressed">
        {if $admin}
        <tr class="crm-mail-form-block-mailerBatchLimit">
            <td class="label">{$form.mailerBatchLimit.label}</td><td>{$form.mailerBatchLimit.html}<br />
            <span class="description">{ts}Throttle email delivery by setting the maximum number of emails sent during each CiviMail run (0 = unlimited).{/ts}</span></td>
        </tr>
        <tr class="crm-mail-form-block-mailerJobSize">
            <td class="label">{$form.mailerJobSize.label}</td><td>{$form.mailerJobSize.html}<br />
            <span class="description">{ts}If you want to utilize multi-threading enter the size you want your sub jobs to be split into (0 = disables multi-threading and processes mail as one single job - batch limits still apply){/ts}</span></td>
        </tr>
        <tr class="crm-mail-form-block-verpSeparator">
            <td class="label">{$form.verpSeparator.label}</td><td>{$form.verpSeparator.html}<br />
            <span class="description">{ts}Separator character used when CiviMail generates VERP (variable envelope return path) Mail-From addresses.{/ts}</span></td>
        </tr>
        {/if}
        <tr class="crm-mail-form-block-enableTransactionalEmail">
          <td class="label">{$form.enableTransactionalEmail.label}</td><td>{$form.enableTransactionalEmail.html}<br />
            <div class="description">
              {ts}Enable email tracking will track email opened data of contribution and event notification to an activity.{/ts}<br>
              {ts}After enable, changes below may affect your current usage:{/ts}<br>
              <i class="zmdi zmdi-alert-circle-o"></i><span class="font-red">{ts}The cc and bcc in notification will no longer in the same mail which means you won't see the from name and email address in cc / bcc in thank-you letter.{/ts}</span><br>
              <i class="zmdi zmdi-alert-circle-o"></i><span class="font-red">{ts}Instead of create an one aggregate activity, email to multiple contacts now will create separate activity on each contact.{/ts}</span>
            </div>
          </td>
        </tr>
      </table>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
<div class="spacer"></div>
</div>
