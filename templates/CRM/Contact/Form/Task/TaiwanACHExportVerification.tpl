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
<div class="crm-block crm-form-block crm-contact-task-taiwanach-export-transaction">
   <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
   <table class="form-layout-compressed">
   <tr class="crm-contact-task-taiwanach-datetime">
      <td class="label">{$form.datetime.label}</td>
      <td>{include file="CRM/common/jcalendar.tpl" elementName="datetime"}</td>
   </tr>
   <tr class="crm-contact-task-taiwanach-payment-type">
      <td class="label">{$form.payment_type.label}</td>
      <td>{$form.payment_type.html}</td>
   </tr>
   <tr class="crm-contact-task-taiwanach-export-format">
      <td class="label">{$form.export_format.label}</td>
      <td>{$form.export_format.html}</td>
   </tr>
   </table>
   <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
