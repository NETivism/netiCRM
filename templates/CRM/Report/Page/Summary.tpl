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
{* Display monthly and yearly contributions using Google charts (Bar and Pie) *} 

{if $hasChart}
  {if $contribute_total or $chartConributeOnlineOffline or $participant_total or $chartParticipantOnlineOffline or $contact_total or $mailing}
  <div class="row">
    {if $contribute_total or $chartConributeOnlineOffline}
    <div class="col-md-3 col-xs-6">
      <div id="column-contribution-online-offline" class="box mdl-shadow--2dp">
        <div class="box-content">
          {if $contribute_total}
          <div class="kpi-box">
            <h4 class="kpi-box-title">{ts}Online Contribution Amount{/ts}</h4>
            <div class="kpi-box-value">{$contribute_online}<span class="kpi-unit"></span><span class="kpi-total-txt">{ts}Total{/ts} {$contribute_total}</span></div>
          </div>
          {/if}
          {if $chartConributeOnlineOffline}{include file="CRM/common/chartist.tpl" chartist=$chartConributeOnlineOffline}{/if}
        </div>
      </div>
    </div>
    {/if}
    {if $participant_total or $chartParticipantOnlineOffline}
    <div class="col-md-3 col-xs-6">
      <div id="column-participant-online-offline" class="box mdl-shadow--2dp">
        <div class="box-content">
          {if $participant_total}
          <div class="kpi-box">
            <h4 class="kpi-box-title">{ts}Online Registration{/ts}</h4>
            <div class="kpi-box-value">{$participant_online}<span class="kpi-unit">{ts}times{/ts}</span><span class="kpi-total-txt">{ts}Total{/ts} {$participant_total} {ts}times{/ts}</span></div>
          </div>
          {/if}
          {if $chartParticipantOnlineOffline}{include file="CRM/common/chartist.tpl" chartist=$chartParticipantOnlineOffline}{/if}
        </div>
      </div>
    </div>
    {/if}
    {if $contact_total}
    <div class="col-md-3 col-xs-6">
      <div id="column-contact-online-offline" class="box mdl-shadow--2dp">
        <div class="box-content">
          <div class="kpi-box">
            <h4 class="kpi-box-title">{ts}Contact{/ts}</h4>
            <div class="kpi-box-value">{$contact_total}<span class="kpi-unit">{ts}People{/ts}</span></div>
          </div>
        </div>
      </div>
    </div>
    {/if}
    {if $mailing}
    <div class="col-md-3 col-xs-6">
      <div id="column-mailing-online-offline" class="box mdl-shadow--2dp">
        <div class="box-content">
          <div class="kpi-box">
            <h4 class="kpi-box-title">{ts}Emailing Sended Count{/ts}</h4>
            <div class="kpi-box-value">{$mailing}<span class="kpi-unit">{ts}letters{/ts}</span></div>
          </div>
        </div>
      </div>
    </div>
    {/if}
  </div>
  {/if}
  
  {if $chartContact}
  <div class="row">
    <div class="col-md-12">
      <div id="column-contact-source" class="box mdl-shadow--2dp">
        <div class="box-header">
          <h3 class="box-title">{ts}Contact Source{/ts}</h3>
        </div>
        <div class="box-content">{include file="CRM/common/chartist.tpl" chartist=$chartContact}</div>
      </div>
    </div>
  </div>
  {/if}

  {if $chartInsSum}
  <div class="row">
    <div class="col-md-12">
      <div id="column-contribution-instrument" class="box mdl-shadow--2dp">
        <div class="box-header">
          <h3 class="box-title">{ts}Payment Instrument{/ts}</h3>
        </div>
        <div class="box-content">{include file="CRM/common/chartist.tpl" chartist=$chartInsSum}</div>
      </div>
    </div>
  </div>
  {/if}

  {if $chartContribTime}
  <div class="row">
    <div class="col-md-12">
      <div id="column-contribution-times" class="box mdl-shadow--2dp">
        <div class="box-header">
          <h3 class="box-title">{ts}Donation Count{/ts}</h3>
        </div>
        <div class="box-content">{include file="CRM/common/chartist.tpl" chartist=$chartContribTimes}</div>
      </div>
    </div>
  </div>
  {/if}

  {if $chartMailing}
  <div class="row">
    <div class="col-md-12">
      <div id="column-mailing-delivered" class="box mdl-shadow--2dp">
        <div class="box-header">
          <h3 class="box-title">{ts}Mailing{/ts}</h3>
        </div>
        <div class="box-content">{include file="CRM/common/chartist.tpl" chartist=$chartMailing}</div>
      </div>
    </div>
  </div>
  {/if}

  {if $chartMailingFunnel}
  <div class="row">
    <div class="col-md-12">
      <div id="column-mailing-delivered" class="box mdl-shadow--2dp">
        <div class="box-header">
          <h3 class="box-title">{ts}Mailing{/ts} (funnel)</h3>
        </div>
        <div class="box-content">{include file="CRM/common/chartist.tpl" chartist=$chartMailingFunnel}</div>
      </div>
    </div>
  </div>
  {/if}

  {if $chartPeopleGender}
  <div class="row">
    <div class="col-md-12">
      <div id="column-people-by-gender" class="box mdl-shadow--2dp">
        <div class="box-header">
          <h3 class="box-title">{ts}People by gender{/ts}</h3>
        </div>
        <div class="box-content">{include file="CRM/common/chartist.tpl" chartist=$chartPeopleGender}</div>
      </div>
    </div>
  </div>
  {/if}

  {if $chartPeopleAge}
  <div class="row">
    <div class="col-md-12">
      <div id="column-people-by-age" class="box mdl-shadow--2dp">
        <div class="box-header">
          <h3 class="box-title">{ts}People by age{/ts}</h3>
        </div>
        <div class="box-content">{include file="CRM/common/chartist.tpl" chartist=$chartPeopleAge}</div>
      </div>
    </div>
  </div>
  {/if}

  {if $chartPeopleProvince}
  <div class="row">
    <div class="col-md-12">
      <div id="column-people-by-province" class="box mdl-shadow--2dp">
        <div class="box-header">
          <h3 class="box-title">{ts}People by province{/ts}</h3>
        </div>
        <div class="box-content">{include file="CRM/common/chartist.tpl" chartist=$chartPeopleProvince}</div>
      </div>
    </div>
  </div>
  {/if}

  {if $static_label and $contribution_type_table}
  <div class="row">
    <div class="col-md-12">
      <div id="column-contribution-types" class="box mdl-shadow--2dp">
        <div class="box-header">
          <h3 class="box-title">{ts}Contribution Total{/ts}</h3>
        </div>
        <div class="box-content">
          <table class="crm-data-table crm-data-table-horizontal crm-data-table-striped">
            <tr>
              <th>
                {ts}Payment Instrument{/ts}
              </th>
              {foreach from=$static_label item=label}
              <th>
                {$label}
              </th>
              {/foreach}
            </tr>

            {foreach from=$contribution_type_table item=row}
            <tr>

              {foreach from=$row item=item key=key}
              {if $key == 0}
              <th>
                {$item}
              </th>
              {else}
                <td>
                  {$item}
                </td>
              {/if}
              {/foreach}
             </tr>
             {/foreach}
          </table>
          <table class="crm-data-table crm-data-table-horizontal crm-data-table-striped">
             <tr>
              <th>
                {ts}Recurring contributions{/ts}
              </th>
              {foreach from=$static_label item=label}
              <th>
                {$label}
              </th>
              {/foreach}
            </tr>

             {foreach from=$recur_table item=row}
            <tr>
              {foreach from=$row item=item key=key}
              {if $key == 0}
              <th>
                {$item}
              </th>
              {else}
                <td>
                  {$item}
                </td>
              {/if}
              {/foreach}
             </tr>
             {/foreach}
          </table>
        </div>
      </div>
    </div>
  </div>
  {/if}

  {if $showhidden}
  {foreach from=$showhiddenChart item=item key=key}
    
  <div class="column-chart-bar-showhidden">
    <h3>{$key}</h3>
    {include file="CRM/common/chartist.tpl" chartist=$item}
  <div id="{$item.id}"></div>
  </div>
  {/foreach}

  {/if}

{/if}
