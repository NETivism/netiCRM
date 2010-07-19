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
{if $action eq 1 or $action eq 2 or $action eq 8}
   {include file="CRM/Admin/Form/MailSettings.tpl"}
{/if}

{if $rows}
<div id="mSettings">
  <p></p>
  <div class="form-item">
    {strip}
      <table cellpadding="0" cellspacing="0" border="0">
        <thead class="sticky">
            <th>{ts}Name{/ts}</th>
            <th>{ts}Server{/ts}</th>
            <th>{ts}Username{/ts}</th>
            <th>{ts}Localpart{/ts}</th>
            <th>{ts}Domain{/ts}</th>
            <th>{ts}Return-Path{/ts}</th>
            <th>{ts}Protocol{/ts}</th>	
            <th>{ts}Source{/ts}</th>
            <!--<th>{ts}Port{/ts}</th>-->
            <th>{ts}Use SSL?{/ts}</th>
            <th>{ts}Default?{/ts}</th>
            <th></th>
        </thead>
        {foreach from=$rows item=row}
          <tr id='rowid{$row.id}' class="{cycle values="odd-row,even-row"}">
              <td>{$row.name}</td>	
              <td>{$row.server}</td>	
              <td>{$row.username}</td>	
              <td>{$row.localpart}</td>	
              <td>{$row.domain}</td>
              <td>{$row.return_path}</td>
              <td>{$row.protocol}</td>
              <td>{$row.source}</td>
              <!--<td>{$row.port}</td>-->
              <td>{if $row.is_ssl eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
              <td>{if $row.is_default eq 1}<img src="{$config->resourceBase}/i/check.gif" alt="{ts}Default{/ts}" />{/if}&nbsp;</td>
              <td>{$row.action|replace:'xx':$row.id}</td>
          </tr>
        {/foreach}
      </table>
    {/strip}

    {if $action ne 1 and $action ne 2}
      <div class="action-link">
        <a href="{crmURL q="action=add&reset=1"}" id="newMailSettings" class="button"><span>&raquo; {ts}New Mail Settings{/ts}</span></a>
      </div>
    {/if}
  </div>
</div>
{else}
    <div class="messages status">
    <dl>
        <dt><img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}"/></dt>
        {capture assign=crmURL}{crmURL p='civicrm/admin/mailSettings' q="action=add&reset=1"}{/capture}
        <dd>{ts 1=$crmURL}There are no Mail Settings present. You can <a href='%1'>add one</a>.{/ts}</dd>
        </dl>
    </div>    
{/if}
