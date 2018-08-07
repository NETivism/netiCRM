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

{if $isAdmin}
 {capture assign=configPagesURL}{crmURL p="civicrm/admin/contribute" q="reset=1"}{/capture}
<div class="float-right">
     <span><a href="{$configPagesURL}" class="button"><span>{ts}Manage Contribution Pages{/ts}</span></a></span>
</div>
{/if}

{if $chart_this_year}
<div class="row">
  <div class="col-xs-12">
    <div class="box mdl-shadow--2dp">
      <div class="box-content">
        <h3 class="kpi-box-title">{ts}Contributions Since This Year{/ts}</h3>
        <div class="this-year-info-wrapper">
          <div class="this-year-info this-year-info-non-recur">
            {ts}Non-recurring Total Amount{/ts} {$this_year_sum_non_recur|crmMoney}
            {ts 1=$this_year_people_non_recur}Number of Donation Donors: %1{/ts} 
            {ts 1=$this_year_count_non_recur}Total Contributions: %1{/ts}
          </div>
          <div class="this-year-info this-year-info-recur">
            {ts}Recurring Total Amount{/ts} {$this_year_sum_recur|crmMoney}
            {ts 1=$this_year_people_recur}Number of Donation Donors: %1{/ts} 
            {ts 1=$this_year_count_recur}Total Payments: %1{/ts}
          </div>
        </div>
        <div class="chartist">
        {include file="CRM/common/chartist.tpl" chartist=$chart_this_year}
        </div>
      </div>
    </div>
  </div>
</div>
{/if}

{if $chartRecur}
<div class="row">
  <div class="col-xs-12">
    <div class="box mdl-shadow--2dp">
      <div class="box-content">
        <div class="crm-section dashboard-section">
          <div class="chartist">
            {include file="CRM/common/chartist.tpl" chartist=$chartRecur}
          </div>
          {capture assign=frequency_unit}{ts}{$frequencyUnit}{/ts}{/capture}
          {foreach from=$summaryRecur key=currency item=summary}
            <ul>
              <li>{ts 1=$summaryTime}Generated at %1{/ts} (<a href="{crmURL p=civicrm/contribute q=reset=1&update=1}">{ts}Update{/ts}</a>) - <strong>{ts 1=$summary.contributions 2=$frequency_unit 3=$summary.contacts 4=$summary.amount|crmMoney}There are %1 contributions in this %2 by %3 contacts. Total amount: %4{/ts}</strong></li>
            </ul>
          {/foreach}
        </div>
      </div>
    </div>
  </div>
</div>
{/if}

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
  .progress-wrapper {
    width: 100%;
    height: 2em;
    margin-bottom: 10px;
  }
  .progress-full {
    display: block;
    background: #ccc;
    height: 100%;
  }
  .progress-inner {
    display: block;
    height: 100%;
    background: black;
    color: white;
    text-align: center;
  }
  .date-selector {
    position: sticky;
    width: 100%;
    top: 0px;
    z-index: 99;
    background-color: #FFFFFFCC;
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
  }
  .more{
    text-align: right;
  }
  .this-year-info {
    position: relative;
    padding-left: 25px;
  }
  .this-year-info::before,
  .this-year-info::after {
    display: block;
    width: 20px;
    height: 20px;
    content: " ";
    float: left;
    background-color: white;
    position: absolute;
    left: 0;
    top: 0;
  }
  .this-year-info-recur::after {
    background-color: rgba(215, 2, 6, .1);
  }
  .this-year-info-non-recur::after {
    background-color: rgba(240, 91, 79, .1);
  }
</style>
{/literal}

