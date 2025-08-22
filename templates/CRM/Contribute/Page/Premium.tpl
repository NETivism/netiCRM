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
{include file="CRM/Contribute/Form/ContributionPage/Premium.tpl"}
{capture assign=managePremiumsURL}{crmURL p='civicrm/admin/contribute/managePremiums' q="reset=1"}{/capture}
{if $rows}
<div id="ltype">
    {if $products ne null }
        <div class="action-link-button">
            <a class="button" href="{crmURL p='civicrm/admin/contribute/addProductToPage' q="reset=1&action=update&id=$id"}"><i class="zmdi zmdi-plus-circle-o"></i> {ts}Offer Another Premium on this Contribution Page{/ts}</a>
        </div>
    {/if}
    <div class="description">
        <p>{ts 1=$managePremiumsURL}The premiums listed below are currently offered on this Contribution Page. If you have other premiums which are not already being offered on this page, you will see a link below to offer another premium. Use <a href='%1'>Administer CiviCRM &raquo; Manage Premiums</a> to create or enable additional premium choices which can be used on any Contribution page.{/ts}</p>
    </div>
    <div class="form-item">
        {strip}
        <table>
        <tr class="columnheader">
            <th>{ts}Name{/ts}</th>
            <th>{ts}SKU{/ts}</th>
            <th>{ts}Market Value{/ts}</th>
            <th>{ts}Min Contribution{/ts}</th>
            <th>{ts}Weight{/ts}</th>
            <th></th>
        </tr>
        {foreach from=$rows item=row}
        <tr class="{cycle values='odd-row,even-row'} {$row.class}{if NOT $row.is_active} disabled{/if}">
	        <td class="crm-contribution-form-block-product_name">{$row.product_name}</td>	
	        <td class="crm-contribution-form-block-sku">{$row.sku}</td>
            <td class="crm-contribution-form-block-price">{$row.price }</td>
	        <td class="crm-contribution-form-block-min_contribution">{$row.min_contribution}</td>
	        <td class="nowrap crm-contribution-form-block-weight">{$row.weight}</td>
	        <td class="crm-contribution-form-block-action">{$row.action}</td>
        </tr>
        {/foreach}
        </table>
        {/strip}
    </div>
    {if $products ne null }
        <div class="action-link-button">
            <a class="button" href="{crmURL p='civicrm/admin/contribute/addProductToPage' q="reset=1&action=update&id=$id"}"><i class="zmdi zmdi-plus-circle-o"></i> {ts}Offer Another Premium on this Contribution Page{/ts}</a>
        </div>
    {/if}
</div>
{else}
    {if $showForm eq false}
        {if $products ne null  && $activePremiums}
        <div class="messages status">
            {if $enablePremiumsCombination eq false}
                {capture assign=crmURL}{crmURL p='civicrm/admin/contribute/addProductToPage' q="reset=1&action=update&id=$id"}{/capture}
                {ts 1=$crmURL}There are no premiums offered on this contribution page yet. You can <a href='%1'>add one</a>.{/ts}
            {elseif $enablePremiumsCombination eq true}
                {ts 1=$managePremiumsURL}This Contribution Page currently has no premiums. You can <a href='%1'>add a gift combination</a>.{/ts}
            {/if}
        </div>
        {/if}
    {else}
        {ts 1=$managePremiumsURL}There are no active premiums for your site. You can <a href='%1'>create and/or enable premiums here</a>.{/ts}
    {/if}
{/if}
