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
{* this template is used for adding/editing/deleting contribution type  *}
<div class="form-item">
<fieldset><legend>{if $action eq 1}{ts}New Contribution Type{/ts}{elseif $action eq 2}{ts}Edit Contribution Type{/ts}{else}{ts}Delete Contribution Type{/ts}{/if}</legend>
  
   {if $action eq 8}
      <div class="messages status">
        <dl>
          <dt><img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}" /></dt>
          <dd>    
          {ts}WARNING: You cannot delete a contribution type if it is currently used by any Contributions, Contribution Pages or Membership Types. Consider disabling this option instead.{/ts} {ts}Deleting a contribution type cannot be undone.{/ts} {ts}Do you want to continue?{/ts}
          </dd>
       </dl>
      </div>
     {else}
      <dl>
 	    <dt>{$form.name.label}</dt><dd>{$form.name.html}</dd>
    	<dt>{$form.description.label}</dt><dd>{$form.description.html}</dd>
    	<dt>{$form.accounting_code.label}</dt><dd>{$form.accounting_code.html}</dd>
        <dt>&nbsp;</dt><dd class="description">{ts}Use this field to flag contributions of this type with the corresponding code used in your accounting system. This code will be included when you export contribution data to your accounting package.{/ts}</dd>
    	<dt>{$form.is_deductible.label}</dt><dd>{$form.is_deductible.html}</dd>
        <dt>&nbsp;</dt><dd class="description">{ts}Are contributions of this type tax-deductible?{/ts}</dd>
        <dt>{$form.is_active.label}</dt><dd>{$form.is_active.html}</dd>
      </dl> 
     {/if}
    <dl>   
      <dt></dt><dd>{$form.buttons.html}</dd>
    </dl>
</fieldset>
</div>
