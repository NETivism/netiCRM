{*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.1                                                |
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
 
<div id="id_form_show" class="section-hidden section-hidden-border">
    <a href="#" onclick="hide('id_form_show'); show('id_form'); return false;"><img src="{$config->resourceBase}i/TreePlus.gif" class="action-icon" alt="{ts}open section{/ts}"/></a><label>{ts}Premiums Settings{/ts}</label><br />
</div>

  <div id="id_form" class="form-item">
    <fieldset><legend><a href="#" onclick="hide('id_form'); show('id_form_show'); return false;"><img src="{$config->resourceBase}i/TreeMinus.gif" class="action-icon" alt="{ts}close section{/ts}"/></a>{ts}Premiums Settings{/ts}</legend>
     <dl>
     <dt>{$form.premiums_active.label}</dt><dd>{$form.premiums_active.html}</dd>
     <dt>&nbsp;</dt><dd class="description">{ts}Is the Premiums section enabled for this Online Contributions page?{/ts}</dd></dl>
    <div class="form-item"><dl id= "premiumFields" class="html-adjust">
    <dt>{$form.premiums_intro_title.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_premiums' field='premiums_intro_title' id=$id}{/if}</dt><dd>{$form.premiums_intro_title.html}</dd>
    <dt>&nbsp;</dt><dd class="description">{ts}Title to appear at the top of the Premiums section.{/ts}</dd>
    <dt>{$form.premiums_intro_text.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_premiums' field='premiums_intro_text' id=$id}{/if}</dt><dd>{$form.premiums_intro_text.html}</dd>
    <dt>&nbsp;</dt><dd class="description">{ts}Enter content for the introductory message. This will be displayed below the Premiums section title. You may include HTML formatting tags. You can also include images, as long as they are already uploaded to a server - reference them using complete URLs.{/ts}</dd>
    
    <dt>{$form.premiums_contact_email.label}</dt><dd>{$form.premiums_contact_email.html}</dd>
    <dt>&nbsp;</dt><dd class="description">{ts}This email address is included in automated contribution receipts if the contributor has selected a premium. It should be an appropriate contact mailbox for inquiries about premium fulfillment/shipping.{/ts}</dd>
	
    <dt>{$form.premiums_contact_phone.label}</dt><dd>{$form.premiums_contact_phone.html}</dd>
    <dt>&nbsp;</dt><dd class="description">{ts}This phone number is included in automated contribution receipts if the contributor has selected a premium. It should be an appropriate phone number for inquiries about premium fulfillment/shipping.{/ts}</dd>

    <dt>{$form.premiums_display_min_contribution.label}</dt><dd>{$form.premiums_display_min_contribution.html}</dd>
    <dt>&nbsp;</dt><dd class="description">{ts}Should the minimum contribution amount be automatically displayed after each premium description?{/ts}</dd>
	
    </dl></div>
    {if  ! $showForm }   
     {if $action ne 4}
             <div id="crm-submit-buttons">	
    <div class="spacer"></div>   
        <dl><dt></dt><dd >{$form.buttons.html}</dd></dl>  
    </div>
    
    {else}
            <div id="crm-done-button">
                {$form.done.html}
         </div>
    {/if} {* $action ne view *}
    {/if}   
   
  </fieldset>
</div>

     {if $showForm }   
     {if $action ne 4}
            <div id="crm-submit-buttons">
            {$form.buttons.html}
    </div>
    {else}
            <div id="crm-done-button">
                {$form.done.html}
         </div>
    {/if} {* $action ne view *}
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
	var premium_block = document.getElementsByName('premiums_active');
  	if ( ! premium_block[0].checked) {
	  hide('premiumFields');
    }
	function premiumBlock(chkbox) {
        if (chkbox.checked) {
		   show('premiumFields', 'table-row');
		    return;
		} else {
		    hide('premiumFields', 'table-row');
    	    return;
		}
	}	
</script>
{/literal}

{* include jscript to warn if unsaved form field changes *}
{include file="CRM/common/formNavigate.tpl"}
