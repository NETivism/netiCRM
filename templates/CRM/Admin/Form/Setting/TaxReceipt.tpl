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
   <h2>{ts}Mapping Option{/ts}</h2>
      <table class="form-layout-compressed">
        <tr class="crm-mail-form-block-taxReceiptType">
            <td class="label">{$form.taxReceiptType.label}</td><td>{$form.taxReceiptType.html}<br />    
        </tr>
        <tr class="crm-mail-form-block-taxReceiptDeviceType">
            <td class="label">{$form.taxReceiptDeviceType.label}</td><td>{$form.taxReceiptDeviceType.html}<br />
            </td>
        </tr>
        <tr class="crm-mail-form-block-taxreceiptDeviceNumber">
            <td class="label">{$form.taxReceiptDeviceNumber.label}</td><td>{$form.taxReceiptDeviceNumber.html}<br />
            </td>
        </tr>
        <tr class="crm-mail-form-block-taxReceiptSerial">
            <td class="label">{$form.taxReceiptSerial.label}</td><td>{$form.taxReceiptSerial.html}<br />
            </td>
        </tr>
        <tr class="crm-mail-form-block-taxReceiptItem">
            <td class="label">{$form.taxReceiptItem.label}</td><td>{$form.taxReceiptItem.html}<br />
            </td>
        </tr>
        <tr class="crm-mail-form-block-taxReceiptNumber">
            <td class="label">{$form.taxReceiptNumber.label}</td><td>{$form.taxReceiptNumber.html}<br />
            </td>
        </tr>
        <tr class="crm-mail-form-block-taxReceiptPaper">
            <td class="label">{$form.taxReceiptPaper.label}</td><td>{$form.taxReceiptPaper.html}<br />
            </td>
        </tr>
        <tr class="crm-mail-form-block-taxReceiptAgree">
            <td class="label">{$form.taxReceiptAgree.label}</td><td>{$form.taxReceiptAgree.html}<br />
            </td>
        </tr>
      </table>
   <h2>{ts}Tax Invoice Giving{/ts}</h2>
      <table class="form-layout-compressed">
        <tr class="crm-mail-form-block-taxReceiptDonate">
            <td class="label">{$form.taxReceiptDonate.label}</td><td>{$form.taxReceiptDonate.html}<br />
            </td>
        </tr>
        <tr class="crm-mail-form-block-taxReceiptDonateSelect">
            <td class="label">{$form.taxReceiptDonateSelect.label}</td><td>{$form.taxReceiptDonateSelect.html}
            <div class="description">{ts}Use 'lovecode|organization' format to config this field, each line is one option.{/ts}</div>
            </td>
        </tr>
      </table>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>     
<div class="spacer"></div>
</div>
