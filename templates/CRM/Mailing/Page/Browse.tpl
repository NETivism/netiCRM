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
{if $action eq 8 or $action eq 64} 
<fieldset><legend>{if $action eq 8}{ts}Delete Mailing{/ts}{else}{ts}Cancel Mailing{/ts}{/if}</legend>
<div class=status>{if $action eq 8}{ts 1=$subject}Are you sure you want to delete the mailing '%1'?{/ts}{else}{ts 1=$subject}Are you sure you want to cancel the mailing '%1'?{/ts}{/if}</div>
<dl><dt></dt><dd>{$form.buttons.html}</dd></dl>
</fieldset>
{/if}
<div class="action-link">
    <a accesskey="N" href="{crmURL p='civicrm/mailing/send' q='reset=1'}" class="button"><span>&raquo; {ts}New Mailing{/ts}</span></a><br/><br/>
</div>
{include file="CRM/Mailing/Form/Search.tpl"}

{if $rows}
    {include file="CRM/common/pager.tpl" location="top"}
    {include file="CRM/common/pagerAToZ.tpl"}

    {strip}
    <table class="selector">
      <thead class="sticky">
      {foreach from=$columnHeaders item=header}
        <th>
          {if $header.sort}
            {assign var='key' value=$header.sort}
            {$sort->_response.$key.link}
          {else}
            {$header.name}
          {/if}
        </th>
      {/foreach}
      </thead>

      {counter start=0 skip=1 print=false}
      {foreach from=$rows item=row}
      <tr class="{cycle values="odd-row,even-row"}">
        <td>{$row.name}</td>
        <td>{$row.status}</td>
        <td><a href ={crmURL p='civicrm/contact/view' q="reset=1&cid="}{$row.created_id}>{$row.created_by}</a></td>
        <td><a href ={crmURL p='civicrm/contact/view' q="reset=1&cid="}{$row.scheduled_id}>{$row.scheduled_by}</a></td>	
        <td>{$row.scheduled}</td>
        <td>{$row.start}</td>
        <td>{$row.end}</td>
        <td>{$row.action|replace:'xx':$row.id}</td>
      </tr>
      {/foreach}
    </table>
    {/strip}

    {include file="CRM/common/pager.tpl" location="bottom"}
    <div class="action-link">
        <a accesskey="N" href="{crmURL p='civicrm/mailing/send' q='reset=1'}" class="button"><span>&raquo; {ts}New Mailing{/ts}</span></a><br/>
    </div>

{* No mailings to list. Check isSearch flag to see if we're in a search or not. *}
{elseif $isSearch eq 1}
    {if $archived}
        {capture assign=browseURL}{crmURL p='civicrm/mailing/browse/archived' q="reset=1"}{/capture}
        {assign var="browseType" value="Archived"}
    {elseif $unscheduled} 
        {capture assign=browseURL}{crmURL p='civicrm/mailing/browse/unscheduled' q="scheduled=false&reset=1"}{/capture}
        {assign var="browseType" value="Draft and Unscheduled"}
    {else}
        {capture assign=browseURL}{crmURL p='civicrm/mailing/browse/scheduled' q="scheduled=true&reset=1"}{/capture}
        {assign var="browseType" value="Scheduled and Sent"}
    {/if}
    <div class="status messages">
        <dl>
            <dt><img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}"/></dt>
            <dd>
               {ts}No Mailings match your search criteria. Suggestions:{/ts} 
                <div class="spacer"></div>
                <ul>
                <li>{ts}Check your spelling.{/ts}</li>
                <li>{ts}Try a different spelling or use fewer letters.{/ts}</li>
                </ul>
                {ts 1=$browseURL 2=$browseType}Or you can <a href='%1'>browse all %2 mailings</a>.{/ts}
            </dd>
        </dl>
    </div>
{elseif $unscheduled}
    <div class="messages status">
        <dl>
            <dt><img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}" /></dt>
            {capture assign=crmURL}{crmURL p='civicrm/mailing/send' q='reset=1'}{/capture}
            <dd>{ts 1=$crmURL}There are no Unscheduled Mailings. You can <a href='%1'>create and send one</a>.{/ts}</dd>
        </dl>
   </div>
{elseif $archived}
    <div class="messages status">
        <dl>
            <dt><img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}" /></dt>
            {capture assign=crmURL}{crmURL p='civicrm/mailing/browse/scheduled' q='scheduled=true&reset=1'}{/capture}
            <dd>{ts 1=$crmURL}There are no Archived Mailings. You can archive mailings from <a href='%1'>Scheduled or Sent Mailings</a>.{/ts}</dd>
        </dl>		
   </div>
{else}
    <div class="messages status">
        <dl>
            <dt><img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}" /></dt>
            {capture assign=crmURL}{crmURL p='civicrm/mailing/send' q='reset=1'}{/capture}
            {capture assign=archiveURL}{crmURL p='civicrm/mailing/browse/archived' q='reset=1'}{/capture}
            <dd>{ts 1=$crmURL, 2=$archiveURL}There are no Scheduled or Sent Mailings. You can <a href='%1'>create and send one</a> OR you can search the <a href='%2'>Archived Mailings</a>.{/ts}</dd>
        </dl>
   </div>
{/if}
