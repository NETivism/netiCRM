{if $progress.display}
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
          <div class="progress-cell progress-button">
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
  // IIFE to encapsulate the code and avoid global scope pollution
  (function() {
    // Define constants for transition time and delay
    const TRANSITION_TIME = "1.5s";
    const DELAY = 800;

    // Function to initialize progress bars
    function initProgressBars() {
      const progressBlocks = document.querySelectorAll(".progress-block");

      // Loop through each progress block to set styles
      progressBlocks.forEach(progressBlock => {
        const progressBar = progressBlock.querySelector(".progress-bar");
        const progressPointer = progressBlock.querySelector(".progress-pointer");
        let goal = progressBar.getAttribute("data-current");

        // Apply styles based on the goal
        applyStyles(progressBar, progressPointer, goal);
      });
    }
    // Function to apply dynamic styles to progress bars and pointers
    function applyStyles(progressBar, progressPointer, goal) {
      // Check if the goal is above 50
      const isGoalAbove50 = goal > 50;

      // Conditionally set margin and class for the pointer
      progressPointer.style.marginLeft = isGoalAbove50 ? `-${progressPointer.offsetWidth}px` : "";
      progressPointer.classList.toggle("white", isGoalAbove50);

      // Set transition styles for bar and pointer
      Object.assign(progressBar.style, { transition: `width ${TRANSITION_TIME}` });
      Object.assign(progressPointer.style, { transition: `all ${TRANSITION_TIME}` });

      // Delay setting the final styles
      setTimeout(() => {
        // Set the final width of the progress bar
        Object.assign(progressBar.style, { width: `${goal}%` });
        // Set the final position and visibility of the pointer
        Object.assign(progressPointer.style, { left: `${goal}%`, opacity: "1" });
      }, DELAY);
    }

    // Function to initialize progress buttons
    function initProgressButtons() {
      const paymentGroup = document.querySelector(".payment_options-group");

      // Return if the group does not exist
      if (!paymentGroup) return;

      // Add smooth scroll event listener for each button in the progress-button class
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

    // Initialize both progress bars and buttons when the DOM is fully loaded
    document.addEventListener("DOMContentLoaded", function() {
      initProgressBars();
      initProgressButtons();
    });
  })();
  {/literal}</script>
{else}
  <div class="pcp-amount-raised-top">
    {if $progress.type|strstr:"amount"}
      {capture assign=amount_achieved}<span class="counter">{$progress.current|crmMoney}</span>{/capture}
      {ts 1=$amount_achieved}Raised %1{/ts}
    {else}
      <span class="counter">{$progress.current}</span> {ts}People{/ts}
    {/if}
  </div>
  {if $progress.link_display}
    <div class="pcp-donate">
      {if $progress.link_url}
        <a class="button contribute-button pcp-contribute-button" href="{$progress.link_url}">{if $progress.link_text}{$progress.link_text}{else}{ts}Donate Now{/ts}{/if}</a>
      {else}
        <div class="button contribute-button pcp-contribute-button"><span>{if $progress.link_text}{$progress.link_text}{else}{ts}Donate Now{/ts}{/if}</span></div>
      {/if}
    </div>
  {/if}
{/if}
