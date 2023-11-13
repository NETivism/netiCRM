{capture assign='percent_css'}{if $progress.achieved_status}100{else}{$progress.achieved_percent}{/if}{/capture}
<div class="progress-block {if $progress.fullwidth}progressbar-fullwidth{/if}">{* progress-block start *}
  <div class="inner">
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
        <div class="progress-cell progress-buttons pcp-buttons-top">
          {if $progress.link_url}
            <a class="button" href="{$progress.link_url}">{if $progress.link_text}{$progress.link_text}{else}{ts}Donate Now{/ts}{/if}</a>
          {else}
            <div class="button"><span>{if $progress.link_text}{$progress.link_text}{else}{ts}Donate Now{/ts}{/if}</span></div>
          {/if}
        </div>
      {/if}
    </div>
  </div>
</div>{* progress-block end *}
{* for animation *}
<script>{literal}
(function() {
  const TRANSITION_TIME = "1.5s";
  const DELAY = 800;

  function initProgressBars() {
    const progressBlocks = document.querySelectorAll(".progress-block");

    progressBlocks.forEach(progressBlock => {
      const progressBar = progressBlock.querySelector(".progress-bar");
      const progressPointer = progressBlock.querySelector(".progress-pointer");
      let goal = progressBar.getAttribute("data-current");

      // Apply styles based on the goal
      applyStyles(progressBar, progressPointer, goal);
    });
  }

  function applyStyles(progressBar, progressPointer, goal) {
    const isGoalAbove50 = goal > 50;

    // Conditionally set margin and class for the pointer
    progressPointer.style.marginLeft = isGoalAbove50 ? `-${progressPointer.offsetWidth}px` : "";
    progressPointer.classList.toggle("white", isGoalAbove50);

    // Set transition styles for bar and pointer
    Object.assign(progressBar.style, { transition: `width ${TRANSITION_TIME}` });
    Object.assign(progressPointer.style, { transition: `all ${TRANSITION_TIME}` });

    // Set the final width, position and visibility of the progress bar
    setTimeout(() => {
      Object.assign(progressBar.style, { width: `${goal}%` });
      Object.assign(progressPointer.style, { left: `${goal}%`, opacity: "1" });
    }, DELAY);
  }

  function initProgressButtons() {
    const paymentGroup = document.querySelector(".payment_options-group");

    if (!paymentGroup) return;

    document.querySelectorAll(".progress-button .button").forEach(button => {
      button.addEventListener("click", () => {
        // Scroll smoothly to the payment group
        paymentGroup.scrollIntoView({
          behavior: "smooth",
          block: "center"
        });
      });
    });
  }

  document.addEventListener("DOMContentLoaded", function() {
    initProgressBars();
    initProgressButtons();
  });
})();
{/literal}</script>
