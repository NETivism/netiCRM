  {capture assign='percent_css'}{if $achievement.achieved}100{else}{$achievement.percent}{/if}{/capture}
  <div class="progress-block">
    <div class="progress-amount">
      <div class="progress-amount-raised bubble">
        {if $achievement.type == "amount"}
          {capture assign=amount_achieved}<span>{$achievement.current|crmMoney:null:null:true}</span>{/capture}
          {ts 1=$amount_achieved}Raised %1{/ts}
        {else}
          <span>{$achievement.current}</span> {ts}People{/ts}
        {/if}
      </div>
      <div class="progress-amount-goal">{if $achievement.type == "amount"}{ts}Goal Amount{/ts} <span>{$achievement.goal|crmMoney:null:null:true}</span>{else}{ts}Goal Subscription{/ts} <span>{$achievement.goal}</span>{ts}People{/ts}{/if}</div>
    </div>
    <div class="progress-wrapper">
      <div class="progress-cell progress-bar-wrapper">
        <div class="progress-bar" style="width:0px;" data-current="{$percent_css}"></div>
        <div class="progress-pointer" style="left:0px;margin-left:0;opacity:0">{ts 1="`$achievement.percent`%"}%1 achieved{/ts}</div>
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
        if ($(".payment_options-group").length) {
          $(".payment_options-group")[0].scrollIntoView({"behavior":"smooth","block":"center"});
        }
      });
    }
    if ($(".progress-bar-wrapper").length) {
      $(".progress-bar-wrapper").each(function(){
        var $progressbar = $(this).find(".progress-bar");
        var $progresspointer = $(this).find(".progress-pointer");
        var goal = $progressbar.data("current");

        if (goal > 50) {
          $progresspointer.css({"margin-left":"-"+$progresspointer.outerWidth()+"px"});
          $progresspointer.addClass("white");
        }
        $progressbar.css({"transition":"width 1.5s"});
        $progresspointer.css({"transition":"all 1.5s"});
        setTimeout(function(){
          $progressbar.css({"width":goal+"%"});
          $progresspointer.css({"left":goal+"%","opacity":100});
        }, 800);
      });
    }
  });
  {/literal}</script>
