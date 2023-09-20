{if $progress_display}
  {capture assign='percent_css'}{if $progress_achieved_status}100{else}{$progress_achieved_percent}{/if}{/capture}
  <div class="progress-block">{* progress-block start *}
    <div class="progress-amount">
      <div class="progress-amount-raised bubble">
        {if $progress_type|strstr:"amount"}
          {capture assign=amount_achieved}<span>{$progress_current|crmMoney:$progress_currency:null:true}</span>{/capture}
          {ts 1=$amount_achieved}Raised %1{/ts}
        {else}
          <span>{$progress_current}</span> {ts}People{/ts}
        {/if}
      </div>
      <div class="progress-amount-goal">
        {if $progress_type|strstr:"amount"}
          {$progress_label} <span>{$progress_goal|crmMoney:$progress_currency:null:true}</span>
        {elseif $progress_type == "recurring"}
          {$progress_label} <span>{$progress_goal}</span>{ts}People{/ts}
        {/if}
      </div>
    </div>
    <div class="progress-wrapper">
      <div class="progress-cell progress-bar-wrapper" {if !$progress_fullwidth}style="width:100%;"{/if}>
        <div class="progress-bar" style="width:0px;" data-current="{$percent_css}"></div>
        <div class="progress-pointer" style="left:0px;margin-left:0;opacity:0">{ts 1="`$progress_achieved_percent`%"}%1 achieved{/ts}</div>
      </div>
      {if $progress_link_display}
        <div class="progress-cell progress-button">
          {if $progress_link_url}
            <a href="{$progress_link_url}">{if $progress_link_text}{$progress_link_text}{else}{ts}Donate Now{/ts}{/if}</a>
          {else}
            <div class="button"><span>{if $progress_link_text}{$progress_link_text}{else}{ts}Donate Now{/ts}{/if}</span></div>
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
