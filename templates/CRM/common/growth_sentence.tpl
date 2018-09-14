{if $bigger}
  {assign var="bigger" value="bigger"}
{/if}
{if $growth eq 0}
  
{else}
  {if $growth > 0}
      {assign var="zmdi" value="zmdi-long-arrow-up"}
      {capture assign="verb"}{ts}grow by{/ts}{/capture}
      {assign var="color" value="blue"}
  {else}
      {assign var="zmdi" value="zmdi-long-arrow-down"}
      {capture assign="verb"}{ts}decrease by{/ts}{/capture}
      {assign var="color" value="red"}
  {/if}
  <i class="{$bigger} zmdi {$zmdi}"></i>
  {if $days}
    {if $debug}
      <a href="{crmURL p='civicrm/contribute' q="reset=1&debug=1&start_date=`$last_start_date`&end_date=`$last_end_date`"}" target="_blank">
    {/if}
      {ts 1=$days}Compared with previous %1 days{/ts}
    {if $debug}
      </a>
    {/if}
  {/if} 
  <span class="{$color}">{$verb} <span class="{$bigger}">{$growth|abs}%</span></span>
{/if}