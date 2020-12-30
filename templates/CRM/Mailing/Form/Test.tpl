{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.1                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2011                                |
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
<div class="crm-block crm-form-block crm-mailing-test-form-block">
{include file="CRM/common/WizardHeader.tpl"}
<div id="help">
    {ts}It's a good idea to test your mailing by sending it to yourself and/or a selected group of people in your organization. You can also view your content by clicking (+) Preview Mailing.{/ts} {help id="test-intro"}
</div>

{include file="CRM/Mailing/Form/Count.tpl"}

<fieldset>
  <legend>{ts}Test Mailing:{/ts}</legend>
  <table class="form-layout">
    <tr class="crm-mailing-test-form-block-test_email">
    <td class="label">{$form.test_email.label}</td>
    <td>{$form.test_email.html} <div class="description">{ts}Enter e-mail address of recipient. (Use a comma to separate multiple e-mail addresses.){/ts}</div></td>
    </tr>
    <tr class="crm-mailing-test-form-block-test_group"><td class="label">{$form.test_group.label}</td><td>{$form.test_group.html}</td></tr>
    <tr><td></td><td>{$form.sendtest.html}</td>  
  </table>
</fieldset>

<div class="crm-accordion-wrapper crm-plain_text_email-accordion crm-accordion-open">
    <div class="crm-accordion-header">
        <div class="zmdi crm-accordion-pointer"></div> 
        {ts}Preview Mailing{/ts}
    </div><!-- /.crm-accordion-header -->
    <div class="crm-accordion-body">
        <ul class="crm-test-mail-preview">
          <li><button type="button" data-type="normal">{ts}Normal{/ts}</button></li>
          <li><button class="is-active" type="button" data-type="mobile">{ts}Mobile Device{/ts}</button></li>
        </ul>
        <table class="form-layout">
          <tr class="crm-mailing-test-form-block-subject"><td class="label">{ts}Subject{/ts}:</td><td>{$subject}</td></tr>
          <!-- TODO: Change to English and make it translatable. -->
          <tr class="crm-mailing-test-form-block-subject-preview">
            <td class="label">{ts}Preview{/ts}:</td>
            <td>
              <div class="mobile-subject-preview subject-preview is-active" data-type="mobile" data-mode="tabs">
                <div class="subject-preview-content">
                  <div class="col-avatar"><i class="zmdi zmdi-account-circle"></i></div>
                  <div class="col-info">
                    <div class="col-info-row-1">
                      <span class="mail-sender"></span>
                      <div class="mail-time"></div>
                    </div>
                    <div class="col-info-row-2">
                      <div class="mail-subject"></div>
                    </div>
                    <div class="col-info-row-3">
                      <span class="mail-teaser"></span>
                      <i class="zmdi zmdi-star-outline"></i>
                    </div>
                  </div>
                </div>
              </div>
              <div class="normal-subject-preview subject-preview" data-type="normal" data-mode="tabs">
                <div class="subject-preview-content">
                  <div class="col-select col"><i class="zmdi zmdi-square-o"></i></div>
                  <div class="col-star col"><i class="zmdi zmdi-star-outline"></i></div>
                  <div class="col-sender col"><div class="mail-sender"></div></div>
                  <div class="col-mail-text col">
                    <div class="mail-subject"></div>
                    <span class="mail-teaser"></span>
                  </div>
                  <div class="col-time col"><div class="mail-time"></div></div>
                </div>
              </div>
            </td>
          </tr>
    {if $preview.attachment}
          <tr class="crm-mailing-test-form-block-attachment"><td class="label">{ts}Attachment(s):{/ts}</td><td>{$preview.attachment}</td></tr>
    {/if}
          {if $preview.text_link}
          <tr><td class="label">{ts}Text Version:{/ts}</td><td><div class="crm-test-mail-preview-frame-wrapper" data-mode="mobile"><iframe class="crm-test-mail-preview-frame" height="300" src="{$preview.text_link}" width="80%"><a href="{$preview.text_link}" onclick="window.open(this.href); return false;">{ts}Text Version{/ts}</a></iframe></div></td></tr>
          {/if}
          {if $preview.html_link}
          <tr><td class="label">{ts}HTML Version:{/ts}</td><td><div class="crm-test-mail-preview-frame-wrapper" data-mode="mobile"><iframe class="crm-test-mail-preview-frame" height="300" src="{$preview.html_link}" width="80%"><a href="{$preview.html_link}" onclick="window.open(this.href); return false;">{ts}HTML Version{/ts}</a></iframe></div></td></tr>
          {/if}
        </table>
    </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->    

<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl"}</div>
    
</div><!-- / .crm-form-block -->

{* include jscript to warn if unsaved form field changes *}
{include file="CRM/common/formNavigate.tpl"}
{literal}
<script type="text/javascript">
cj(function() {
   cj().crmaccordions();

  if (cj(".subject-preview").length) {
    var currentDate = new Date(),
        mailSenderText = '{/literal}{$mailFrom}{literal}',
        mailSenderArr = mailSenderText.split("\" <"),
        mailPreview = {};

    mailPreview.sender = {};
    mailPreview.sender.name = mailSenderArr[0].substring(1);
    mailPreview.sender.email = mailSenderArr[1].slice(0, -1);
    mailPreview.subject = '{/literal}{$subject}{literal}';
    // TODO: Change to English and make it translatable.
    mailPreview.teaser = "這是假文，供排版或預覽示意時填充版面用，中文與English都在這個假文之中，有時會有一些數字例如123、456以及7890。這是假文，供排版或預覽示意時填充版面用，中文與English都在這個假文之中，有時會有一些數字例如123、456以及7890。這是假文，供排版或預覽示意時填充版面用，中文與English都在這個假文之中，有時會有一些數字例如123、456以及7890。";
    mailPreview.time = ("0" + currentDate.getHours()).slice(-2) + ":" + ("0" + currentDate.getMinutes()).slice(-2);

    cj(".subject-preview .mail-subject").text(mailPreview.subject);
    cj(".subject-preview .mail-sender").text(mailPreview.sender.name);
    cj(".subject-preview .mail-teaser").text(mailPreview.teaser);
    cj(".subject-preview .mail-time").text(mailPreview.time);
  }

  cj(".crm-test-mail-preview").on("click", "button", function(e) {
    var $btn = cj(this),
        type = $btn.data("type"),
        $container = $btn.closest(".crm-test-mail-preview"),
        $btns = $container.find("button"),
        $previewFrameWrapper = cj(".crm-test-mail-preview-frame-wrapper"),
        $subjectPreview = cj(".subject-preview"),
        activeClass = "is-active";

    $btns.removeClass(activeClass);

    if (!$btn.hasClass(activeClass)) {
      $btn.addClass(activeClass);
    }

    if ($subjectPreview.length) {
      $subjectPreview.removeClass(activeClass);
      cj(".subject-preview[data-type='" + type + "']").addClass(activeClass);
    }

    if ($previewFrameWrapper.length) {
      $previewFrameWrapper.attr("data-mode", type);
    }
  });
});
</script>
{/literal}

