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
{* this template is used for displaying event information *}

{if $registerClosed }
<div class="spacer"></div>
<div class="messages status">
  <div class="icon inform-icon"></div>
     &nbsp;{ts}Registration is closed for this event{/ts}
  </div>
{/if}
{if $sharethis}
<div class="sharethis">{$sharethis}</div>
{/if}
<div class="vevent crm-block crm-event-info-form-block">
  <div class="event-info">

  {if $event.summary}
    <div class="event-info-header">
      <div class="crm-section event_summary-section">
        {$event.summary}
      </div>
    {literal}
    <script type="text/javascript">
      cj(".event-info-header").insertAfter("#content-header");
    </script>
    {/literal}
    </div> <!-- .event-info-header -->
  {/if}

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

  {if $allowRegistration}
    <div class="action-link section register_link-section register_link-top">
      <a href="{$registerURL}" title="{$registerText}" class="button crm-register-button"><span>{$registerText}</span></a>
    </div>
  {/if}

  {if $event.description}
      <div class="crm-section event_description-section">
          {$event.description}
      </div>
  {/if}

    {if $event.is_monetary eq 1 && $feeBlock.value}
      <div class="crm-section event_fees-section crm-section-big">
          <div class="label"><label>{$event.fee_label}</label></div>
          <div class="content">
              <table class="form-layout-compressed fee_block-table">
                  {foreach from=$feeBlock.value name=fees item=value}
                      {assign var=idx value=$smarty.foreach.fees.iteration}
                      {if $feeBlock.lClass.$idx}
                          {assign var="lClass" value=$feeBlock.lClass.$idx}
                      {else}
                          {assign var="lClass" value="fee_level-label"}
                      {/if}
                      <tr>
                          <td class="{$lClass} crm-event-label">{$feeBlock.label.$idx}</td>
                          <td class="fee_amount-value right">{$feeBlock.value.$idx|crmMoney}</td>
                      </tr>
                  {/foreach}
              </table>
          </div>
          <div class="clear"></div>
      </div>
  {/if}

  {if $isShowLocation}

        {if $location.address.1}
            <div class="crm-section event_address-section crm-section-big">
                <div class="label"><label>{ts}Location{/ts}</label></div>
                <div class="content">{$location.address.1.display|nl2br}</div>
                <div class="clear"></div>
            </div>
        {/if}

      {if ( $event.is_map && $config->mapProvider && 
          ( is_numeric($location.address.1.geo_code_1)  || 
          ( $config->mapGeoCoding && $location.address.1.city AND $location.address.1.state_province ) ) ) }
          <div class="crm-section event_map-section crm-section-big">
              <div class="content">
                  <div class="event-map">
                    {assign var=showDirectly value="1"}
                    {if $mapProvider eq 'Google'}
                        {include file="CRM/Contact/Form/Task/Map/Google.tpl" fields=$showDirectly}
                    {elseif $mapProvider eq 'Yahoo'}
                        {include file="CRM/Contact/Form/Task/Map/Yahoo.tpl"  fields=$showDirectly}
                    {/if}
                    <div class="event-map-links">
                      <a href="{$mapURL}" title="{ts}Show large map{/ts}">{ts}Show large map{/ts}</a>
                    </div>
                  </div>
              </div>
              <div class="clear"></div>
          </div>
      {/if}

  {/if}{*End of isShowLocation condition*}  


  {if $location.phone.1.phone || $location.email.1.email}
      <div class="crm-section event_contact-section crm-section-big">
          <div class="label"><label>{ts}Contact info{/ts}</label></div>
          <div class="content">
              {* loop on any phones and emails for this event *}
              {foreach from=$location.phone item=phone}
                  {if $phone.phone}
                      {if $phone.phone_type}{$phone.phone_type_display}{else}{ts}Phone{/ts}{/if}: 
                          <span class="tel">{$phone.phone}</span> <br />
                      {/if}
              {/foreach}
  
              {foreach from=$location.email item=email}
                  {if $email.email}
                      {ts}Email:{/ts} <span class="email"><a href="mailto:{$email.email}">{$email.email}</a></span>
                  {/if}
              {/foreach}
          </div>
          <div class="clear"></div>
      </div>
  {/if}


    {include file="CRM/Custom/Page/CustomDataView.tpl"}
  {if $allowRegistration}
      <div class="action-link section register_link-section">
          <a href="{$registerURL}" title="{$registerText}" class="button crm-register-button"><span>{$registerText}</span></a>
      </div>
  {/if}
        
    </div> <!-- .event-info-content -->      
  </div> <!-- .event-info -->
</div><!-- .crm-event-info-form-block -->
