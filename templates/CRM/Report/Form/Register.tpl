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
<div class="form-item">	
<fieldset>
{if $action eq 8} 
    <legend>{ts}Delete Report Template{/ts}</legend>
    <div class="messages status"> 
        <dl> 
            <dt><img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}" /></dt> 
            <dd>{ts}WARNING: Deleting this option will result in the loss of all Report related records which use the option. This may mean the loss of a substantial amount of data, and the action cannot be undone. Do you want to continue?{/ts}</dd>
        </dl> 
    </div> 
{else}
  	
    <legend>{if $action eq 2}{ts}Edit Report Template{/ts}{else}{ts}New Report Template{/ts}{/if}</legend>
    <dl>
        <dt>{$form.label.label}</dt>
        <dd>{$form.label.html}</dd>
	<dt></dt>	   
        <dd class="description">{ts}Report title appear in the display screen.{/ts}</dd>
              
        <dt class="label">{$form.description.label}</dt>
        <dd>{$form.description.html}</dd>
        <dt></dt>
	<dd class="description">{ts}Report description appear in the display screen.{/ts}</dd>
      
        <dt class="label">{$form.value.label}</dt>
        <dd>{$form.value.html}</dd>
	<dt></dt>
        <dd class="description">{ts}Report Url must be like "contribute/summary"{/ts}</dd>
        
        <dt class="label">{$form.name.label}</dt>
        <dd>{$form.name.html}</dd>
	<dt></dt>
        <dd class="description">{ts}Report Class must be present before adding the report here, e.g. 'CRM_Report_Form_Contribute_Summary'{/ts}</dd>
       
        <dt class="label">{$form.weight.label}</dt>
        <dd>{$form.weight.html}</dd>
      
        <dt class="label">{$form.component_id.label}</dt>   
        <dd>{$form.component_id.html}</dd>
	<dt></dt>
        <dd class="description">{ts}Specify the Report if it is belongs to any component like "CiviContribute"{/ts}</dd>
           
        <dt class="label">{$form.is_active.label}</dt>
        <dd>{$form.is_active.html}</dd> 
    </dl>    
{/if} 
 <dl>
    <dt></dt>
    <dd>{$form.buttons.html}<dd/>
 </dl>
</fieldset>
 </div>
