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
{* Event Import Wizard - Step 1 (upload data file) *}
{* @var $form Contains the array for the form elements and other form associated information assigned to the template by the controller *}

 {* WizardHeader.tpl provides visual display of steps thru the wizard as well as title for current step *}
 {include file="CRM/common/WizardHeader.tpl"}
  
 <div id="help">
    {ts}The Event Import Wizard allows you to easily upload event participation data such as event registrations from other applications into CiviCRM.{/ts}
    {ts}Files to be imported must be in the 'comma-separated-values' format (CSV) and must contain data needed to match the participant data to an existing contact in your CiviCRM database.{/ts} {help id='upload'}
 </div>    

 <div id="upload-file" class="form-item">
 <fieldset>
    <dl>
        <dt>{$form.uploadFile.label}</dt>
            <dd>{$form.uploadFile.html}<br />
                <span class="description">
                    {ts}File format must be comma-separated-values (CSV).{/ts}
                </span>
            </dd>
        <dt>&nbsp;</dt>
	        <dd>{ts 1=$uploadSize}Maximum Upload File Size: %1 MB{/ts}</dd>
        <dt>&nbsp;</dt>
            <dd>{$form.skipColumnHeader.html} {$form.skipColumnHeader.label}<br />
                <span class="description">
                    {ts}Check this box if the first row of your file consists of field names (Example: "Contact ID", "Participant Role").{/ts}
                </span>
            </dd>
        <dt class="">{$form.contactType.label}</dt>
            <dd>{$form.contactType.html}<br />
                <span class="description">
                    {ts}Select 'Individual' if you are importing event participation data for individual persons.{/ts}
                    {ts}Select 'Organization' or 'Household' if you are importing event participation data for contacts of that type. (NOTE: Some built-in contact types may not be enabled for your site.){/ts}
                </span>
            </dd>
        <dt>{$form.onDuplicate.label}</dt><dd>{$form.onDuplicate.html} {help id="id-onDuplicate"}</dd> 
        {include file="CRM/Core/Date.tpl"}  
{if $savedMapping}
      <dt>{if $loadedMapping}{ts}Select a Different Field Mapping{/ts}{else}{ts}Load Saved Field Mapping{/ts}{/if}</dt>
      <dd><span>{$form.savedMapping.html}</span> </dd>
      <dt>&nbsp;</dt>
      <dd class="description">{ts}Select Saved Mapping, or leave blank to create a new mapping.{/ts}</dd>
{/if}
    </dl>
    <div class="spacer"></div>
 </fieldset>
 </div>
 <div id="crm-submit-buttons">
    {$form.buttons.html}
 </div>
