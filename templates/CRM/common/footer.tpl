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
{include file="CRM/common/version.tpl" assign=version}
{include file="CRM/common/accesskeys.tpl"}
{if isset($contactId) and $contactId} {* Display contact-related footer. *}
    <div class="footer" id="record-log">
    {if isset($legal_identifier) and $legal_identifier}<span class="col">{ts}Legal Identifier{/ts}: {$legal_identifier}</span>{/if} {if isset($external_identifier) and $external_identifier}<span class="col">{ts}External ID{/ts}:&nbsp;{$external_identifier}</span>{/if}{if $action NEQ 2}<span class="col">&nbsp; &nbsp;{ts}CiviCRM ID{/ts}:&nbsp;{$contactId}</span>{/if}
    {if isset($lastModified) and $lastModified}
        <span class="col">
        {ts}Last Change by{/ts} <a href="{crmURL p='civicrm/contact/view' q="action=view&reset=1&cid=`$lastModified.id`"}">{$lastModified.name}</a> ({$lastModified.date|crmDate}) &nbsp;
	{if $changeLog != '0'}
	    <a href="{crmURL p='civicrm/contact/view' q="reset=1&action=browse&selectedChild=log&cid=`$contactId`"}">&raquo; {ts}View Change Log{/ts}</a>
	{/if}
        </span>
    {/if}
    </div>
{/if}

