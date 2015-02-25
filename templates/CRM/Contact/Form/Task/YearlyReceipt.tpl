  <div id="dialog-confirm" title="{ts}Procceed Receipt Generation?{/ts}" style="display:none;">
    <p><span class="ui-icon ui-icon-alert" style="float: left; margin: 0 7px 20px 0;"></span>{ts}This will take a period of time.{/ts}<br />{ts}Are you sure you want to continue?{/ts}</p>
  </div>
<div class="form-item">
  <div>
  <label>{$form.year.label}</label>
  {$form.year.html}
  </div>
</div>
<div class="spacer"></div>
<div class="form-item">
 {$form.buttons.html}
</div>
{literal}
<script type="text/javascript" >
cj(document).ready(function(){
  cj( "#dialog-confirm" ).dialog({
    autoOpen: false,
    resizable: false,
    width:450,
    height:250,
    modal: true,
    buttons: {
      "Go!": function() {
        cj( this ).dialog( "close" );
        document.YearlyReceipt.submit();
      },
      Cancel: function() {
        cj( this ).dialog( "close" );
        return false;
      }
    }
  });
  cj("#YearlyReceipt").submit(function(){
    var result = cj('#dialog-confirm').dialog('open');
    return false;
  });
});
</script>
{/literal}
