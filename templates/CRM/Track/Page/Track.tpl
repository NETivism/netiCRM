{if $filters}
<div class="crm-block crm-form-block">
  {foreach from=$filters item=filter key=name}
    <span class="filter-box"><i class="zmdi zmdi-filter-list"></i> {$filter.title}: {$filter.value_display}<a href="{crmURL q=$filter.url}" class="zmdi zmdi-close-circle"></a></span>
  {/foreach}
</div>
{/if}
{if $chart_track}
<div class="col-xs-12">
  <div class="box mdl-shadow--2dp">
    <div class="chartist">
      {include file="CRM/common/chartist.tpl" chartist=$chart_track}
    </div>
  </div>
</div>
{/if}

{include file="CRM/common/pager.tpl" location="top"}
{if $rows }
{include file="CRM/common/jsortable.tpl"}
{strip}
<table id="mailing_event">
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
