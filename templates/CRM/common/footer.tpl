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
{if isset($contactId) and $contactId} {* Display contact-related footer. *}
    <div class="footer" id="record-log">
    <span class="col">
      <div>
      {if $action NEQ 2}<i class="zmdi zmdi-memory"></i> {ts}CiviCRM ID{/ts}: {$contactId}{/if}
      {if isset($legal_identifier) and $legal_identifier}<i class="zmdi zmdi-account-box-o"></i> {ts}ID/Tax Number{/ts}: {$legal_identifier}{/if}
      </div>
      {if isset($external_identifier) and $external_identifier}<div><i class="zmdi zmdi-share"></i> {ts}External ID{/ts}: {$external_identifier}</div>{/if}
    </span>
    {if isset($createdBy) and $createdBy}
    <span class="col">
      <div>
      <i class="zmdi zmdi-time"></i> {ts}Created by{/ts} <a href="{crmURL p='civicrm/contact/view' q="action=view&reset=1&cid=`$createdBy.id`"}">{$createdBy.name}</a> ({$createdBy.date|crmDate})
      </div>
    </span>
    {/if}
    <span class="col">
    {if isset($lastModified) and $lastModified}
      <div>
        <i class="zmdi zmdi-time"></i> {ts}Last Change by{/ts} <a href="{crmURL p='civicrm/contact/view' q="action=view&reset=1&cid=`$lastModified.id`"}">{$lastModified.name}</a> ({$lastModified.date|crmDate})
      </div>
    {/if}
    {if $changeLog != '0'}<div>
      <a href="{crmURL p='civicrm/contact/view' q="reset=1&action=browse&selectedChild=log&cid=`$contactId`"}"><i class="zmdi zmdi-calendar-note"></i>{ts}View Change Log{/ts}</a>
    </div>{/if}
    </span>
    </div>
{/if}

