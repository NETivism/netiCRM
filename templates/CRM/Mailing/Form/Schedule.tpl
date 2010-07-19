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
    {ts}You can schedule this mailing to be sent starting at a specific date and time, OR you can request that it be sent as soon as possible by checking &quot;Send Immediately&quot;.{/ts} {help id="sending"}
</div>

<fieldset>
 <table class="form-layout">
  <tbody>
    <tr>
        <td>{$form.now.label}</td>
        <td>{$form.now.html}</td>
    </tr>
    <tr>
        <td>{ts}OR{/ts}</td>
        <td>&nbsp;</td>
    </tr>
    <tr>
        <td>{$form.start_date.label}</td>
        <td>{include file="CRM/common/jcalendar.tpl" elementName=start_date}
            <span class="description">{ts}Set a date and time when you want CiviMail to start sending this mailing.{/ts}</span>
        </td>
    </tr>
    <tr><td colspan="2">{$form.buttons.html}</td></tr>
  </tbody>
</table>
</fieldset>

{* include jscript to warn if unsaved form field changes *}
{include file="CRM/common/formNavigate.tpl"}


