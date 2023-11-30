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
<div class="crm-submit-buttons crm-submit-buttons-top crm-pcp-submit-buttons-top">{include file="CRM/common/formButtons.tpl" location="top"}</div>
<div id="help">
    {ts}Personalize the contents and appearance of your fundraising page here. You will be able to return to this page and make changes at any time.{/ts}
</div>
<div class="crm-block crm-contribution-campaign-form-block">
{if !$form.is_active.frozen}
<table class="form-layout-compressed">
	<tr class="crm-contribution-form-block-is_active">
		<td class="label">{$form.is_active.label}</td>
		<td>{$form.is_active.html}
    <span class="description">{ts}Is your Personal Campaign Page active? You can activate/de-activate it any time during it's lifecycle.{/ts}</span></td>
	</tr>
</table>
{/if}
{include file="CRM/Form/attachment.tpl" context="pcpCampaign"}
<table class="form-layout-compressed" width="100%">
	<tr class="crm-contribution-form-block-is_thermometer">
		<td class="label">{$form.is_thermometer.label}</td>
		<td>{$form.is_thermometer.html}
            <span class="description">{ts}If this option is checked, a "thermometer" showing progress toward your goal will be included on the page.{/ts}</span>
        </td>
	</tr>
	<tr class="crm-contribution-form-block-goal_amount">
		<td class="label">{$form.goal_amount.label}</td>
		<td>{$form.goal_amount.html|crmReplace:class:six}
            <span class="description">{ts}Total amount you would like to raise for this campaign.{/ts}</span>
		</td>
	</tr>
	<tr class="crm-contribution-form-block-title">
		<td class="label">{$form.title.label}</td>
		<td>{$form.title.html|crmReplace:class:big}</td>
	</tr>
	<tr class="crm-contribution-form-block-intro_text">
		<td class="label">{$form.intro_text.label}</td>
		<td>
            {$form.intro_text.html|crmReplace:class:big}
            <span class="description">{ts}Introduce the campaign and why you're supporting it. This text will appear at the top of your personal page AND at the top of the main campaign contribution page when people make a contribution through your page.{/ts}</span>
        </td>
	</tr>
	<tr class="crm-contribution-form-block-page_text">
		<td class="label" width="15%">{$form.page_text.label}</td>
		<td width="85%">
            <span class="description">{ts}Tell people why this campaign is important to you.{/ts}</span>
            {$form.page_text.html|crmReplace:class:huge}
        </td>
	</tr>
	<tr class="crm-contribution-form-block-donate_link_text">
		<td class="label">{$form.donate_link_text.label}</td>
		<td>{$form.donate_link_text.html}
            <span class="description">{ts}The text for the contribute button.{/ts}</span>
		</td>
	</tr>
	<tr class="crm-contribution-form-block-is_honor_roll">
		<td class="label">{$form.is_honor_roll.label}</td>
		<td>{$form.is_honor_roll.html}
		<span class="description">{ts}If this option is checked, an "honor roll" will be displayed with the names (or nicknames) of the people who donated through your fundraising page. (Donors will have the option to remain anonymous. Their names will NOT be listed.){/ts}</span></td>
	</tr>
</table>
</div>
<div class="crm-submit-buttons crm-submit-buttons-bottom crm-pcp-submit-buttons-bottom">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>

<script>{literal}
cj(document).ready(function($){
  $('input[name=_qf_Campaign_upload]').click(function(e){
    var confirmed = confirm("{/literal}{ts}The content cannot be modified once it is submit.{/ts} {ts}Are you sure you want to continue?{/ts}{literal}");
    if (!confirmed) {
      e.preventDefault();
      return false;
    }
  });
});
{/literal}</script>

{if $smarty.get.preview && $smarty.get.preview == 1 && isset($pcpPagePreviewUrl)}
<link rel="stylesheet" href="{$config->resourceBase}packages/Magnific-Popup/dist/magnific-popup.css?v{$config->ver}">
{js src=packages/Magnific-Popup/dist/jquery.magnific-popup.min.js group=999 weight=997 library=civicrm/civicrm-js-mailingeditor}{/js}

<div id="pcp-preview-popup" class="pcp-preview-popup crm-preview-popup">
  <div class="inner">
    <div class="crm-preview-toolbar">
      <div class="crm-preview-title">{ts}Preview{/ts}</div>
      <div class="crm-preview-mode">
        <button type="button" class="crm-preview-mode-btn" data-mode="desktop">{ts}Normal{/ts}</button>
        <button type="button" class="crm-preview-mode-btn is-active" data-mode="mobile">{ts}Mobile Device{/ts}</button>
      </div>
      <button type="button" class="crm-preview-close"><i class="zmdi zmdi-close"></i></button>
    </div>
    <div class="crm-preview-content">
      <div class="crm-preview-panels">
        <div class="crm-preview-panel crm-preview-desktop-panel" data-mode="desktop">
          <div class="desktop-preview-container preview-container">
            <div class="preview-content"><iframe id="crm-preview-iframe-desktop" class="crm-preview-iframe" src="{$pcpPagePreviewUrl}"></iframe>
            </div>
          </div>
        </div>
        <div class="crm-preview-panel crm-preview-mobile-panel is-active" data-mode="mobile">
          <div class="mobile-preview-container preview-container">
            <div class="preview-content"><iframe id="crm-preview-iframe-mobile" class="crm-preview-iframe" src="{$pcpPagePreviewUrl}"></iframe>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>{literal}
(function ($) {
  $(function () {
    let resizeTimer = null,
        deviceWidthMode = 'mobile';

    const updateDeviceWidthMode = function() {
      let windowWidth = $(window).width();

      if (windowWidth >= 1200) {
        deviceWidthMode = 'desktop';
      }
      else {
        deviceWidthMode = 'mobile';
      }

      $('html').attr('data-device-width-mode', deviceWidthMode);
      $(`.crm-preview-mode-btn[data-mode="${deviceWidthMode}"]`).click();
    }

    const windowResize = function() {
      updateDeviceWidthMode();
    }

    const previewPopupInit = function () {
      $.magnificPopup.open({
        items: {
          src: "#pcp-preview-popup"
        },
        type: "inline",
        mainClass: "mfp-preview-popup",
        preloader: true,
        showCloseBtn: false,
        callbacks: {
          open: function () {
            $("body").addClass("mfp-is-active");
            $(".crm-preview-mode-btn").on("click", function () {
              let mode = $(this).data("mode");
              $(".crm-preview-mode-btn").removeClass("is-active");
              $(this).addClass("is-active");
              $(".crm-preview-panel").removeClass("is-active");
              $(".crm-preview-panel[data-mode='" + mode + "']").addClass("is-active");
            });
          },
          close: function () {
            $("body").removeClass("mfp-is-active");
          },
        }
      });

      $(".crm-preview-popup").on("click", ".crm-preview-close", function () {
        $.magnificPopup.close();
      });
    }

    if ($.fn.magnificPopup) {
      previewPopupInit();
      updateDeviceWidthMode();
    }

    $(window).resize(function() {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(windowResize, 250);
    });
  });
})(cj);
{/literal}</script>
{/if}
