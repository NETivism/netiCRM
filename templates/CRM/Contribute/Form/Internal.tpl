{if $ajax}{*ajax*}
        <td class="label">{$form.original_id.label}</td>
        <td>
          {$form.original_id.html}
          <div class="description">{ts}When select, new contribution will have default value from this record.{/ts}</div>
        </td>
{else}{*normal*}
  <div class="crm-block crm-form-block crm-contribution-internal-form-block">
    <table class="form-layout-compressed" clas>
      <tr>
        <td>{$form.is_test.label}</td><td>{$form.is_test.html}</td>
      </tr>
      {if $contact_id}
      <tr>
        <td>{$form.contact_id.label}</td><td>{$form.contact_id.html} {if $display_name}({$display_name}){/if}</td>
      </tr>
      {else}
        {include file="CRM/Contact/Form/NewContact.tpl" readonly=true}
      {/if}
      <tr>
        <td class="label">{$form.contribution_page_id.label}</td>
        <td>{$form.contribution_page_id.html}</td>
      </tr>
      <tr id="original_id_row">
      {if $contact_id}
        <td class="label">{$form.original_id.label}</td>
        <td>
          {$form.original_id.html}
          <div class="description">{ts}When select, new contribution will have default value from this record.{/ts}</div>
        </td>
      {/if}
      </tr>
    </table>
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
  </div>
  <script>
  {literal}
  cj(document).ready(function($){
    if ($("input[name=contact_select_id\\\[1\\\]]").length > 0 ) {
      var $obj = $("input[name=contact_select_id\\\[1\\\]]");
      var $contactObj  = $("input[name=contact\\\[1\\\]]");
      var oldValue = $obj.val();
      $contactObj.on('blur', function(){
        if (oldValue != $obj.val()) {
          oldValue = $obj.val();
          if ($obj.val()) {
            var dataUrl = {/literal}"{crmURL p='civicrm/contribute/internal' h=0 q="snippet=4&cid=" }"{literal};
            $('#original_id_row').html('<td></td><td><i class="zmdi zmdi-spinner zmdi-hc-spin" id="loading"></i></td>');
            $.ajax({
              url: dataUrl + $obj.val(),
              dataType: "html",
              timeout : 5000, //Time in milliseconds
              success : function(data){
                setTimeout(function(){
                  $('#original_id_row').html(data);
                }, 800);
              },
            });
          }
        }
      });
    }
  });
  {/literal}
  </script>
{/if}