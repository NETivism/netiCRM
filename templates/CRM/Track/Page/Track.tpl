<style>{literal}
.export-confirm-dialog {
  position: fixed ;
  top: 210px;
  left: 50%;
  transform: translate(-50%, -50%) ;
  margin: 0 ;
}
.track-filter-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  margin-bottom: 12px;
}
.track-filter-item  {
  display: flex;
  flex-direction: column;
  width: 180px;
}
input[type="text"].form-text{
  width:190px
}
.track-filter-item label {
  font-weight: bold;
  margin-bottom: 4px;
  white-space: nowrap;
}
.track-filter-item input,
.track-filter-item select {
  box-sizing: border-box;
}

.crm-form-block label {
  font-weight: bold;
}
{/literal}</style>
<div class="crm-block crm-form-block">
  <div class="track-filter-grid">
    {if !$filters.start}
    <div class="track-filter-item">
      <label>{ts}Start Date{/ts}</label>
      <input formattype="activityDate" addtime="1" timeformat="2" startoffset="20" endoffset="0" format="yy-mm-dd" name="start" type="text" id="start" class="form-text dateplugin" value="{$defaultStartDate}">
      {include file="CRM/common/jcalendar.tpl" elementId=start action=4}
    </div>
    {/if}
    {if !$filters.end}
    <div class="track-filter-item">
      <label>{ts}End Date{/ts}</label>
      <input formattype="activityDate" addtime="1" timeformat="2" startoffset="20" endoffset="0" format="yy-mm-dd" name="end" type="text" id="end" class="form-text dateplugin">
      {include file="CRM/common/jcalendar.tpl" elementId=end action=4}
    </div>
    {/if}
    
    <div class="track-filter-item">
      <label>{ts}Page Type{/ts}</label>
      <select id="filter-ptype" class="form-select">
        <option value="">-- {ts}All{/ts} --</option>
        {foreach from=$pageTypes item=label key=value}
          <option value="{$value}"{if $currentPageType eq $value} selected="selected"{/if}>{$label}</option>
        {/foreach}
      </select>
    </div>
    {if !$filters.page_title}
    <div class="track-filter-item">
      <label>{ts}Page Name{/ts}</label>
      <input type="text" id="filter-page-title" class="form-text">
    </div>
    {/if}
    {if !$filters.state}
    <div class="track-filter-item">
      <label>{ts}Visit State{/ts}</label>
      <select id="filter-state" class="form-select">
        <option value="">-- {ts}Select{/ts} --</option>
        {foreach from=$trackStates item=label key=value}
          <option value="{$value}">{$label}</option>
        {/foreach}
      </select>
    </div>
    {/if}
     
  </div>
  <div class="crm-accordion-wrapper crm-accordion-open">
    <div class="crm-accordion-header">
      <div class="zmdi crm-accordion-pointer"></div>
      {ts}Traffic Source{/ts}
    </div>
    <div class="crm-accordion-body">
      <table class="form-layout">
        <tbody>
          <tr>
            {if !$filters.rtype}
            <td>
              <div class="track-filter-item">
                <label>{ts}Referrer Type{/ts}</label>
                <select id="filter-rtype" class="form-select">
                <option value="">-- {ts}Select{/ts} --</option>
                  {foreach from=$referrerTypes item=label key=value}
                <option value="{$value}">{$label}</option>
                  {/foreach}
              </select>
              </div>
            </td>
            {/if}
            {if !$filters.rnetwork}
            <td>
              <label>{ts}Referrer Network{/ts}</label><br>
              <div class="crm-form-elem crm-form-textfield"><input type="text" id="filter-rnetwork" class="form-text"></div>
            </td>
            {else}<td></td>{/if}
           
            {if !$filters.referrer_url}
            <td>
              <label>{ts}Referrer URL{/ts}</label><br>
              <div class="crm-form-elem crm-form-textfield"><input type="text" id="filter-referrer-url" class="form-text"></div>
            </td>
            {else}<td></td>{/if}
            {if !$filters.landing}
            <td>
              <label>{ts}Landing Page{/ts}</label><br>
              <div class="crm-form-elem crm-form-textfield"><input type="text" id="filter-landing" class="form-text"></div>
            </td>
            {else}<td></td>{/if}
            {if !$filters.entity_id}
            <td>
              <label>{ts}Entity ID{/ts}</label><br>
              <div class="crm-form-elem crm-form-textfield"><input type="text" id="filter-entity-id" class="form-text" placeholder="{ts}ID or 'null'{/ts}"></div>
            </td>
            {else}<td></td>{/if}
            
          </tr>
          <tr>
            {if !$filters.utm_source}
            <td>
              <label>UTM Source</label><br>
              <div class="crm-form-elem crm-form-textfield"><input type="text" id="filter-utm-source" class="form-text"></div>
            </td>
            {else}<td></td>{/if}
             {if !$filters.utm_medium}
            <td>
              <label>UTM Medium</label><br>
              <div class="crm-form-elem crm-form-textfield"><input type="text" id="filter-utm-medium" class="form-text"></div>
            </td>
            {else}<td></td>{/if}
            {if !$filters.utm_campaign}
            <td>
              <label>UTM Campaign</label><br>
              <div class="crm-form-elem crm-form-textfield"><input type="text" id="filter-utm-campaign" class="form-text"></div>
            </td>
            {else}<td></td>{/if}
            {if !$filters.utm_term}
            <td>
              <label>UTM Term</label><br>
              <div class="crm-form-elem crm-form-textfield"><input type="text" id="filter-utm-term" class="form-text"></div>
            </td>
            {else}<td></td>{/if}
            {if !$filters.utm_content}
            <td>
              <label>UTM Content</label><br>
              <div class="crm-form-elem crm-form-textfield"><input type="text" id="filter-utm-content" class="form-text"></div>
            </td>
            {else}<td></td>{/if}
          </tr>
        </tbody>
      </table>
    </div>
  </div>
  <a id="submit-filter" class="button" href="{$drill_down_base}"><i class="zmdi zmdi-search-in-page"></i>{ts}Filter{/ts}</a>
  <a id="export-track" class="button" href="{$drill_down_base}&output=csv">{ts}Export to CSV{/ts}</a>
