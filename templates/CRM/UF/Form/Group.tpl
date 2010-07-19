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
{* add/update/view CiviCRM Profile *}       
<div class="form-item">   
    {if $action eq 8 or $action eq 64}
        <fieldset>
            {if $action eq 8}
                <legend>{ts}Delete CiviCRM Profile{/ts}</legend>
            {else}
                <legend>{ts}Disable CiviCRM Profile{/ts}</legend>
            {/if}
            <div class="messages status">
                <dl>
                    <dt><img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}" /></dt>
                    <dd>{$message}</dd>
                </dl>
            </div>
        </fieldset>
    {else}
        <fieldset>
            <legend>{ts}CiviCRM Profile{/ts}</legend>
                <table class="form-layout">
                    <tr>
                        <td class="label">{$form.title.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_uf_group' field='title' id=$gid}{/if}</td>
                        <td class="html-adjust">{$form.title.html}</td>
                    </tr>
                    <tr>
                        <td class="label">{$form.uf_group_type.label} {help id='id-used_for' file="CRM/UF/Form/Group.hlp"}</td>
                        <td class="html-adjust">{$form.uf_group_type.html}&nbsp;{$otherModuleString}</td>
                    </tr>
                    <tr>
                        <td class="label">{$form.weight.label}{if $config->userFramework EQ 'Drupal'} {help id='id-profile_weight' file="CRM/UF/Form/Group.hlp"}{/if}</td>
                        <td class="html-adjust">{$form.weight.html}</td>
                    </tr>
                    <tr>
                        <td class="label">{$form.help_pre.label} {help id='id-help_pre' file="CRM/UF/Form/Group.hlp"}</td>
                        <td class="html-adjust">{$form.help_pre.html}</td>
                    </tr>
                    <tr>
                        <td class="label">{$form.help_post.label} {help id='id-help_post' file="CRM/UF/Form/Group.hlp"}</td>
                        <td class="html-adjust">{$form.help_post.html}</td>
                    </tr>
                    <tr>
                        <td class="label"></td><td class="html-adjust">{$form.is_active.html} {$form.is_active.label}</td>
                    </tr>
                </table>
	</fieldset>
        {* adding advance setting tab *}
        {include file='CRM/UF/Form/AdvanceSetting.tpl'}        
    {/if}
    {if $action ne 4}
        <dl>
            <dt></dt>
            <dd><div id="crm-submit-buttons">{$form.buttons.html}</div></dd>

            <dt></dt>
            <dd></dd>
        </dl>
    {else}
        <div id="crm-done-button">
            <dt></dt>
            <dd>{$form.done.html}</dd>
        </div>
    {/if} {* $action ne view *}
</div>
  
{if $action eq 2 or $action eq 4 } {* Update or View*}
    <div class="action-link">
	<a href="{crmURL p='civicrm/admin/uf/group/field' q="action=browse&reset=1&gid=$gid"}" class="button"><span>&raquo; {ts}View or Edit Fields for this Profile{/ts}</a></span>
    </div>
{/if}

{include file="CRM/common/showHide.tpl"}

{* include jscript to warn if unsaved form field changes *}
{include file="CRM/common/formNavigate.tpl"}

