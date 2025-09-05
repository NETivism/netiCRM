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
{if $id and $action eq 2}
    {include file="CRM/Contribute/Page/ContributionPageEdit.tpl"}
{else}
    {capture assign=newPageURL}{crmURL p='civicrm/admin/contribute/add' q='action=add&reset=1'}{/capture}
    <div id="help">
    	 {ts}CiviContribute allows you to create and maintain any number of Online Contribution Pages. You can create different pages for different programs or campaigns - and customize text, amounts, types of information collected from contributors, etc.{/ts} {help id="id-intro"}
    </div>

    {include file="CRM/Contribute/Form/SearchContribution.tpl"}  
    {if NOT ($action eq 1 or $action eq 2) }
      <div class="action-link-button">
	      <a href="{$newPageURL}" class="button"><span><i class="zmdi zmdi-plus-circle-o"></i>{ts}Add Contribution Page{/ts}</span></a>
        <a class="button" href="{crmURL p="civicrm/track/report" q="reset=1&ptype=civicrm_contribution_page"}"><i class="zmdi zmdi-chart"></i><span>{ts}Traffic Source{/ts}</span></a>
        <a class="button" href="{crmURL p="civicrm/admin/pcp" q="reset=1"}"><i class="zmdi zmdi-accounts-list"></i><span>{ts}Manage Personal Campaign Pages{/ts}</span></a> {help id="id-pcp-intro" file="CRM/Contribute/Page/PCP.hlp"}
      </div>
    {/if}

    {if $rows}
    	<div id="configure_contribution_page">
             {strip}
        
	     {include file="CRM/common/pager.tpl" location="top"}
             {include file="CRM/common/pagerAToZ.tpl"}
             {* handle enable/disable actions *}
             {include file="CRM/common/enableDisable.tpl"}
	     {include file="CRM/common/jsortable.tpl"}
             <table id="options" class="display">
               <thead>
               <tr>
            	 <th id="sortable">{ts}ID{/ts}</th>
               <th>{ts}Title{/ts}</th>
               <th>{ts}Contribution Type{/ts}</th>
            	 <th>{ts}Enabled?{/ts}</th>
		 <th></th>
               </tr>
               </thead>
               {foreach from=$rows item=row}
                 <tr id="row_{$row.id}" class="{cycle values="odd-row,even-row"} {$row.class}{if NOT $row.is_active} disabled{/if}">
                     <td>{$row.id}</td>
                     <td><strong><a href="{crmURL a=true p='civicrm/admin/contribute' q="action=update&reset=1&id=`$row.id`"}">{$row.title}</a></strong></td>
                     <td>{$row.contribution_type}</td>
                     <td id="row_{$row.id}_status">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}{if $row.is_special eq 1}({ts}Special Style{/ts}){/if}</td>
		     <td class="crm-contribution-page-actions right nowrap">
		
			{if $row.configureActionLinks}	
		  	<div class="crm-contribution-page-configure-actions">
		       	     {$row.configureActionLinks|replace:'xx':$row.id}
		  	</div>
                  	{/if}

        {if $row.contributionLinks}	
		  	<div class="crm-contribution-online-contribution-actions">
		       	     {$row.contributionLinks|replace:'xx':$row.id}
		  	</div>
		  	{/if}

		  	{if $row.onlineContributionLinks}	
		  	<div class="crm-contribution-search-contribution-actions">
		       	     {$row.onlineContributionLinks|replace:'xx':$row.id}
		  	</div>
		  	{/if}
		  	{if $row.exportLinks}	
		  	<div class="crm-contribution-search-contribution-actions">
		      {$row.exportLinks|replace:'xx':$row.id}
		  	</div>
		  	{/if}

		  	<div class="crm-contribution-page-more">
                       	     {$row.action|replace:'xx':$row.id}
                  	</div>

		     </td>

            	 </tr>
               {/foreach}
	     </table>
        
             {/strip}
    	</div>
    {else}
	{if $isSearch eq 1}
    	<div class="status messages">
                {capture assign=browseURL}{crmURL p='civicrm/contribute/manage' q="reset=1"}{/capture}
                    {ts}No available Contribution Pages match your search criteria. Suggestions:{/ts}
                    <div class="spacer"></div>
                    <ul>
                    <li>{ts}Check your spelling.{/ts}</li>
                    <li>{ts}Try a different spelling or use fewer letters.{/ts}</li>
                    <li>{ts}Make sure you have enough privileges in the access control system.{/ts}</li>
                    </ul>
                    {ts 1=$browseURL}Or you can <a href='%1'>browse all available Contribution Pages</a>.{/ts}
    	</div>
    	{else}
    	<div class="messages status">
              &nbsp;
             {ts 1=$newPageURL}No contribution pages have been created yet. Click <a accesskey="N" href='%1'>here</a> to create a new contribution page using the step-by-step wizard.{/ts}
    	</div>
      	{/if}
    {/if}
{/if}
