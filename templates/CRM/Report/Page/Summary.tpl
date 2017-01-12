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

<style>
  {literal}
  .page-title:after{
    content: "beta";
    font-size: 13px;
    color: #9e9e9e;
    vertical-align: top;
    padding-left: 5px;
  }
  {/literal}
</style>

{if $hasChart}

  <div class="column-summary-count">
    {if $contribute_total}<div>{ts}Online Contribution{/ts}{$contribute_total}</div>{/if}
    {if $participant_total}<div>{ts}Online Registration{/ts}{$participant_total}{ts}times{/ts}</div>{/if}
    {if $contact_total}<div>{ts}Contact{/ts}{$contact_total}{ts}People{/ts}</div>{/if}
    {if $mailing}<div>{ts}Emailing Sended Count{/ts}{$mailing}{ts}{/ts}</div>{/if}

    <div class="column-contribution-online-offline">
      <h3>{ts}Online Contribution Amount{/ts}</h3>
    {include file="CRM/common/chartist.tpl" chartist=$chartConributeOnlineOffline}
    <div id="chart-pie-with-legend-contribution-online-offline"></div>
    </div>

    <div class="column-participant-online-offline">
      <h3>{ts}Online Event Registration{/ts}</h3>
    {include file="CRM/common/chartist.tpl" chartist=$chartParticipantOnlineOffline}
    <div id="chart-pie-with-legend-participant-online-offline"></div>
    </div>

  </div>

  <div class="column-contact-source">
    <h3>{ts}Contact Source{/ts}</h3>
    {* chartist *}
    {include file="CRM/common/chartist.tpl" chartist=$chartContact}
    <div id="{$chartContact.id}"></div>

  </div>

  <div class="column-contribution-instrument">
    <h3>{ts}Payment Instrument{/ts}</h3>
  {* chartist *}
  {include file="CRM/common/chartist.tpl" chartist=$chartInsSum}
  <div id="chart-pie-with-legend-contribute-instrument"></div>
  </div>

  <div class="column-contribution-times">
    <h3>{ts}Donation Count{/ts}</h3>
  {include file="CRM/common/chartist.tpl" chartist=$chartContribTimes}
  <div id="chart-pie-with-legend-contribute-times"></div>
  </div>

  <div class="column-mailing-delivered">
    <h3>{ts}Mailing{/ts}</h3>
    {include file="CRM/common/chartist.tpl" chartist=$chartMailing}
  <div id="chart-bar-mailing"></div>
  </div>

  <div class="column-contribution-types">
  <h3>{ts}Contribution Total{/ts}</h3>
    <table class="report-data-table">
      <tr>
        <th></th>
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

       <tr>
        <th colspan="{capture assign=colspan}{$static_label|@count}{/capture}{$colspan+1}"></th>
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

  {literal}
  <style>
     table.report-data-table td, table.report-data-table th{
      text-align: right;
    }
  </style>
  {/literal}

  {if $showhidden}
  {foreach from=$showhiddenChart item=item key=key}
    
  <div class="column-chart-bar-showhidden">
    <h3>{$key}</h3>
    {include file="CRM/common/chartist.tpl" chartist=$item}
  <div id="chart-bar-{$key}"></div>
  </div>
  {/foreach}

  {/if}

{/if}
