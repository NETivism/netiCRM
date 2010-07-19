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
{* add/update/view price set *}
{capture assign="enableComponents"}{crmURL p='civicrm/admin/setting/component' q="reset=1"}{/capture}
<div class="form-item">
    <fieldset><legend>{ts}Price Set{/ts}</legend>
    <div id="help">
        <p>
        {ts}Use this form to setup the title and group-level help of each set of Price fields.{/ts}
        </p>
    </div>
    <dl class="html-adjust">
    <dt>{$form.title.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_price_set' field='title' id=$sid}{/if}</dt><dd>{$form.title.html}</dd>
    <dt>&nbsp;</dt><dd class="description">{ts}The name of this Price Set{/ts}</dd>
    <dt>{$form.extends.label} </dt><dd>
    {if $extends eq false}
	<div class="status message">{ts 1=$enableComponents}No Components have been enabled for your site that can be configured with the price sets. Click <a href='%1'>here</a> if you want to enable CiviEvent/CiviContribute for your site.{/ts}</div>
    {else}
	{$form.extends.html}
    {/if}</dd>
    <dt>{$form.help_pre.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_price_set' field='help_pre' id=$sid}{/if}</dt><dd>{$form.help_pre.html}</dd>
    <dt>&nbsp;</dt><dd class="description">{ts}Explanatory text displayed at the beginning of this group of fields.{/ts}</dd>
    <dt>{$form.help_post.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_price_set' field='help_post' id=$sid}{/if}</dt><dd>{$form.help_post.html}</dd>
    <dt>&nbsp;</dt><dd class="description">{ts}Explanatory text displayed below this group of fields.{/ts}</dd>
    <dt>{$form.is_active.label}</dt><dd>{$form.is_active.html}</dd>
    {*if $action ne 4*}
        <dt></dt>
        <dd>
        <div id="crm-submit-buttons">{$form.buttons.html}</div>
        </dd>
    {*else}
        <dt></dt>
        <dd>
        <div id="crm-done-button">{$form.done.html}</div>
        </dd>
    {/if*} {* $action ne view *}
    </dl>
    </fieldset>
</div>
{if $action eq 2 or $action eq 4} {* Update or View*}
    <p></p>
    <div class="action-link">
    <a href="{crmURL p='civicrm/admin/price/field' q="action=browse&reset=1&sid=$sid"}">&raquo;  {ts}Fields for this Set{/ts}</a>
    </div>
{/if}
