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
{* Template for Full-text search component. *}
<div id="searchForm">
    <fieldset>
    <div class="form-item">
    <table class="form-layout-compressed">
        <tr>
            <td class="label">{$form.text.label}</td><td>{$form.text.html}</td>
            <td class="label">{ts}in...{/ts}</td><td>{$form.table.html}</td>
            <td>{$form.buttons.html} {help id="id-fullText"}</td>
        </tr>
    </table>
    </div>
</fieldset>
</div>
{include file="CRM/common/jsortable.tpl"}
{if $rowsEmpty}
    {include file="CRM/Contact/Form/Search/Custom/EmptyResults.tpl"}
{/if}

{if !empty($summary.Contact) }
    {* Search request has returned 1 or more matching rows. Display results. *}
    <fieldset>
        <legend>{ts}Contacts{/ts}</legend>
        {* This section displays the rows along and includes the paging controls *}
            {strip}
            <table id="contact_listing" class="display" class="selector" summary="{ts}Contact listings.{/ts}">
                <thead>
                <tr>
                    <th>{ts}Name{/ts}</th>
                    <th></th>
                </tr>
                </thead>
                {foreach from=$summary.Contact item=row}
                    <tr class="{cycle values="odd-row,even-row"}">
                        <td><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.contact_id`"}" title="{ts}View contact details{/ts}">{$row.display_name}</a></td>
                        <td><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.contact_id`"}">{ts}View{/ts}</a></td>
                    </tr>
                {/foreach}
            </table>
            {/strip}
    </fieldset>
    {* END Actions/Results section *}
{/if}

{if !empty($summary.Activity) }
    {* Search request has returned 1 or more matching rows. Display results. *}

    <fieldset>
        <legend>{ts}Activities{/ts}</legend>
        {* This section displays the rows along and includes the paging controls *}
            {strip}
            <table id="activity_listing" class="display" summary="{ts}Activity listings.{/ts}">
                <thead>
                <tr>
                    <th>{ts}Type{/ts}</th>
                    <th>{ts}Subject{/ts}</th>
                    <th>{ts}Details{/ts}</th>
                    <th>{ts}Added By{/ts}</th>
                    <th>{ts}With{/ts}</th>
                    <th>{ts}Assignee{/ts}</th>
                    <th></th>
                </tr>
                </thead>
                {foreach from=$summary.Activity item=row}
                    <tr class="{cycle values="odd-row,even-row"}">
                        <td>{$row.activity_type}</td>
                        <td>{$row.subject|truncate:40}</td>
                        <td>{$row.details}</td>
                        <td><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.contact_id`"}" title="{ts}View contact details{/ts}">{$row.display_name}</a></td>
                        <td><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.target_contact_id`"}" title="{ts}View contact details{/ts}">{$row.target_display_name}</a></td>
                        <td><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.assignee_contact_id`"}" title="{ts}View contact details{/ts}">{$row.assignee_display_name}</a></td>
                        <td>
                            {if $row.case_id }
                                <a href="{crmURL p='civicrm/case/activity/view' q="reset=1&aid=`$row.activity_id`&cid=`$row.contact_id`&caseID=`$row.case_id`"}">
                            {else}
                                <a href="{crmURL p='civicrm/contact/view/activity' q="atype=`$row.activity_type_id`&action=view&reset=1&id=`$row.activity_id`&cid=`$row.contact_id`"}">
                            {/if}
                            {ts}View{/ts}</a>
                        </td>
                    </tr>
                {/foreach}
            </table>
            {/strip}
    </fieldset>
    {* END Actions/Results section *}
{/if}

{if !empty($summary.Case) }
    {* Search request has returned 1 or more matching rows. Display results. *}

    <fieldset>
        <legend>{ts}Cases{/ts}</legend>
        {* This section displays the rows along and includes the paging controls *}
            {strip}
            <table id="case_listing" class="display" summary="{ts}Case listings.{/ts}">
                <thead>
                <tr>
                    <th>{ts}Client Name{/ts}</th>
                    <th id="start_date">{ts}Start Date{/ts}</th>
                    <th id="end_date">{ts}End Date{/ts}</th>
                    <th>{ts}Case ID{/ts}</th>
                    <th></th>
                </tr>
                </thead>
                {foreach from=$summary.Case item=row}
                    <tr class="{cycle values="odd-row,even-row"}">
                        <td><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.contact_id`"}" title="{ts}View contact details{/ts}">{$row.display_name}</a></td>
                        <td>{$row.case_start_date|crmDate:"%b %d, %Y %l:%M %P"}</td>
                        <td>{$row.case_end_date|crmDate:"%b %d, %Y %l:%M %P"}</td>
                        <td>{$row.case_id}</td>
                        <td><a href="{crmURL p='civicrm/contact/view/case' q="reset=1&id=`$row.case_id`&cid=`$row.contact_id`&action=view"}">{ts}Manage Case{/ts}</a></td>
                        <td class="start_date hiddenElement">{$row.case_start_date|crmDate}</td>
                        <td class="end_date hiddenElement">{$row.case_end_date|crmDate}</td>
                    </tr>
                {/foreach}
            </table>
            {/strip}
    </fieldset>
    {* END Actions/Results section *}
{/if}

