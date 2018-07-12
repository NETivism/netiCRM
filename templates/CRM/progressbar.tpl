  {capture assign='percent_css'}{if $achievement.achieved}100{else}{$achievement.percent}{/if}{/capture}
  <div class="progress-block">
    <div class="progress-amount">
      <div class="progress-amount-raised">{if $achievement.type == "amount"}{$achievement.current|crmMoney}{else}{$achievement.current} {ts}People{/ts}{/if}</div>
      <div class="progress-amount-goal">{if $achievement.type == "amount"}{ts}Goal Amount{/ts} {$achievement.goal|crmMoney}{else}{ts}Goal Subscription{/ts} {$achievement.goal}{ts}People{/ts}{/if}</div>
    </div>
    <div class="progress-wrapper">
      <div class="progress-cell progress-bar-wrapper">
        <div class="progress-bar" style="width:{$percent_css}%;"></div>
        <div class="progress-pointer" style="{if $percent_css > 50}right:{math equation="x - y" x=100 y=`$percent_css` format="%.1f"}{else}left:{$percent_css}{/if}%">{ts 1="`$achievement.percent`%"}%1 achieved{/ts}</div>
      </div>
      {if $intro_text}
      <div class="progress-cell progress-button">
        <div class="button"><span>{ts}Donate Now{/ts}</span></div>
      </div>
      {/if}
    </div>
  </div><!--progress-block-->