</div>
<div id="dialog-confirm-export" title="{ts}Confirm Export?{/ts}" style="display:none;">
  <p>{ts}Are you sure you want to export this data?{/ts}</p>
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
    {if $summary}
    <style>{literal}
			.track-outer {
				display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 10px;
			}
			.track-inner {
				flex: 0 0 auto;
        padding: 0 8px;
			}
    {/literal}</style>
		<div class="box-content track-outer">
			{foreach from=$summary item=source}
			<div class="track-inner type-{$source.name}">
				<div><strong>{$source.label}</strong></div>
				<div>{if $source.display}{$source.display}{else}{$source.percent}%{/if}</div>
			</div>
			{/foreach}
		</div>
    {/if}
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
  var filterFieldMap = {
    'ptype': '#filter-ptype',
    'page_title': '#filter-page-title',
    'start': '#start',
    'end': '#end',
    'rtype': '#filter-rtype',
    'rnetwork': '#filter-rnetwork',
    'state': '#filter-state',
    'entity_id': '#filter-entity-id',
    'referrer_url': '#filter-referrer-url',
    'landing': '#filter-landing',
    'utm_source': '#filter-utm-source',
    'utm_medium': '#filter-utm-medium',
    'utm_campaign': '#filter-utm-campaign',
    'utm_term': '#filter-utm-term',
    'utm_content': '#filter-utm-content'
  };
  function buildFilterUrl(href) {
    var appendQuery = [];
    cj.each(filterFieldMap, function(param, selector) {
      var el = cj(selector);
      if (!el.length || !el.val()) {
        return;
      }
      // ptype is always in the base URL, replace it instead of appending
      if (param === 'ptype') {
        href = href.replace(/ptype=[^&]*/, 'ptype=' + encodeURIComponent(el.val()));
        href = href.replace(/pid=[^&]*/, 'pid=');
        return;
      }
      if (href.indexOf(param + '=') === -1) {
        appendQuery.push(param + '=' + encodeURIComponent(el.val()));
      }
    });
    if (appendQuery.length > 0) {
      href += '&' + appendQuery.join('&');
    }
    return href;
  }
  cj('a#submit-filter').click(function(e){
    var href = buildFilterUrl(cj(this).attr('href'));
    cj(this).attr('href', href);
  });
  var exportHref = '';
  cj('#dialog-confirm-export').dialog({
    autoOpen: false,
    resizable: false,
    width: 450,
    modal: true,
    dialogClass: 'export-confirm-dialog',
    open: function() {
    cj(this).dialog('widget').removeClass('ui-front');
    },
    buttons: {
      '{/literal}{ts}Confirm Export{/ts}{literal}': function() {
        cj(this).dialog('close');
        window.location.href = exportHref;
      },
      '{/literal}{ts}Cancel{/ts}{literal}': function() {
        cj(this).dialog('close');
      }
    }
  });
  cj('a#export-track').click(function(e) {
    e.preventDefault();
    exportHref = buildFilterUrl(cj(this).attr('href'));
    cj('#dialog-confirm-export').dialog('open');
  });
  cj().crmaccordions();
  cj('.crm-accordion-header').click(function() {
    cj('.crm-accordion-body').find('.chartist-chart').hide();
    cj('.crm-accordion-body').find('center').remove();
    cj('.crm-accordion-body').append('<center><div class="zmdi zmdi-replay zmdi-hc-spin" style="font-size:2em;"></div></center>');
    cj('.crm-accordion-body').find('.chartist-chart').each(function(i, e) {
      setTimeout(function(){
        cj('.crm-accordion-body').find('.chartist-chart').show();
        e.__chartist__.update();
        cj('.crm-accordion-body').find('center').remove();
      }, 1000);
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
        urlParams += '{/literal}{$k}={$val}{literal}&';
      {/literal}
      {/if}
      {/foreach}
      {literal}
      urlParams += 'crmPID='+parseInt(jumpTo);
      var submitUrl = '{/literal}{crmURL p="civicrm/track/report"}{literal}';
      document.location = submitUrl+"?"+urlParams;
  }
</script>
{/literal}
{/if}
