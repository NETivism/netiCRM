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
{capture assign=newEventURL}{crmURL p='civicrm/event/add' q="action=add&reset=1"}{/capture}
{capture assign=icalFile}{crmURL p='civicrm/event/ical' q="reset=1" fe=1}{/capture}
{capture assign=icalFeed}{crmURL p='civicrm/event/ical' q="reset=1&page=1" fe=1}{/capture}
{capture assign=rssFeed}{crmURL p='civicrm/event/ical' q="reset=1&page=1&rss=1" fe=1}{/capture}

<div class="crm-actions-ribbon action-link-button">
  <a accesskey="N" href="{$newEventURL}" id="newManageEvent" class="button"><i class="zmdi zmdi-plus-circle-o"></i>{ts}Add Event{/ts}</a>
  <div class="float-right top-icon">
    <a href="{$rssFeed}" target="_blank" title="{ts}Get RSS 2.0 feed for current and future public events.{/ts}"><i class="zmdi zmdi-rss"></i>RSS</a> 
    <a href="{$icalFeed}" target="_blank" title="{ts}Get iCalendar feed for current and future public events.{/ts}"><i class="zmdi zmdi-calendar"></i>iCAL</a>
  </div>
  <div class="clear"></div>
</div>
{include file="CRM/Event/Form/SearchEvent.tpl"}
{if $rows}
    <div id="event_status_id" class="crm-block crm-manage-events">
        {strip}
        {include file="CRM/common/pager.tpl" location="top"}
        {* handle enable/disable actions*}
        {include file="CRM/common/enableDisable.tpl"}         
        {include file="CRM/common/jsortable.tpl"}         
        <table id="options" class="display">
         <thead>
         <tr>
            <th>#</th>
            <th>{ts}Event Type{/ts}</th>
            <th>{ts}Event{/ts}</th>
            <th>{ts}Starts{/ts}-{ts}Ends{/ts}</th>
            <th>{ts}Public?{/ts}</th>
	          <th>{ts}Active?{/ts}</th>
            <th></th>
        		<th class="hiddenElement"></th>
        		<th class="hiddenElement"></th>	
         </tr>
         </thead>
        {foreach from=$rows item=row}
          <tr id="row_{$row.id}" class="{cycle values="odd-row,even-row"} {$row.class}{if NOT $row.is_active} disabled{/if}">
            <td>{$row.id}</td>
            <td class="crm-event-type">{$row.event_type}</td>
            <td class="crm-event-title crm-event_{$row.id}">
              <a href="{crmURL p='civicrm/event/search' q="reset=1&force=1&event=`$row.id`"}" class="bold">{$row.title|smarty:nodefaults|purify}</a>
              <ul class="crm-nav-menu crm-nav-links-event">
                <li><a href="{crmURL p='civicrm/event/search' q="reset=1&force=1&event=`$row.id`"}" title="{ts}Statistics{/ts}"><i class="zmdi zmdi-chart"></i>{ts}Participant Count{/ts}: {$row.counted}{if $row.max_participants}/{$row.max_participants}{/if}</a></li>
                <li><a href="{crmURL p='civicrm/participant/add' q="reset=1&action=add&context=standalone&eid=`$row.id`"}" title="{ts}Register New Participant{/ts}"><i class="zmdi zmdi-account-add"></i>{ts}Register Event Participant{/ts}</a></li>
                <li>
                  <div class="crm-configure-actions">
                    <i class="zmdi zmdi-settings"></i>
                    <span id="event-configure-{$row.id}" class="btn-slide">{ts}Configure{/ts}
                      <ul class="panel" id="panel_info_{$row.id}">
                      <li><a title="Info and Settings" class="action-item-wrap" href="{crmURL p='civicrm/event/manage/eventInfo' q="reset=1&action=update&id=`$row.id`"}">{ts}Info and Settings{/ts}</a></li>
                      <li><a title="Event Location" class="action-item-wrap {if NOT $row.is_show_location} disabled{/if}" href="{crmURL p='civicrm/event/manage/location' q="reset=1&action=update&id=`$row.id`"}">{ts}Event Location{/ts}</a></li>
                      <li><a title="Fees" class="action-item {if NOT $row.is_monetary} disabled{/if}" href="{crmURL p='civicrm/event/manage/fee' q="reset=1&action=update&id=`$row.id`"}">{ts}Fees{/ts}</a></li>
                      <li><a title="Online Registration" class="action-item-wrap {if NOT $row.is_online_registration} disabled{/if}" href="{crmURL p='civicrm/event/manage/registration' q="reset=1&action=update&id=`$row.id`"}">{ts}Online Registration{/ts}</a></li>
                      <li><a title="Tell a Friend" class="action-item-wrap {if NOT $row.friend} disabled{/if}" href="{crmURL p='civicrm/event/manage/friend' q="reset=1&action=update&id=`$row.id`"}">{ts}Tell a Friend{/ts}</a></li>
                      </ul> 
                    </span>
                  </div>
                </li>
                <li><a href="{crmURL p='civicrm/event/links' q="reset=1&id=`$row.id`"}" title="{ts}Event Links{/ts}"><i class="zmdi zmdi-link"></i>{ts}Event Links{/ts}</a></li>
              </ul>
            </td> 
            <td class="crm-event-start_date">{$row.start_date|crmDate} ~<br />{$row.end_date|crmDate}</td>
            <td class="crm-event-is_public">{if $row.is_public eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>    
            <td class="crm-event_status" id="row_{$row.id}_status">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
            <td class="crm-event-actions right nowrap">
              <div class="crm-event-more">
               {$row.action|replace:'xx':$row.id}
              </div>
            </td>
            <td class="crm-event-start_date hiddenElement">{$row.start_date|crmDate}</td>
            <td class="crm-event-end_date hiddenElement">{$row.end_date|crmDate}</td>
          </tr>
        {/foreach}    
        </table>
        {include file="CRM/common/pager.tpl" location="bottom"}
        {/strip}
    </div>
{else}
   {if $isSearch eq 1}
    <div class="status messages">
        
             {capture assign=browseURL}{crmURL p='civicrm/event/manage' q="reset=1"}{/capture}
             {ts}No available Events match your search criteria. Suggestions:{/ts}
             <div class="spacer"></div>
             <ul>
                <li>{ts}Check your spelling.{/ts}</li>
                <li>{ts}Try a different spelling or use fewer letters.{/ts}</li>
                <li>{ts}Make sure you have enough privileges in the access control system.{/ts}</li>
             </ul>
              {ts 1=$browseURL}Or you can <a href='%1'>browse all available Current Events</a>.{/ts}
    </div>
   {else}
    <div class="messages status">
         
         {ts 1=$newEventURL}There are no events scheduled for the date range. You can <a href='%1'>add one</a>.{/ts}
    </div>    
   {/if}
{/if}
