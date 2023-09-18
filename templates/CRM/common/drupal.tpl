{*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.3                                                |
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
{if $config->debug}
{include file="CRM/common/debug.tpl"}
{/if}

<div id="crm-container" class="{$crm_container_class}" lang="{$config->lcMessages|truncate:2:"":true}" xml:lang="{$config->lcMessages|truncate:2:"":true}">
{include file="CRM/common/action.tpl"}
{if $buildNavigation }
    {include file="CRM/common/Navigation.tpl" }
{/if}

{* temporary hack to fix wysiysg editor failure if js compression is on *}
{if $defaultWysiwygEditor eq 2}
   {* we will put this javascript include into quickform *}
   {* quickform can easily override by other modules *}
{/if}

{* Make sure we've tidy url from backend. *}
<div id="printer-friendly">
  <a href="{$printerFriendly}" title="{ts}Print this page.{/ts}" class="print-icon">{ts}Print{/ts}</a>
</div>

{if isset($localTasks) and $localTasks}
   {include file="CRM/common/localNav.tpl"}
{/if}

{include file="CRM/common/status.tpl"}

{if isset($isForm) and $isForm}
    {include file="CRM/Form/$formTpl.tpl"}
{else}
    {include file=$tplFile}
{/if}

{if ! $urlIsPublic}
{include file="CRM/common/footer.tpl"}
{/if}

{include file="CRM/common/footerJs.tpl"}
{* We need to set jquery $ object back to $*}

</div> {* end crm-container div *}
<!-- callback: {$callbackPath} -->