{if !empty($summary.Contribution) }
    {* Search request has returned 1 or more matching rows. Display results. *}

    <fieldset>
        <legend>{ts}Contributions{/ts}</legend>
        {* This section displays the rows along and includes the paging controls *}
            {strip}
            <table id="contribute_listing" class="display" summary="{ts}Contribution listings.{/ts}">
                <thead>
                <tr>
                    <th>{ts}Contributor's Name{/ts}</th>
                    <th id="currency">{ts}Amount{/ts}</th>
                    <th>{ts}Contribution Type{/ts}</th>
                    <th>{ts}Source{/ts}</th>
                    <th id="received_date">{ts}Received{/ts}</th>
                    <th>{ts}Status{/ts}</th>
                    <th></th>
                </tr>
                </thead>
                {foreach from=$summary.Contribution item=row}
                    <tr class="{cycle values="odd-row,even-row"}">
                        <td><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.contact_id`"}" title="{ts}View contact details{/ts}">{$row.display_name}</a></td>
                        <td>{$row.contribution_total_amount|crmMoney}</td>
                        <td>{$row.contribution_type}</td>
                        <td>{$row.contribution_source}</td>
                        <td>{$row.contribution_receive_date|crmDate:"%b %d, %Y %l:%M %P"}</td>
                        <td>{$row.contribution_status}</td>
                        <td><a href="{crmURL p='civicrm/contact/view/contribution' q="reset=1&id=`$row.contribution_id`&cid=`$row.contact_id`&action=view"}">{ts}View{/ts}</a></td>
                        <td class="received_date hiddenElement">{$row.contribution_receive_date|crmDate}</td>
                    </tr>
                {/foreach}
            </table>
            {/strip}
    </fieldset>
    {* END Actions/Results section *}
{/if}

{if !empty($summary.Participant) }
    {* Search request has returned 1 or more matching rows. *}

    <fieldset>
        <legend>{ts}Event Participants{/ts}</legend>
        {* This section displays the rows along and includes the paging controls *}
            {strip}
            <table id="participant_listing" class="display" summary="{ts}Participant listings.{/ts}">
                <thead>
                <tr>
                    <th>{ts}Participant's Name{/ts}</th>
                    <th>{ts}Event{/ts}</th>
                    <th>{ts}Fee Level{/ts}</th>
                    <th id="currency">{ts}Fee Amount{/ts}</th>
                    <th id="register_date">{ts}Register Date{/ts}</th>
                    <th>{ts}Source{/ts}</th>
                    <th>{ts}Status{/ts}</th>
                    <th>{ts}Role{/ts}</th>
                    <th></th>
                </tr>
                </thead>
                {foreach from=$summary.Participant item=row}
                    <tr class="{cycle values="odd-row,even-row"}">
                        <td><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.contact_id`"}" title="{ts}View contact details{/ts}">{$row.display_name}</a></td>
                        <td>{$row.event_title}</td>
                        <td>{$row.participant_fee_level}</td>
                        <td>{$row.participant_fee_amount|crmMoney}</td>
                        <td>{$row.participant_register_date|crmDate:"%b %d, %Y %l:%M %P"}</td>
                        <td>{$row.participant_source}</td>
                        <td>{$row.participant_status}</td>
                        <td>{$row.participant_role}</td>
                        <td><a href="{crmURL p='civicrm/contact/view/participant' q="reset=1&id=`$row.participant_id`&cid=`$row.contact_id`&action=view"}">{ts}View{/ts}</a></td>
                        <td class="register_date hiddenElement">{$row.participant_register_date|crmDate}</td>
                    </tr>
                {/foreach}
            </table>
            {/strip}
    </fieldset>
    {* END Actions/Results section *}
{/if}

{if !empty($summary.Membership) }
    {* Search request has returned 1 or more matching rows. *}

    <fieldset>
        <legend>{ts}Memberships{/ts}</legend>
        {* This section displays the rows along and includes the paging controls *}
            {strip}
            <table id="membership_listing" class="display" summary="{ts}Membership listings.{/ts}">
                <thead>
                <tr>
                    <th>{ts}Member's Name{/ts}</th>
                    <th>{ts}Membership Type{/ts}</th>
                    <th id="currency">{ts}Membership Fee{/ts}</th>      
                    <th id="start_date">{ts}Membership Start Date{/ts}</th>
                    <th id="end_date">{ts}Membership End Date{/ts}</th>
                    <th>{ts}Source{/ts}</th>
                    <th>{ts}Status{/ts}</th>
                    <th></th>
                </tr>
                </thead>
                {foreach from=$summary.Membership item=row}
                    <tr class="{cycle values="odd-row,even-row"}">
                        <td><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.contact_id`"}" title="{ts}View contact details{/ts}">{$row.display_name}</a></td>
                        <td>{$row.membership_type}</td>
                        <td>{$row.membership_fee|crmMoney}</td>
                        <td>{$row.membership_start_date|crmDate:"%b %d, %Y %l:%M %P"}</td>
                        <td>{$row.membership_end_date|crmDate:"%b %d, %Y %l:%M %P"}</td>
                        <td>{$row.membership_source}</td>
                        <td>{$row.membership_status}</td>
                        <td><a href="{crmURL p='civicrm/contact/view/membership' q="reset=1&id=`$row.membership_id`&cid=`$row.contact_id`&action=view"}">{ts}View{/ts}</a></td>
                        <td class="start_date hiddenElement">{$row.membership_start_date|crmDate}</td>
                        <td class="end_date hiddenElement">{$row.membership_end_date|crmDate}</td>
                    </tr>
                {/foreach}
            </table>
            {/strip}
    </fieldset>
    {* END Actions/Results section *}
{/if}
