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
{* this template is used for adding/editing/deleting activity type  *}
<div class="form-item">
<fieldset><legend>{if $action eq 1}{ts}New Option Value{/ts}{elseif $action eq 2}{ts}Edit Option Value{/ts}{else}{ts}Delete Option Value{/ts}{/if}</legend>
  
   {if $action eq 8}
      <div class="messages status">
        <dl>
          <dt><img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}" /></dt>
          <dd>    
          {ts}WARNING: Deleting this option value will result in the loss of all records which use the option value.{/ts} {ts}This may mean the loss of a substantial amount of data, and the action cannot be undone.{/ts} {ts}Do you want to continue?{/ts}
          </dd>
       </dl>
      </div>
     {else}
      <dl>
 	    <dt>{$form.label.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_option_value' field='label' id=$id}{/if}</dt><dd>{$form.label.html}</dd>
        <dt>{$form.value.label}</dt><dd>{$form.value.html}</dd>
        {if $config->languageLimit|@count >= 2}
          <dt></dt><dd class="description">{ts}The same option value is stored for all languages. Changing this value will change it for all languages.{/ts}</dd>
        {/if}
        <dt>{$form.name.label}</dt><dd>{$form.name.html}</dd>
        <dt>{$form.grouping.label}</dt><dd>{$form.grouping.html}</dd>
    	<dt>{$form.description.label}</dt><dd>{$form.description.html}</dd>
        <dt>{$form.weight.label}</dt><dd>{$form.weight.html}</dd>
       {if $form.is_default}
        <dt>{$form.is_default.label}</dt><dd>{$form.is_default.html}</dd>
       {/if}
        <dt>{$form.is_active.label}</dt><dd>{$form.is_active.html}</dd>
        <dt>{$form.is_optgroup.label}</dt><dd>{$form.is_optgroup.html}</dd>
       {if $form.contactOptions}{* contactOptions is exposed for email/postal greeting and addressee types to set filter for contact types *}
        <dt>{$form.contactOptions.label}</dt><dd>{$form.contactOptions.html}</dd>
       {/if}
      </dl> 
     {/if}
    <dl>   
      <dt></dt><dd>{$form.buttons.html}</dd>
    </dl>
</fieldset>
</div>