<div class="date-selector">
  <h3>{ts 1=$days}In %1 days{/ts} {ts}Contribution Summary{/ts}</h3>
  <div>
    <span class="labels">
      {ts}Start Date{/ts}
    </span>
    <span class="fields">
      <input formattype="activityDate" addtime="1" timeformat="2" startoffset="20" endoffset="0"  format="yy-mm-dd" name="start_date" type="text" id="start_date" class="form-text dateplugin" value="{$start_date}"/>
      <input timeformat="1" name="start_date_time" type="hidden" id="start_date_time" class="form-text" />
      {include file="CRM/common/jcalendar.tpl" elementName=start_date elementId=start_date action=4}
    </span>
    <span class="labels">
      {ts}End Date{/ts}
    </span>
    <span class="fields">
      <input formattype="activityDate" addtime="1" timeformat="2" startoffset="20" endoffset="0" format="yy-mm-dd" name="end_date" type="text" id="end_date" class="form-text dateplugin" value="{$end_date}"/>
      <input timeformat="1" name="end_date_time" type="hidden" id="end_date_time" class="form-text" />
      {include file="CRM/common/jcalendar.tpl" elementName=end_date elementId=end_date action=4}
    </span>
    <span>
      <button type="button" id="date-selector-button">{ts}Submit{/ts}</button>
      {literal}
        <script>
          cj('#date-selector-button').click(function(){
            var url = '/civicrm/contribute?reset=1';
            var start_date = cj('#start_date').val();
            var end_date = cj('#end_date').val();
            if(start_date){
              url += '&start_date='+start_date;
            }
            if(end_date){
              url += '&end_date='+end_date;
            }
            location.href = url;
          });
        </script>
      {/literal}
    </span>
  </div>
</div><!--date selector-->
<div class="row">
  <div class="col-xs-12 col-md-4">
    <div class="box mdl-shadow--2dp">
      <div class="box-content">
        <i class="zmdi zmdi-hc-5x zmdi-male"></i>
        <div>
          <h5 class="kpi-box-title">{ts}Number of First Time Donation Donors{/ts}</h5>
          <div class="box-detail">
            <span class="bigger">
              {$duration_count}
            </span>
            {if $duration_count_growth}
              <div>{include file="CRM/common/growth_sentence.tpl" growth=$duration_count_growth}</div>
            {/if}
            {if $debug}
            <div class="more">
              <a href="{crmURL p='civicrm/search/FirstTimeDonor' q='force=1'}">{ts}more{/ts}</a>
            </div>
            {/if}
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-xs-12 col-md-4">
    <div class="box mdl-shadow--2dp">
      <div class="box-content">
        <i class="zmdi zmdi-hc-5x zmdi-money"></i>
        <div>
          <h5 class="kpi-box-title">{ts}Contribution with Maximum Amount{/ts}</h5>
          <div class="box-detail">
            <span class="bigger">{$duration_max_amount|crmMoney}</span><br>
            {ts}From{/ts}<a href="{crmURL p='civicrm/contact/view/contribution' q="reset=1&id=`$duration_max_id`&cid=`$duration_max_contact_id`&action=view&context=contribution&selectedChild=contribute" h=0 a=1 fe=1}">{$duration_max_display_name}{ts}in{/ts}{$duration_max_receive_date}</a>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-xs-12 col-md-4">
    <div class="box mdl-shadow--2dp">
      <div class="box-content">
        <i class="zmdi zmdi-hc-5x zmdi-money"></i>
        <div> 
          <h5 class="kpi-box-title">{ts}Total Amount{/ts}</h5>
            <div class="box-detail">
              <span class="bigger">
                {$duration_sum|crmMoney}
              </span>
              {if $duration_sum_growth}
                <div>{include file="CRM/common/growth_sentence.tpl" growth=$duration_sum_growth}</div>
              {/if}
              {if $debug}
              <div class="more">
                <a class="more" href="{crmURL p='civicrm/contribute/search' q="reset=1&force=1&start=`$start_date`&end=`$end_date`&status=1"}">
                  {ts}more{/ts}
                </a>
              </div>
              {/if}
            </div>
        </div>
      </div>
    </div>
  </div>
</div>

{if $contribution_page_stat}
<div class="row">
  <h3>{ts 1=$days}In %1 days{/ts} {ts}Contribution Page Status{/ts}</h3>
  {foreach from=$contribution_page_stat item=stat}
  <div class="col-xs-12 col-md-{$page_col_n}">
    <div class="box mdl-shadow--2dp">
      <div class="box-content">
        {include file="CRM/common/ContributionPageStatusCard.tpl" statistics=$stat}
      </div>
    </div>
  </div>
  {/foreach}
