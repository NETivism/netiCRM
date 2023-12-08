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
<div id="help">
{ts}This screen shows all the Personal Campaign Pages created in the system and allows administrator to review them and change their status.{/ts} {help id="id-pcp-intro"}
</div>
{if $action ne 8} 
{include file="CRM/Contribute/Form/PCP/PCP.tpl"} 
{else}
{include file="CRM/Contribute/Form/PCP/Delete.tpl"} 
{/if}

{if $rows}
<div id="ltype">
{include file="CRM/common/enableDisable.tpl"}
{strip}
<table id="options" class="display">
	<thead>
    <tr>
		<th>{ts}Page Title{/ts}</th>
		<th>{ts}Created by{/ts}</th>
		<th>{ts}Belonging Main Contribution Page{/ts}</th>
		<th id="sortable">{ts}Status{/ts}</th>
		<th></th>
		<th class="hiddenElement"></th>
		<th class="hiddenElement"></th>
    </tr>
	</thead>
	<tbody>
	{foreach from=$rows item=row}
	<tr id="row_{$row.id}" class="{cycle values="odd-row,even-row"} {$row.class}">
        	<td><a href="{crmURL p='civicrm/contribute/pcp/info' q="reset=1&id=`$row.id` " fe='true'}" title="{ts}View Personal Campaign Page{/ts}" target="_blank">{$row.title}</a></td>
		<td><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.pcp_contact_id`"}" title="{ts}View contact record{/ts}" target="_blank">{$row.pcp_contact}</a> ({if $row.pcp_contact_external_id}{$row.pcp_contact_id} - {$row.pcp_contact_external_id}{else}{$row.pcp_contact_id}{/if})</td>
		<td><a href="{crmURL p='civicrm/admin/contribute' q="action=update&reset=1&id=`$row.contribution_page_id`" fe='true'}" title="{ts}View contribution page{/ts}" target="_blank">{$row.contribution_page_title} ( {ts}ID{/ts}: {$row.contribution_page_id})</td>
		<td>{$row.status_id}</td>
		<td id={$row.id}>{$row.action|replace:'xx':$row.id}</td>
	</tr>
	{/foreach}
	</tbody>
</table>
<div id="statusChangeMessage" title="{ts}Status Change{/ts}">
  <p>{ts 1=approve}Change this pcp page to status <span id="status-indicator">%1</span>{/ts}</p>
  <p>{ts}Are you sure you want to continue?{/ts}</p>
</div>
{/strip}
<script>{literal}
  cj(document).ready(function($){
    $("a.action-item.dialog").click(function(e){
      e.preventDefault();
      var $item = $(this);
      let status = $item.text();
      $('#status-indicator').text(status);
      $("#statusChangeMessage").dialog({
        autoOpen:false,
        resizable:false,
        width:450,
        height:250,
        modal:true,
        buttons: {
          "{/literal}{ts}Proceed and Send Notification{/ts}{literal}": function() {
            window.location = $item.attr('href');
            return true;
          },
          "{/literal}{ts}Cancel{/ts}{literal}": function() {
            $(this).dialog('close');
            return false;
          }
        }
      });
      $('#statusChangeMessage').dialog('open');
      return false;
    });
  });
{/literal}</script>
</div>
{else}
<div class="messages status">

    {if $isSearch}
        {ts}There are no Personal Campaign Pages which match your search criteria.{/ts}
    {else}
        {ts}There are currently no Personal Campaign Pages.{/ts}
    {/if}
</div>
{/if}
