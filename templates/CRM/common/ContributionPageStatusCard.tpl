{literal}
<style>
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
  .track-outter {
    display: flex;
  }
  .track-inner {
    flex: 0 0 20%;
  }
  .grey-background{
    background: #eee;
    padding: 10px;
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
    width: 100%;
  }
  .progress-inner {
    display: block;
    height: 100%;
    background: black;
    color: white;
    text-align: center;
  }
</style>
{/literal}
{if $statistics.page}
<h4><a href="{crmURL p='civicrm/admin/contribute' q="action=update&reset=1&id=`$statistics.page.id`" h=0 a=1 fe=1}">{$statistics.page.title}</a></h4>
{/if}

{if $statistics.duration}
{capture assign="contribution_count"}<span class="bigger">{$statistics.duration.count}</span>{/capture}
<div>{ts 1=$contribution_count}There are %1 new contributions.{/ts}</div>
  {if $statistics.duration.growth}
  <div>{include file="CRM/common/growth_sentence.tpl" growth=$statistics.duration.growth bigger=1}</div>
  {/if}
{/if}
<br/>
<div>
  <h5><a href="{crmURL p='civicrm/track/report' q="reset=1&ptype=civicrm_contribution_page&pid=`$statistics.page.id`"}">{ts}Traffic Source{/ts}</a></h5>
  {if $statistics.track}
  <div class="track-outter">
    {foreach from=$statistics.track item=source}
    <div class="track-inner type-{$source.name}">
      <div>{$source.label}</div>
      <div>{if $source.display}{$source.display}{else}{$source.percent}%{/if}</div>
    </div>
    {/foreach}
  </div>
  {/if}
</div>
<br/>
<div class="grey-background">
  <a href="{crmURL p='civicrm/contribute/search' q="reset=1&pid=`$statistics.page.id`&force=1&status=1&test=0" h=0 a=1 fe=1}">
    {if $statistics.achievement}
    <div>
      {if $statistics.achievement.type == "amount"}
        {capture assign=achieved}{$statistics.achievement.current|crmMoney}{/capture}
        {ts 1="`$achieved`"}%1 achieved{/ts}
        {if $statistics.achievement.goal} / {$statistics.achievement.goal|crmMoney}{/if}
      {else}
        {ts 1="`$statistics.achievement.current`"}%1 achieved{/ts}
        {if $statistics.achievement.goal} / {$statistics.achievement.goal} {ts}People{/ts}{/if}
      {/if}
    </div>
    {else}
    <div>
      {capture assign=achieved}{$statistics.page.total_amount|crmMoney}{/capture}
      {ts 1="`$achieved`"}%1 achieved{/ts}
    </div>
    {/if}
    <div>{ts}Donation Count{/ts} {$statistics.page.total_count}</div>
    {if $statistics.achievement}
    <div class="progress-wrapper">
      <span class="progress-full"><span class="progress-inner" style="width:{if $statistics.achievement.achieved}100{else}{$statistics.achievement.percent}{/if}%;">{$statistics.achievement.percent}%</span></span>
    </div>
    {/if}
  </a>
</div>
