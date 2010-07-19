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
{capture assign=docLink}{docURL page="CiviMail Admin" text="CiviMail Administration Guide"}{/capture}
<div id="help">
    {ts 1=$docLink}These settings are used to configure mailer properties for the optional CiviMail component. They are NOT used for the built-in 'Send Email to Contacts' feature. Refer to the %1 for more information.{/ts}
</div>
<div class="form-item">
<fieldset><legend>{ts}CiviMail Configuration{/ts}</legend>
      <dl>
       
        <dt>{$form.mailerPeriod.label}</dt><dd>{$form.mailerPeriod.html}</dd>      
        <dt>&nbsp;</dt><dd class="description">{ts}Number of seconds between delivery attempts for new outgoing mailings.{/ts}</dd>
        <dt>{$form.mailerBatchLimit.label}</dt><dd>{$form.mailerBatchLimit.html}</dd>    
        <dt>&nbsp;</dt><dd class="description">{ts}Throttle email delivery by setting the maximum number of emails sent during each CiviMail run (0 = unlimited).{/ts}</dd>
        <dt>{$form.mailerSpoolLimit.label}</dt><dd>{$form.mailerSpoolLimit.html}</dd>    
        <dt>&nbsp;</dt><dd class="description">{ts}Set the limit of emails sent via smtp mailer, for more than limit send them in Spool table.{/ts}</dd>
        <dt>{$form.verpSeparator.label}</dt><dd>{$form.verpSeparator.html}</dd>
        <dt>&nbsp;</dt><dd class="description">{ts}Separator character used when CiviMail generates VERP (variable envelope return path) Mail-From addresses.{/ts}</dd>
        <dt></dt><dd>{$form.buttons.html}</dd>
       </dl>
<div class="spacer"></div>
</fieldset>
</div>
