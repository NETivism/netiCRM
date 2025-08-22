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
{* WizardHeader.tpl provides visual display of steps thru the wizard as well as title for current step *}
{include file="CRM/common/WizardHeader.tpl"}
<div id="help">
    {ts}Edit <strong>Premiums Settings</strong> to enable the Premiums section for this Online Contribution Page, and customize the title and introductory message (e.g ...in appreciation of your support, you will be able to select from a number of exciting thank-you gifts...). You can optionally provide a contact email address and/or phone number for inquiries.{/ts}
    {ts}Then select and review the premiums that you want to offer on this contribution page.{/ts}
</div> 
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
<table class="form-layout-compressed">
  <tr class="crm-contribution-contributionpage-premium-form-block-premiums_active">
    <td class="label">{$form.premiums_active.label}</td>
    <td class="html-adjust">{$form.premiums_active.html}<br />
      <span class="description">{ts}Is the Premiums section enabled for this Online Contributions page?{/ts}</span>
    </td>
  </tr>
  <tr class="crm-contribution-contributionpage-premium-form-block-premiums_intro_title">
      <td class="label">{$form.premiums_intro_title.label}{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_premiums' field='premiums_intro_title' id=$id}{/if}
      </td>
      <td class="html-adjust">{$form.premiums_intro_title.html}<br />
        <span class="description">{ts}Title to appear at the top of the Premiums section.{/ts}</span>
      </td>
  </tr>
  <!-- // todo : When the database has a value, change the element name -->
  <tr class="crm-contribution-contributionpage-premium-form-block-premiums_combination">
      <td class="label">{$form.premiums_combination.label}
      </td>
      <td class="html-adjust">{$form.premiums_combination.html}<br />
        <span class="description">{ts}Once enabled, the Contribution Page will display gift combinations instead of individual premiums.{/ts}</span>
      </td>
  </tr>
</table>
<div class="crm-accordion-wrapper crm-accordion-closed" id="premiums-settings">
  <div class="crm-accordion-header">
    <div class="zmdi crm-accordion-pointer"></div>{ts}Premiums Settings{/ts}
  </div>
  <div class="crm-accordion-body crm-form-block crm-contribution-contributionpage-premium-form-block">
    <table id= "premiumFields" class="form-layout-compressed">
    <tr class="crm-contribution-contributionpage-premium-form-block-premiums_intro_text">
       <td class="label">{$form.premiums_intro_text.label}{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_premiums' field='premiums_intro_text' id=$id}{/if}
       </td>
       <td class="html-adjust">{$form.premiums_intro_text.html}<br />
       	   <span class="description">{ts}Enter content for the introductory message. This will be displayed below the Premiums section title. You may include HTML formatting tags. You can also include images, as long as they are already uploaded to a server - reference them using complete URLs.{/ts}</span>
      </td>
    </tr>
    <tr class="crm-contribution-contributionpage-premium-form-block-premiums_contact_email">    
       <td class="label">{$form.premiums_contact_email.label}
       </td>
       <td class="html-adjust">{$form.premiums_contact_email.html}<br />
       	   <span class="description">{ts}This email address is included in automated contribution receipts if the contributor has selected a premium. It should be an appropriate contact mailbox for inquiries about premium fulfillment/shipping.{/ts}</span>
      </td>
    </tr>	
    <tr class="crm-contribution-contributionpage-premium-form-block-premiums_contact_phone">
       <td class="label">{$form.premiums_contact_phone.label}
       </td>
       <td class="html-adjust">{$form.premiums_contact_phone.html}<br />
       	   <span class="description">{ts}This phone number is included in automated contribution receipts if the contributor has selected a premium. It should be an appropriate phone number for inquiries about premium fulfillment/shipping.{/ts}</span>
      </td>
    </tr>
    <tr class="crm-contribution-contributionpage-premium-form-block-premiums_display_min_contribution">
       <td class="label">{$form.premiums_display_min_contribution.label}
       </td>
       <td class="html-adjust">{$form.premiums_display_min_contribution.html}<br />
       	   <span class="description">{ts}Should the minimum contribution amount be automatically displayed after each premium description?{/ts}</span>
      </td>
    </tr>
    </table>
  </div><!--Accordion Body-->
</div>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
{if $showForm}
<div class="messages status">
  {capture assign=crmURL}{crmURL p='civicrm/admin/contribute/managePremiums' q="reset=1"}{/capture}
  <p>{ts}This Contribution Page currently has no premiums. You can <a href='%1'>add one</a>.{/ts}</p>
</div>
{else}
<div class="messages status">
  {capture assign=managePremiumsURL}{crmURL p='civicrm/admin/contribute/managePremiums' q="reset=1"}{/capture}
  <p>{ts 1=$managePremiumsURL}This Contribution Page currently has no premiums. You can <a href='%1'>add a gift combination</a>.{/ts}</p>
</div>
{/if}

<script type="text/javascript">
    var myElement1 = document.getElementById('id_form');
    var myElement2 = document.getElementById('id_form_show');
    {if $showForm }
        myElement1.style.display = 'block';
        myElement2.style.display = 'none';
    {else}
        myElement1.style.display = 'none';
        myElement2.style.display = 'block';
    {/if}
</script>
{literal}
<script type="text/javascript">
  cj().crmaccordions(); 
  cj(document).ready(function($){
    var showHidePremium = function(obj) {
      if($(obj).is(":checked")) {
        $("tr.crm-contribution-contributionpage-premium-form-block-premiums_intro_title").show();
        $("tr.crm-contribution-contributionpage-premium-form-block-premiums_combination").show();
        $("#premiums-settings").show();
      }
      else {
        $("tr.crm-contribution-contributionpage-premium-form-block-premiums_intro_title").hide();
        $("tr.crm-contribution-contributionpage-premium-form-block-premiums_combination").hide();
        $("#premiums-settings").hide();
      }
    }
    $("#premiums_active").click(function(){
      showHidePremium($(this));
    });    
    showHidePremium($("#premiums_active"));
  });
</script>
{/literal}

{* include jscript to warn if unsaved form field changes *}
{include file="CRM/common/formNavigate.tpl"}
