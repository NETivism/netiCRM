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
{capture assign=docLink}{docURL page="Tags Admin"}{/capture}

<div id="help">
    {ts 1=$docLink}Tags can be assigned to any contact record, and are a convenient way to find contacts. You can create as many tags as needed to organize and segment your records.{/ts} {$docLink}
</div>

{if $action eq 1 or $action eq 2 or $action eq 8}
    {include file="CRM/Admin/Form/Tag.tpl"}	
{/if}

{if $rows}
{include file="CRM/common/jsortable.tpl"}
<div id="cat">
        {strip}
        <table id="options" class="display">
	    <thead>
        <tr>
	        <th id="sortable">{ts}Tag{/ts}</th>
            <th>{ts}ID{/ts}</th>
	        <th id="nosort">{ts}Description{/ts}</th>
	        <th>{ts}Parent ID{/ts}</th>
	        <th></th>
        </tr>
        </thead>
        {foreach from=$rows item=row}
        <tr class="{cycle values="odd-row,even-row"} {$row.class}">
            <td>{$row.name}</td>
            <td>{$row.id}</td>	
            <td>{$row.description} </td>
            <td>{$row.parent_id}</td>
            <td>{$row.action|replace:'xx':$row.id}</td>
        </tr>
        {/foreach}
        </table>
        {/strip}
        
        {if !($action eq 1 and $action eq 2)}
	    <div class="action-link">
        <a href="{crmURL q="action=add&reset=1"}" id="newTag" class="button"><span>&raquo; {ts}New Tag{/ts}</span></a>
        </div>
        {/if}
</div>
{else}
    <div class="messages status">
    <dl>
        <dt><img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}"/></dt>
        {capture assign=crmURL}{crmURL p='civicrm/admin/tag' q="action=add&reset=1"}{/capture}
        <dd>{ts 1=$crmURL}There are no Tags present. You can <a href='%1'>add one</a>.{/ts}</dd>
        </dl>
    </div>    
{/if}