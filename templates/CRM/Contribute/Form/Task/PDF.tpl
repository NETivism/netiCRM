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
{* Confirmation of contribution deletes  *}
<div class="messages status">
  {include file="CRM/Contribute/Form/Task.tpl"}
</div>
<div id="help">
  <div>{ts}Please notice that, because the serial number must continuous, once you generate receipt, it will also generte receipt ID and you can't modify receipt ID after generation. Make sure your search result have correct receive date search to prevent generate wrong number.{/ts}</div>
</div>
{if $config->debug}
  <div class="form-item">
    {$form.nopdf.html}
  </div>
{/if}

<div class="form-item">
  <label>{$form.window_envelope.label}</label><br/>
  {$form.window_envelope.html}
  <div class="description">{ts}By default we generate one receipt in every A4 page. After you check this option, receipt will include default address of donor in every page instead. We will generate their address in the top of page. This is useful when you want to send by post directly without envelop.{/ts}</div>
</div>

<div class="form-item">
  {$form.email_pdf_receipt.html}
  {if !empty($form.email_pdf_receipt.html)}
  <span class="description">{ts}Add receipt as attachment in email.{/ts}</span>
  {/if}
</div>
<table class="form-layout-compressed pdf-receipt-table" style="display:none;">
<tr class="form-item">
  <td class="label"><label>{$form.from_email.label}</label></td>
  <td>
    {$form.from_email.html}
    <span class="description font-red">
      {ts}Only verified domain of email can be set as sender.{/ts} {ts}Otherwise, the email will be hidden on above select list.{/ts}<br>
      {capture assign="from_email_admin_path"}{crmURL p="civicrm/admin/from_email" q="reset=1"}{/capture}
      {ts 1=$from_email_admin_path}Make sure at least one of your email domain verified in <a href="%1">FROM email address</a> list.{/ts}
    </span>
  </td>
</tr>
<tr class="form-item">
  <td class="label"><label>{$form.receipt_text.label}</label></td>
  <td>{$form.receipt_text.html}</td>
</tr>
{if $suppressed_no_email}
<tr class="form-item">
  <td colspan="2">
    <span class="font-red">{ts count=$suppressed_no_email plural='Email will NOT be sent to %count contacts - (no email address on file, or communication preferences specify DO NOT NOTIFY, or contact is deceased).'}Email will NOT be sent to %count contact - (no email address on file, or communication preferences specify DO NOT NOTIFY, or contact is deceased).{/ts}</span>
    {if $empty_email_list}
      <ul>
      {foreach from=$empty_email_list item=sort_name}
        <li><span class="font-red">{$sort_name}</span></li>
      {/foreach}
      </ul>
    {/if}
  </td>
</tr>
{/if}
</table>

<div id="dialog-confirm-download" title="{ts}Procceed Receipt Generation?{/ts}" style="display:none;">
  <p><span class="zmdi zmdi-alert-circle" style="margin: 0 7px 0 0;"></span>{ts}In order to prevent non-continues receipt id. After generate, you can't insert any receipt number between these contribution.{/ts}</p>
  <p>{ts}Are you sure you want to continue?{/ts}</p>
</div>

<div id="dialog-confirm-email" title="{ts}Procceed Receipt Sending?{/ts}" style="display:none;">
  <p><span class="zmdi zmdi-alert-circle" style="margin: 0 7px 0 0;"></span>{ts}In order to prevent non-continues receipt id. After generate, you can't insert any receipt number between these contribution.{/ts}</p>
  <p>{ts}Are you sure you want to continue?{/ts}</p>
</div>

<div class="form-item">
 {$form.buttons.html}
</div>
{literal}
<script type="text/javascript" >
cj(document).ready(function($){
  var isBatch = "{/literal}{$isBatch}{literal}";
  var emailPDFReceipt = function(obj){
    if($(obj).is(':checked')) {
      $("table.pdf-receipt-table").show();
      $("input[name=_qf_PDF_upload]").prop('disabled', false).show();
      $("input[name=_qf_PDF_next]").prop('disabled', true).hide();
    }
    else {
      $("table.pdf-receipt-table").hide();
      $("input[name=_qf_PDF_upload]").prop('disabled', true).hide();
      $("input[name=_qf_PDF_next]").prop('disabled', false).show();
    }
  }
  $("input[name=_qf_PDF_upload]").prop('disabled', true);
  $("input[name=_qf_PDF_upload]").hide();
  $("input[name^=_qf_PDF_]").on("click", function(){
    $(this).closest("form").data("action", $(this).prop('name'));
  });
  
  $("input[name^=email_pdf_receipt]").click(function(){
    emailPDFReceipt(this);
  });
  emailPDFReceipt($("input[name^=email_pdf_receipt]"));

  var confirmDownload = false;
  var confirmEmail = false;
  $("#dialog-confirm-download").dialog({
    autoOpen: false,
    resizable: false,
    width:450,
    height:250,
    modal: true,
    buttons: {
      "{/literal}{ts}OK{/ts}{literal}": function() {
        confirmDownload = true;
        $(this).dialog( "close" );
        $("input[name=_qf_PDF_next]").trigger('click');
        return true;
      },
      "{/literal}{ts}Cancel{/ts}{literal}": function() {
        $( this ).dialog( "close" );
        return false;
      }
    }
  });
  $("#dialog-confirm-email").dialog({
    autoOpen: false,
    resizable: false,
    width:450,
    height:250,
    modal: true,
    buttons: {
      "{/literal}{ts}OK{/ts}{literal}": function() {
        confirmEmail = true;
        $(this).dialog( "close" );
        $("input[name=_qf_PDF_upload]").trigger('click');
        return true;
      },
      Cancel: function() {
        $(this).dialog( "close" );
        return false;
      }
    }
  });

  $("#PDF").submit(function(e){
    if ($(this).data('action')) {
      var button = $(this).data('action');
    }
    else {
      var button = $(document.activeElement).attr('name');
    }
    if (button == '_qf_PDF_next' && !confirmDownload) {
      $('#dialog-confirm-download').dialog('open');
    }
    else if (button == '_qf_PDF_upload' && !confirmEmail) {
      $('#dialog-confirm-email').dialog('open');
    }
    else if (button == '_qf_PDF_back') {
      return true;
    }
    if (confirmEmail || confirmDownload) {
      if (confirmDownload) {
        if (!isBatch) {
          $(this).attr("target", "_blank");
        }
      }
      else {
        $(this).removeAttr("target");
      }
      confirmEmail = confirmDownload = false;
      return true;
    }
    return false;
  });
});
</script>
{/literal}
