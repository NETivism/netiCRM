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
{* this template is used for displaying PCP information *}
{capture assign="contribPageURL"}{crmURL p='civicrm/contribute/transact' q="reset=1&id=`$pcp.contribution_page_id`"}{/capture}
{if $owner}
<div class="messages status">

	<p><strong>{ts}Personal Fundraiser View{/ts}</strong> - {ts 1=$contribPageURL 2=$pageName}This is a preview of your Personal Campaign Page in support of <a href="%1"><strong>"%2"</strong></a>.{/ts}</p>
        {ts}The current status of your page is{/ts}: <strong {if $pcp.status_id NEQ 2}class=disabled {/if}>{$owner.status}</strong>.
        {if $pcp.status_id NEQ 2}<br /><span class="description">{ts}You will receive an email notification when your page is Approved and you can begin promoting your campaign.{/ts}</span>{/if}
        {if $owner.start_date}<br />{ts}This campaign is active from{/ts} <strong>{$owner.start_date|truncate:10:''|crmDate}</strong> {ts}until{/ts} <strong>{$owner.end_date|truncate:10:''|crmDate}</strong>.{/if}
        <table class="form-layout-compressed">
        <tr><td colspan="2"><strong>{ts}You can{/ts}:</strong></td></tr>
		{foreach from = $links key = k item = v}
          <tr>
            <td>
                <a href="{crmURL p=$v.url q=$v.qs|replace:'%%pcpId%%':$replace.id|replace:'%%pcpBlock%%':$replace.block}" title="{$v.title}"{if $v.extra}{$v.extra}{/if}><strong>&raquo; {$v.name}</strong></a>
		   </td>
  		   <td>&nbsp;<cite>{$hints.$k}</cite></td>
	 	 </tr>
        {/foreach}
  	   </table>
	<i class="zmdi zmdi-lamp"></i>
     <strong>{ts}Tip{/ts}</strong> - <span class="description">{ts}You must be logged in to your account to access the editing options above. (If you visit this page without logging in, you will be viewing the page in "live" mode - as your visitors and friends see it.){/ts}</span>
</div>
{/if}

{if $pcpImageSrc}
<div class="pcp-leading" style="background-image:url({$pcpImageSrc})">
</div>
{/if}

{if $progress.display}
  {include file="CRM/common/progressbar.tpl" progress=$progress}
{/if}

<div class="pcp-campaign">
  <div class="pcp-intro-text">{$pcp.intro_text|purify}</div>
  <div class="pcp-page-text">
    {$pcp.page_text|purify}
  </div>
  {if $validDate && $contributeURL}
  <div class="pcp-donate">
      <a href="{$contributeURL}" class="button contribute-button pcp-contribute-button"><span>{$contributionText}</span></a>
  </div>
  {/if}{* end contribute button *}
  {if $pcp.is_honor_roll && $honor}
  <div class="honor-roll-wrapper">
    <h3 class="honor-roll-title">{ts}HONOR ROLL{/ts}</h3>
    <div class="pcp-honor-roll-names">
      {foreach from=$honor item=v key=honor_id}
        <div class="pcp-honor-roll-entry">
          <div class="pcp-honor-roll-nickname" id="pcp-honor-roll-{$honor_id}">{$v.nickname}{if $v.personal_note}{help id="pcp-honor-roll-$honor_id" text=$v.personal_note helpicon='zmdi zmdi-comment-text-alt'}{else}<i class="zmdi zmdi-comment-outline"></i>{/if}</div>
        </div>
      {/foreach}
    </div>
  </div>
  {/if}{* end pcp honor roll *}

  <div class="pcp-parent-link"><a href="{$contribPageURL}">{ts}Back to contribution page{/ts} <i class="zmdi zmdi-arrow-right-top"></i></a></div>
</div><!-- /.campaign -->




{literal}
<script language="JavaScript">
cj(document).ready(function($){
  var percent = $('.thermometer-wrapper .thermometer-fill').data('percent');
  if (percent) {
    $('.thermometer-fill').css({"width":"0%", "transition":"width 1.5s ease-in-out"});
    setTimeout(function(){
      $('.thermometer-fill').css({"width":percent+"%"});
      $('.thermometer-pointer').animate({"left":percent+'%'}, 1500);
    }, 500);
  }
});
</script>
{/literal}
