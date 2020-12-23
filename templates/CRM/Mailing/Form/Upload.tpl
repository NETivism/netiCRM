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
<div class="crm-block crm-form-block crm-mailing-upload-form-block">
{include file="CRM/common/WizardHeader.tpl"}

<div id="help">
    {ts}You can either <strong>upload</strong> the mailing content from your computer OR <strong>compose</strong> the content on this screen.{/ts} {help id="content-intro"} 
</div>

{include file="CRM/Mailing/Form/Count.tpl"}

<table class="form-layout-compressed">
    <tr class="crm-mailing-upload-form-block-from_email_address"><td class="label">{$form.from_email_address.label}</td>
        <td>{$form.from_email_address.html} {help id ="id-from_email"}</td>
    </tr>
    {if $trackReplies}
    <tr class="crm-mailing-upload-form-block-reply_to_address">
        <td style="color:#3E3E3E;"class="label">{ts}Reply-to email{/ts}<span class="crm-marker">*</span></td>
        <td>{ts}Auto-Generated{/ts}</td>
    </tr>
    {else}
    <tr class="crm-mailing-upload-form-block-reply_to_address">
        <td class="label">{$form.reply_to_address.label}</td>
        <td>{$form.reply_to_address.html}</td>
    </tr>
    {/if}
    <tr class="crm-mailing-upload-form-block-template">
    	<td class="label">{$form.template.label}</td>
      <td colspan="2">{$form.template.html} <a id="online-template-link" class="online-template-link" href="https://neticrm.tw/enews/embed?embed=1" title="{ts}Online tempalte{/ts}">{ts}Online tempalte{/ts}</a></td>
    </tr>
    <tr class="crm-mailing-upload-form-block-subject"><td class="label">{$form.subject.label}</td>
        <td colspan="2">{$form.subject.html|crmReplace:class:huge}
                        <div id='tokenSubject' style="display:none">
                           <input style="border:1px solid #999999;" type="text" id="filter3" size="20" name="filter3" onkeyup="filter(this, 3)"/><br />
                           <span class="description">{ts}Begin typing to filter list of tokens{/ts}</span><br/>
                           {$form.token3.html}
                        </div>
                        <div id="subject-editor">{$form.subject.value}</div>
        </td>
    </tr>
    <tr class="crm-mailing-upload-form-block-subject-normal-preview">
      <!-- TODO: Change to English and make it translatable. -->
      <td class="label"><label>電子報主旨預覽 - 電腦版</label></td>
      <td>
        <div class="subject-preview">
          <div class="subject-preview-content">
            <div class="subject-preview-item" data-type="normal">
              <div class="normal-subject-preview">
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
          </div>
        </div>
      </td>
    </tr>
    <tr class="crm-mailing-upload-form-block-subject-mobile-preview">
      <!-- TODO: Change to English and make it translatable. -->
      <td class="label"><label>電子報主旨預覽 - 手機版</label></td>
      <td>
        <div class="subject-preview">
          <div class="subject-preview-content">
            <div class="subject-preview-item" data-type="mobile">
              <div class="mobile-subject-preview">
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
          </div>
        </div>
      </td>
    </tr>
    <tr class="crm-mailing-upload-form-block-upload_type"><td></td><td colspan="2">{$form.upload_type.label} {$form.upload_type.html} {help id="upload-compose"}</td></tr>
</table>

<fieldset id="compose_id"><legend>{ts}Compose On-screen{/ts}</legend>
{include file="CRM/common/mailingEditor.tpl"}
{$form.body_json.html}
</fieldset>

<fieldset id="compose_old_id"><legend>{ts}Traditional Editor{/ts}</legend>
{include file="CRM/Contact/Form/Task/EmailCommon.tpl" upload=1 noAttach=1}
</fieldset>
<div id="saveTemplateBlock">
</div>

  {capture assign=docLink}{docURL page="Sample CiviMail Messages" text="More information and sample messages..."}{/capture}
  <fieldset id="upload_id"><legend>{ts}Upload Content{/ts}</legend>
    <table class="form-layout-compressed">
        <tr class="crm-mailing-upload-form-block-textFile">
            <td class="label">{$form.textFile.label}</td>
            <td>{$form.textFile.html}<br />
                <span class="description">{ts}Browse to the <strong>TEXT</strong> message file you have prepared for this mailing.{/ts}<br /> {$docLink}</span>
            </td>
        </tr>
        <tr class="crm-mailing-upload-form-block-htmlFile">
            <td class="label">{$form.htmlFile.label}</td>
            <td>{$form.htmlFile.html}<br />
                <span class="description">{ts}Browse to the <strong>HTML</strong> message file you have prepared for this mailing.{/ts}<br /> {$docLink}</span>
            </td>
        </tr>
    </table>
  </fieldset>

  {include file="CRM/Form/attachment.tpl"}

  <fieldset id="mailing_header_footer"><legend>{ts}Header / Footer{/ts}</legend>
    <table class="form-layout-compressed">
        <tr class="crm-mailing-upload-form-block-header_id">
            <td class="label">{$form.header_id.label}</td>
            <td>{$form.header_id.html}<br />
                <span class="description">{ts}You may choose to include a pre-configured Header block above your message.{/ts}</span>
            </td>
        </tr>
        <tr class="crm-mailing-upload-form-block-footer_id">
            <td class="label">{$form.footer_id.label}</td>
            <td>{$form.footer_id.html}<br />
                <span class="description">{ts}You may choose to include a pre-configured Footer block below your message. This is a good place to include the required unsubscribe, opt-out and postal address tokens.{/ts}</span>
            </td>
        </tr>
    </table> 
  </fieldset>

  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl"}</div>
