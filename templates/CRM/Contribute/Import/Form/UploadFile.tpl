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
{* Contribution Import Wizard - Step 1 (upload data file) *}
{* @var $form Contains the array for the form elements and other form associated information assigned to the template by the controller *}
 <div class="crm-block crm-form-block  crm-contribution-import-uploadfile-form-block id="upload-file">
 {* WizardHeader.tpl provides visual display of steps thru the wizard as well as title for current step *}
{include file="CRM/common/WizardHeader.tpl"}
<div id="help">
    {ts}The Contribution Import Wizard allows you to easily upload contributions from other applications into CiviCRM.{/ts}
    {ts}Files to be imported must be in the 'comma-separated-values' format (CSV) and must contain data needed to match the contribution to an existing contact in your CiviCRM database.{/ts} {help id='upload'}
 </div>
 <fieldset>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
  <table class="form-layout-compressed">
        <tr>
          <td class="label">{$form.uploadFile.label}</td>
          <td class="html-adjust"> {$form.uploadFile.html}
       	   <div class="description">{ts}File format must be comma-separated-values (CSV).{/ts}</div>
          </td>
        </tr>
        <tr><td class="label"></td><td>{ts 1=$uploadSize}Maximum Upload File Size: %1 MB{/ts}</td></tr>
        <tr>
          <td class="label"></td>
          <td>
            {$form.skipColumnHeader.html}{$form.skipColumnHeader.label}
            <div class="description">{ts}Check this box if the first row of your file consists of field names (Example: 'Contact ID', 'Amount').{/ts} </div>
          </td>
        </tr>
        <tr>
          <td class="label">{$form.onDuplicate.label}{help id="id-onDuplicate"}</td>
          <td>
            {$form.onDuplicate.html}
          </td>
        </tr>
        <tr class="create-new-contact"><td class="label">{$form.createContactOption.label}{help id="id-createContactOption"}</td><td>{$form.createContactOption.html}</td></tr>
        <tr>
          <td class="{$form.contactType.name} label">{$form.contactType.label}</td>
          <td class="{$form.contactType.name}">{$form.contactType.html}<br />
            <div class="description">
              {ts}Select 'Individual' if you are importing contributions made by individual persons.{/ts}
              {ts}Select 'Organization' or 'Household' if you are importing contributions made by contacts of that type. (NOTE: Some built-in contact types may not be enabled for your site.){/ts}
            </div>
          </td>
        </tr>
        <tr class="dedupe-rule-group">
          <td class="label">{$form.dedupeRuleGroup.label}</td>
          <td>
            {$form.dedupeRuleGroup.html}
            <div class="description">
              {capture assign='newrule'}{crmURL p='civicrm/contact/deduperules' q='reset=1'}{/capture}
              {ts 1=$newrule}Use rule you choose above for matching contact in each row. You can also <a href="%1">add new rule</a> anytime.{/ts}
              <ul style="list-style-type: decimal;">
              <li>{ts}Uploading file must include the following columns or the data cannot be imported successfully.{/ts}</li>
              <ul style="list-style-type: disc;">
                <li>{ts}First Name,Last Name,Email(or Dedupe Rule of Contact you selected){/ts}</li>
                <li>{ts}Total Amount{/ts}</li>
                <li>{ts}Contribution Type{/ts}</li>
                <li>{ts}Contribution Received Date{/ts}</li>
                <li>{ts}Receipt Date{/ts}</li>
                <li>{ts}Invoice ID{/ts}</li>
              </ul>
              <li>{ts}When importing contributions, if the contributor has already have data in the system, the content of the contributor's personal information (contact, personal field) you imported this time,It is not possible to update the personal information of this contributor, but only for the purpose of comparing contributor. If you want to update your contributor information, please use the Import Contacts function to do so.{/ts}</li>
              </ul>
            </div>
          </td>
        </tr>
        <tr>{include file="CRM/Core/Date.tpl"}</tr>
{if $savedMapping}
      <tr> <td class="label">{if $loadedMapping}{ts}Select a Different Field Mapping{/ts}{else}{ts}Load Saved Field Mapping{/ts}{/if}</td><td>{$form.savedMapping.html}<br /> <span class="description">{ts}Select a saved field mapping if this file format matches a previous import.{/ts}</span></tr>
{/if}
    </table>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
 </fieldset>
 </div>
 <script>{literal}
cj(document).ready(function($){
  var showHideCreateContact = function(init){
    $("input[name=onDuplicate]:checked").each(function(){
      if ($(this).val() == 4) {
        $("input[name=createContactOption]").not("[value=102]").closest("label").addClass("disabled");
        $("input[name=createContactOption]").not("[value=102]").attr('disabled', 'disabled');
        $("input[name=createContactOption][value=102]").click();
        $("input[name=contactType]").attr('disabled', 'disabled');
        $("select[name=dedupeRuleGroup]").attr('disabled', 'disabled');
        $("input[name=contactType]").removeAttr('checked');
        $("select[name=dedupeRuleGroup]").empty();
      }
      else {
        $("input[name=createContactOption]").not("[value=102]").removeAttr('disabled');
        if (!init) {
          $("input[name=createContactOption][value=100]").click();
        }
        $("tr.create-new-contact label").removeClass("disabled");
        $("input[name=contactType]").removeAttr('disabled');
        $("select[name=dedupeRuleGroup]").removeAttr('disabled');
        $("input[value=Individual]").attr('checked', 'checked');
        showDedupeRuleOption();

      }
    });
  }

  var showHideDedupeRule = function(){
    $("input[name=contactType]:checked").each(function(){
      var contactType = $(this).next('.elem-label').text();
      $("#dedupeRuleGroup option").each(function(){
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
      var $option = $("#dedupeRuleGroup option").filter(function(){
        if($(this).css('display') == 'none'){
          return false;
        }
        return true;
      });
			var selected = $option.filter("[selected=selected]");
			if (selected.length) {
				$("#dedupeRuleGroup").val(selected.val());
			}
			else {
				$("#dedupeRuleGroup").val($option.val());
			}
    });
  }

  var showDedupeRuleOption = function(){
      $("select[name=dedupeRuleGroup]").append($option);
  }
  var $option = $("#dedupeRuleGroup option").filter(function(){
        if($(this).css('display') == 'none'){
          return false;
        }
        return true;
      });
  $("input[name=onDuplicate]").click(showHideCreateContact);
  $("input[name=contactType]").click(showHideDedupeRule);
  $("tr.create-new-contact label.crm-form-elem").css('display', 'block');
  $("tr.create-new-contact").find("br").remove();
  showHideCreateContact(true);
  showHideDedupeRule();
});
{/literal}</script>
