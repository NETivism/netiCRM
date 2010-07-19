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
<fieldset><legend>{if $action eq 8 }{ts}Selection Options{/ts}{else}{ts}Selection Options{/ts}{/if}</legend>
      {if $action eq 8}
      <div class="messages status">
        <dl>
          <dt><img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}" /></dt>
          <dd>    
          {ts}WARNING: Deleting this option will result in the loss of all data.{/ts} {ts}This action cannot be undone.{/ts} {ts}Do you want to continue?{/ts}
          </dd>
       </dl>
      </div>
     {else}
	<dl>
        <dt>{$form.label.label}</dt><dd>&nbsp;{$form.label.html}</dd>
        <dt>{$form.value.label}</dt><dd>&nbsp;{$form.value.html}</dd>
        <dt>{$form.weight.label}</dt><dd>&nbsp;{$form.weight.html}</dd>
        <dt>{$form.is_active.label}</dt><dd>&nbsp;{$form.is_active.html}</dd>
	{if !$hideDefaultOption}
	<dt>{$form.is_default.label}</dt><dd>&nbsp;{$form.is_default.html}</dd>
	{/if}
	</dl>
      {/if}
    
    
    <div id="crm-submit-buttons" class="form-item">
    <dl>
        <dt>&nbsp;</dt><dd>{$form.buttons.html}</dd>
    </dl>
    </div>

</fieldset>
</div>
