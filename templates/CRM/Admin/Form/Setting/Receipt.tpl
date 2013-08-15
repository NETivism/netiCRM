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
<div class="crm-block crm-form-block crm-mail-form-block">
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>                         
      <table class="form-layout-compressed">
        <tr class="crm-mail-form-block-receiptLogo">
            <td class="label">{$form.receiptLogo.label}</td><td>{$form.receiptLogo.html}<br />    
            <span class="description">{ts}Paste logo url. Start with http://{/ts}</span></td>
        </tr>
        <tr class="crm-mail-form-block-receiptPrefix">
            <td class="label">{$form.receiptPrefix.label}</td><td>{$form.receiptPrefix.html}<br />    
            <span class="description">{ts}Receipt ID prefix. Can be numberic or alphabetic.{/ts} {ts}Use this screen to configure formats for date display and date input fields. Defaults are provided for standard United States formats. Settings use standard POSIX specifiers.{/ts} {help id='date-format'}</span></td>
        </tr>
        <tr class="crm-mail-form-block-receiptDescription">
            <td class="label">{$form.receiptDescription.label}</td><td>{$form.receiptDescription.html}<br />    
            <span class="description">{ts}Description will appear at the end of receipt.{/ts}</span></td>
        </tr>
        <tr class="crm-mail-form-block-receiptOrgInfo">
            <td class="label">{$form.receiptOrgInfo.label}</td><td>{$form.receiptOrgInfo.html}<br />
            <span class="description">{ts}Organization info will appear at the end of receipt.{/ts}</span></td>
        </tr>
        <tr class="crm-mail-form-block-receiptTitle">
            <td class="label">{$form.receiptTitle.label}</td><td>{$form.receiptTitle.html}<br />
            <span class="description">{ts}When your receipt title save in another field, use this to select the field.{/ts}</span></td>
        </tr>
        <tr class="crm-mail-form-block-receiptSerial">
            <td class="label">{$form.receiptSerial.label}</td><td>{$form.receiptSerial.html}<br />
            <span class="description">{ts}When your serial code save in another field, use this to select the field.{/ts}</span></td>
        </tr>
      </table>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>     
<div class="spacer"></div>
</div>
