{if $progress.display}
  {capture assign='percent_css'}{if $progress.achieved_status}100{else}{$progress.achieved_percent}{/if}{/capture}
  <div class="progress-block {if $progress.fullwidth}progressbar-fullwidth{/if}">{* progress-block start *}
    <div class="progress-amount">
      <div class="progress-amount-raised bubble">
        {if $progress.type|strstr:"amount"}
          {capture assign=amount_achieved}<span>{$progress.current|crmMoney}</span>{/capture}
          {ts 1=$amount_achieved}Raised %1{/ts}
        {else}
          <span>{$progress.current}</span> {ts}People{/ts}
        {/if}
      </div>
      <div class="progress-amount-goal">
        {if $progress.type|strstr:"amount"}
          {$progress.label} <span>{$progress.goal|crmMoney}</span>
        {elseif $progress.type == "recurring"}
          {$progress.label} <span>{$progress.goal}</span>{ts}People{/ts}
        {/if}
      </div>
    </div>
    <div class="progress-wrapper">
      <div class="progress-cell progress-bar-wrapper">
        <div class="progress-bar" style="width:0px;" data-current="{$percent_css}"></div>
        <div class="progress-pointer" style="left:0px;margin-left:0;opacity:0">{ts 1="`$progress.achieved_percent`%"}%1 achieved{/ts}</div>
      </div>
      {if $progress.link_display}
        <div class="progress-cell progress-button">
          {if $progress.link_url}
            <a class="button" href="{$progress.link_url}">{if $progress.link_text}{$progress.link_text}{else}{ts}Donate Now{/ts}{/if}</a>
          {else}
            <div class="button"><span>{if $progress.link_text}{$progress.link_text}{else}{ts}Donate Now{/ts}{/if}</span></div>
          {/if}
        </div>
      {/if}
    </div>
  </div>{* progress-block end *}
  {* for css animation *}
  <script>{literal}
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
{/if}
