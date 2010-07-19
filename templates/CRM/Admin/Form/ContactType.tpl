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
{* this template is used for adding/editing Contact Type  *}
<div class="form-item">
<fieldset><legend>{if $action eq 1}{ts}New Contact Type{/ts}{elseif $action eq 2}{ts}Edit Contact Type{/ts}{else}{ts}Delete Contact Type{/ts}{/if}</legend>
{if $action eq 8}
  <div class="messages status">
    <dl>
      <dt><img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}" /></dt>
      <dd>    
        {ts}WARNING: {ts}This action cannot be undone.{/ts} {ts}Do you want to continue?{/ts}{/ts}
      </dd>
    </dl>
 </div>
{else}
  <dl>
    <dt>{$form.label.label}</dt>
    <dd>
        {if $action eq 2}
           {include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contact_type' field='label' 
             id= $id }
        {/if}
        {$form.label.html}
    </dd>
    <dt>{$form.parent_id.label}</dt>
        {if $is_parent OR $action EQ 1}
            <dd>&nbsp;{$form.parent_id.html}</dd>
         {else}
            <dd>{ts}{$contactTypeName} (built-in){/ts}</dd>
        {/if}
     <dt>{$form.image_URL.label}</dt>
     <dd>
         {$form.image_URL.html|crmReplace:class:'huge40'}{help id="id-image_URL"}
     </dd> 
     <dt>{$form.description.label}</dt>
     <dd>
        {if $action eq 2}
	  {include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contact_type' field='description' 
             id= $id }
        {/if}
        {$form.description.html}
     </dd>
     {if $is_parent OR $action eq 1}
     <dt>{$form.is_active.label}</dt><dd>{$form.is_active.html}</dd>
     {/if}
  </dl>
{/if}
  <dl> 
    <dt></dt><dd>{$form.buttons.html}</dd>
  </dl> 
</fieldset>
</div>
