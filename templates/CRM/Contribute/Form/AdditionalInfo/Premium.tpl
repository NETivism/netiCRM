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
{* this template is used for adding/editing Premium Information *} 
 <div id="id-premium" class="section-shown crm-contribution-additionalinfo-premium-form-block">
     {if $is_combination_premium}
     {* Display read-only combination information *}
     <div class="crm-section">
         <div class="messages info">
             <div class="icon inform-icon"></div>
             {ts}This premium combination will have its inventory automatically calculated based on the "contribution status" and cannot be edited here.{/ts}
         </div>
     </div>
     <table class="form-layout-compressed">
        <tr class="crm-contribution-form-block-combination_name">
           <td class="label">{ts}Combination Name{/ts}</td>
	   <td class="html-adjust"><strong>{$combination_name}</strong></td>
        </tr>
        <tr class="crm-contribution-form-block-combination_content">
           <td class="label">{ts}Combination Contents{/ts}</td>
	   <td class="html-adjust">{$combination_content}</td>
        </tr>
     </table>
     {else}
     {* Display editable premium selection *}
     <table class="form-layout-compressed">
        <tr class="crm-contribution-form-block-product_name">
           <td class="label">{$form.product_name.label}</td>
	   <td class="html-adjust">{$form.product_name.html}</td>
        </tr>
     </table>

    <div id="premium_contri">
        <table class="form-layout-compressed"> 
	  <tr class="crm-contribution-form-block-min_amount">
             <td class="label">{$form.min_amount.label}</td>
	     <td class="html-adjust">{$form.min_amount.html|crmReplace:class:texttolabel|crmMoney:$currency}</td>
          </tr>
        </table>
        <div class="spacer"></div>
    </div>
     {/if}
    <table class="form-layout-compressed"> 
	  <tr class="crm-contribution-form-block-fulfilled_date">
             <td class="label">{$form.fulfilled_date.label}</td>
	     <td class="html-adjust">{include file="CRM/common/jcalendar.tpl" elementName=fulfilled_date}</td>
          </tr>
        </table>
</div>

<div id="premium-dialog-confirm" title="{ts}Confirmation{/ts}" style="display: none;">
  <p><strong id="premium-name-display"></strong></p>
  <p>{ts}After saving, the premium inventory will be automatically calculated based on the "contribution status" and the premium linked to this contribution cannot be changed.{/ts}</p>
  <p>{ts}Are you sure you want to continue?{/ts}</p>
</div>

{if !$is_combination_premium}
      {literal}
        <script type="text/javascript">
            var min_amount = document.getElementById("min_amount");
            min_amount.readOnly = 1;

            cj(document).ready(function($){
              var targetForm;
              $("#premium-dialog-confirm").dialog({
                autoOpen: false,
                resizable: false,
                dialogClass: 'noTitleStuff',
                width: 600,
                height: 300,
                modal: true,
                open: function() {
                  $(this).siblings('.ui-dialog-titlebar').remove();
                },
                buttons: {
                  "{/literal}{ts}OK{/ts}{literal}": function() {
                    $(this).dialog("close");
                    if (targetForm && targetForm.length > 0) {
                      targetForm.off("submit.premium-check");
                      targetForm[0].submit();
                    }
                  },
                  "{/literal}{ts}Cancel{/ts}{literal}": function() {
                    $(this).dialog("close");
                    return false;
                  }
                }
              });

              $("form").on("submit.premium-check", function(e){
                var product = $("select[name='product_name[0]']");
                if (product.length > 0 && product.val() > 0) {
                  var selectedText = product.find("option:selected").text();
                  var bracketPattern = /\[.*\]/;
                  if (bracketPattern.test(selectedText)) {
                    targetForm = $(this);
                    var premiumText = "{/literal}{ts}Are you sure you want to link this contribution to{/ts}{literal} \"" + selectedText + "\"?";
                    $("#premium-name-display").text(premiumText);
                    $("#premium-dialog-confirm").dialog("open");
                    return false;
                  }
                }
                return true;
              });
            });

    	    function showMinContrib( ) {
               var product = document.getElementsByName("product_name[0]")[0];
               var product_id = product.options[product.selectedIndex].value;
               var min_amount = document.getElementById("min_amount");
 	 
	       
               var amount = new Array();
               amount[0] = '';  
	
               if( product_id > 0 ) {  
		  show('premium_contri');	      	
               } else {
	          hide('premium_contri');	      
             }
	
      {/literal}
		
      var index;
      {foreach from= $mincontribution item=amt key=id}
            {literal}index = {/literal}{$id};
            {literal}amount[index]{/literal} = "{$amt}";
      {/foreach}
      {literal}
          if(amount[product_id]) {  
              min_amount.value = amount[product_id];
          } else {
              min_amount.value = "";
          }           
     }  
     </script> 
     {/literal}
{if $action eq 1 or $action eq 2 or $action eq null }
    <script type="text/javascript">
       showMinContrib( );
    </script>            
{/if}
{if $action ne 2 or $showOption eq true}
    {$initHideBoxes}
{/if}
{/if}
