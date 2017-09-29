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
{if $products}
<div id="premiums" class="crm-group premiums-group">
    {if $context EQ "makeContribution"}

{literal}
<script type="text/javascript">
<!--
// Selects the product (radio button) if user selects an option for that product.
// Putting this function directly in template so they are available for standalone forms.
function selectPremium(optionField) {
    premiumId = optionField.name.slice(8);
    for( i=0; i < document.Main.elements.length; i++) {
        if (document.Main.elements[i].type == 'radio' && document.Main.elements[i].name == 'selectProduct' && document.Main.elements[i].value == premiumId ) {
            element = document.Main.elements[i];
            element.checked = true;
        }
    }
}
//-->
</script>
{/literal}

        <fieldset class="crm-group premiums_select-group">
        {if $premiumBlock.premiums_intro_title}
            <legend>{$premiumBlock.premiums_intro_title}<span class="crm-marker" title="{ts}This field is required.{/ts}">*</span></legend>
        {/if}
        {if $premiumBlock.premiums_intro_text}
            <div id="premiums-intro" class="crm-section premiums_intro-section">
                {$premiumBlock.premiums_intro_text|nl2br}
            </div> 
        {/if}
    {/if}

    {if $context EQ "confirmContribution" OR $context EQ "thankContribution"}
    <div class="crm-group premium_display-group">
        <div class="header-dark">
            {if $premiumBlock.premiums_intro_title}
                {$premiumBlock.premiums_intro_title}
            {else}
                {ts}Your Premium Selection{/ts}
            {/if}
        </div>
    {/if}
    {if $preview}
        {assign var="showSelectOptions" value="1"}
    {/if}
    {strip}
        <table class="premiums-listings">
        {if $showRadioPremium AND !$preview }
            <tr><td colspan="4">{$form.selectProduct.no_thanks.html}</td></tr>
        {/if}
        {foreach from=$products item=row}
        <tr {if $context EQ "makeContribution"} {/if}valign="top"> 
            {if $showRadioPremium }
                {assign var="pid" value=$row.id}
                <td class="premium-selected">{$form.selectProduct.$pid.html}</td>
            {/if}
            {if $row.thumbnail}
            <td class="premium-img"><label for="{$form.selectProduct.$pid.id}"><img src="{$row.thumbnail}" alt="{$row.name}" class="no-border" /></label></td>
            {/if}
	        <td class="premium-info"{if !$row.thumbnail} colspan="2"{/if}>
                <label class="premium-name" for="{$form.selectProduct.$pid.id}">{$row.name}</label>
                <div>{$row.description|nl2br}</div>
                {if ( ($premiumBlock.premiums_display_min_contribution AND $context EQ "makeContribution") OR $preview EQ 1) AND $row.min_contribution GT 0 }
                    {ts 1=$row.min_contribution|crmMoney}(Contribute at least %1 to be eligible for this gift.){/ts}
                {/if}
            {if $showSelectOptions }
                {assign var="pid" value="options_"|cat:$row.id}
                {if $pid}
                    <div class="premium-options">
                      <div>{$form.$pid.html}</div>
                    </div>
                {/if}
            {else}
                <div class="premium-options">
                    <div><strong>{$row.options}</strong></div>
                </div>
            {/if}
            {if $context EQ "thankContribution" AND $is_deductible AND $row.price}
                <div class="premium-tax-disclaimer">
                <p>
                {ts 1=$row.price|crmMoney}The value of this premium is %1. This may affect the amount of the tax deduction you can claim. Consult your tax advisor for more information.{/ts}
                </p>
                </div>
            {/if}
            </td>
        </tr>
        {/foreach}
        </table>
    {/strip}
    {if $context EQ "makeContribution"}
        </fieldset>
    {elseif ! $preview} {* Close premium-display-group div for Confirm and Thank-you pages *}
        </div>
    {/if}
</div>
{/if}

