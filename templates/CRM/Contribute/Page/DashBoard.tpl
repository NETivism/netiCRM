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
{* CiviContribute DashBoard (launch page) *}


<div class="row">
  <div class="col-md-12">
    <div class="chartist">
    {include file="CRM/common/chartist.tpl" chartist=$chart_this_year}
    </div>
  </div>
</div>



{literal}
<style type="text/css">
  .bigger{
    font-size: 1.5em;
  }
  .red {
    color: red;
  }
  .grey {
    color: grey;
  }
  .blue {
    color: #03a9f4;
  }
  .source-outter {
    display: flex;
  }
  .source-inner {
    flex: 0 0 20%;
  }
  .process-wrapper {
    width: 100%;
    height: 2em;
  }
  .process-full {
    display: block;
    background: #ccc;
    height: 100%;
  }
  .process-inner {
    display: block;
    height: 100%;
    background: black;
    color: white;
    text-align: center;
  }
</style>
{/literal}

<div class="row">
  <h3>過去 30 天捐款概況</h3>
  <div class="col-md-4 col-xs-6">
    <div class="box-content">
      <i class="zmdi zmdi-hc-5x zmdi-male" style="float:left;"></i>
      <div style="float:left">
        <h4 class="kpi-box-title">首次捐款人數</h4>
        <div class="box-detail">
          <span class="bigger"><span class="red">{$last_30_count}</span> 人</span>
          {if $last_30_count_growth}
            {if $last_30_count_is_growth}
                {assign var="zmdi" value="zmdi-long-arrow-up"}
                {assign var="verb" value="成長"}
                {assign var="color" value="blue"}
            {else}
                {assign var="zmdi" value="zmdi-long-arrow-down"}
                {assign var="verb" value="下降"}
                {assign var="color" value="red"}
            {/if}
            <div><i class="bigger zmdi {$zmdi}"></i>較前30天<span class="{$color}">{$verb}<span>{$last_30_count_growth}</span>%</span></div>
          {/if}
        </div>

      </div>
    </div>
  </div>
  <div class="col-md-4 col-xs-6">
    <div class="box-content">
      <i class="zmdi zmdi-hc-5x zmdi-money" style="float: left;"></i>
      <div>
        <h4 class="kpi-box-title">最大額捐款</h4>
        <div class="box-detail">
          <span class="bigger"> <span class="red">{$last_30_max_amount}</span>元 </span><br>
          來自<a href="{crmURL p='civicrm/contact/view/contribution' q="reset=1&id=`$last_30_max_id`&cid=`$last_30_max_contact_id`&action=view&context=contribution&selectedChild=contribute" h=0 a=1 fe=1}">{$last_30_max_display_name}於{$last_30_max_receive_date}</a>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-4 col-xs-6">
    <div class="box-content">
      <i class="zmdi zmdi-hc-5x zmdi-money" style="float: left;"></i>
      <div> 
        <h4 class="kpi-box-title">捐款總金額</h4>
          <div class="box-detail">
            <span class="bigger"><span class="red">{$last_30_sum}</span> 元</span>
            {if $last_30_sum_growth}
              {if $last_30_sum_is_growth}
                  {assign var="zmdi" value="zmdi-long-arrow-up"}
                  {assign var="verb" value="成長"}
                  {assign var="color" value="blue"}
              {else}
                  {assign var="zmdi" value="zmdi-long-arrow-down"}
                  {assign var="verb" value="下降"}
                  {assign var="color" value="red"}
              {/if}
              <div><i class="bigger zmdi {$zmdi}"></i>較前30天<span class="{$color}">{$verb}<span>{$last_30_sum_growth}</span>%</span></div>
            {/if}
          </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <h3>過去30天募款頁狀況</h3>
  {foreach from=$contribution_page_status item=page}
  {if $page.last_30_count_is_growth}
      {assign var="zmdi" value="zmdi-long-arrow-up"}
      {assign var="verb" value="成長"}
      {assign var="color" value="blue"}
  {else}
      {assign var="zmdi" value="zmdi-long-arrow-down"}
      {assign var="verb" value="下降"}
      {assign var="color" value="red"}
  {/if}
    <div class="col-md-{$page_col_n}">
      <h5>{$page.title}</h5>
      <div>過去30天有<span class="bigger"><span class="red">{$page.last_30_count}</span>筆</span>新增捐款</div>
      {if $page.last_30_count_growth}
        <div><i class="bigger zmdi {$zmdi}"></i>較前30天<span class="{$color}">{$verb}<span class="bigger">{$page.last_30_count_growth}%</span></span></div>
      {/if}
      {if $page.goal}
        <div class="process-wrapper"><span class="process-full"><span class="process-inner" style="width:{$page.process}%;">{$page.process}%</span></span></div>
      {/if}
      <div class="grey">總達成金額 {$page.total_amount|crmMoney}{if $page.goal} / {$page.goal|crmMoney}{/if}</div>
      <div class="grey">總捐款人次 {$page.total_count}</div>
      <div><h5>捐款來源</h5>
        <div class="source-outter">
          {foreach from=$page.source item=source}
          <div class="source-inner">
            <div>{$source.type}</div>
            <div>{$source.count}%</div>
          </div>
          {/foreach}
        </div>
      </div>
    </div>
  {/foreach}
