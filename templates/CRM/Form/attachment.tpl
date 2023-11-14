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
{assign var=emptyStringKey value=""}

{if $form.attachFile.$emptyStringKey || $currentAttachmentURL}
{if $action EQ 4 AND $currentAttachmentURL} {* For View action we exclude the form fields and just show any current attachments. *}
    <tr>
        <td class="label"><label>{ts}Current Attachment(s){/ts}</label></td>
        <td class="view-value"><strong>{$currentAttachmentURL}</strong></td>
    </tr>
{elseif $action NEQ 4}
    {if $context EQ 'pcpCampaign'}
        {capture assign=attachTitle}{ts}Include a Picture or an Image{/ts}{/capture}
        {assign var=openCloseStyle value='crm-accordion-open'}
    {else}
        {capture assign=attachTitle}{ts}Attachment(s){/ts}{/capture}
        {assign var=openCloseStyle value='crm-accordion-closed'}
    {/if}
    {if !$noexpand}
    <div class="crm-accordion-wrapper crm-accordion_title-accordion {$openCloseStyle}">
     <div class="crm-accordion-header">
        <div class="zmdi crm-accordion-pointer"></div> 
        {$attachTitle}
      </div><!-- /.crm-accordion-header -->
     <div class="crm-accordion-body">    
   {/if}
    <div id="attachments" class="attachments">
    <table class="form-layout-compressed">
    {if $context EQ 'pcpCampaign'}
        <tr>
              <td class="label">{ts}Select from gallery{/ts}</td>
              <td class="view-value select-from-gallery pcp-select-from-gallery">
                  <div class="description">
                  {if $currentAttachmentURL}
                    {ts}Please remove the current attachment before selecting images from the gallery.{/ts}
                  {else}
                    {ts}You can use our preset images from the gallery or upload your own in the attached file below.{/ts}
                  {/if}
                  </div>
                  <script>{literal}
                  (function ($) {
                    $(function() {
                      const pageAction = "{/literal}{$action}{literal}";
                      const presetImgStartIndex = 1;
                      const presetImgMaxNum = 5;

                      function disableSelectGallery() {
                        let desc = "{/literal}{ts}Please remove the current attachment before selecting images from the gallery.{/ts}{literal}";
                        $('input[name="preset_image"][type="hidden"]').val('');
                        $('.pcp-select-from-gallery .description').text(desc);
                        $(document).off('click', '.pcp-preset-img-list.is-active .item');
                        $('.pcp-preset-img-list.is-active').removeClass('is-active');
                        $('.pcp-preset-img-list .item.is-selected').removeClass('is-selected');
                      }

                      function getRandomImgId() {
                        return Math.floor(Math.random() * (presetImgMaxNum - presetImgStartIndex + 1)) + presetImgStartIndex;
                      }

                      function setPresetImg(imgId) {
                        imgId = imgId || getRandomImgId();

                        if (imgId < presetImgStartIndex || imgId > presetImgMaxNum) {
                          return;
                        }

                        $('.pcp-preset-img-list .item').removeClass('is-selected');
                        $(`.pcp-preset-img-list .item[data-img-id="${imgId}"]`).addClass('is-selected');
                        $('input[name="preset_image"][type="hidden"]').val(imgId);
                      }
                      
                      if (!$('.pcp-preset-img-list').length && $('.pcp-select-from-gallery').length && $('input[name="preset_image"][type="hidden"]').length) {
                        let pcpPresetImgList = '<ul class="pcp-preset-img-list is-active">';
                        for (let i = presetImgStartIndex; i <= presetImgMaxNum; i++) {
                          pcpPresetImgList += `<li class='item' data-img-id="${i}"><img src="{/literal}{$config->resourceBase}{literal}packages/midjourney/pcp_preset_${i}.png"></li>`;
                        }
                        pcpPresetImgList += '</ul>';
                        $('.pcp-select-from-gallery').prepend(pcpPresetImgList);

                        $(document).on('click', '.pcp-preset-img-list.is-active .item', function() {
                          let imgId = $(this).attr('data-img-id');
                          setPresetImg(imgId);
                        });
                      }

                      if ($('.current-attachments').length && $('.pcp-preset-img-list').length) {
                        disableSelectGallery();
                      }

                      let $formFile = $('.pcp-preset-img-list').closest('#attachments').find('.form-file[name*="attachFile[]"]');
                      if ($formFile.length) {
                        $formFile.change(function() {
                          if ($(this).prop('files').length > 0) {
                            disableSelectGallery();
                          }
                        });
                      }

                      if (pageAction != 2) {
                        setPresetImg();
                      }
                    });
                  })(cj);
                  {/literal}</script>
              </td>
        </tr>
    {/if}
    {if $form.attachFile.$emptyStringKey}
            <tr>
                <td class="label">{$form.attachFile.$emptyStringKey.label}</td>
                <td>{$form.attachFile.$emptyStringKey.html}<br />
                  {if $context EQ 'pcpCampaign'}
                      <div class="description">{ts}You can upload a picture or image to include on your page. Your file should be in .jpg, .gif, or .png format. Recommended image size is 250 x 250 pixels. Maximum size is 360 x 360 pixels.{/ts}</div>
                  {/if}
                </td>
            </tr>
    {/if}
    {if $currentAttachmentURL}
        <tr>
            <td class="label">{ts}Current Attachment(s){/ts}</td>
            <td class="view-value current-attachments"><strong>{$currentAttachmentURL}</strong></td>
        </tr>
        <tr>
            <td class="label">&nbsp;</td>
            <td>{$form.is_delete_attachment.html}&nbsp;{$form.is_delete_attachment.label}<br />
                <span class="description">{ts}Check this box and click Save to delete all current attachments.{/ts}</span>
            </td>
        </tr>
    {/if}
        </table>
    </div>
  </div><!-- /.crm-accordion-body -->
  </div><!-- /.crm-accordion-wrapper -->
  {literal}
  <script type="text/javascript">
    var maxFilesize = {/literal}{$maxFileSize}{literal};
    var numAttachments = {/literal}{$numAttachments}{literal};
    cj(function($) {
      $("#attachFile_").closest('form').submit(function(e){
        var files = $('#attachFile_').get(0).files;
        if (typeof files !== 'undefined' && files.length > 0) {
          var valid = 1;
          if (parseInt(files.length) > numAttachments){
            alert("{/literal}{ts 1=$numAttachments}You can have a maximum of %1 attachment(s).{/ts}{literal}");
            valid = 0;
          }
          var filesize = 0;
          for (i = 0; i < files.length; i++) {
            filesize += files[i].size;
          }
          filesize = filesize/1024/1024;
          if (filesize > maxFilesize) {
            alert("{/literal}{ts 1=$maxFileSize}File size should be less than %1 MByte(s){/ts}{literal}");
            valid = 0;
          }
          if (!valid) {
            e.preventDefault();
						$('html, body').animate({
              scrollTop: $("#attachFile_").offset().top - 100
						}, 1000);
          }
        }
      }); 
    });
  </script>
  {/literal}
  {if !$noexpand}
  {literal}
    <script type="text/javascript">
        var attachmentUrl = {/literal}'{$currentAttachmentURL}'{literal};
    cj(function() {
       cj().crmaccordions(); 
    });
    </script>
  {/literal}
  {/if}{* end noexpand *}
{/if}{* end action *}
{/if}{* end first if *}

