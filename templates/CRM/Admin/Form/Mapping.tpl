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
{* this template is used for adding/editing a tag (admin)  *}
<div class="form-item">
<fieldset><legend>{if $action eq 1}{ts}New Tag{/ts}{elseif $action eq 2}{ts}Edit Mapping{/ts}{else}{ts}Delete Mapping{/ts}{/if}</legend>
    {if $action eq 1 or $action eq 2 }
        <dl>
        <dt>{$form.name.label}</dt><dd>{$form.name.html}</dd>
        <dt>{$form.description.label}</dt><dd>{$form.description.html}</dd>
        <dt>{$form.mapping_type_id.label}</dt><dd>{$form.mapping_type_id.html}</dd>
        </dl>
    {else}
        <div class="status">
        <img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}"/>
        {ts 1=$mappingName}WARNING: Are you sure you want to delete mapping '<b>%1</b>'? This action cannot be undone.{/ts}</div>
    {/if}
    <dl>
    <dt></dt><dd>{$form.buttons.html}</dd>
    </dl>
    <div class="spacer"></div>
</fieldset>
</div>
