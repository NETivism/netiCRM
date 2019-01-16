<div class="crm-block crm-form-block">
  {if !$filters.start}
    {ts}Start Date{/ts}: <input formattype="activityDate" addtime="1" timeformat="2" startoffset="20" endoffset="0" format="yy-mm-dd" name="start" type="text" id="start" class="form-text dateplugin">
    {include file="CRM/common/jcalendar.tpl" elementId=start action=4}
  {/if}
  {if !$filters.end}
    {ts}End Date{/ts}: <input formattype="activityDate" addtime="1" timeformat="2" startoffset="20" endoffset="0" format="yy-mm-dd" name="end" type="text" id="end" class="form-text form-text dateplugin">
    {include file="CRM/common/jcalendar.tpl" elementId=end action=4}
  {/if}
  {if !$filters.start || !$filters.end}
    <a id="submit-filter" class="button" href="{crmURL q=$drill_down_base}"><i class="zmdi zmdi-search-in-page"></i>{ts}Filter{/ts}</a>
  {/if}
</div>
{if $filters}
<div class="crm-block crm-form-block">
  {foreach from=$filters item=filter key=name}
    <span class="filter-box">
      <i class="zmdi zmdi-filter-list"></i>
      {if $name == "start"}
        {$filter.title} &gt;= {$filter.value_display}
      {elseif $name == "end"}
        {$filter.title} &lt;= {$filter.value_display}
      {else}
        {$filter.title}={$filter.value_display}
      {/if}
      <a href="{crmURL q=$filter.url}" class="zmdi zmdi-close-circle"></a></span>
  {/foreach}
</div>
{/if}
<div class="crm-accordion-wrapper crm-contribution_search_form-accordion {if $filters}crm-accordion-closed{else}crm-accordion-open{/if}">
  <div class="crm-accordion-header crm-master-accordion-header">
    <div class="zmdi crm-accordion-pointer"></div> {ts}Summary{/ts}
  </div><!-- /.crm-accordion-header -->
  <div class="crm-accordion-body">
    {if $chart_track}
    <div class="chart-display">
      {include file="CRM/common/chartist.tpl" chartist=$chart_track}
    </div>
    {/if}
  </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->

<div class="crm-block crm-content-block">
{include file="CRM/common/pager.tpl" location="top"}
{if $rows }
{include file="CRM/common/jsortable.tpl"}
{strip}
<table id="crm-track">
  <thead>
  <tr>
  {foreach from=$columnHeaders item=header}
    <th>
    {if $header.sort}
      {assign var='key' value=$header.sort}
      {$sort->_response.$key.link}
    {else}
      {$header.name}
    {/if}
    </th>
  {/foreach}
  </tr>
  </thead>
  {counter start=0 skip=1 print=false} 
  {foreach from=$rows item=row}
  <tr class="{cycle values="odd-row,even-row"}">
  {foreach from=$row item=value}
    <td>{$value}</td>
  {/foreach}
  </tr>
  {/foreach}
</table>
{/strip}
{else}
   <div class="messages status">
        &nbsp;
        {ts 1=$title}There are currently no %1.{/ts}
    </div>    
{/if}  

{include file="CRM/common/pager.tpl" location="bottom"}
</div>

{literal}
<script type="text/javascript">
cj(function() {
  cj('a#submit-filter').click(function(e){
    var href = cj(this).attr('href');
    var appendQuery = [];
    if (cj('#start').val()) {
      appendQuery.push('start='+cj('#start').val());
    }
    if (cj('#end').val()) {
      appendQuery.push('end='+cj('#end').val());
    }
    if (appendQuery.length > 0){
      href += '&'+appendQuery.join('&');
      cj(this).attr('href', href); 
    }
    console.log(href);
  });
  cj().crmaccordions();
  cj('.crm-accordion-header').click(function() {
    cj('.crm-accordion-body').find('.chartist-chart').each(function(i, e) {
      setTimeout(function(){
        e.__chartist__.update();
      }, 300);
    });
  });
});
</script>
{/literal}
{if $pager and ( $pager->_totalPages > 1 )}
{literal}
<script type="text/javascript">
  var totalPages = {/literal}{$pager->_totalPages}{literal};
  cj( function ( ) {
    cj("#crm-container .crm-pager input.form-submit").click( function( ) {
      submitPagerData( this );
    }); 
  });
  function submitPagerData( el ) {
      var urlParams= '';
      var jumpTo   = cj(el).parent( ).children('input[type=text]').val( );
      if ( parseInt(jumpTo)== "Nan" ) jumpTo = 1;
      if ( jumpTo > totalPages ) jumpTo = totalPages;
      {/literal} 
      {foreach from=$pager->_linkData item=val key=k }
      {if $k neq 'crmPID' && $k neq 'force' && $k neq 'q' } 
      {literal}
        urlParams += '&{/literal}{$k}={$val}{literal}';
      {/literal}
      {/if}
      {/foreach}
      {literal}
      urlParams += '&crmPID='+parseInt(jumpTo);
      var submitUrl = {/literal}'{crmURL p="civicrm/mailing/report/event" q="force=1" h=0 }'{literal};
      document.location = submitUrl+urlParams;
  }
</script>
{/literal}
{/if}
