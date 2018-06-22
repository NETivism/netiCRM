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
  <div class="col-xs-12">
    <div class="box mdl-shadow--2dp">
      <div class="box-content">
        <div class="chartist">
        {include file="CRM/common/chartist.tpl" chartist=$chart_this_year}
        </div>
      </div>
    </div>
  </div>
</div>



{literal}
<style type="text/css">
  .bigger{
    font-size: 2em;
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
    margin-bottom: 10px;
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
  <div class="col-xs-12 col-md-4">
    <div class="box mdl-shadow--2dp">
      <div class="box-content">
        <i class="zmdi zmdi-hc-5x zmdi-male" style="float:left;"></i>
        <div>
          <h5 class="kpi-box-title">首次捐款人數</h5>
          <div class="box-detail">
            <span class="bigger"><span class="red">{$duration_count}</span> 人</span>
            {if $duration_count_growth}
              {if $duration_count_is_growth}
                  {assign var="zmdi" value="zmdi-long-arrow-up"}
                  {assign var="verb" value="成長"}
                  {assign var="color" value="blue"}
              {else}
                  {assign var="zmdi" value="zmdi-long-arrow-down"}
                  {assign var="verb" value="下降"}
                  {assign var="color" value="red"}
              {/if}
              <div><span class="{$color}"><i class="zmdi {$zmdi}"></i>較前30天{$verb}<span>{$duration_count_growth}</span>%</span></div>
            {/if}
          </div>

        </div>
      </div>
    </div>
  </div>
  <div class="col-xs-12 col-md-4">
    <div class="box mdl-shadow--2dp">
      <div class="box-content">
        <i class="zmdi zmdi-hc-5x zmdi-money" style="float: left;"></i>
        <div>
          <h5 class="kpi-box-title">最大額捐款</h5>
          <div class="box-detail">
            <span class="bigger"> <span class="red">{$duration_max_amount}</span>元 </span><br>
            來自<a href="{crmURL p='civicrm/contact/view/contribution' q="reset=1&id=`$duration_max_id`&cid=`$duration_max_contact_id`&action=view&context=contribution&selectedChild=contribute" h=0 a=1 fe=1}">{$duration_max_display_name}於{$duration_max_receive_date}</a>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-xs-12 col-md-4">
    <div class="box mdl-shadow--2dp">
      <div class="box-content">
        <i class="zmdi zmdi-hc-5x zmdi-money" style="float: left;"></i>
        <div> 
          <h5 class="kpi-box-title">捐款總金額</h5>
            <div class="box-detail">
              <span class="bigger"><span class="red">{$duration_sum}</span> 元</span>
              {if $duration_sum_growth}
                {if $duration_sum_is_growth}
                    {assign var="zmdi" value="zmdi-long-arrow-up"}
                    {assign var="verb" value="成長"}
                    {assign var="color" value="blue"}
                {else}
                    {assign var="zmdi" value="zmdi-long-arrow-down"}
                    {assign var="verb" value="下降"}
                    {assign var="color" value="red"}
                {/if}
                <div><span class="{$color}"><i class="zmdi {$zmdi}"></i>較前30天{$verb}<span>{$duration_sum_growth}</span>%</span></div>
              {/if}
            </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <h3>過去30天募款頁狀況</h3>
  {foreach from=$contribution_page_status item=page}
  {if $page.duration_count_is_growth}
      {assign var="zmdi" value="zmdi-long-arrow-up"}
      {assign var="verb" value="成長"}
      {assign var="color" value="blue"}
  {else}
      {assign var="zmdi" value="zmdi-long-arrow-down"}
      {assign var="verb" value="下降"}
      {assign var="color" value="red"}
  {/if}
  <div class="col-xs-12 col-md-{$page_col_n}">
    <div class="box mdl-shadow--2dp">
      <div class="box-content">
        <h5>{$page.title}</h5>
        <div>過去30天有<span class="bigger"><span class="red">{$page.duration_count}</span>筆</span>新增捐款</div>
        {if $page.duration_count_growth}
          <div><i class="bigger zmdi {$zmdi}"></i>較前30天<span class="{$color}">{$verb}<span class="bigger">{$page.duration_count_growth}%</span></span></div>
        {/if}
        <span class="grey">總達成金額 {$page.total_amount|crmMoney}{if $page.goal} / {$page.goal|crmMoney}{/if}</span>
        <span class="grey">總人次 {$page.total_count}</span>
        
        <div class="process-wrapper">
          {if $page.goal}
          <span class="process-full"><span class="process-inner" style="width:{if $page.process > 100}100{else}{$page.process}{/if}%;">{$page.process|number_format:2:".":","}%</span></span>
          {/if}
        </div>
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
    </div>
  </div>
  {/foreach}
</div>
<div class="row">
  <div class="col-xs-12 col-md-4">
    <div class="box mdl-shadow--2dp">
      <div class="box-content">
        <h3>過去30天新進捐款（單筆）</h3>
        <table>
          {foreach from=$single_contributions item=contribution}
          <tr>
            <td><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$contribution.contact_id`" h=0 a=1 fe=1}">{$contribution.name}</a></td>
            <td>{$contribution.date}</td>
            <td><a href="{crmURL p='civicrm/contact/view/contribution' q="reset=1&id=`$contribution.id`&cid=`$contribution.contact_id`&action=view&context=contribution&selectedChild=contribute" h=0 a=1 fe=1}">{$contribution.amount|crmMoney}</a></td>
            <td>{$contribution.instrument}</td>
          </tr>
          {/foreach}
        </table>
      </div>
    </div>
  </div>

  
  <div class="col-xs-12 col-md-4">
    <div class="box mdl-shadow--2dp">
      <div class="box-content">
        <h3>過去30天新進捐款（定期定額）</h3>
        <table>
          {foreach from=$recur_contributions item=contribution}
          <tr>
            <td><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$contribution.contact_id`" h=0 a=1 fe=1}">{$contribution.name}</a></td>
            <td>{$contribution.date}</td>
            <td><a href="{crmURL p='civicrm/contact/view/contribution' q="reset=1&id=`$contribution.id`&cid=`$contribution.contact_id`&action=view&context=contribution&selectedChild=contribute" h=0 a=1 fe=1}">{$contribution.amount|crmMoney}</a></td>
            <td>{$contribution.instrument}</td>
          </tr>
          {/foreach}
        </table>
      </div>
    </div>
  </div>

  
  <div class="col-xs-12 col-md-4">
    <div class="box mdl-shadow--2dp">
      <div class="box-content">
        <h3>即將到期的定期定額</h3>
        <table>
          {foreach from=$expire_recur item=contribution}
          <tr>
            <td><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$contribution.contact_id`" h=0 a=1 fe=1}">{$contribution.name}</a></td>
            <td><a href="{crmURL p='civicrm/contact/view/contributionrecur' q="reset=1&id=`$contribution.recur_id`&cid=`$contribution.contact_id`" h=0 a=1 fe=1}">{$contribution.amount|crmMoney}</a></td>
            <td>{$contribution.end_date}</td>
          </tr>
          {/foreach}
        </table>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-xs-12">
    <div class="box mdl-shadow--2dp">
      <div class="box-content">
      <h3>最近30天捐款金額</h3>
      <div>
        <div class="chartist">
        {include file="CRM/common/chartist.tpl" chartist=$chart_duration_sum}
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-xs-12">
    <div class="box mdl-shadow--2dp">
      <div class="box-content">
      <h3>最近30天成交捐款人來源（募款頁的上一個頁面網址）</h3>
      <div>
        <div class="chartist">
        {include file="CRM/common/chartist.tpl" chartist=$chart_duration_track}
        </div>
      </div>
    </div>
  </div>
</div>


  <div class="col-xs-12">
    <div class="box mdl-shadow--2dp">
      <div class="box-content">
      <h3>最近30天縣市捐款金額</h3>
      <div>
        <div class="chartist">
        {include file="CRM/common/chartist.tpl" chartist=$chart_duration_province_sum}
        </div>
      </div>
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
