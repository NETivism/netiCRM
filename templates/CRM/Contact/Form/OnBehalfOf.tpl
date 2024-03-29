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
{* This file provides the HTML for the on-behalf-of form. Can also be used for related contact edit form. *}
<fieldset id="for_organization" class="for_organization-group">
<legend>{$fieldSetTitle}</legend>
{if $contact_type eq 'Individual'}

  {if $contactEditMode}<fieldset><legend></legend>{/if}
	<table class="form-layout-compressed">
    <tr>
		<td>{$form.prefix_id.label}</td>
		<td>{$form.first_name.label}</td>
		<td>{$form.middle_name.label}</td>
		<td>{$form.last_name.label}</td>
		<td>{$form.suffix_id.label}</td>
	</tr>
	<tr>
		<td>{$form.prefix_id.html}</td>
		<td>{$form.first_name.html}</td>
		<td>{$form.middle_name.html|crmReplace:class:eight}</td>
		<td>{$form.last_name.html}</td>
		<td>{$form.suffix_id.html}</td>
	</tr>
   	
    </table>
  {if $contactEditMode}</fieldset>{/if}


{elseif $contact_type eq 'Household'}

 {if $contactEditMode}<fieldset><legend></legend>{/if}
   	<table class="form-layout-compressed">
      <tr>
		<td>{$form.household_name.label}</td>
      </tr>
      <tr>
        <td>{$form.household_name.html|crmReplace:class:big}</td>
      </tr>
    </table>   
 {if $contactEditMode}</fieldset>{/if}


{elseif $contact_type eq 'Organization'}

 {if $contactEditMode}
 	<fieldset><legend></legend>
 {/if}
    <div class="crm-section organizationName-section">
    {if $relatedOrganizationFound}
      <div class="section crm-section org_option-section">
        <div class="content">{$form.org_option.html}</div>
      </div>
      <div id="select_org" class="crm-section select_org-section">
        <div class="label">{$form.organization_name.label}{if !$form.organization_name.required}<span class="crm-marker" title="{ts}This field is required.{/ts}">*</span>{/if}</div>	   
        <div class="content">{$form.organization_id.html|crmReplace:class:big}</div>
      </div>
    {/if}
      <div id="create_org" class="crm-section create_org-section">
        <div class="label">{$form.organization_name.label}{if !$form.organization_name.required}<span class="crm-marker" title="{ts}This field is required.{/ts}">*</span>{/if}</div>
        <div class="content">{$form.organization_name.html|crmReplace:class:big}</div>
        <div class="clear"></div>
      </div>
      <div id="set_sic_code" class="crm-section create_org-section">
        <div class="label">{$form.sic_code.label}</div>
        <div class="content">{$form.sic_code.html|crmReplace:class:big}</div>
        <div class="clear"></div>
      </div>
    </div>
 {if $contactEditMode}
 	</fieldset>
 {/if}

{/if}

{* Display the address block *}
{assign var=index value=1}

{if $contactEditMode}
  <fieldset><legend>{ts}Phone and Email{/ts}</legend>
    <table class="form-layout-compressed">
		<tr>
            <td width="25%">{$form.phone.$index.phone.label}</td>
            <td>{$form.phone.$index.phone.html}</td>
        </tr>
		<tr>
            <td>{$form.email.$index.email.label}</td>
            <td>{$form.email.$index.email.html}</td>
        </tr>
    </table>
  </fieldset>
{/if}

    {if $contactEditMode}<fieldset><legend>{ts}Address{/ts}</legend>{/if}
    <div class="crm-section address-section">
        {if !$contactEditMode}
		<div class="crm-section {$form.phone.$index.phone.id}-section">
            <div class="label">{$form.phone.$index.phone.label}</div>
            <div class="content">{$form.phone.$index.phone.html}</div>
            <div class="clear"></div>
        </div>
		<div class="crm-section {$form.email.$index.email.id}-section">
            <div class="label">{$form.email.$index.email.label}<span class="crm-marker" title="{ts}This field is required.{/ts}">*</span></div>
            <div class="content">{$form.email.$index.email.html}</div>
            <div class="clear"></div>
        </div>
        {/if}
        {if $addressSequence.country && $form.address.$index.country_id.id}
        <div class="crm-section {$form.address.$index.country_id.id}-section">
            <div class="label">{$form.address.$index.country_id.label}</div>
            <div class="content">{$form.address.$index.country_id.html}</div>
            <div class="clear"></div>
        </div>
        {/if}
        {if $addressSequence.state_province}
		<div class="crm-section {$form.address.$index.state_province_id.id}-section">
            <div class="label">{$form.address.$index.state_province_id.label}</div>
            <div class="content">{$form.address.$index.state_province_id.html}</div>
            <div class="clear"></div>
        </div>
        {/if}
        {if $addressSequence.city}
		<div class="crm-section {$form.address.$index.city.id}<-section">
            <div class="label">{$form.address.$index.city.label}</div>
            <div class="content">{$form.address.$index.city.html}</div>
            <div class="clear"></div>
        </div>
        {/if}
        {if $addressSequence.postal_code}
		<div class="crm-section {$form.address.$index.postal_code.id}-section">
            <div class="label">{$form.address.$index.postal_code.label}</div>
            <div class="content">{$form.address.$index.postal_code.html}</div>
            <div class="clear"></div>
        </div>
        {/if}
        {if $addressSequence.street_address}
		<div class="crm-section {$form.address.$index.street_address.id}-section">
            <div class="label">{$form.address.$index.street_address.label}</div>
            <div class="content">{$form.address.$index.street_address.html}</div>
            <div class="clear"></div>
        </div>
        {/if}
        {if $form.address.$index.supplemental_address_1.id}
		<div class="crm-section {$form.address.$index.supplemental_address_1.id}-section">
            <div class="label">{$form.address.$index.supplemental_address_1.label}</div>
            <div class="content">{$form.address.$index.supplemental_address_1.html}    
                <br class="spacer"/>
                <span class="description">{ts}Supplemental address info, e.g. c/o, department name, building name, etc.{/ts}</span>
            </div>
            <div class="clear"></div>
        </div>
        {/if}
        {if $form.address.$index.supplemental_address_2.id}
		<div class="crm-section {$form.address.$index.supplemental_address_2.id}-section">
            <div class="label">{$form.address.$index.supplemental_address_2.label}</div>
            <div class="content">{$form.address.$index.supplemental_address_2.html}</div>
            <div class="clear"></div>
        </div>
        {/if}
        {if $contactEditMode and $form.location.$index.address.geo_code_1.label}
		<div class="crm-section {$form.address.$index.geo_code_1.id}-{$form.address.$index.geo_code_2.id}-section">
            <div class="label">{$form.address.$index.geo_code_1.label}, {$form.address.$index.geo_code_2.label}</div>
            <div class="content">{$form.address.$index.geo_code_1.html}, {$form.address.$index.geo_code_2.html}    
                <br class="spacer"/>
                <span class="description">
                    {ts}Latitude and longitude may be automatically populated by enabling a Mapping Provider.{/ts} {docURL page="Mapping and Geocoding"}</span>
            </div>
            <div class="clear"></div>
        </div>
        {/if}
    </div>

    {if $contactEditMode}</fieldset>{/if}

