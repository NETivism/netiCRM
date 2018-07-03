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
  .source-outter {
    display: flex;
  }
  .source-inner {
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
<h4><a href="{crmURL p='civicrm/admin/contribute' q="action=update&reset=1&id=`$page.id`" h=0 a=1 fe=1}">{$page.title}</a></h4>
{capture assign="contribution_count"}<span class="bigger">{$page.duration_count}</span>{/capture}
<div>{ts 1=$contribution_count}There are %1 new contributions.{/ts}</div>
{if $page.duration_count_growth}
  <div>{include file="CRM/common/growth_sentence.tpl" growth=$page.duration_count_growth bigger=1}</div>
{/if}
<br/>
<div><h5>{ts}Flow Source{/ts}</h5>
  <div class="source-outter">
    {foreach from=$page.source item=source}
    <div class="source-inner">
      <div>{$source.type}</div>
      <div>{$source.count}%</div>
    </div>
    {/foreach}
  </div>
</div>
<br/>
<div class="grey-background">
  <a href="{crmURL p='civicrm/contribute/search' q="reset=1&pid=`$page.id`&force=1&test=0" h=0 a=1 fe=1}">
    <div>{ts}Amount reached{/ts} {$page.total_amount|crmMoney}{if $page.goal} / {$page.goal|crmMoney}{/if}</div>
    <div>{ts}Total Donate Times{/ts} {$page.total_count}</div>
    <div class="progress-wrapper">
      {if $page.goal}
      <span class="progress-full"><span class="progress-inner" style="width:{if $page.progress > 100}100{else}{$page.progress}{/if}%;">{$page.progress|number_format:2:".":","}%</span></span>
      {/if}
    </div>
  </a>
</div>