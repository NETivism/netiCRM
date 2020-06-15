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
{* Form elements for displaying and running action tasks on search results for all component searches. *}
<div id="search-status">
  <table class="form-layout-compressed">
  <tr>
    <td class="font-size12pt" style="width: 40%;">
    {if $savedSearch.name}{$savedSearch.name} ({ts}smart group{/ts}) - {/if}
    {ts count=$pager->_totalItems plural='%count Results'}%count Result{/ts}{if $selectorLabel}&nbsp;-&nbsp;{$selectorLabel}{/if}
    </td>
    <td class="search-criteria">
        {* Search criteria are passed to tpl in the $qill array *}
        {if $qill}
            {include file="CRM/common/displaySearchCriteria.tpl"}
        {/if}
    {if $context == 'Event' && ( $pager->_totalItems ne $participantCount ) }
    <!--{ts}Actual Registered participant count{/ts} : {$participantCount}-->
    {/if}
    </td>
  </tr>
{if $context == 'Contribution'}
  <tr>
    <td colspan="2">
{include file="CRM/Contribute/Page/ContributionTotals.tpl"}
    </td>
  </tr>
{/if}
  <tr>
    <td class="font-size11pt"> {ts}Select Records{/ts}:</td>
    <td class="nowrap">
        {$form.radio_ts.ts_all.html} <label for="{$ts_all_id}">{ts count=$pager->_totalItems plural='All %count records'}The found record{/ts}</label> &nbsp;  {$form.radio_ts.ts_sel.html} <label for="{$ts_sel_id}">{ts}Selected records only{/ts}</label>
    </td>
  </tr>
  <tr>
    <td colspan="2">
    {if $printButtonName}
       {$form.$printButtonName.html} &nbsp; &nbsp;
    {else}
       {$form._qf_Search_next_print.html} &nbsp; &nbsp;
     {/if}
     {$form.task.html}
    {if $actionButtonName}
       {$form.$actionButtonName.html} &nbsp; &nbsp;
    {else}
     {$form._qf_Search_next_action.html} 
   {/if}
    </td>
  </tr>
  </table>
</div>
{literal}
<script type="text/javascript">
cj(document).ready(function($){
  $("input[name=toggleSelect]").click(function(){
    toggleTaskAction(true);
    toggleCheckboxVals('mark_x_', this);
  });
  $("input[name=radio_ts]").click(function(){
    toggleTaskAction(true);
    if($(this).val() == 'ts_all'){
      toggleCheckboxVals('mark_x_', this);
    }
  });
  $("table.selector td > input[type=checkbox]").click(function(){
    $("input[name=radio_ts][value=ts_sel]").trigger("click");
    if(!$(this).prop("checked")){
      $('input[name=toggleSelect]').prop("checked", false)
    }
  });
  $("input[name=radio_ts][value=ts_all]").trigger("click");
});
</script>
{/literal}
