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
  
<div class="crm-block crm-form-block crm-import-datasource-form-block">
{if $showOnlyDataSourceFormPane}
  {include file=$dataSourceFormTemplateFile}
{else}
  {* Import Wizard - Step 1 (choose data source) *}
  {* @var $form Contains the array for the form elements and other form associated information assigned to the template by the controller *}

  {* WizardHeader.tpl provides visual display of steps thru the wizard as well as title for current step *}
  {include file="CRM/common/WizardHeader.tpl"}
   <div id="help">
      {ts}The Import Wizard allows you to easily import contact records from other applications into CiviCRM. For example, if your organization has contacts in MS Access&reg; or Excel&reg;, and you want to start using CiviCRM to store these contacts, you can 'import' them here.{/ts} {help id='choose-data-source-intro'}
  </div>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
  <div id="choose-data-source" class="form-item">
    <fieldset>
      <legend>{ts}Choose Data Source{/ts}</legend>
      <table class="form-layout">
        <tr class="crm-import-datasource-form-block-dataSource">
            <td class="label">{$form.dataSource.label}</td>
            <td>{$form.dataSource.html} {help id='data-source-selection'}</td>
        </tr>
      </table>
    </fieldset>
  </div>

  {* Data source form pane is injected here when the data source is selected. *}
  <div id="data-source-form-block">
    {if $dataSourceFormTemplateFile}
      {include file=$dataSourceFormTemplateFile}
    {/if}
  </div>

  <div id="common-form-controls" class="form-item">
    <fieldset>
      <legend>{ts}Import Options{/ts}</legend>
      <table class="form-layout-compressed">
         <tr class="crm-import-datasource-form-block-contactType">
	     <td class="label">{$form.contactType.label}</td>
             <td>
               {$form.contactType.html} {help id='contact-type'}&nbsp;&nbsp;&nbsp;
               <div id="contact-subtype">{$form.subType.label}&nbsp;&nbsp;&nbsp;{$form.subType.html} {help id='contact-sub-type'}</div>
             </td>
         </tr>
        <tr class="dedupe-rule-group">
          <td class="label">{$form.dedupeRuleGroupId.label}</td>
          <td>
            {$form.dedupeRuleGroupId.html}
            <div class="description">
              {capture assign='newrule'}{crmURL p='civicrm/contact/deduperules' q='reset=1'}{/capture}
              {ts 1=$newrule}Use rule you choose above for matching contact in each row. You can also <a href="%1">add new rule</a> anytime.{/ts}
            </div>
          </td>
        </tr>
         <tr class="crm-import-datasource-form-block-onDuplicate">
             <td class="label">{$form.onDuplicate.label}</td>
             <td>{$form.onDuplicate.html} {help id='dupes'}</td>
         </tr>
         <tr>{include file="CRM/Core/Date.tpl"}</tr>
         <tr>
             <td></td><td class="description">{ts}Select the format that is used for date fields in your import data.{/ts}</td>
         </tr>
         
        {if $geoCode}
         <tr class="crm-import-datasource-form-block-doGeocodeAddress">
             <td class="label"></td>
             <td>{$form.doGeocodeAddress.html} {$form.doGeocodeAddress.label}<br />
               <span class="description">
                {ts}This option is not recommended for large imports. Use the command-line geocoding script instead.{/ts} 
               </span>
               {docURL page="Batch Geocoding Script"}
            </td>
         </tr>
        {/if}

        {if $savedMapping}
         <tr  class="crm-import-datasource-form-block-savedMapping">
              <td class="label"><label for="savedMapping">{if $loadedMapping}{ts}Select a Different Field Mapping{/ts}{else}{ts}Load Saved Field Mapping{/ts}{/if}</label></td>
              <td>{$form.savedMapping.html}<br />
	    &nbsp;&nbsp;&nbsp;<span class="description">{ts}Select Saved Mapping or Leave blank to create a new One.{/ts}</span></td>
         </tr>
        { /if}
 </table>
    </fieldset>
  </div>

  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"} </div>

  {literal}
  <script type="text/javascript">
    cj(document).ready(function($) {
      //build data source form block
      var buildDataSourceFormBlock = function(){
        var dataUrl = {/literal}"{crmURL p=$urlPath h=0 q=$urlPathVar}"{literal};
        if (!dataSource ) {
          var dataSource = $("#dataSource").val();
        }
        if ( dataSource ) {
          dataUrl = dataUrl + '&dataSource=' + dataSource;
        }
        else {
          $("#data-source-form-block").html('');
          return;
        }
        $("#data-source-form-block").load( dataUrl );
      }

      var buildSubTypes = function(){
        var element = cj("input[name=contactType]:checked").val();
        var postUrl = {/literal}"{crmURL p='civicrm/ajax/subtype' h=0 }"{literal};
        var param = 'parentType=' + element;
        $.ajax({
          type: "POST",
          url: postUrl,
          data: param,
          async: false,
          dataType: 'json',
          success: function(subtype){
            if ( subtype.length == 0 ) {
              $("#subType").empty();
              $("#contact-subtype").hide();
            }
            else {      
              $("#contact-subtype").show();  
              $("#subType").empty();                                  
              $("#subType").append("<option value=''>{/literal}{ts}-- Select --{/ts}{literal}</option>"); 
              for ( var key in  subtype ) {
                // stick these new options in the subtype select
                $("#subType").append("<option value="+key+">"+subtype[key]+" </option>"); 
              }
            }
          }
        });
      }

			var showHideDedupeRule = function(){
				$("input[name=contactType]:checked").each(function(){
					var contactType = $(this).next('.elem-label').text();
					$("#dedupeRuleGroupId option").each(function(){
						if ($(this).attr("value")) {
							var re = new RegExp("^"+contactType,"g");
							if(!$(this).text().match(re)){
								$(this).hide();
							}
							else{
								$(this).show();
							}
						}
					});
					var $option = $("#dedupeRuleGroupId option").filter(function(){
						if($(this).css('display') == 'none'){
							return false;
						}
						return true;
					});
          var selected = $option.filter("[selected=selected]");
          if (selected.length) {
            $("#dedupeRuleGroupId").val(selected.val());
          }
          else {
            $("#dedupeRuleGroupId").val($option.val());
          }
				});
			}

			buildDataSourceFormBlock();
      buildSubTypes();
      showHideDedupeRule();
      $("select[id=dataSource]").change(buildDataSourceFormBlock);
			$("input[name=contactType]").click(buildSubTypes);
			$("input[name=contactType]").click(showHideDedupeRule);
    });

    </script>
  {/literal}
{/if}
</div>
