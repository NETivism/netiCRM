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
{if $products OR $combinations}
<div id="premiums" class="crm-group premiums-group">
    {if $context EQ "makeContribution"}

{literal}
<script type="text/javascript">
cj(document).ready(function($){
  var detectAmount = function(obj) {
    var amount = $(obj).prop('type') == 'number' ? parseFloat($(obj).val()) : parseFloat($(obj).data('amount'));
    var is_recur = parseInt($("input[name=is_recur]:checked").val());
    if (typeof amount === 'number' && amount) {
      if (typeof is_recur === 'number' && is_recur) {
        return filterPremiumByAmount(0, amount);
      }
      else {
        return filterPremiumByAmount(amount, 0);
      }
    }
  }
  var filterPremiumByAmount = function(amt, amt_recur){
    $('tr.product-row, tr.combination-row').addClass('not-available');
    $('tr.product-row input[name=selectProduct], tr.combination-row input[name=selectProduct], tr.product-row.not-available  .premium-options select').prop('disabled', false);
    $('tr.product-row.not-available .premium-info .description, tr.combination-row.not-available .premium-info .description').find('.zmdi-alert-triangle').remove();
    var $available = $("input[name=selectProduct]").filter(function(idx){
      var minContribution = parseFloat($(this).data('min-contribution'));
      var minContributionRecur = parseFloat($(this).data('min-contribution-recur'));
      if (amt < minContribution && amt > 0) {
        return false;
      }
      if (amt_recur < minContributionRecur && amt_recur > 0) {
        if ($(this).data('calculate-mode') == 'first') {
          return false;
        }
        if ($(this).data('calculate-mode') == 'cumulative') {
          var installments = $("input[name=installments]").val() ? $("input[name=installments]").val() : $(this).data('installments');
          installments = parseInt(installments);
          if (installments) {
            if (amt_recur * installments < $(this).data('min-contribution-recur')) {
              return false;
            }
          }
          if (amt_recur * 99 < $(this).data('min-contribution-recur')) {
            return false;
          }
        }
      }
      return true;
    });
    $available.closest('tr.product-row, tr.combination-row').removeClass('not-available');
    if (!$available.filter(":checked").length) {
      $('input[name=selectProduct]').prop('checked', false);
    }
    $('tr.product-row.not-available input[name=selectProduct], tr.combination-row.not-available input[name=selectProduct], tr.product-row.not-available  .premium-options select').prop('disabled', true);
    $('tr.product-row.not-available .premium-info .description, tr.combination-row.not-available .premium-info .description').prepend('<i class="zmdi zmdi-alert-triangle"></i>');
  }
  var initialize = function (){
    if ($("input[name=amount_other]").val()) {
      detectAmount($("input[name=amount_other]")[0]);
    }
    else {
      $("input[name=amount]:checked:eq(0)").each(function(){
        detectAmount(this);
      });
    }
  }
  $("input[name=amount]").click(function(){
    detectAmount(this);
  });
  $("input[name=amount_other]").change(function(){
    detectAmount(this);
  });
  $("input[name=installments]").change(function(){
    initialize();
  });
  $("input[name=is_recur]").change(function(){
    initialize();
  });

  // after page load, use selected value to determin amount
  initialize();
});
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
            <tr>
                <td>{$form.selectProduct.no_thanks.html}</td>
                <td colspan="3"><label for="{$form.selectProduct.no_thanks.id}">{$no_thanks_label}</label></td>
            </tr>
        {/if}
        {if $useCombinations}
            {* Display premium combinations *}
            <!-- DEBUG: useCombinations = {$useCombinations}, combinations count = {if $combinations}{$combinations|@count}{else}0{/if} -->
            {foreach from=$combinations item=combination}
            <tr valign="top" class="combination-row">
                {if $showRadioPremium}
                    {assign var="combination_id" value=$combination.id}
                    <td class="premium-selected">{$form.selectProduct.$combination_id.html}</td>
                {/if}
                {if $combination.thumbnail}
                <td class="premium-img"><label for="{$form.selectProduct.$combination_id.id}"><img src="{$combination.thumbnail}" alt="{$combination.combination_name}" class="no-border" /></label></td>
                {/if}
                <td class="premium-info"{if !$combination.thumbnail} colspan="2"{/if}>
                    <label class="premium-name" for="{$form.selectProduct.$combination_id.id}">{$combination.combination_name}</label>
                    <div class="combination-products">
                        {foreach from=$combination.products item=product name=productLoop}
                            {$smarty.foreach.productLoop.iteration}.{$product.name} x {$product.quantity}<br/>
                        {/foreach}
                    </div>
                    {if ($premiumBlock.premiums_display_min_contribution AND $context EQ "makeContribution") OR $preview EQ 1}
                      {capture assign="limitation"}{/capture}
                      {capture assign="one_time_limit"}{/capture}
                      {capture assign="recur_limit"}{/capture}
                      {if $combination.min_contribution > 0 && (!$is_recur_only || $preview == 1)}
                        {capture assign="one_time_limit"}{ts 1=$combination.min_contribution|crmMoney}one-time support at least %1{/ts}{/capture}
                      {/if}
                      {if $combination.min_contribution_recur > 0 && ($form.is_recur || $preview == 1)}
                        {if $combination.calculate_mode == 'first'}
                          {capture assign="recur_limit"}{ts 1=$combination.min_contribution_recur|crmMoney}first support of recurring payment at least %1{/ts}{/capture}
                        {else if $combination.calculate_mode == 'cumulative'}
                          {capture assign="recur_limit"}{ts 1=$combination.min_contribution_recur|crmMoney}total support of recurring payment at least %1{/ts}{/capture}
                        {/if}
                      {/if}

                      {if $one_time_limit && $recur_limit}
                        {capture assign="limitation"}{$one_time_limit} {ts}or{/ts} {$recur_limit}{/capture}
                      {elseif $one_time_limit}
                        {capture assign="limitation"}{$one_time_limit}{/capture}
                      {elseif $recur_limit}
                        {capture assign="limitation"}{$recur_limit}{/capture}
                      {/if}

                      {if $limitation}
                        <div class="description">
                        {ts 1=$limitation}This gift will be eligible when your %1.{/ts}
                        </div>
                      {/if}
                    {/if}
                </td>
            </tr>
            {/foreach}
        {else}
            {* Display regular products *}
            {foreach from=$products item=row}
            <tr {if $context EQ "makeContribution"} {/if}valign="top" class="product-row"> 
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
                    {if ($premiumBlock.premiums_display_min_contribution AND $context EQ "makeContribution") OR $preview EQ 1}
                      {capture assign="limitation"}{/capture}
                      {capture assign="one_time_limit"}{/capture}
                      {capture assign="recur_limit"}{/capture}
                      {if $row.min_contribution > 0 && (!$is_recur_only || $preview == 1)}
                        {capture assign="one_time_limit"}{ts 1=$row.min_contribution|crmMoney}one-time support at least %1{/ts}{/capture}
                      {/if}
                      {if $row.min_contribution_recur > 0 && ($form.is_recur || $preview == 1)}
                        {if $row.calculate_mode == 'first'}
                          {capture assign="recur_limit"}{ts 1=$row.min_contribution_recur|crmMoney}first support of recurring payment at least %1{/ts}{/capture}
                        {else if $row.calculate_mode == 'cumulative'}
                          {capture assign="recur_limit"}{ts 1=$row.min_contribution_recur|crmMoney}total support of recurring payment at least %1{/ts}{/capture}
                        {/if}
                      {/if}

                      {if $one_time_limit && $recur_limit}
                        {capture assign="limitation"}{$one_time_limit} {ts}or{/ts} {$recur_limit}{/capture}
                      {elseif $one_time_limit}
                        {capture assign="limitation"}{$one_time_limit}{/capture}
                      {elseif $recur_limit}
                        {capture assign="limitation"}{$recur_limit}{/capture}
                      {/if}

                      {if $limitation}
                        <div class="description">
                        {ts 1=$limitation}This gift will be eligible when your %1.{/ts}
                        </div>
                      {/if}
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
                </td>
            </tr>
            {/foreach}
        {/if}
        </table>
    {/strip}
    {if $context EQ "makeContribution"}
        </fieldset>
    {elseif ! $preview} {* Close premium-display-group div for Confirm and Thank-you pages *}
        </div>
    {/if}
</div>
{/if}

