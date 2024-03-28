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
{capture assign="contrib_page_url"}{crmURL p='civicrm/contribute/transact' q="reset=1&id=`$pcp.contribution_page_id`"}{/capture}
{if $owner}
{if $is_embed}
<div class="pcp-management crm-management is-embed-mode">
{else}
<div class="pcp-management crm-management">
{/if}
<div class="inner">
<div class="crm-box crm-box-info">
  <div class="crm-box-inner inner">
    <div class="crm-box-body">
      <div class="message">
        <h3><strong>{ts}Personal Fundraiser View{/ts}</strong> - {ts 1=$contrib_page_url 2=$pageName}This is a preview of your Personal Campaign Page in support of <a href="%1"><strong>"%2"</strong></a>.{/ts}</h3>
        {ts}The current status of your page is{/ts}: <strong {if $pcp.status_id NEQ 2}class="font-red" {/if}>{$owner.status}</strong>. {if $pcp.status_id NEQ 2}<br />{ts}You will receive an email notification when your page is Approved and you can begin promoting your campaign.{/ts}{/if}{if $owner.start_date}<br />{ts}This campaign is active from{/ts}<strong>{$owner.start_date|truncate:10:''|crmDate}</strong> {ts}until{/ts}<strong>{$owner.end_date|truncate:10:''|crmDate}</strong>.{/if}
      </div> {* message end *}
      <ul class="crm-list">
        {foreach from = $links key = k item = v}
        <li class="crm-list-item">
          <a class="action-link" href="{crmURL p=$v.url q=$v.qs|replace:'%%pcpId%%':$replace.id|replace:'%%pcpBlock%%':$replace.block}" title="{$v.title}" {if $v.extra}{$v.extra}{/if}>{$v.name}</a>
          <div class="desc">{$hints.$k}</div>
        </li>
        {/foreach}
      </ul> {* crm-list end *}
      <div class="tip"><strong>{ts}Tip{/ts}</strong> - {ts}You must be logged in to your account to access the editing options above. (If you visit this page without logging in, you will be viewing the page in "live" mode - as your visitors and friends see it.){/ts}</div>
    </div> {* crm-box-body end *}
  </div> {* crm-box-inner end *}
</div> {* crm-box end *}
</div> {* pcp-management inner end *}
</div> {* pcp-management end *}
{elseif $anonMessage}
<div class="pcp-management crm-management">
  <div class="inner">
  <div class="crm-box crm-box-info">
    <div class="crm-box-inner inner">
      <div class="crm-box-body">
        <div class="message">
        </div>
      </div> {* crm-box-body end *}
    </div> {* crm-box-inner end *}
  </div> {* crm-box end *}
  </div> {* pcp-management inner end *}
</div> {* pcp-management end *}
{/if}{*end owner*}

{if $pcp_image_src}
<div class="pcp-leading" style="background-image:url({$pcp_image_src})">
</div>
{/if}

{if $progress.display}
  {if $progress.display == "1"} {* Display progress bar and amount raised *}
    {include file="CRM/common/progressbar.tpl" progress=$progress}
  {/if}

  {if $progress.display == "2"} {* Display amount raised only *}
    {if $progress.type}
    <div class="pcp-amount-goal pcp-amount-goal-top">
      {if $progress.type|strstr:"amount"}
        {$progress.label} <span class="counter">{$progress.goal|crmMoney}</span>
      {elseif $progress.type == "recurring"}
        {$progress.label} <span class="counter">{$progress.goal}</span>{ts}People{/ts}
      {/if}
    </div>
    {/if}
    {if $link_display}
      <div class="pcp-donate pcp-buttons pcp-buttons-top">
        {if $contribution_page.is_active}
          {if $contribute_url}
          <a href="{$contribute_url}" class="button contribute-button pcp-contribute-button"><span class="normal-text">{$contribution_text}</span></a>
          {else}
          <div class="button contribute-button pcp-contribute-button"><span class="normal-text">{$contribution_text}</span></div>
          {/if}
        {else}
        <div class="button contribute-button pcp-contribute-button is-disabled"><span class="normal-text">{$contribution_text}</span></div>
        {/if}
      </div>
      {if !$contribution_page.is_active}
      <div class="pcp-donate-desc">{ts}This fundraising campaign has ended.{/ts}</div>
      {/if}
    {/if}
  {/if}
{/if}

