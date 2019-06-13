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
{if $action eq 2 || $action eq 16}
<div class="form-item">
  {if $pager}
  {include file="CRM/common/pager.tpl" location="top"}
  {/if}
  <table>
    <tr class="columnheader">
      <th>{ts}Contact{/ts} 1 ({ts}duplicate{/ts})</th>
      <th>{ts}Contact{/ts} 2 ({ts}Reserved{/ts})</th>
      <th>{ts}Threshold{/ts}</th>
      <th>{ts}Batch Merge{/ts}?</th>
      <th>{ts}Conflicting Rows{/ts}</th>
      <th></th>
    </tr>
    {foreach from=$main_contacts item=main key=main_id}
        {capture assign=srcLink}<a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$main.srcID`"}">{$main.srcName}</a>{/capture}
        {capture assign=dstLink}<a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$main.dstID`"}">{$main.dstName}</a>{/capture}
	{assign var="qParams" value="reset=1&cid=`$main.srcID`&oid=`$main.dstID`&action=update&rgid=`$rgid`"}
	{if $gid}{assign var="qParams" value="$qParams&gid=`$gid`"}{/if}
        {capture assign=merge}<a target="_blank" href="{crmURL p='civicrm/contact/merge' q="`$qParams`"}">{ts}merge{/ts}</a>{/capture}
        <tr id="dupeRow_{$main.srcID}_{$main.dstID}" class="{cycle values="odd-row,even-row"}">
          <td>{$dstLink} ({ts}ID{/ts}: {$main.dstID})</td>
          <td>{$srcLink} ({ts}ID{/ts}: {$main.srcID})</td>
          <td>{$main.weight}</td>
          <td>{if $main.batchMerge}<i class="zmdi zmdi-check"></i>{else}<i class="zmdi zmdi-close-circle"></i>{/if}</td>
          <td>{if $main.conflicts}{"<br>"|implode:$main.conflicts}{/if}</td>
          <td style="text-align: right;">
	  {if $main.canMerge}
              {$merge}
	      &nbsp;|&nbsp;
	      <a id='notDuplicate' href="#" title={ts}not a duplicate{/ts} onClick="processDupes( {$main.srcID}, {$main.dstID}, 'dupe-nondupe', 'dupe-listing' );return false;">{ts}not a duplicate{/ts}</a>
	  {else}
	       <em>{ts}Insufficient access rights - cannot merge{/ts}</em>
	  {/if}
        </td>
        </tr>
    {/foreach}
  </table>
  {if $cid}
    <table style="width: 45%; float: left; margin: 10px;">
      <tr class="columnheader"><th colspan="2">{ts 1=$main_contacts[$cid]}Merge %1 with{/ts}</th></tr>
      {foreach from=$dupe_contacts[$cid] item=dupe_name key=dupe_id}
        {if $dupe_name}
          {capture assign=link}<a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=$dupe_id"}">{$dupe_name}</a>{/capture}
          {capture assign=merge}<a href="{crmURL p='civicrm/contact/merge' q="reset=1&cid=$cid&oid=$dupe_id"}">{ts}merge{/ts}</a>{/capture}
          <tr class="{cycle values="odd-row,even-row"}">
	    <td>{$link}</td>
	    <td style="text-align: right">{$merge}</td>
	    <td style="text-align: right"><a id='notDuplicate' href="#" title={ts}not a duplicate{/ts} onClick="processDupes( {$main.srcID}, {$main.dstID}, 'dupe-nondupe' );return false;">{ts}not a duplicate{/ts}</a></td>
	    </tr>
        {/if}
      {/foreach}
    </table>
  {/if}
  {if $pager}
  {include file="CRM/common/pager.tpl" location="bottom"}
  {/if}
</div>
<div class="crm-actions-ribbon">
  <a href="{$smarty.server.REQUEST_URI|replace:'action=update':'action=renew'}" class="button">{ts}Refresh{/ts}</a>
{if $context eq 'search'}
   <a href="{$backURL}" class="button"><span>{ts}Done{/ts}</span></a>
{else}
   {capture assign=backURL}{crmURL p="civicrm/contact/dedupefind" q="reset=1&rgid=`$rgid`&action=preview" a=1}{/capture}
   <a href="{$backURL}" class="button"><span>{ts}Done{/ts}</span></a>
{/if}
</div>
{else}
{include file="CRM/Contact/Form/DedupeFind.tpl"}
{/if}

{* process the dupe contacts *}
{include file='CRM/common/dedupe.tpl'}
