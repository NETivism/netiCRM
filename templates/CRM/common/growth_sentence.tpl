{if $bigger}
  {assign var="bigger" value="bigger"}
{/if}
{if $growth eq 0}
  
{else}
  {if $growth > 0}
      {assign var="zmdi" value="zmdi-long-arrow-up"}
      {assign var="verb" value="成長"}
      {assign var="color" value="blue"}
  {else}
      {assign var="zmdi" value="zmdi-long-arrow-down"}
      {assign var="verb" value="下降"}
      {assign var="color" value="red"}
  {/if}
  <i class="{$bigger} zmdi {$zmdi}"></i>{if $days}較前 {$days} 天{/if}<span class="{$color}">{$verb}<span class="{$bigger}">{$growth|abs}%</span></span>
{/if}