</div>
{/if}


<div class="row">
  <div class="col-xs-12 col-md-4">
    <div class="box mdl-shadow--2dp">
      <div class="box-content">
        <h3>{ts 1=$days}In %1 days{/ts} {ts}Non-recurring Contribution{/ts}</h3>
        <table>
          {foreach from=$non_recur_contributions item=contribution}
          <tr>
            <td><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$contribution.contact_id`" h=0 a=1 fe=1}">{$contribution.name}</a></td>
            <td>{$contribution.date}</td>
            <td><a href="{crmURL p='civicrm/contact/view/contribution' q="reset=1&id=`$contribution.id`&cid=`$contribution.contact_id`&action=view&context=contribution&selectedChild=contribute" h=0 a=1 fe=1}">{$contribution.amount|crmMoney}</a></td>
            <td>{$contribution.instrument}</td>
          </tr>
          {/foreach}
        </table>
        <div class="more" >
          <a href="{crmURL p='civicrm/contribute/search' q="reset=1&force=1&start=`$start_date`&end=`$end_date`&status=1&recur=0"}">{ts}more{/ts}</a>
        </div>
      </div>
    </div>
  </div>

  
  <div class="col-xs-12 col-md-4">
    <div class="box mdl-shadow--2dp">
      <div class="box-content">
        <h3>{ts 1=$days}In %1 days{/ts} {ts}New Recurring Contribution{/ts}</h3>
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
        <div class="more">
          <a href="{crmURL p='civicrm/contact/search/custom' q="force=1&reset=1&csid=17&start=`$start_date`&end=`$end_date`"}">{ts}more{/ts}</a>
        </div>
      </div>
    </div>
  </div>

  
  <div class="col-xs-12 col-md-4">
    <div class="box mdl-shadow--2dp">
      <div class="box-content">
        <h3>{ts}End of recurring contribution{/ts}</h3>
        <table>
          {foreach from=$due_recur item=contribution}
          <tr>
            <td><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$contribution.contact_id`" h=0 a=1 fe=1}">{$contribution.name}</a></td>
            <td><a href="{crmURL p='civicrm/contact/view/contributionrecur' q="reset=1&id=`$contribution.recur_id`&cid=`$contribution.contact_id`" h=0 a=1 fe=1}">{$contribution.amount|crmMoney}</a></td>
            <td>{$contribution.end_date}</td>
          </tr>
          {/foreach}
        </table>
        <div class="more">
          <a href="{crmURL p='civicrm/contact/search/custom' q="mode=booster&force=1&reset=1&csid=17"}">{ts}more{/ts}</a>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-xs-12">
    <div class="box mdl-shadow--2dp">
      <div class="box-content">
      <h3>{ts 1=$days}In %1 days{/ts} {ts}Contribution Amount{/ts}</h3>
      <div>
        <div class="chartist">
        {include file="CRM/common/chartist.tpl" chartist=$chart_duration_sum}
        </div>
      </div>
      <div class="more">
        <a href="{crmURL p='civicrm/contribute/search' q="reset=1&force=1&start=`$start_date`&end=`$end_date`&status=1"}">{ts}more{/ts}</a>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-xs-12">
    <div class="box mdl-shadow--2dp">
      <div class="box-content">
      <h3>{ts 1=$days}In %1 days{/ts} {ts}Contribution Amount by Flow Source{/ts}</h3>
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
      <h3>{ts 1=$days}In %1 days{/ts} {ts}Contribution Amount by Province{/ts}</h3>
      <div>
        <div class="chartist">
        {include file="CRM/common/chartist.tpl" chartist=$chart_duration_province_sum}
        </div>
      </div>
    </div>
  </div>
</div>

{literal}
<script type="text/javascript">
       
cj(document).ready( function( ) {
    if (cj("#admin-header").length) {
      var stickytop = cj("#admin-header").outerHeight();
      cj(".crm-container .date-selector").css("top", stickytop+"px");
    }
});

</script>
{/literal}
