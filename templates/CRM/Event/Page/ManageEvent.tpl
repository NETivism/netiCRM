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
{capture assign=newEventURL}{crmURL q="action=add&reset=1"}{/capture}
{capture assign=icalFile}{crmURL p='civicrm/event/ical' q="reset=1"}{/capture}
{capture assign=icalFeed}{crmURL p='civicrm/event/ical' q="reset=1&page=1"}{/capture}
{capture assign=rssFeed}{crmURL p='civicrm/event/ical' q="reset=1&page=1&rss=1"}{/capture}
{capture assign=htmlFeed}{crmURL p='civicrm/event/ical' q="reset=1&page=1&html=1"}{/capture}

{if $action eq 1 or $action eq 2 }
    {include file="CRM/Event/Page/ManageEventEdit.tpl"}
{/if}

<a accesskey="N" href="{$newEventURL}" id="newManageEvent" class="button"><span>&raquo; {ts}New Event{/ts}</span></a>
<div class="right">
    <a href="{$htmlFeed}" title="{ts}HTML listing of current and future public events.{/ts}"><img src="{$config->resourceBase}i/applications-internet.png" alt="{ts}HTML listing of current and future public events.{/ts}" /></a>&nbsp;&nbsp;<a href="{$rssFeed}" title="{ts}Get RSS 2.0 feed for current and future public events.{/ts}"><img src="{$config->resourceBase}i/feed-icon.png" alt="{ts}Get RSS 2.0 feed for current and future public events.{/ts}" /></a>&nbsp;&nbsp;<a href="{$icalFile}" title="{ts}Download iCalendar file for current and future public events.{/ts}"><img src="{$config->resourceBase}i/office-calendar.png" alt="{ts}Download iCalendar file for current and future public events.{/ts}" /></a>&nbsp;&nbsp;<a href="{$icalFeed}" title="{ts}Get iCalendar feed for current and future public events.{/ts}"><img src="{$config->resourceBase}i/ical_feed.gif" alt="{ts}Get iCalendar feed for current and future public events.{/ts}" /></a>&nbsp;&nbsp;&nbsp;{help id='icalendar'}
</div>


{include file="CRM/Event/Form/SearchEvent.tpl"}

{if $rows}
    <div id=event_status_id>
        {strip}
        {include file="CRM/common/pager.tpl" location="top"}
        {include file="CRM/common/pagerAToZ.tpl"}
        {* handle enable/disable actions*}
        {include file="CRM/common/enableDisable.tpl"}         
        {include file="CRM/common/jsortable.tpl"}         
        <table id="options" class="display">
         <thead>
         <tr>
            <th>{ts}Event{/ts}</th>
            <th>{ts}City{/ts}</th>
            <th>{ts}State/Province{/ts}</th>
            <th>{ts}Public?{/ts}</th>
            <th id="start_date">{ts}Starts{/ts}</th>
            <th id="end_date">{ts}Ends{/ts}</th>
	        <th>{ts}Active?{/ts}</th>
	        <th></th>
         </tr>
         </thead>
        {foreach from=$rows item=row}
          <tr id="row_{$row.id}" class="{cycle values="odd-row,even-row"} {$row.class}{if NOT $row.is_active} disabled{/if}">
            <td><a href="{crmURL p='civicrm/event/info' q="id=`$row.id`&reset=1"}" title="{ts}View event info page{/ts}" class="bold">{$row.title}</a>&nbsp;&nbsp;({ts}ID:{/ts} {$row.id})<br /><a href="{crmURL p='civicrm/event/search' q="reset=1&force=1&event=`$row.id`"}" title="{ts}List participants for this event (all statuses){/ts}">({ts}participants{/ts})</a></td> 
            <td>{$row.city}</td>  
            <td>{$row.state_province}</td>	
            <td>{if $row.is_public eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>    
    	    <td>{$row.start_date|crmDate:"%b %d, %Y %l:%M %P"}</td>
            <td>{$row.end_date|crmDate:"%b %d, %Y %l:%M %P"}</td>
	        <td id="row_{$row.id}_status">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
    	    <td>{$row.action|replace:'xx':$row.id}</td>
            <td class="start_date hiddenElement">{$row.start_date|crmDate}</td>
            <td class="end_date hiddenElement">{$row.end_date|crmDate}</td>
          </tr>
        {/foreach}    
        </table>
        {include file="CRM/common/pager.tpl" location="bottom"}
        {/strip}
    </div>
{else}
   {if $isSearch eq 1}
    <div class="status messages">
        <dl>
            <dt><img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}" /></dt>
            {capture assign=browseURL}{crmURL p='civicrm/event/manage' q="reset=1"}{/capture}
            <dd>
                {ts}No available Events match your search criteria. Suggestions:{/ts}
                <div class="spacer"></div>
                <ul>
                <li>{ts}Check your spelling.{/ts}</li>
                <li>{ts}Try a different spelling or use fewer letters.{/ts}</li>
                <li>{ts}Make sure you have enough privileges in the access control system.{/ts}</li>
                </ul>
                {ts 1=$browseURL}Or you can <a href='%1'>browse all available Current Events</a>.{/ts}
            </dd>
        </dl>
    </div>
   {else}
    <div class="messages status">
    <dl>
        <dt><img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}" /></dt>
        <dd>{ts 1=$newEventURL}There are no events scheduled for the date range. You can <a href='%1'>add one</a>.{/ts}</dd>
        </dl>
    </div>    
   {/if}
{/if}