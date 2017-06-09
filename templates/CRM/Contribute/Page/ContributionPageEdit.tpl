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
<h2>{$title}</h2>                                
<div id="help">
    {capture assign=docLink}{docURL page="CiviContribute Admin" text="CiviContribute Administration Documentation"}{/capture}
    {ts 1=$docLink}Use the links below to update features and content for this Online Contribution Page, as well as to run through the contribution process in <strong>test mode</strong>. Refer to the %1 for more information.{/ts}
</div>
<table class="report"> 
<tr>
    {if $is_active}
        <td nowrap><a href="{crmURL p='civicrm/contribute/transact' q="reset=1&id=`$id`" fe='true'}" target="_blank" class="button"><i class="zmdi zmdi-share"></i> {ts}Go to this LIVE Online Contribution page{/ts}</a></td>
        <td>
        {if $config->userFramework EQ 'Drupal'}
         {ts}Create links to this contribution page by copying and pasting the following URL into any web page.{/ts}<br>
         <textarea cols="60" rows="1" onclick="this.select()">{crmURL a=true p='civicrm/contribute/transact' q="reset=1&id=`$id`"}</textarea>

        {elseif $config->userFramework EQ 'Joomla'}
            {ts 1=$id}Create front-end links to this contribution page using the Menu Manager. Select <strong>Online Contribution</strong> and choose your desired contribution page from the parameters section.{/ts}
        {/if}
        </td>
    {else}
        <td>{ts}This page is currently <strong>inactive</strong> (not accessible to visitors).{/ts}</td>
        <td>
        {capture assign=crmURL}{crmURL p='civicrm/admin/contribute/settings' q="reset=1&action=update&id=`$id`"}{/capture}
        {ts 1=$crmURL}When you are ready to make this page live, click <a href='%1'>Title and Settings</a> and update the <strong>Active?</strong> checkbox.{/ts}</td>
    {/if}
</tr>
<tr>
    <td><a href="{crmURL p='civicrm/contribute/transact' q="reset=1&action=preview&id=`$id`"}" target="_blank" class="button"><i class="zmdi zmdi-link"></i>{ts}Test-drive{/ts}</a></td>
    <td>{ts}Test-drive the entire contribution process - including custom fields, confirmation, thank-you page, and receipting. Transactions will be directed to your payment processor's test server. <strong>No live financial transactions will be submitted. However, a contact record will be created or updated and a test contribution record will be saved to the database. Use obvious test contact names so you can review and delete these records as needed. Test contributions are not visible on the Contributions tab, but can be viewed by searching for 'Test Contributions' in the CiviContribute search form.</strong>{/ts}
    </td>
</tr>
<tr>
    <td class="nowrap"><a href="{crmURL p='civicrm/admin/contribute/settings' q="reset=1&action=update&id=`$id`"}" id="idTitleAndSettings">&raquo; {ts}Title and Settings{/ts}</a></td>
    <td>{ts}Set page title and describe your cause or campaign. Select contribution type (donation, campaign contribution, etc.), and set optional fund-raising goal and campaign start and end dates. Enable honoree features and allow individuals to contribute on behalf of an organization. Enable or disable this page.{/ts}</td>
</tr>
<tr>
    <td class="nowrap"><a href="{crmURL p='civicrm/admin/contribute/amount' q="reset=1&action=update&id=`$id`"}" id="idContributionAmounts">&raquo; {ts}Contribution Amounts{/ts}</a></td>
    <td>
        {ts}Select the payment processor to be used for this contribution page.{/ts}
        {ts}Configure contribution amount options and labels, minimum and maximum amounts.{/ts}
        {ts}Enable pledges OR recurring contributions (recurring contributions are not supported for all payment processors).{/ts}
        {ts}Give contributors the option to 'pay later' (e.g. mail in a check, call in a credit card, etc.).{/ts}
    </td>
</tr>
{if $CiviMember}
<tr>
    <td class="nowrap"><a href="{crmURL p='civicrm/admin/contribute/membership' q="reset=1&action=update&id=`$id`"}" id="idMembershipSettings">&raquo; {ts}Membership Settings{/ts}</a></td>
    <td>{ts}Configure membership sign-up and renewal options.{/ts}</td>
</tr>
{/if}
<tr>
    <td class="nowrap"><a href="{crmURL p='civicrm/admin/contribute/custom' q="reset=1&action=update&id=`$id`"}" id="idCustomPageElements">&raquo; {ts}Include Profiles{/ts}</a></td>
    <td>{ts}Collect additional information from contributors by selecting CiviCRM Profile(s) to include in this contribution page.{/ts}</td>
</tr>
<tr>
    <td class="nowrap"><a href="{crmURL p='civicrm/admin/contribute/thankYou' q="reset=1&action=update&id=`$id`"}" id="idThank-youandReceipting">&raquo; {ts}Thank-you and Receipting{/ts}</a></td>
    <td>{ts}Edit thank-you page contents and receipting features.{/ts}</td>
</tr>
<tr>
    <td class="nowrap"><a href="{crmURL p='civicrm/admin/contribute/friend' q="reset=1&action=update&id=`$id`"}" id="idFriend">&raquo; {ts}Tell a Friend{/ts}</a></td>
    <td>{ts}Make it easy for contributors to spread the word to friends and colleagues.{/ts}</td>
</tr>
<tr>
    <td class="nowrap" id="idPcp">&raquo; {ts}Personal Campaign Pages{/ts}</td>
    <td>{ts}Allow constituents to create their own personal fundraising pages linked to this contribution page.{/ts}<br>
      <a href="{crmURL p='civicrm/admin/contribute/pcp' q="reset=1&action=update&id=`$id`"}" target="_blank"><i class="zmdi zmdi-link"></i> {ts}Personal Campaign Pages{/ts} - {ts}Settings{/ts}</a> <br>
      <a href="{crmURL p="civicrm/admin/pcp" q="reset=1&contribution_page_id=`$id`"}" target="_blank"><i class="zmdi zmdi-link"></i> {ts}Manage Personal Campaign Pages{/ts}</a> <br>
      <a href="{crmURL a=true p='civicrm/contribute/campaign' q="action=add&reset=1&pageId=`$id`"}" target="_blank"><i class="zmdi zmdi-link"></i> {ts}Setup a Personal Campaign Page{/ts}</a>
    </td>
</tr>
<tr>
    <td class="nowrap"><a href="{crmURL p='civicrm/admin/contribute/widget' q="reset=1&action=update&id=`$id`"}" id="idWidget">&raquo; {ts}Contribution Widget{/ts}</a></td>
    <td>{ts}Create a contribution widget which you and your supporters can embed in websites and blogs.{/ts}</td>
</tr>
<tr>
    <td class="nowrap"><a href="{crmURL p='civicrm/admin/contribute/premium' q="reset=1&action=update&id=`$id`"}" id="idPremiums">&raquo; {ts}Premiums{/ts}</a></td>
    <td>{ts}Enable a Premiums section (incentives / thank-you gifts) for this page, and configure premiums offered to contributors.{/ts}</td>
</tr>
</table>
