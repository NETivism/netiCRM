  {capture assign='percent_css'}{if $achievement.achieved}100{else}{$achievement.percent}{/if}{/capture}
  <div class="progress-block">
    <div class="progress-amount">
      <div class="progress-amount-raised">{if $achievement.type == "amount"}{$achievement.current|crmMoney}{else}{$achievement.current} {ts}People{/ts}{/if}</div>
      <div class="progress-amount-goal">{if $achievement.type == "amount"}{ts}Goal Amount{/ts} {$achievement.goal|crmMoney}{else}{ts}Goal Subscription{/ts} {$achievement.goal}{ts}People{/ts}{/if}</div>
    </div>
    <div class="progress-wrapper">
      <div class="progress-cell progress-bar-wrapper">
        <div class="progress-bar" style="width:0px;" data-current="{$percent_css}"></div>
        <div class="progress-pointer" style="left:0px;margin-left:0;">{ts 1="`$achievement.percent`%"}%1 achieved{/ts}</div>
      </div>
      {if $intro_text}
      <div class="progress-cell progress-button">
        <div class="button"><span>{ts}Donate Now{/ts}</span></div>
      </div>
      {/if}
    </div>
  </div><!--progress-block-->
  <script>{literal}
  <!-- for css animation -->
  cj(document).ready(function($){
    // click then scroll to bottom
    if ($(".payment_options-group").length) {
      $(".progress-button .button").click(function(){
        $(".payment_options-group")[0].scrollIntoView({"behavior":"smooth","block":"center"});
      });
    }
    if ($(".progress-bar-wrapper").length) {
      $(".progress-bar-wrapper").each(function(){
        var $progressbar = $(this).find(".progress-bar");
        var $progresspointer = $(this).find(".progress-pointer");
        var goal = $progressbar.data("current");
        $progressbar.css({"transition":"width 1.5s linear 0.5s", "width":goal+"%"});

        if (goal > 50) {
          $progresspointer.css({"margin-left":"-"+$progresspointer.outerWidth()+"px"});
        }
        $progresspointer.css({"transition":"all 1.5s linear 0.5s", "left":goal+"%"});
      });
    }
  });
  {/literal}</script>
