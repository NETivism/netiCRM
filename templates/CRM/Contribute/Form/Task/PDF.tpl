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
  <label>{ts}Contribution need to match conditions below in order to generate receipt(and receipt serial id number){/ts}</label>
  <ul>
    <li>{ts 1="$contribution_type_setting"}Contribution record must dedutible.(base on <a href="%1">Contribution type</a> settings){/ts}</li>
    <li>{ts}Contribution record must completed.{/ts}</li>
    <li>{ts}Contribution record must have receive date.{/ts}</li>
  </ul>

  <div>{ts}Please notice that, because the serial number must continuous, once you generate receipt, it will also generte receipt ID and you can't modify receipt ID after generation. Make sure your search result have correct receive date search to prevent generate wrong number.{/ts}</div>
</div>
<div class="form-item">{$form.single_page_letter.html}<label>{$form.single_page_letter.label}</label><div class="description">{ts}By default we generate one receipt in every A4 page. After you check this option, receipt will include default address of donor in every page instead. We will generate their address in the top of page. This is useful when you want to send by post directly without envelop.{/ts}</div></div>

  <div id="dialog-confirm" title="{ts}Procceed Receipt Generation?{/ts}" style="display:none;">
    <p><span class="zmdi zmdi-alert-circle" style="margin: 0 7px 20px 0;"></span>{ts}In order to prevent non-continues receipt id. After generate, you can't insert any receipt number between these contribution.{/ts}<br />{ts}Are you sure you want to continue?{/ts}</p>
  </div>

<div class="spacer"></div>
<div class="form-item">
 {$form.buttons.html}
</div>
{literal}
<script type="text/javascript" >
cj(document).ready(function(){
  var single_page = function(obj){
    if(cj(obj).attr("checked") == 'checked'){
      cj("input[name=output][value=copy_receipt]").click();
      cj(".receipt-type:not(.copy_receipt)").hide();
    }
    else{
      cj(".receipt-type").show();
    }
  }
  cj("#single_page_letter").click(function(){
    single_page(this);
  });
  single_page(cj("#single_page_letter"));

  cj( "#dialog-confirm" ).dialog({
    autoOpen: false,
    resizable: false,
    width:450,
    height:250,
    modal: true,
    buttons: {
      "Go!": function() {
        cj( this ).dialog( "close" );
        document.PDF.submit();
      },
      Cancel: function() {
        cj( this ).dialog( "close" );
        return false;
      }
    }
  });
  cj("#PDF").submit(function(){
    var result = cj('#dialog-confirm').dialog('open');
    return false;
  });
});
</script>
{/literal}
