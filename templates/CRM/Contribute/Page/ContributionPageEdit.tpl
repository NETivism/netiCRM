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
{if $is_active}
<div class="flex-general">
  {capture assign=liveURL}{crmURL a=true p='civicrm/contribute/transact' q="reset=1&id=`$id`"}{/capture}
  <a href="{crmURL p='civicrm/contribute/transact' q="reset=1&id=`$id`" fe='true'}" target="_blank">
    &raquo; {ts}Go to this LIVE Online Contribution page{/ts}
  </a>
  <textarea name="url_to_copy" class="url_to_copy" cols="45" rows="1" onclick="this.select(); document.execCommand('copy');" data-url-original="{$liveURL}">{if $shorten}{$shorten}{else}{$liveURL}{/if}</textarea>
  <span>
    <a href="#" class="button url-copy" onclick="document.querySelector('textarea[name=url_to_copy]').select(); document.execCommand('copy'); return false;"><i class="zmdi zmdi-link"></i> {ts}Copy{/ts}</a>
  </span>
  <span>
    <a href="#" class="button url-shorten" data-url-shorten="url_to_copy" data-page-id="{$id}" data-page-type="civicrm_contribution_page"><i class="zmdi zmdi-share"></i> {ts}Shorten URL{/ts}</a>
  </span>
</div>
{include file="CRM/common/ShortenURL.tpl"}
{else}
<div class="messages">
  {ts}This page is currently <strong>inactive</strong> (not accessible to visitors).{/ts}
  {capture assign=settingURL}{crmURL p='civicrm/admin/contribute/settings' q="reset=1&action=update&id=`$id`"}{/capture}
  {ts 1=$settingURL}When you are ready to make this page live, click <a href='%1'>Title and Settings</a> and update the <strong>Active?</strong> checkbox.{/ts}
</div>
{/if}

<h2>{ts}Statistics{/ts}</h2>
{include file="CRM/common/ContributionPageStatusCard.tpl" statistics=$contribution_page_statistics}

<h2>{ts}Settings{/ts}</h2>
<table class="report">
<tr>
    <td><a href="{crmURL p='civicrm/contribute/transact' q="reset=1&action=preview&id=`$id`"}" target="_blank">&raquo; {ts}Test-drive{/ts}</a></td>
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
      <a href="{crmURL p='civicrm/admin/contribute/pcp' q="reset=1&action=update&id=`$id`"}"><i class="zmdi zmdi-settings"></i> {ts}Admin Basic Settings{/ts}</a> <br>
      <a href="{crmURL p="civicrm/admin/pcp" q="reset=1&contribution_page_id=`$id`"}"><i class="zmdi zmdi-file"></i> {ts}Manage Personal Campaign Pages{/ts}</a> <br>
      <div class="new-personal-campaign-wrap flex-general">
        {capture assign=newPersonalCampaignURL}{crmURL a=true p='civicrm/contribute/campaign' q="action=add&reset=1&pageId=`$id`"}{/capture}
        <div class="new-personal-campaign-link-wrap link-desc-wrap">
          <a href="{crmURL a=true p='civicrm/contribute/campaign' q="action=add&reset=1&cid=0&pageId=`$id`"}" target="_blank"><i class="zmdi zmdi-plus-square"></i> {ts}New personal campaign page{/ts}</a>
          <div class="description">{ts}You can provide this URL to supporters for creating new Personal Campaign Pages.{/ts}</div>
        </div>
        <textarea name="url_to_copy_new_personal_campaign" class="url_to_copy" cols="45" rows="1" onclick="this.select(); document.execCommand('copy');" data-url-original="{$newPersonalCampaignURL}">{if $shorten}{$shorten}{else}{$newPersonalCampaignURL}{/if}</textarea>
        <span>
        <a href="#" class="button url-copy" onclick="document.querySelector('textarea[name=url_to_copy_new_personal_campaign]').select(); document.execCommand('copy'); return false;"><i class="zmdi zmdi-link"></i> {ts}Copy{/ts}</a>
        </span>
        <span>
        <a href="#" class="button url-shorten" data-url-shorten="url_to_copy_new_personal_campaign"><i class="zmdi zmdi-share"></i> {ts}Shorten URL{/ts}</a>
        </span>
      </div>
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
