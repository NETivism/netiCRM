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
<div class="crm-block crm-form-block crm-form-block-search crm-event-searchevent-form-block">
 <h3>{ts}Find Events{/ts}</h3>
  <table class="form-layout">
    <tr class="crm-event-searchevent-form-block-title">
        <td>
            <label>{ts}Complete OR partial Event name.{/ts}</label>
            {$form.title.html|crmReplace:class:huge}
        </td>
        <td>
          <label>{ts}Event Type{/ts}</label>
          {$form.event_type_id.html} 
        </td>
        <td class="right" rowspan="2">
          {include file="CRM/common/formButtons.tpl"}
        </td>  
    </tr>
  </table>
</div>
{include file="CRM/common/chosen.tpl" selector="select#event_type_id"}
