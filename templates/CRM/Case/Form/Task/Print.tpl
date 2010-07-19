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
<p>

{if $rows } 
<div class="form-item">
     <span class="element-right">{$form.buttons.html}</span>
</div>
<div class="spacer"></div>
<br />
<p>
<table>
  <tr class="columnheader">
    <th>{ts}Client{/ts}</th>
    <th>{ts}Status{/ts}</th>
    <th>{ts}Case Type{/ts}</th>
    <th>{ts}My Role{/ts}</th>
    <th>{ts}Most Recent Activity{/ts}</th>
    <th>{ts}Next Scheduled Activity{/ts}</th>
  </tr>

{foreach from=$rows item=row}
    <tr class="{cycle values="odd-row,even-row"}">
        <td>{$row.sort_name}<br /><span class="description">{ts}Case ID{/ts}: {$row.case_id}</span></td>
        <td>{$row.case_status_id}</td>
        <td>{$row.case_type_id}</td>
        <td>{if $row.case_role}{$row.case_role}{else}---{/if}</td>
        <td>{if $row.case_recent_activity_type}
    	{$row.case_recent_activity_type}<br />{$row.case_recent_activity_date|crmDate}{else}---{/if}</td>
        <td>{if $row.case_scheduled_activity_type}
    	{$row.case_scheduled_activity_type}<br />{$row.case_scheduled_activity_date|crmDate}{else}---{/if}</td>
    </tr>
{/foreach}
</table>

<div class="form-item">
     <span class="element-right">{$form.buttons.html}</span>
</div>

{else}
   <div class="messages status">
    <dl>
    <dt><img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}" /></dt>
    <dd>
        {ts}There are no records selected for Print.{/ts}
    </dd>
    </dl>
   </div>
{/if}