<div class="pcp-campaign">
  <div class="pcp-intro-text">{$pcp.intro_text|purify}</div>
  <div class="pcp-page-text main-content">
    {$pcp.page_text|purify}
  </div>
  {if $link_display}
    <div class="pcp-donate pcp-buttons pcp-buttons-bottom">
      {if $contribution_page.is_active}
        {if $contribute_url}
        <a href="{$contribute_url}" class="button contribute-button pcp-contribute-button"><span class="normal-text">{$contribution_text}</span></a>
        {else}
        <div class="button contribute-button pcp-contribute-button"><span class="normal-text">{$contribution_text}</span></div>
        {/if}
      {else}
      <div class="button contribute-button pcp-contribute-button is-disabled"><span class="normal-text">{$contribution_text}</span></div>
      {/if}
    </div>
    {if !$contribution_page.is_active}
    <div class="pcp-donate-desc">{ts}This fundraising campaign has ended.{/ts}</div>
    {/if}
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
    const resizeGridItem = (item, grid, rowHeight, rowGap) => {
      // Calculate the number of rows a grid item should span
      const rowSpan = Math.ceil(
        (item.querySelector(".inner").getBoundingClientRect().height + rowGap) / 
        (rowHeight + rowGap)
      );
      // Set the grid-row-end property to span the calculated number of rows
      item.style.gridRowEnd = `span ${rowSpan}`;
    };

    const resizeAllGridItems = () => {
      const container = document.querySelector(".crm-container");
      const grid = container.querySelector(".pcp-honor-roll-items");
      const style = window.getComputedStyle(grid);
      const rowHeight = parseInt(style.getPropertyValue("grid-auto-rows")) || 20;
      const rowGap = parseInt(style.getPropertyValue("grid-row-gap"));
      const allItems = document.querySelectorAll(".pcp-honor-roll-item");

      // Loop through each grid item and resize it
      allItems.forEach(item => resizeGridItem(item, grid, rowHeight, rowGap));
    };

    const resizeInstance = (instance) => {
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

    $(document).ready(resizeAllGridItems);
    window.addEventListener("resize", resizeAllGridItems);
  })(cj);
  </script>
  {/literal}
  {/if}{* end pcp honor roll *}

  <div class="pcp-parent-link"><a href="{$contrib_page_url}">{ts}Back to contribution page{/ts} <i class="zmdi zmdi-arrow-right-top"></i></a></div>
</div><!-- /.campaign -->

<div class="pcp-sticky-header">
  <div class="inner">
    {if $pcp.is_honor_roll && $honor}
    <div class="pcp-honor-counter">{ts 1=$honor|@count}Supported by %1 people{/ts}</div>
    {/if}
    {if $link_display}
    <div class="pcp-donate">
      {if $contribute_url}
      <a href="{$contribute_url}" class="button contribute-button pcp-contribute-button"><span class="normal-text">{$contribution_text}</span><span class="mini-text">{ts}Contribute Now{/ts}</span></a>
      {else}
      <div class="button contribute-button pcp-contribute-button"><span class="normal-text">{$contribution_text}</span><span class="mini-text">{ts}Contribute Now{/ts}</span></div>
      {/if}
    </div>
    {/if}
    {if $share_data}
    <ul class="pcp-social-links">
      <li><a class="social-link facebook" href="https://www.facebook.com/sharer.php?u={$share_data.url}" target="_blank" title="{ts 1='Facebook'}Share to %1{/ts}">Facebook</a></li>
      <li><a class="social-link line" href="https://line.me/R/msg/text/?{$share_data.title}%0D%0A{$share_data.url}" target="_blank" title="{ts 1='LINE'}Share to %1{/ts}">LINE</a></li>
    </ul>
    {/if}
  </div>
</div>
{* window resize helper script *}
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

    var $pcpButtonsTop;
    if ($('.progress-block').length > 0) {
      $pcpButtonsTop = $('.progress-block .progress-buttons');
      console.log($pcpButtonsTop);
    }
    else {
      $pcpButtonsTop = $('.pcp-buttons-top');
    }
    let $pcpStickyHeader = $('.pcp-sticky-header');

    $(window).on('scroll', function() {
      let buttonOffset = $pcpButtonsTop.offset().top + $pcpButtonsTop.outerHeight();
      if ($(window).scrollTop() > buttonOffset) {
        $pcpStickyHeader.addClass('is-visible');
      } else {
        $pcpStickyHeader.removeClass('is-visible');
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
  $(".crm-container > .messages.status").hide();
  let pcpSessionMessage = $(".crm-container > .messages.status").html();
  if (pcpSessionMessage) {
    $pcpSessionMessage = $('<p>'+pcpSessionMessage+'</p>');
    $pcpSessionMessage.prependTo('.pcp-management .inner .message');
  }
});
</script>
{/literal}