</fieldset>

{if $form.is_for_organization}
    {include file="CRM/common/showHideByFieldValue.tpl" 
         trigger_field_id    ="is_for_organization"
         trigger_value       ="true"
         target_element_id   ="for_organization" 
         target_element_type ="block"
         field_type          ="radio"
         invert              = "false"
    }
{/if}

{if $relatedOrganizationFound}
    {include file="CRM/common/showHideByFieldValue.tpl" 
         trigger_field_id    ="org_option"
         trigger_value       ="true"
         target_element_id   ="select_org" 
         target_element_type ="table-row"
         field_type          ="radio"
         invert              = "true"
    }
    {include file="CRM/common/showHideByFieldValue.tpl" 
         trigger_field_id    ="org_option"
         trigger_value       ="true"
         target_element_id   ="create_org" 
         target_element_type ="table-row"
         field_type          ="radio"
         invert              = "false"
    }
{/if}

{literal}
<script type="text/javascript">
{/literal}
{* If mid present in the url, take the required action (poping up related existing contact ..etc) *}
{if $membershipContactID}
    {literal}
    cj( function( ) {
        cj( '#organization_id' ).val("{/literal}{$membershipContactName}{literal}");
        cj( '#organization_name' ).val("{/literal}{$membershipContactName}{literal}");
        cj( '#onbehalfof_id' ).val("{/literal}{$membershipContactID}{literal}");
        setLocationDetails( "{/literal}{$membershipContactID}{literal}" );
    });
    {/literal}
{/if}
{* Javascript method to populate the location fields when a different existing related contact is selected *}
{literal}
    var dataUrl   = "{/literal}{$employerDataURL}{literal}";
    cj('#organization_id').autocomplete( dataUrl, { width : 180, selectFirst : false, matchContains: true
    }).result( function(event, data, formatted) {
        cj('#organization_name').val( data[0] );
        cj('#onbehalfof_id').val( data[1] );
        setSICCode( data[1] );
        setLocationDetails( data[1] );
    });
    function setSICCode( contactID ) {
        var SICUrl = "{/literal}{$SICUrl}{literal}"+ contactID;
        cj.ajax({
            url         : SICUrl,
            dataType    : "json",
            timeout     : 5000, //Time in milliseconds
            success     : function( data, status ) {
                cj("#sic_code").val(data[0].sic_code);
            },
            error       : function( XMLHttpRequest, textStatus, errorThrown ) {
                console.error("HTTP error status: ", textStatus);
            }
        });
    }
    
    function setLocationDetails( contactID ) {
        var locationUrl = {/literal}"{$locDataURL}"{literal}+ contactID; 
        cj.ajax({
            url         : locationUrl,
            dataType    : "json",
            timeout     : 5000, //Time in milliseconds
            success     : function( data, status ) {
                for (var ele in data) {
                    cj( "#"+ele ).val( data[ele] );
                }
            },
            error       : function( XMLHttpRequest, textStatus, errorThrown ) {
                console.error("HTTP error status: ", textStatus);
            }
        });
    }
</script>
{/literal}
