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

{if $action eq 1 or $action eq 2 or $action eq 8}
   {include file="CRM/Admin/Form/Options.tpl"}
{else}	

<div id="help">
  {if $gName eq "gender"}
    {ts}CiviCRM is pre-configured with standard options for individual gender (e.g. Male, Female, Transgender). You can use this page to customize these options and add new options as needed for your installation.{/ts}
  {elseif $gName eq "individual_prefix"}
      {ts}CiviCRM is pre-configured with standard options for individual contact prefixes (e.g. Ms., Mr., Dr. etc.). You can use this page to customize these options and add new ones as needed for your installation.{/ts}
  {elseif $gName eq "mobile_provider"}
     {ts}When recording mobile phone numbers for contacts, it may be useful to include the Mobile Phone Service Provider (e.g. Cingular, Sprint, etc.). CiviCRM is installed with the most commonly encountered service providers. Administrators may define as many additional providers as needed.{/ts}
  {elseif $gName eq "instant_messenger_service"}
     {ts}When recording Instant Messenger (IM) 'screen names' for contacts, it is useful to include the IM Service Provider (e.g. AOL, Yahoo, etc.). CiviCRM is installed with the most commonly encountered service providers. Administrators may define as many additional providers as needed.{/ts}
  {elseif $gName eq "individual_suffix"}
     {ts}CiviCRM is pre-configured with standard options for individual contact name suffixes (e.g. Jr., Sr., II etc.). You can use this page to customize these options and add new ones as needed for your installation.{/ts}
  {elseif $gName eq "activity_type"}
     {ts}Activities are 'interactions with contacts' which you want to record and track.{/ts} {help id='id-activity-types'}
  {elseif $gName eq "payment_instrument"}
     {ts}You may choose to record the Payment Instrument used for each Contribution. The common payment methods are installed by default and cannot be modified (e.g. Check, Cash, Credit Card...). If your site requires additional payment methods, you can add them here.{/ts}
  {elseif $gName eq "accept_creditcard"}
    {ts}This page lists the credit card options that will be offered to contributors using your Online Contribution pages. You will need to verify which cards are accepted by your chosen Payment Processor and update these entries accordingly.{/ts}<br /><br />
    {ts}IMPORTANT: This page does NOT control credit card/payment method choices for sites and/or contributors using the PayPal Express service (e.g. where billing information is collected on the Payment Processor's website).{/ts}
  {elseif $gName eq "acl_role"}
    {capture assign=docLink}{docURL page="Access Control" text="Access Control Documentation"}{/capture}
    {capture assign=aclURL}{crmURL p='civicrm/acl' q='reset=1'}{/capture}
    {capture assign=erURL}{crmURL p='civicrm/acl/entityrole' q='reset=1'}{/capture}
    {ts 1=$docLink}ACLs allow you control access to CiviCRM data. An ACL consists of an <strong>Operation</strong> (e.g. 'View' or 'Edit'), a <strong>set of data</strong> that the operation can be performed on (e.g. a group of contacts), and a <strong>Role</strong> that has permission to do this operation. Refer to the %1 for more info.{/ts}<br /><br />
    {ts 1=$aclURL 2=$erURL}You can add or modify your ACL Roles below. You can create ACL&rsquo;s and grant permission to roles <a href='%1'>here</a>... and you can assign role(s) to CiviCRM contacts who are users of your site <a href='%2'>here</a>.{/ts}
  {elseif $gName eq 'event_type'}
    {ts}Use Event Types to categorize your events. Event feeds can be filtered by Event Type and participant searches can use Event Type as a criteria.{/ts}
  {elseif $gName eq 'participant_role'}
    {ts}Define participant roles for events here (e.g. Attendee, Host, Speaker...). You can then assign roles and search for participants by role.{/ts}
  {elseif $gName eq 'participant_status'}
    {ts}Define statuses for event participants here (e.g. Registered, Attended, Cancelled...). You can then assign statuses and search for participants by status.{/ts} {ts}"Counted?" controls whether a person with that status is counted as participant for the purpose of controlling the Maximum Number of Participants.{/ts}
  {elseif $gName eq 'from_email_address'}
    {ts}By default, CiviCRM uses the primary email address of the logged in user as the FROM address when sending emails to contacts. However, you can use this page to define one or more general Email Addresses that can be selected as an alternative. EXAMPLE: <em>"Client Services" &lt;clientservices@example.org&gt;</em>{/ts}
  {else}
    {ts 1=$GName}The existing option choices for %1 group are listed below. You can add, edit or delete them from this screen.{/ts}
  {/if}
</div>

<div class="crm-content-block crm-block">
{if $rows}
{if $action ne 1 and $action ne 2}
    <div class="action-link-button">
        <a href="{crmURL q="group="|cat:$gName|cat:"&action=add&reset=1"}" id="new"|cat:$GName class="button"><span><i class="zmdi zmdi-plus-circle-o"></i>{ts 1=$GName}Add %1{/ts}</span></a>
    </div>
{/if}
<div id={$gName}>
        {strip}
	{* handle enable/disable actions*} 
	{include file="CRM/common/enableDisable.tpl"}
    {include file="CRM/common/jsortable.tpl"}
        <table id="options" class="display">
	       <thead>
	       <tr>
            {if $showComponent}
                <th>{ts}Component{/ts}</th>
            {/if}
            <th>
                {if $gName eq "redaction_rule"}
                    {ts}Match Value or Expression{/ts}
                {else}
                    {ts}Label{/ts}
                {/if}
            </th>
        <th>
          {ts}Name{/ts}
        </th>
	    {if $gName eq "case_status"}
	    	<th>
		    {ts}Status Class{/ts}
		</th>	    
            {/if}
            <th>
                {if $gName eq "redaction_rule"}
                    {ts}Replacement{/ts}
                {else}
                    {ts}Value{/ts}
                {/if}
            </th>
            {if $showCounted}<th>{ts}Counted?{/ts}</th>{/if}
            {if $showVisibility}<th>{ts}Visibility{/ts}</th>{/if}
            <th id="nosort">{ts}Description{/ts}</th>
            <th id="order" class="sortable">{ts}Order{/ts}</th>
	        {if $showIsDefault}<th>{ts}Default{/ts}</th>{/if}
            <th>{ts}Reserved{/ts}</th>
            <th>{ts}Enabled?{/ts}</th>
            <th class="hiddenElement"></th>
            <th></th>
            </tr>
            </thead>
            <tbody>
        {foreach from=$rows item=row}
        <tr id="row_{$row.id}" class="crm-admin-options crm-admin-options_{$row.id} {cycle values="odd-row,even-row"}{if NOT $row.is_active} disabled{/if}">
            {if $showComponent}
                <td class="crm-admin-options-component_name">{$row.component_name}</td>
            {/if}
	        <td class="crm-admin-options-label">{$row.label}</td>
          <td class="crm-admin-options-name">{$row.name}</td>
	    {if $gName eq "case_status"}				
		<td class="crm-admin-options-grouping">{$row.grouping}</td>
            {/if}	
	        <td class="crm-admin-options-value">{$row.value}</td>
		{if $showCounted}
		<td class="yes-no crm-admin-options-filter">{if $row.filter eq 1}<img src="{$config->resourceBase}/i/check.gif" alt="{ts}Counted{/ts}" />{/if}</td>
		{/if}
            {if $showVisibility}<td class="crm-admin-visibility_label">{$row.visibility_label}</td>{/if}
	        <td class="crm-admin-options-description">{$row.description}</td>	
	        <td class="nowrap crm-admin-options-order">{$row.order}</td>
            {if $showIsDefault}
	    	<td class="crm-admin-options-is_default" align="center">{if $row.is_default eq 1}<img src="{$config->resourceBase}/i/check.gif" alt="{ts}Default{/ts}" />{/if}&nbsp;</td>{/if}
	        <td class="crm-admin-options-is_reserved">{if $row.is_reserved eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
	        <td class="crm-admin-options-is_active" id="row_{$row.id}_status">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
	        <td>{$row.action|replace:'xx':$row.id}</td>
	        <td class="order hiddenElement">{$row.weight}</td>
        </tr>
        {/foreach}
        </tbody>
        </table>
        {/strip}

        {if $action ne 1 and $action ne 2}
            <div class="action-link-button">
                <a href="{crmURL q="group="|cat:$gName|cat:"&action=add&reset=1"}" id="new"|cat:$GName class="button"><span><i class="zmdi zmdi-plus-circle-o"></i>{ts 1=$GName}Add %1{/ts}</span></a>
            </div>
        {/if}
</div>
{else}
    <div class="messages status">
         
        {capture assign=crmURL}{crmURL  q="group="|cat:$gName|cat:"&action=add&reset=1"}{/capture}
        {ts 1=$crmURL}There are no option values entered. You can <a href='%1'>add one</a>.{/ts}
    </div>    
{/if}
</div>
{/if}