</div>

<div class="row">
  <h3>最近30天捐款金額</h3>
  <div>
    <div class="chartist">
    {include file="CRM/common/chartist.tpl" chartist=$chart_last_30_sum}
    </div>
  </div>
</div>

<div class="row">
  <h3>最近30天縣市捐款金額</h3>
  <div>
    <div class="chartist">
    {include file="CRM/common/chartist.tpl" chartist=$chart_last_30_province_sum}
    </div>
  </div>
</div>


{if $pager->_totalItems}
<div class="crm-section dashboard-section">
    <h3>{ts}Recent Contributions{/ts}</h3>
    <div>
        {include file="CRM/Contribute/Form/Selector.tpl" context="dashboard"}
    </div>
</div>
{/if}{literal}
<script type="text/javascript">
       
cj(document).ready( function( ) {
    getChart( );
    cj('#chart_view').click(function( ) {
        if ( cj('#chart_view').hasClass('ui-state-default') ) { 
            cj('#chart_view').removeClass('ui-state-default').addClass('ui-state-active ui-tabs-selected');
            cj('#table_view').removeClass('ui-state-active ui-tabs-selected').addClass('ui-state-default');
            getChart( );
            cj('#tableData').children().html('');
        }
    });
    cj('#table_view').click(function( ) {
        if ( cj('#table_view').hasClass('ui-state-default') ) {
            cj('#table_view').removeClass('ui-state-default').addClass('ui-state-active ui-tabs-selected');
            cj('#chart_view').removeClass('ui-state-active ui-tabs-selected').addClass('ui-state-default');
            buildTabularView();
            cj('#chartData').children().html('');
        }
    });
});        
           
function getChart( ) {
   var year        = cj('#select_year').val( );
   var charttype   = cj('#chart_type').val( );
   var date        = new Date()
   var currentYear = date.getFullYear( );
   if ( !charttype ) charttype = 'bvg';     
   if ( !year ) year           = currentYear;

   var chartUrl = {/literal}"{crmURL p='civicrm/ajax/chart' q='snippet=4' h=0}"{literal};
   chartUrl    += "&year=" + year + "&type=" + charttype;

   cj.ajax({
       url     : chartUrl,
       async    : false,
       success  : function(html){
           cj( "#chartData" ).html( html );
       }	 
   });

}

function buildTabularView( ) {
    var tableUrl = {/literal}"{crmURL p='civicrm/contribute/ajax/tableview' q='snippet=4' h=0}"{literal};
    tableUrl    += "&showtable=1";
    cj.ajax({
        url      : tableUrl,
        async    : false,
        success  : function(html){
            cj( "#tableData" ).html( html );
        }	 
    });
}

</script>
{/literal}
