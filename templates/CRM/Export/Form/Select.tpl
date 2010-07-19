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
{* Export Wizard - Step 2 *}
{* @var $form Contains the array for the form elements and other form associated information assigned to the template by the controller *}

{* WizardHeader.tpl provides visual display of steps thru the wizard as well as title for current step *}
{include file="CRM/common/WizardHeader.tpl"}

<div id="help">
<p>{ts}<strong>Export PRIMARY fields</strong> provides the most commonly used data values. This includes primary address information, preferred phone and email.{/ts}</p>
<p>{ts}Click <strong>Select fields for export</strong> and then <strong>Continue</strong> to choose a subset of fields for export. This option allows you to export multiple specific locations (Home, Work, etc.) as well as custom data. You can also save your selections as a 'field mapping' so you can use it again later.{/ts}</p>
</div>

<fieldset>
  <div id="export-type" class="form-item">
    <dl>
        <dd>
         {ts count=$totalSelectedRecords plural='%count records selected for export.'}One record selected for export.{/ts}
        </dd> 
        <dd>{$form.exportOption.html}</dd>
    </dl>
      <div id="map">
       {if $form.mapping }
            <table class="form-layout-compressed">
            <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>{$form.mapping.label} &nbsp; {$form.mapping.html}</td></tr></table>
       {/if}
      </div>
  </div>
</fieldset>

<div id="crm-submit-buttons">
    {$form.buttons.html}
</div>

{literal}
  <script type="text/javascript">
     function showMappingOption( )
     {
	var element = document.getElementsByName("exportOption");

	if ( element[1].checked ) { 
	  show('map');
        } else {
	  hide('map');
	}
     } 
   showMappingOption( );
  </script>
{/literal}
