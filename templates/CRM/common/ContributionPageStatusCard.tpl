<h5>{$page.title}</h5>
<div>有<span class="bigger"><span class="red">{$page.duration_count}</span>筆</span>新增捐款</div>
{if $page.duration_count_growth}
  <div>{include file="CRM/common/growth_sentence.tpl" growth=$page.duration_count_growth bigger=1}</div>
{/if}
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
<div class="grey">總達成金額 {$page.total_amount|crmMoney}{if $page.goal} / {$page.goal|crmMoney}{/if}</div>
<div class="grey">總人次 {$page.total_count}</div>
<div class="process-wrapper">
  {if $page.goal}
  <span class="process-full"><span class="process-inner" style="width:{if $page.process > 100}100{else}{$page.process}{/if}%;">{$page.process|number_format:2:".":","}%</span></span>
  {/if}
</div>