</div><!-- / .crm-form-block -->

{* -- Javascript for showing/hiding the upload/compose options -- *}
{include file="CRM/common/showHide.tpl"}
{literal}
<script type="text/javascript">
    function showHideUpload() {
      // Upload
      if (cj(".form-radio[name='upload_type'][value='0']").is(":checked")) {
        hide('compose_id');
        hide('compose_old_id')
        cj('.crm-mailing-upload-form-block-template').hide();
        show('upload_id');
        cj("#saveTemplateBlock").hide();
      }
      else {
        hide('upload_id');
        cj("#editMessageDetails").appendTo("#saveTemplateBlock");
        cj("#saveDetails").appendTo("#saveTemplateBlock");
        cj("#saveTemplateBlock").show();
        // Compose On-screen
        if (cj(".form-radio[name='upload_type'][value='2']").is(":checked")) {
          hide('compose_old_id');
          show('compose_id');

          // refs #23719. Remove mailing header and footer when select 'Compose On-screen' mode and hide this fieldset
          cj("#header_id option[value='']").prop("selected", true);
          cj("#footer_id option[value='']").prop("selected", true);
          hide('mailing_header_footer');
          cj('.crm-mailing-upload-form-block-template').show();
        }

        // Traditional Editor (old compose mode)
        if (cj(".form-radio[name='upload_type'][value='1']").is(":checked")) {
          hide('compose_id');
          show('compose_old_id');
          show('mailing_header_footer');
          cj('.crm-mailing-upload-form-block-template').show();
          verify();
        }
      }
    }

    cj(document).ready(function(){
      showHideUpload();

      // show dialog for online tempalte
      cj("#online-template-link").click(function(e){
        e.preventDefault();
        var $this = cj(this);
        var horizontalPadding = 30;
        var verticalPadding = 30;
        var popupHeight = cj(window).height() * 0.85;
        var popupWidth = cj(window).width() * 0.95;
        cj('<iframe id="externalSite" class="externalSite" src="' + this.href + '" />').dialog({
            title: ($this.attr('title')) ? $this.attr('title') : 'Online template',
            autoOpen: true,
            width: popupWidth,
            height: popupHeight,
            modal: true,
            resizable: true,
            autoResize: true,
            overlay: {
              opacity: 0.5,
              background: "black"
            }
        }).width(popupWidth - horizontalPadding).height(popupHeight - verticalPadding);
      });
      function addAnEventListener(obj,evt,func){
        if ('addEventListener' in obj){
          obj.addEventListener(evt, func, false);
        }
        else if ('attachEvent' in obj){//IE
          obj.attachEvent('on'+evt, func);
        }
      }

      function iFrameListener(e){
        if (event.origin.match(/neticrm\.tw/)) {
          var data = e.data;
          cj('.externalSite').dialog('close')
          cj("#compose_id")[0].scrollIntoView();
          CKEDITOR.instances['html_message'].setData(data);
        }
      }


      addAnEventListener(window, 'message', iFrameListener);


      // refs #26473.
      var subjectQuill;
      var mailPreview = {};
      function subjectUpdateHelper(value, syncQuill) {
        if (value) {
          mailPreview.subject = value;
          cj(".subject-preview .mail-subject").text(mailPreview.subject);

          if (syncQuill) {
            if (subjectQuill && typeof Quill === "function") {
              subjectQuill.setText(value);
            }
          }
        }
      }

      function removeQuillLastBlankLine(quill) {
        if (typeof quill === "object" && typeof Quill === "function") {
          var delta = quill.getContents();

          if (Array.isArray(delta.ops) && delta.ops.length) {
            var lastIndex = delta.ops.length - 1,
            lastOp = delta.ops[lastIndex];

            if (lastOp.insert && typeof lastOp.insert === "string") {
              // refs https://github.com/quilljs/quill/issues/1235#issuecomment-273044116
              // Because quill will generate two line breaks at the end of the content, we need to remove one line break so that the edited content is consistent with the content when browsing.
              delta.ops[lastIndex].insert = lastOp.insert.replace(/\n$/, "");
              quill.setContents(delta);
            }
          }
        }
      }

      if (cj(".subject-preview").length) {
        mailPreview.sender = {};

        // TODO: Change to English and make it translatable.
        mailPreview.teaser = "這是假文，供排版或預覽示意時填充版面用，中文與English都在這個假文之中，有時會有一些數字例如123、456以及7890。這是假文，供排版或預覽示意時填充版面用，中文與English都在這個假文之中，有時會有一些數字例如123、456以及7890。這是假文，供排版或預覽示意時填充版面用，中文與English都在這個假文之中，有時會有一些數字例如123、456以及7890。";

        var getMailSender = function() {
          var mailSenderText = cj("#from_email_address option:selected").text(),
              mailSenderArr = mailSenderText.split("\" <");

          mailPreview.sender.name = mailSenderArr[0].substring(1);
          mailPreview.sender.email = mailSenderArr[1].slice(0, -1);
          cj(".subject-preview .mail-sender").text(mailPreview.sender.name);
        }

        var getMailTime = function() {
          var currentDate = new Date();
          mailPreview.time = currentDate.getHours() + ":" + currentDate.getMinutes();
          cj(".subject-preview .mail-time").text(mailPreview.time);
        }

        getMailTime();
        getMailSender();

        cj("#from_email_address").change(function() {
          getMailSender();
        });

        if (cj("#subject").length) {
          cj(".subject-preview .mail-subject").text(cj("#subject").val());
        }

        cj(".subject-preview .mail-teaser").text(mailPreview.teaser);
      }

      // refs #26473. Added quill editor (quill) to replace subject field of mailing upload form.
      if (cj("#subject").length && cj("#subject-editor").length) {
        if (typeof Quill === "function") {
          // Because both Quill and CKEditor have "contenteditable"
          // disableAutoInline of CKEditor must be enabled to avoid conflicts
          // https://ckeditor.com/docs/ckeditor4/latest/guide/dev_inline.html#enabling-inline-editing
          CKEDITOR.disableAutoInline = true;

          // Replace <p> with <div>
          var quillBlock = Quill.import("blots/block");
          class DivBlock extends quillBlock {}
          DivBlock.tagName = "DIV";
          Quill.register("blots/block", DivBlock, true);

          var toolbarOptions = [
            ['emoji']
          ];

          var tokenToolbar = [];
          var tokenQuillOption = [];
          if (window.nmEditor.tokenTrigger) {
            Quill.register("modules/placeholder", PlaceholderModule.default(Quill));
            cj(window.nmEditor.tokenTrigger).find("option").each(function() {
              var tokenName = cj(this).attr("value");
              tokenToolbar.push(tokenName);
              tokenQuillOption.push({id:tokenName, label:tokenName});
            });
            toolbarOptions.push([{"placeholder":tokenToolbar}]);
          }

          var quillOptions = {
            modules: {
              toolbar: toolbarOptions,
              "emoji-toolbar": true
            },
            theme: "snow"
          };

          if (window.nmEditor.tokenTrigger) {
            quillOptions.modules.placeholder = {};
            quillOptions.modules.placeholder.delimiters = ["", ""];
            quillOptions.modules.placeholder.placeholders = tokenQuillOption;
          }

          var subjectQuill = new Quill("#subject-editor", quillOptions);
          subjectQuill.on("text-change", function(delta, oldDelta, source) {
            // Get text by quill.root.innerText
            // Because innerText contains '\ufeff', it needs to be removed by jQuert.trim()
            var subject = cj.trim(subjectQuill.root.innerText);

            // Update value of subject field
            cj("#subject").val(subject);
            subjectUpdateHelper(subject);
          });

          // Remove the last blank line generated by quill
          removeQuillLastBlankLine(subjectQuill);
        }

        cj("#subject").on("change keyup input paste", function() {
          var subjectVal = cj(this).val(),
              syncQuill = true;

          // Sync the subject value to the subject editor (Quill).
          subjectUpdateHelper(subjectVal, syncQuill);
        });

        // Added a MutationObserver to watch for changes being made to the subject DOM tree
        var subjectObserver = new MutationObserver(function(list) {
          cj("#subject").trigger("change");
        });
        subjectObserver.observe(cj("#subject")[0], {
          attributes: true
        });
      }
    });
</script>
{/literal}

{* include jscript to warn if unsaved form field changes *}
{include file="CRM/common/formNavigate.tpl"}
