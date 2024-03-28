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
{if $context EQ 'Search'}
    {include file="CRM/common/pager.tpl" location="top"}
{/if}

{strip}
<table class="selector">
<thead class="sticky">
    <tr>
    {if ! $single and $context eq 'Search' }
      <th scope="col" title="Select Rows">{$form.toggleSelect.html}</th> 
    {/if}
    {foreach from=$columnHeaders item=header}
        <th scope="col" {if $header.title}title="{$header.title}"{/if}>
        {if $header.sort}
          {assign var='key' value=$header.sort}
          {$sort->_response.$key.link}
        {else}
          {$header.name}
        {/if}
        </th>
    {/foreach}
    </tr>
 </thead>

  {counter start=0 skip=1 print=false}
  {foreach from=$rows key=k item=row}
  <tr id='rowid{$row.participant_id}' class="{cycle values="odd-row,even-row"} crm-event crm-event_{$row.event_id} crm-event_type_{$row.event_type_id}">
     {if ! $single }
        {if $context eq 'Search' }       
            {assign var=cbName value=$row.checkbox}
            <td>{$form.$cbName.html}</td> 
        {/if}	
	<td class="crm-participant-contact_type">{$row.contact_type}</td>
      <td class="crm-participant-id nowrap">
        {assign var="nk" value=$k+1}
        {assign var="next" value=$rows[$nk]}
        {if !$smarty.get.crmSID or $smarty.get.crmSID == '1_d'}
          {if $row.participant_registered_by_id}
            <span>&nbsp;</span><i class="zmdi zmdi-long-arrow-return zmdi-hc-flip-horizontal"></i><span>&nbsp;</span>
          {elseif $next.participant_registered_by_id}
            <i class="zmdi zmdi-accounts" title="{ts}Registered by ID{/ts}"></i>
          {else}
            <i class="zmdi zmdi-account"></i>
          {/if}
        {/if}
        {$row.participant_id}
      </td>
    	<td class="crm-search-display_name"><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.contact_id`"}" title="{ts}View contact record{/ts}">{$row.sort_name|smarty:nodefaults|purify}</a></td>
    {/if}

    <td class="crm-participant-event_title twelve">
      <a href="{crmURL p='civicrm/event/search' q="reset=1&force=1&event=`$row.event_id`"}" title="{ts}List participants for this event (all statuses){/ts}">{$row.event_title}</a>
      {if $contactId}
      <ul class="crm-nav-links">
        <li><a href="{crmURL p='civicrm/event/info' q="reset=1&id=`$row.event_id`"}" title="{ts}View event info page{/ts}" target="_blank"><i class="zmdi zmdi-info"></i>{ts}View event info page{/ts}</a></li>
      </ul>
      {/if}
    </td> 
    {assign var="participant_id" value=$row.participant_id}
    {if $lineItems.$participant_id}
        <td>
        {foreach from=$lineItems.$participant_id item=line name=lineItemsIter}
            {if $line.html_type eq 'Text'}{$line.label}{else}{$line.field_title}-{$line.label}x{$line.qty}{/if}: {$line.line_total}
            {if ! $smarty.foreach.lineItemsIter.last}<br />{/if}
        {/foreach}
        <hr>
        {$row.participant_fee_amount|crmMoney:$row.participant_fee_currency}
        {if $row.coupon}
          <hr>
          {ts}Coupon{/ts}-{$row.coupon.code}-{$row.coupon.description}: -{$row.coupon.discount_amount}
        {/if}
        <br>
        </td>
    {else}
        <td class="crm-participant-participant_fee_level">{if !$row.paid && !$row.participant_fee_level} {ts}(no fee){/ts}{else} {$row.participant_fee_level} ({$row.participant_fee_amount|crmMoney:$row.participant_fee_currency}){/if}
        {if $row.coupon}
          <hr>
          {ts}Coupon{/ts}-{$row.coupon.code}-{$row.coupon.description}: -{$row.coupon.discount_amount}
        {/if}
        </td>
    {/if}
    <td class="right nowrap crm-paticipant-contribution_total_amount">
      {$row.contribution_total_amount|crmMoney:$row.contribution_currency}<br>
      {if $row.contribution_status}({$row.contribution_status} {if $row.contribution_id}<a href="{crmURL p='civicrm/contact/view/contribution' q="reset=1&id=`$row.contribution_id`&cid=`$row.contact_id`&action=view&context=participant&selectedChild=contribute&compId=`$row.participant_id`&compAction=4&compContext=participant&key=`$qfKey`"}" target="_blank">{ts}View{/ts}{/if}){/if}
    </td>
    <td class="crm-participant-participant_register_date">{$row.participant_register_date|crmDate}</td>	
    <td class="crm-participant-participant_status crm-participant_status_{$row.participant_status_id}">{$row.participant_status}</td>
    <td class="crm-participant-participant_role">{$row.participant_role_id}</td>
    <td class="row-action">{$row.action|replace:'xx':$participant_id}</td>
   </tr>
  {/foreach}
{* Link to "View all participants" for Dashboard and Contact Summary *}
{if $limit and $pager->_totalItems GT $limit }
  {if $context EQ 'event_dashboard' }
    <tr class="even-row">
    <td colspan="10"><a href="{crmURL p='civicrm/event/search' q='reset=1'}">&raquo; {ts}Find more event participants{/ts}...</a></td></tr>
    </tr>
  {elseif $context eq 'participant' }  
    <tr class="even-row">
    <td colspan="7"><a href="{crmURL p='civicrm/contact/view' q="reset=1&force=1&selectedChild=participant&cid=$contactId"}">&raquo; {ts}View all events for this contact{/ts}...</a></td></tr>
    </tr>
  {/if}
{/if}
</table>
{/strip}

{if $context EQ 'Search'}
 <script type="text/javascript">
 {* this function is called to change the color of selected row(s) *}
    var fname = "{$form.formName}";	
    on_load_init_checkboxes(fname);
 </script>
{/if}

{if $context EQ 'Search'}
    {include file="CRM/common/pager.tpl" location="bottom"}
{/if}
