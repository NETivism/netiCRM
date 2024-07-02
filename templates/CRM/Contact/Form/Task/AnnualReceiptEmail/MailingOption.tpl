
<div class="crm-block crm-contact-form-task-annual_receipt_email mailing_option">
  {include file="CRM/common/WizardHeader.tpl"}
  <div class="messages">
    {ts}PDF receipt will be an email attachment.{/ts}
    <ul>
      <li>{ts 1=$total_selected}Total Selected Contact(s): %1{/ts}</li>
      <li>
        {ts}Search Settings{/ts}:
        <ul>
          {if $search_option.year}<li>{ts}Receipt Year{/ts} = {$search_option.year}</li>{/if}
          {if $search_option.contribution_types}<li>{ts}Contribution Types{/ts} {ts}IN{/ts} {', '|implode:$search_option.contribution_types}</li>{/if}
          <li>{ts}Find Recurring Contributions?{/ts} = {$search_option.recur}</li>
        </ul>
      </li>
      {if $suppressed_no_email}
      <li>{ts count=$suppressed_no_email plural='Email will NOT be sent to %count contacts - (no email address on file, or communication preferences specify DO NOT EMAIL, or contact is deceased).'}Email will NOT be sent to %count contact - (no email address on file, or communication preferences specify DO NOT EMAIL, or contact is deceased).{/ts}</li>
      {/if}
      {if $suppressed_no_record}
      <li>{ts count=$suppressed_no_record plural='Email will NOT be sent to %count contacts.'}Email will NOT be sent to %count contact.{/ts}{ts}There were no contributions during the selected year.{/ts}</li>
      {/if}
      <li>{ts}Total Recipients:{/ts} {$total_recipient}</li>
    </ul>
  </div>
  <table class="form-layout-compressed">
    <tr>
      <td class="label">{$form.receipt_from_email.label}</td>
      <td>{$form.receipt_from_email.html}</td>
    </tr>
    <tr>
      <td class="label">{$form.receipt_text.label}</td>
      <td>{$form.receipt_text.html}</td>
    </tr>
    <tr>
      <td class="label">{$form.bcc.label}</td>
      <td>
        {$form.bcc.html}
        <div class="description">
          <i class="zmdi zmdi-alert-circle-o"></i> <span class="font-red">{ts}NOTE: Due to the confidential nature of receipts containing personal information, please use a mailbox with a high level of security for the confidential copy.{/ts}</span><br>
          <i class="zmdi zmdi-alert-circle-o"></i> <span class="font-red">{ts}When sending a large number of copies, the mailbox will receive a large number of duplicate emails with PDF receipts attached, so please make sure the mailbox has enough space.{/ts}</span><br>
          {ts}You may specify one or more email addresses to receive a blind carbon copy (bcc) of the confirmation email. Multiple email addresses should be separated by a comma (e.g. jane@example.org, paula@example.org).{/ts}
        </div>
      </td>
    </tr>
  </table>
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl"}
  </div>
  <div id="dialog-confirm-email" title="{ts}Procceed Receipt Sending?{/ts}" style="display:none;">
    <p><span class="zmdi zmdi-alert-circle" style="margin: 0 7px 0 0;"></span> {ts}Because of the large amount of data you are about to perform, we will schedule this job for the batch process after you submit. You will receive an email notification when the work is completed.{/ts}</p>
    <p>{ts}Are you sure you want to continue?{/ts}</p>
  </div>
  {literal}
  <script>
  cj(document).ready(function($){
    var confirmEmail = false;
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
          $("input[name=_qf_MailingOption_next]").trigger('click');
          return true;
        },
        Cancel: function() {
          $(this).dialog( "close" );
          return false;
        }
      }
    });

    $("#MailingOption").submit(function(e){
      var button;
      if ($(this).data('action')) {
        button = $(this).data('action');
      }
      else {
        button = $(e.originalEvent.submitter).attr('name');
      }

      if (button == '_qf_MailingOption_next' && !confirmEmail) {
        $('#dialog-confirm-email').dialog('open');
      }
      else if (button == '_qf_MailingOption_back') {
        return true;
      }
      if (confirmEmail) {
        return true;
      }
      return false;
    });

  });
  </script>
  {/literal}
</div>