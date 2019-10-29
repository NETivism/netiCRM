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

{if $smarty.get.snippet eq 4}
    {if $isForm}
        {include file="CRM/Form/default.tpl"}
    {else}
        {include file=$tplFile}
    {/if}
{else}
    {if $smarty.get.snippet eq 2}
    {include file="CRM/common/print.tpl"}
    {else}
    <div id="crm-container-snippet" class="crm-container" bgColor="white">

    {* Check for Status message for the page (stored in session->getStatus). Status is cleared on retrieval. *}
    {if $session->getStatus(false)}
    {assign var="statuses" value=$session->getStatus(true)}
    {foreach from=$statuses key=message_type item=status}
    <div class="messages {$message_type}">
        {if is_array($status)}
            {foreach name=statLoop item=statItem from=$status}
                {if $smarty.foreach.statLoop.first}
                    {if $statItem}<h3>{$statItem}</h3><div class='spacer'></div>{/if}
                {else}               
                   <ul><li>{$statItem}</li></ul>
                {/if}                
            {/foreach}
        {else}
            {$status}
        {/if}
    </div>
    {/foreach}
    {/if}

    <!-- .tpl file invoked: {$tplFile}. Call via form.tpl if we have a form in the page. -->
    {if $isForm}
        {include file="CRM/Form/default.tpl"}
    {else}
        {include file=$tplFile}
    {/if}

    {include file="CRM/common/action.tpl" isSnippet = true}
    </div> {* end crm-container-snippet div *}
    {/if}
{/if}
{if $additional_snippet_css}<style type="text/css">
{$additional_snippet_css}
</style>{/if}
{if $additional_snippet_js}<script type="text/javascript">
{$additional_snippet_js}
</script>{/if}
