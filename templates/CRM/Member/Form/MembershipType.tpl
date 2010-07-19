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
{* this template is used for adding/editing/deleting membership type  *}
<fieldset>
<legend>{if $action eq 1}{ts}New Membership Type{/ts}{elseif $action eq 2}{ts}Edit Membership Type{/ts}{else}{ts}Delete Membership Type{/ts}{/if}</legend>
<div class="form-item" id="membership_type_form">
    {if $action eq 8}   
    <div class="messages status">
        {ts}WARNING: Deleting this option will result in the loss of all membership records of this type.{/ts} {ts}This may mean the loss of a substantial amount of data, and the action cannot be undone.{/ts} {ts}Do you want to continue?{/ts}
    </div>
    <dl><dt>&nbsp;</dt><dd>{$form.buttons.html}</dd></dl>
    {else}
    <dl> 
        <dt>{$form.name.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_membership_type' field='name' id=$membershipTypeId}{/if}</dt>
        <dd>{$form.name.html}</dd>
        <dt>&nbsp;</dt>
        <dd class="description">{ts}e.g. 'Student', 'Senior', 'Honor Society'...{/ts}</dd>
        <dt>{$form.description.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_membership_type' field='description' id=$membershipTypeId}{/if}</dt>
        <dd>{$form.description.html}</dd>
        <dt>&nbsp;</dt>
        <dd class="description">{ts}Description of this membership type for display on signup forms. May include eligibility, benefits, terms, etc.{/ts}</dd>
        {if !$searchDone or !$searchCount or !$searchRows}
        <dt>{$form.member_org.label}<span class="marker"> *</span></dt>
        <dd><label>{$form.member_org.html}</label>&nbsp;&nbsp;{$form._qf_MembershipType_refresh.html}</dd>
        <dt>&nbsp;</dt>
        <dd class="description">{ts}Members assigned this membership type belong to which organization (e.g. this is for membership in 'Save the Whales - Northwest Chapter'). NOTE: This organization/group/chapter must exist as a CiviCRM Organization type contact.{/ts}</dd>
        {/if} 
    </dl>
    <div class="spacer"></div>	
    {if $searchDone} {* Search button clicked *}
        {if $searchCount}
            {if $searchRows} {* we've got rows to display *}
            <fieldset><legend>{ts}Select Target Contact for the Membership-Organization{/ts}</legend>
	        <dl>                
	            <dt>{$form.member_org.label}</dt>
                <dd>{$form.member_org.html}&nbsp;&nbsp;{$form._qf_MembershipType_refresh.html}</dd>
                <dt>&nbsp;</dt>
                <dd class="description">{ts}Organization, who is the owner for this membership type.{/ts}</dd>
	        </dl>
            <div class="spacer"></div>
            <div class="description">
                {ts}Select the target contact for this membership-organization if it appears below. Otherwise you may modify the search name above and click Search again.{/ts}
            </div>
            {strip}
            <table>
                {*Column Headers*}
                <tr class="columnheader">
                    <th>&nbsp;</th>
                    <th>{ts}Name{/ts}</th>
                    <th>{ts}City{/ts}</th>
                    <th>{ts}State{/ts}</th>
                    <th>{ts}Email{/ts}</th>
                    <th>{ts}Phone{/ts}</th>
                    </tr>
                {*Data to be displyed*}
                {foreach from=$searchRows item=row}
                <tr class="{cycle values="odd-row,even-row"}">
                    <td>{$form.contact_check[$row.id].html}</td>
                    <td>{$row.type} {$row.name}</td>
                    <td>{$row.city}</td>
                    <td>{$row.state}</td>
                    <td>{$row.email}</td>
                    <td>{$row.phone}</td>
                </tr>
                {/foreach}
            </table>
            {/strip}
            </fieldset>{*End of Membership Organization Block*}

            {else} {* too many results - we're only displaying 50 *}
                {capture assign=infoMessage}{ts}Too many matching results. Please narrow your search by entering a more complete target contact name.{/ts}{/capture}
                {include file="CRM/common/info.tpl"}
            {/if}
        {else} {* no valid matches for name + contact_type *}
            {capture assign=infoMessage}{ts 1=$form.member_org.value 2=Organization}No matching results for <ul><li>Name like: %1</li><li>Contact type: %2</li></ul>Check your spelling, or try fewer letters for the target contact name.{/ts}{/capture}
            {include file="CRM/common/info.tpl"}                
        {/if} {* end if searchCount *}
    {/if} {* end if searchDone *}

    <dl> 
        <dt>{$form.minimum_fee.label}</dt>
        <dd>{$form.minimum_fee.html|crmMoney}</dd>
        <dt>&nbsp;</dt>
        <dd class="description">{ts}Minimum fee required for this membership type. For free/complimentary memberships - set minimum fee to zero (0).{/ts}</dd>
       	<dt>{$form.contribution_type_id.label}<span class="marker"> *</span></dt>
        <dd>{$form.contribution_type_id.html}</dd>
        <dt>&nbsp;</dt>
        <dd class="description">{ts}Select the contribution type assigned to fees for this membership type (for example 'Membership Fees'). This is required for all membership types - including free or complimentary memberships.{/ts}</dd>
        <dt>{$form.duration_unit.label}<span class="marker">*</span></dt>
        <dd>{$form.duration_interval.html}&nbsp;&nbsp;{$form.duration_unit.html}</dd>
        <dt>&nbsp;</dt>
        <dd class="description">{ts}Duration of this membership (e.g. 30 days, 2 months, 5 years, 1 lifetime){/ts}</dd>
        <dt>{$form.period_type.label}<span class="marker"> *</span></dt>
        <dd>{$form.period_type.html}</dd>     
        <dt>&nbsp;</dt>
        <dd class="description">{ts}Select 'rolling' if membership periods begin at date of signup. Select 'fixed' if membership periods begin on a set calendar date.{/ts} {help id="period-type"}</dd>
    </dl>   
    
    <div id="fixed_period">
        <dl>
            <dt>{$form.fixed_period_start_day.label}</dt>
            <dd>{$form.fixed_period_start_day.html}</dd>
            <dt>&nbsp;</dt>
            <dd class="description">{ts}Month and day on which a <strong>fixed</strong> period membership or subscription begins. Example: A fixed period membership with Start Day set to Jan 01 means that membership periods would be 1/1/06 - 12/31/06 for anyone signing up during 2006.{/ts}</dd>
            <dt>{$form.fixed_period_rollover_day.label}</dt>
            <dd>{$form.fixed_period_rollover_day.html}</dd>
            <dt>&nbsp;</dt>
            <dd class="description">{ts}Membership signups after this date cover the following calendar year as well. Example: If the rollover day is November 31, membership period for signups during December will cover the following year.{/ts}</dd>
        </dl>
    </div>

    <div class="spacer"></div>	    
    <dl> 	
        <dt>{$form.relationship_type_id.label}</dt>
        <dd>{$form.relationship_type_id.html}</dd>
        <dt>&nbsp;</dt>
        <dd class="description">{ts}Memberships can be automatically granted to related contacts by selecting a Relationship Type.{/ts} {help id="rel-type"}</dd>
        <dt>{$form.visibility.label}</dt>
        <dd>{$form.visibility.html}</dd>
        <dt>&nbsp;</dt>
        <dd class="description">{ts}Is this membership type available for self-service signups ('Public') or assigned by CiviCRM 'staff' users only ('Admin'){/ts}</dd>
        <dt>{$form.weight.label}</dt>
        <dd>{$form.weight.html}</dd>
        <dt>{$form.is_active.label}</dt>
        <dd>{$form.is_active.html}</dd>
    </dl>{*End of dl*}
    <div class="spacer"></div>
    <fieldset><legend>{ts}Renewal Reminders{/ts}</legend>
        <div class="description">
            {ts}If you would like Membership Renewal Reminder emails sent to members automatically, you need to create a reminder message template and you need to configure and periodically run a 'cron' job on your server.{/ts} {docURL page="Membership Types"}
        </div>
        {if $noMsgTemplates}
            {capture assign=msgTemplate}{crmURL p='civicrm/admin/messageTemplates' q="action=add&reset=1"}{/capture}
            <div class="status message">
                {ts 1=$msgTemplate}No message templates have been created yet. If you want renewal reminders to be sent, <a href='%1'>click here</a> to create a reminder email template. Then return to this screen to assign the renewal reminder message, and set reminder date.{/ts}
            </div>
        {else}
            <dl>
                <dt>{$form.renewal_msg_id.label}</dt>
                <dd>{$form.renewal_msg_id.html}</dd>
          
                <dd class="description">{ts}Select the renewal reminder message to be sent to the members of this membership type.{/ts}</dd>              
                <dt>{$form.renewal_reminder_day.label}</dt>
                <dd>{$form.renewal_reminder_day.html}</dd>
                <dt>&nbsp;</dt>
                <dd class="description">{ts}Send Reminder these many days prior to membership expiration.{/ts}</dd>
            </dl>
        {/if}
    </fieldset>
    <dl>
        <dt>&nbsp;</dt>
        <dd>{$form.buttons.html}</dd>
    </dl>
    {/if}
    <div class="spacer"></div>
</div>
</fieldset>

{literal}
    <script type="text/javascript">
    if ( ( document.getElementsByName("period_type")[0].value   == "fixed" ) && 
         ( document.getElementsByName("duration_unit")[0].value == "year"  ) ) {
	   show( 'fixed_period' );
    } else {
	   hide( 'fixed_period' );
    }
	function showHidePeriodSettings(){
        if ( ( document.getElementsByName("period_type")[0].value   == "fixed" ) && 
             ( document.getElementsByName("duration_unit")[0].value == "year"  ) ) {
	        show('fixed_period');
		    document.getElementsByName("fixed_period_start_day[M]")[0].value = "1";
		    document.getElementsByName("fixed_period_start_day[d]")[0].value = "1";
            document.getElementsByName("fixed_period_rollover_day[M]")[0].value = "12";
		    document.getElementsByName("fixed_period_rollover_day[d]")[0].value = "31";
        } else {
		    hide('fixed_period');
            document.getElementsByName("fixed_period_start_day[M]")[0].value = "";
		    document.getElementsByName("fixed_period_start_day[d]")[0].value = "";
		    document.getElementsByName("fixed_period_rollover_day[M]")[0].value = "";
		    document.getElementsByName("fixed_period_rollover_day[d]")[0].value = "";
	    }
    }
    </script>
{/literal}
