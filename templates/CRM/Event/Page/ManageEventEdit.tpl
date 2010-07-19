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
<div id="help">
    {capture assign=docLink}{docURL page="Manage Events" text="CiviEvent Administration Documentation"}{/capture}
    {if $isTemplate}
        {ts 1=$docLink}Edit the features of this <strong>Event Template</strong> here. Refer to the %1 for more information.{/ts}
    {else}
        {ts 1=$docLink}You can update the features and content for this event from here. Refer to the %1 for more information.{/ts}
    {/if}
</div>

{* Skip participant search links for event templates. *}
{if ! $isTemplate}
    {ts}Participants{/ts}: <a href="{$findParticipants.urlCounted}" title="{ts}Find participants with counted statuses{/ts}">{$findParticipants.statusCounted}</a>, <a href="{$findParticipants.urlNotCounted}" title="{ts}Find participants with NOT counted statuses{/ts}">{$findParticipants.statusNotCounted}</a>
{/if}

<table class="report"> 
<tr>
    <td class="nowrap"><a href="{crmURL q="reset=1&action=update&id=`$id`&subPage=EventInfo"}" id="idEventInformationandSettings">&raquo; {ts}Event Information and Settings{/ts}</a></td>
    <td>{ts}Set event title, type (conference, performance etc.), description, start and end dates, maximum number of participants, and activate the event. Enable the public participant listing feature.{/ts}</td>
</tr>
<tr>
    <td class="nowrap"><a href="{crmURL q="reset=1&action=update&id=`$id`&subPage=Location"}" id="idLocation">&raquo; {ts}Event Location{/ts}</a></td>
    <td>{ts}Set event location and event contact information (email and phone).{/ts}</td>
</tr>
<tr>
    <td class="nowrap"><a href="{crmURL q="reset=1&action=update&id=`$id`&subPage=Fee"}" id="idFee">&raquo; {ts}Event Fees{/ts}</a></td>
     <td>{ts}Determine if the event is free or paid. For paid events, set the payment processor, fee level(s) and discounts. Give online registrants the option to 'pay later' (e.g. mail in a check, call in a credit card, etc.).{/ts}</td>
</tr>
<tr>
    <td class="nowrap"><a href="{crmURL q="reset=1&action=update&id=`$id`&subPage=Registration"}" id="idRegistration">&raquo; {ts}Online Registration{/ts}</a></td>
    <td>{ts}Determine whether an online registration page is available. If so, configure registration, confirmation and thank you page elements and confirmation email details.{/ts}</td>
</tr>
<tr>
    <td class="nowrap"><a href="{crmURL q="reset=1&action=update&id=`$id`&subPage=Friend"}" id="idFriend">&raquo; {ts}Tell a Friend{/ts}</a></td>
    <td>{ts}Make it easy for participants to spread the word about this event to friends and colleagues.{/ts}</td>
</tr>

{if ! $isTemplate}
    <tr>
    {if $participantListingURL}
        <td class="nowrap"><a href="{$participantListingURL}" id="idParticipantListing">&raquo; {ts}Public Participant Listing{/ts}</a></td>
        {if $config->userFramework EQ 'Drupal'}
          <td>{ts 1=$participantListingURL}The following URL will display a list of registered participants for this event to users whose role includes "view event participants" permission: <a href="%1">%1</a>{/ts}</td>
        {else}
          <td>{ts 1=$participantListingURL}The following URL will display a list of registered participants for this event: <a href="%1">%1</a>{/ts}</td>
        {/if}
    {else}
        <td class="nowrap">&raquo; {ts}Public Participant Listing{/ts}</td>
        <td>{ts}Participant Listing is not enabled for this event. You can enable it from{/ts} <a href="{crmURL q="reset=1&action=update&id=`$id`&subPage=EventInfo"}">{ts}Event Information and Settings{/ts}</a>.
    {/if}
    </tr>

    <tr>
        <td class="nowrap"><a href="{crmURL p='civicrm/event/info' q="reset=1&id=`$id`" fe=1}" id="idDisplayEvent">&raquo; {ts}View Event Info{/ts}</a></td>
        <td>{ts}View the Event Information page as it will be displayed to site visitors.{/ts}</td>
    </tr>

    {if $isOnlineRegistration}
    <tr>
        <td class="nowrap"><a href="{crmURL p='civicrm/event/register' q="reset=1&action=preview&id=`$id`" fe=1}" id="idTest-drive">&raquo; {ts}Test-drive Registration{/ts}</a></td>
        <td>{ts}Test-drive the entire online registration process - including custom fields, confirmation, thank-you page, and receipting. Fee payment transactions will be directed to your payment processor's test server. <strong>No live financial transactions will be submitted. However, a contact record will be created or updated and participant and contribution records will be saved to the database. Use obvious test contact names so you can review and delete these records as needed.</strong>{/ts}</td>
    </tr>

    <tr>
        <td class="nowrap"><a href="{crmURL p='civicrm/event/register' q="reset=1&id=`$id`"}" id="idLive">&raquo; {ts}Live Registration{/ts}</a></td>
        <td>{ts}Review your customized <strong>LIVE</strong> online event registration page here. Use the following URL in links and buttons on any website to send visitors to this live page{/ts}:<br />
            <strong>{crmURL a=true p='civicrm/event/register' q="reset=1&id=`$id`"}</strong>
        </td>
    </tr>
    {/if}
{/if}
</table>
