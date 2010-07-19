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
    <th>{ts}Name{/ts}</th>
    <th>{ts}Event{/ts}</th>
    <th>{ts}Fee Level{/ts}</th>
    <th>{ts}Fee Amount{/ts}</th>
    <th>{ts}Event Date{/ts}</th>
    <th>{ts}Status{/ts}</th>
    <th>{ts}Role{/ts}</th>
  </tr>
{foreach from=$rows item=row}
    <tr class="{cycle values="odd-row,even-row"}">
        <td>{$row.sort_name}</td>
	<td>{$row.event_title}</td>
        {assign var="participant_id" value=$row.participant_id}
        {if $lineItems.$participant_id}
            <td>
            {foreach from=$lineItems.$participant_id item=line name=lineItemsIter}
               {$line.description}: {$line.qty}
               {if ! $smarty.foreach.lineItemsIter.last}<br>{/if}
            {/foreach}
            </td>
        {else}
            <td>{if !$row.paid && !$row.participant_fee_level} {ts}(no fee){/ts}{else} {$row.participant_fee_level}{/if}</td>
        {/if}
        <td>{$row.participant_fee_amount|crmMoney}</td>
        <td>{$row.event_start_date|truncate:10:''|crmDate}
          {if $row.event_end_date && $row.event_end_date|date_format:"%Y%m%d" NEQ $row.event_start_date|date_format:"%Y%m%d"}
              <br/>- {$row.event_end_date|truncate:10:''|crmDate}
          {/if}
        </td>
        <td>{$row.participant_status_id}</td>
        <td>{$row.participant_role_id}</td>
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
