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
{if $notConfigured} {* Case types not present. Component is not configured for use. *}
    {include file="CRM/Case/Page/ConfigureError.tpl"}
{else}
<tr>
  <td width="25%"><label>{ts}Case Type{/ts}</label>
    <br />
      <div class="listing-box" style="width: auto; height: 120px">
       {foreach from=$form.case_type_id item="case_type_id_val"}
        <div class="{cycle values="odd-row,even-row"}">
                {$case_type_id_val.html}
        </div>
      {/foreach}
      </div><br />
  </td>
  
  <td>
    {$form.case_status_id.label}<br /> 
    {$form.case_status_id.html}<br /><br />	
    {$form.case_owner.html} &nbsp;<a href="javascript:unselectRadio('case_owner', '{$form.formName}')">unselect</a><br />
    {if $form.case_deleted}	
        {$form.case_deleted.html}	
        {$form.case_deleted.label}	
    {/if}
  </td>

</tr>     
{/if}