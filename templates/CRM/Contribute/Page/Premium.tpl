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
{capture assign=addPremiumsCombinationURL}{crmURL p='civicrm/admin/contribute/addPremiumsCombinationToPage' q="action=add&reset=1&id=$id"}{/capture}

{* Premium Combinations Table - Show when combination feature is enabled *}
{if $combinations ne null }
    <div class="action-link-button">
        <a class="button" href="{crmURL p='civicrm/admin/contribute/addPremiumsCombinationToPage' q="action=add&reset=1&id=$id"}"><i class="zmdi zmdi-plus-circle-o"></i> {ts}Offer other gift combinations on this Contribution Page{/ts}</a>
    </div>
{/if}
{if $enablePremiumsCombination && $combinations}
<div id="premiums-combinations-section">
  {strip}
  <table>
  <tr class="columnheader">
      <th>{ts}Combination Name{/ts}</th>
      <th>{ts}SKU{/ts}</th>
      <th>{ts}Combination Contents{/ts}</th>
      <th>{ts}Market Value{/ts}</th>
      <th>{ts}Min Contribution{/ts}</th>
      <th>{ts}Weight{/ts}</th>
      <th>{ts}Operation{/ts}</th>
      <th></th>
  </tr>
  {foreach from=$combinations item=combination}
  <tr class="{cycle values='odd-row,even-row'} {$combination.class}{if NOT $combination.is_active} disabled{/if}">
      <td class="crm-contribution-form-block-combination_name">{$combination.combination_name}</td>
      <td class="crm-contribution-form-block-sku">{$combination.sku|default:'-'}</td>
      <td class="crm-contribution-form-block-combination_content">{$combination.combination_content|replace:', ':'<br>'}</td>
      <td class="crm-contribution-form-block-price">
        {if $combination.min_contribution_recur}
          {$combination.min_contribution_recur|crmMoney:$combination.currency}
        {else}
          {$combination.min_contribution|crmMoney:$combination.currency}
        {/if}
      </td>
      <td class="crm-contribution-form-block-min_contribution">{$combination.min_contribution|crmMoney:$combination.currency}</td>
      <td class="nowrap crm-contribution-form-block-weight">
        <span class="nowrap">
          <a href="#" onclick="moveUp('{$combination.id}'); return false;" title="{ts}Move Up{/ts}">↑</a>
          <a href="#" onclick="moveDown('{$combination.id}'); return false;" title="{ts}Move Down{/ts}">↓</a>
        </span>
      </td>
      <td class="crm-contribution-form-block-action">{$combination.action}</td>
  </tr>
  {/foreach}
  </table>
  {/strip}
    {if $combinations ne null }
    <div class="action-link-button">
        <a class="button" href="{crmURL p='civicrm/admin/contribute/addPremiumsCombinationToPage' q="action=add&reset=1&id=$id"}"><i class="zmdi zmdi-plus-circle-o"></i> {ts}Offer other gift combinations on this Contribution Page{/ts}</a>
    </div>
    {/if}
</div>
{/if}

{if $rows && !$enablePremiumsCombination}
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
            {if $enablePremiumsCombination eq false}
            <div class="messages status">
                {capture assign=crmURL}{crmURL p='civicrm/admin/contribute/addProductToPage' q="reset=1&action=update&id=$id"}{/capture}
                {ts 1=$crmURL}There are no premiums offered on this contribution page yet. You can <a href='%1'>add one</a>.{/ts}
            {elseif $enablePremiumsCombination eq true && !$combinations}
            <div class="messages status">
                {ts 1=$addPremiumsCombinationURL}This Contribution Page currently has no premiums. You can <a href='%1'>add a gift combination</a>.{/ts}
            {/if}
        </div>
        {else}
           {ts 1=$managePremiumsURL}There are no active premiums for your site. You can <a href='%1'>create and/or enable premiums here</a>.{/ts}
        {/if}
    {/if}
{/if}

{literal}
<script type="text/javascript">
  cj(document).ready(function($){
    var showHideCombinations = function(obj) {
      if($(obj).is(":checked")) {
        $("#premiums-combinations-section").show();
      } else {
        $("#premiums-combinations-section").hide();
      }
    }
    $("#premiums_combination").click(function(){
      showHideCombinations($(this));
    });
    showHideCombinations($("#premiums_combination"));
  });

  // TODO : Functions for combination weight management
  function moveUp(combinationId) {
    // AJAX call to move combination up - placeholder for future implementation
    return false;
  }
  function moveDown(combinationId) {
    // AJAX call to move combination down - placeholder for future implementation  
    return false;
  }
</script>
{/literal}
