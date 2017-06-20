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
<div class="crm-block crm-event-participant_confirm-form-block">
  <div class="messages status">
        {$statusMsg}
  </div>

  <div class="event-info-content">
  <div class="crm-section event_date_time-section">
      <div class="label"><label>{ts}Event Date{/ts}</label></div>
      <div class="content">
            <abbr class="dtstart" title="{$event.event_start_date|crmDate}">
            {$event.event_start_date|crmDate}</abbr>
            {if $event.event_end_date}
                &nbsp; {ts}through{/ts} &nbsp;
                {* Only show end time if end date = start date *}
                {if $event.event_end_date|date_format:"%Y%m%d" == $event.event_start_date|date_format:"%Y%m%d"}
                    <abbr class="dtend" title="{$event.event_end_date|crmDate:0:1}">
                    {$event.event_end_date|crmDate:0:1}
                    </abbr>        
                {else}
                    <abbr class="dtend" title="{$event.event_end_date|crmDate}">
                    {$event.event_end_date|crmDate}
                    </abbr>   
                {/if}
            {/if}
            {capture assign=event_info_url}{crmURL p='civicrm/event/info' q="reset=1&id=`$event.id`"}{/capture}
            <a class="add-to-calendar" target="_blank" href="{$share_google_calendar}"><i class="zmdi zmdi-account-calendar"></i>{ts}Add to Google Calendar{/ts}</a>
        </div>
    <div class="clear"></div>
  </div>

  {if $event.registration_start_date}
    <div class="crm-section registration_date_time-section">
        <div class="label"><label>{ts}Registration Date{/ts}</label></div>
        <div class="content">
              <abbr class="dtstart" title="{$event.registration_start_date|crmDate}">
              {$event.registration_start_date|crmDate}</abbr>
              {if $event.registration_end_date}
                  &nbsp; {ts}through{/ts} &nbsp;
                  {* Only show end time if end date = start date *}
                  {if $event.registration_end_date|date_format:"%Y%m%d" == $event.registration_start_date|date_format:"%Y%m%d"}
                      <abbr class="dtend" title="{$event.event_end_date|crmDate:0:1}">
                      {$event.registration_end_date|crmDate:0:1}
                      </abbr>        
                  {else}
                      <abbr class="dtend" title="{$event.event_end_date|crmDate}">
                      {$event.registration_end_date|crmDate}
                      </abbr>   
                  {/if}
              {/if}
          </div>
      <div class="clear"></div>
    </div>
  {/if}

  {if $isShowLocation}
    {if $location.address.1}
      <div class="crm-section event_address-section">
        <div class="label"><label>{ts}Location{/ts}</label></div>
        <div class="content">{$location.address.1.display|nl2br}</div>
        <div class="clear"></div>
      </div>
    {/if}
  {/if}

  <div id="crm-submit-buttons">
	{include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
  </div>
</div>