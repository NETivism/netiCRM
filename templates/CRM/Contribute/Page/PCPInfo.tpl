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
<link rel="stylesheet" href="{$config->resourceBase}packages/fontawesome/css/fontawesome.min.css?v{$config->ver}">
<link rel="stylesheet" href="{$config->resourceBase}packages/fontawesome/css/brands.min.css?v{$config->ver}">
{if $owner}
<div class="messages status">

	<p><strong>{ts}Personal Fundraiser View{/ts}</strong> - {ts 1=$contribPageURL 2=$pageName}This is a preview of your Personal Campaign Page in support of <a href="%1"><strong>"%2"</strong></a>.{/ts}</p>
        {ts}The current status of your page is{/ts}: <strong {if $pcp.status_id NEQ 2}class="font-red"{/if}>{$owner.status}</strong>.
        {if $pcp.status_id NEQ 2}<br />{ts}You will receive an email notification when your page is Approved and you can begin promoting your campaign.{/ts}{/if}
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
     <strong>{ts}Tip{/ts}</strong> - <span>{ts}You must be logged in to your account to access the editing options above. (If you visit this page without logging in, you will be viewing the page in "live" mode - as your visitors and friends see it.){/ts}</span>
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
  <div class="pcp-page-text main-content">
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
    <div class="pcp-honor-roll-items">
      {foreach from=$honor item=v key=honor_id}
        {if $v.nickname || $v.personal_note}
        <div class="pcp-honor-roll-item" id="pcp-honor-roll-item-{$honor_id}">
          <div class="inner">
            {if $v.nickname}
            <div class="pcp-honor-roll-name">{$v.nickname}</div>
            {/if}
            {if $v.personal_note}
            <div class="pcp-honor-roll-message">{$v.personal_note}</div>
            {/if}
          </div>
        </div>
        {/if}
      {/foreach}
    </div>
  </div>
  {* pcp honor roll masonry layout helper script *}
  {literal}
  <script>
  (function ($) {
    // Function to resize a single grid item
    const resizeGridItem = (item, grid, rowHeight, rowGap) => {
      // Calculate the number of rows a grid item should span
      const rowSpan = Math.ceil(
        (item.querySelector(".inner").getBoundingClientRect().height + rowGap) / 
        (rowHeight + rowGap)
      );
      // Set the grid-row-end property to span the calculated number of rows
      item.style.gridRowEnd = `span ${rowSpan}`;
    };

    // Function to resize all grid items in the container
    const resizeAllGridItems = () => {
      // Query the container and grid element
      const container = document.querySelector(".crm-container");
      const grid = container.querySelector(".pcp-honor-roll-items");
      // Get computed style of the grid to obtain row height and row gap values
      const style = window.getComputedStyle(grid);
      // Parse row height from the grid's computed style, default to 20 if not set
      const rowHeight = parseInt(style.getPropertyValue("grid-auto-rows")) || 20;
      // Parse row gap from the grid's computed style
      const rowGap = parseInt(style.getPropertyValue("grid-row-gap"));
      // Query all grid items
      const allItems = document.querySelectorAll(".pcp-honor-roll-item");
      // Loop through each grid item and resize it
      allItems.forEach(item => resizeGridItem(item, grid, rowHeight, rowGap));
    };

    // Function to resize a single grid item instance
    const resizeInstance = (instance) => {
      // Get the first element from the instance object
      const item = instance.elements[0];
      // Assuming grid, rowHeight, and rowGap are available in scope, 
      // or fetch them as in resizeAllGridItems
      resizeGridItem(item, grid, rowHeight, rowGap);
    };

    // Update grid item height when mouse enters each item
    document.querySelectorAll('.pcp-honor-roll-item').forEach(function(item) {
      item.addEventListener('mouseenter', function() {
        resizeAllGridItems();
      });

      item.addEventListener('mouseleave', function() {
        resizeAllGridItems();
      });
    });

    // Resize all grid items once the document is ready
    $(document).ready(resizeAllGridItems);

    // Add a resize event listener to the window to resize grid items when the window is resized
    window.addEventListener("resize", resizeAllGridItems);
  })(cj);
  </script>
  {/literal}
  {/if}{* end pcp honor roll *}

  <div class="pcp-parent-link"><a href="{$contribPageURL}">{ts}Back to contribution page{/ts} <i class="zmdi zmdi-arrow-right-top"></i></a></div>
</div><!-- /.campaign -->

<div class="pcp-sticky-header">
  <div class="inner">
    <div class="pcp-honor-counter">{ts 1=$honor|@count}Supported by %1 people{/ts}</div>
    <div class="pcp-donate">
      <a href="{$contributeURL}" class="button contribute-button pcp-contribute-button"><span class="normal-text">{$contributionText}</span><span class="mini-text">{ts}Contribute Now{/ts}</span></a>
    </div>
    <ul class="pcp-social-links">
      <li><a class="social-link facebook" href="{$shareUrl.facebook}">Facebook</a></li>
      <li><a class="social-link line" href="{$shareUrl.line}">Line</a></li>
    </ul>
  </div>
</div>
{literal}
<script>
(function ($) {
  const windowResizeEvent = () => {
    if ($(window).width() < 768) {
      $('body').addClass('is-mobile-mode');
    }
    else {
      $('body').removeClass('is-mobile-mode');
    }
  };
  $(document).ready(function() {
    windowResizeEvent();

    $(window).on('scroll', function() {
      let buttonOffset = $('.progress-button').offset().top + $('.progress-button').outerHeight();
      if ($(window).scrollTop() > buttonOffset) {
        $('.pcp-sticky-header').addClass('is-visible');
      } else {
        $('.pcp-sticky-header').removeClass('is-visible');
      }
      });
  });
  window.addEventListener("resize", windowResizeEvent);
})(cj);
</script>
{/literal}

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