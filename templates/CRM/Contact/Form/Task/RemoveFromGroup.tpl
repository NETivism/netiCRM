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
<div class="crm-form-block crm-block crm-contact-task-removefromgroup-form-block">
<div class="messages warning">
  <div>{ts}The operation of removing contacts from the group cannot be undone.{/ts}</div>
  {if $smart_marketing_hint}
    <div>{ts}The smart markeintg journey is still in progress. To delete the contact or remove contact from group list, you need to go to the external smart marketing tool to proceed.{/ts}</div>
  {/if}
</div>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>

 <table class="form-layout-compressed">
     <tr class="crm-contact-task-removefromgroupform-block-group_id">
        <td class="label">{if $group.id}{ts}Group{/ts}{else}{$form.group_id.label}{/if}</td>
        <td>
          {$form.group_id.html}
        </td>
      </tr>
      <tr>
        <td></td><td>{include file="CRM/Contact/Form/Task.tpl"}</td>
      </tr>
 </table>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
