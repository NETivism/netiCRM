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
{if $form.attachFile.0 || $currentAttachmentURL}
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
    <div id="attachments">
    <table class="form-layout-compressed">
    {if $form.attachFile.0}
            <tr>
                <td class="label">{$form.attachFile.0.label}</td>
                <td>{$form.attachFile.0.html}<br />
                  {if $context EQ 'pcpCampaign'}
                      <div class="description">{ts}You can upload a picture or image to include on your page. Your file should be in .jpg, .gif, or .png format.{/ts}</div>
                  {/if}
                  <div class="description">{ts}Browse to the <strong>file</strong> you want to upload.{/ts}{if $numAttachments GT 1} {ts 1=$numAttachments}You can have a maximum of %1 attachment(s).{/ts}{/if}</div>
                </td>
            </tr>
    {/if}
    {if $currentAttachmentURL}
        <tr>
            <td class="label">{ts}Current Attachment(s){/ts}</td>
            <td class="view-value"><strong>{$currentAttachmentURL}</strong></td>
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

