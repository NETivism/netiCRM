<div class='loading-placeholder-wrapper placeholder-intro-text'>
<div class='placeholder-row placeholder-title-row'>
    <div class='placeholder-item'></div>
  </div>
  <div class='placeholder-row placeholder-p-row'>
    <div class='placeholder-item'></div>
    <div class='placeholder-item'></div>
    <div class='placeholder-item'></div>
    <div class='placeholder-item'></div>
  </div>
</div>
{if $page eq "main"}
<div class='loading-placeholder-wrapper placeholder-form'>
  <div class='placeholder-row placeholder-btn-row'>
    <span class='placeholder-item'></span>
    <span class='placeholder-item'></span>
  </div>
  <div class='placeholder-row placeholder-amount-row'>
    <div class='placeholder-amount-item'>
      <div class='placeholder-item placeholder-amount'></div>
      <div class='placeholder-item placeholder-label'></div>
    </div>
    <div class='placeholder-amount-item'>
      <div class='placeholder-item placeholder-amount'></div>
      <div class='placeholder-item placeholder-label'></div>
    </div>
    <div class='placeholder-amount-item'>
      <div class='placeholder-item placeholder-amount'></div>
      <div class='placeholder-item placeholder-label'></div>
    </div>
    <div class='placeholder-amount-item'>
      <div class='placeholder-item placeholder-amount'></div>
      <div class='placeholder-item placeholder-label'></div>
    </div>
  </div>
</div>
{else}
<div class='loading-placeholder-wrapper placeholder-form'>
<div class='placeholder-row placeholder-title-row'>
    <div class='placeholder-item'></div>
  </div>
  <div class='placeholder-row placeholder-p-row'>
    <div class='placeholder-item'></div>
    <div class='placeholder-item'></div>
    <div class='placeholder-item'></div>
    <div class='placeholder-item'></div>
  </div>
</div>
{/if}
<script type="text/javascript">
  {literal}
    window.ContribPageParams = {
      backgroundImageUrl : "{/literal}{$backgroundImageUrl}{literal}",
      mobileBackgroundImageUrl : "{/literal}{$mobileBackgroundImageUrl}{literal}",
      // creditCardOnly : "{/literal}{$credit_card_only}{literal}",
      minAmount : {/literal}{if $min_amount}"{$min_amount}"{else}false{/if}{literal},
      maxAmount : {/literal}{if $max_amount}"{$max_amount}"{else}false{/if}{literal},
      {/literal}{if $thankyou_text and $payment_result_type eq 4}thankyouTitle : "{$thankyou_title}",{/if}{literal}
      ts: {
        "Monthly Installments" : "{/literal}{ts}Monthly Installments{/ts}{literal}",
        "Yearly Installments" : "{/literal}{ts}Yearly Installments{/ts}{literal}",
        "Single Contribution" : "{/literal}{ts}Single Contribution{/ts}{literal}",
        "Recurring Contributions" : "{/literal}{ts}Recurring Contributions{/ts}{literal}",
        "Other Amount" : "{/literal}{ts}Other Amount{/ts}{literal}",
        "Installments" : "{/literal}{ts}Installments{/ts}{literal}",
        "Every-Month Recurring Contributions" : "{/literal}{ts}Every-Month Recurring Contributions{/ts}{literal}",
        "Installments Recurring Contributions" : "{/literal}{ts}Installments Recurring Contributions{/ts}{literal}",
        "Amount Step" : "{/literal}{ts}Amount Step{/ts}{literal}",
        "Profile Step" : "{/literal}{ts}Profile Step{/ts}{literal}",
        "Confirm Step" : "{/literal}{ts}Confirm Step{/ts}{literal}",
        "Payment Step" : "{/literal}{ts}Payment Step{/ts}{literal}",
        "Not any" : "{/literal}{ts}Not any{/ts}{literal}",
        "Type Here" : "{/literal}{ts}Type Here{/ts}{literal}",
        "Choose Amount Option or Custom Amount" : "{/literal}{ts}Choose Amount Option or Custom Amount{/ts}{literal}",
        "Single or Recurring Contributions" : "{/literal}{ts}Single or Recurring Contributions{/ts}{literal}",
        "Change to Single Contribution" : "{/literal}{ts}Change to Single Contribution{/ts}{literal}",
        "Stay on Recurring Contributions" : "{/literal}{ts}Stay on Recurring Contributions{/ts}{literal}",
        "You cannot set up a recurring contribution if you are not paying online by credit card." : "{/literal}{ts}You cannot set up a recurring contribution if you are not paying online by credit card.{/ts}{literal}",
        "<< Previous" : "{/literal}{ts}<< Previous{/ts}{literal}",
        "Next >>" : "{/literal}{ts}Next >>{/ts}{literal}",
        "Contribution amount must be at least %1" : "{/literal}{ts 1=$min_amount}Contribution amount must be at least %1{/ts}{literal}",
        "Contribution amount cannot be more than %1." : "{/literal}{ts 1=$max_amount}Contribution amount cannot be more than %1.{/ts}{literal}",
        "Payment failed." : "{/literal}{ts}Payment failed.{/ts}{literal}",
        "No Limit" : "{/literal}{ts}No Limit{/ts}{literal}",
        "Read more" : "{/literal}{ts}Read more{/ts}{literal}",
        "Close" : "{/literal}{ts}Close{/ts}{literal}",
        // Original wordings
        "monthly" : "{/literal}{ts}monthly{/ts}{literal}",
        "yearly" : "{/literal}{ts}yearly{/ts}{literal}",
        "Recurring contributions" : "{/literal}{ts}Recurring contributions{/ts}{literal}",
        "Every-Month Recurring Contribution" : "{/literal}{ts}Every-Month Recurring Contribution{/ts}{literal}",
        "Installments Recurring Contribution" : "{/literal}{ts}Installments Recurring Contribution{/ts}{literal}",
        "Type here" : "{/literal}{ts}Type here{/ts}{literal}",
        "Single or Recurring Contribution" : "{/literal}{ts}Single or Recurring Contribution{/ts}{literal}",
        "I want contribute once." : "{/literal}{ts}I want contribute once.{/ts}{literal}",
        "I want recurring contribution." : "{/literal}{ts}I want recurring contribution.{/ts}{literal}",
        "no limit" : "{/literal}{ts}no limit{/ts}{literal}",
        "One-time Contribution" : "{/literal}{ts}One-time Contribution{/ts}{literal}",
        "Monthly Recurring Contributions" : "{/literal}{ts}Monthly Recurring Contributions{/ts}{literal}",
        "Yearly Recurring Contributions" : "{/literal}{ts}Yearly Recurring Contributions{/ts}{literal}",
        "Months Recurring Contributions" : "{/literal}{ts}Months Recurring Contributions{/ts}{literal}",
        "Years Recurring Contributions" : "{/literal}{ts}Years Recurring Contributions{/ts}{literal}",
        "Installments Contributions" : "{/literal}{ts}Installments Contributions{/ts}{literal}",
        "One-time" : "{/literal}{ts}One-time{/ts}{literal}",
        "Recurring" : "{/literal}{ts}Recurring{/ts}{literal}",
        "One-time or Recurring Contributions" : "{/literal}{ts}One-time or Recurring Contributions{/ts}{literal}",
        "Please enter a valid amount." : "{/literal}{ts}Please enter a valid amount.{/ts}{literal}"
      }
    };
  {/literal}
</script>
{if $config->userFrameworkVersion >= 8}
  <script data-contribution-page-type="special" type="text/javascript" src="{$config->resourceBase}js/contribution_page.d9.js?v{$config->ver}"></script>
{else}
  <script data-contribution-page-type="special" type="text/javascript" src="{$config->resourceBase}js/contribution_page.js?v{$config->ver}"></script>
{/if}
<img class="pre-load-background-images" src="{$backgroundImageUrl}" alt="" style="display: none;" loading="lazy">
<img class="pre-load-background-images" src="{$mobileBackgroundImageUrl}" alt="" style="display: none;" loading="lazy">
<style>
{literal}
@media screen and (min-width: 480px) {
  body.frontend{
    background: url({/literal}{$backgroundImageUrl}{literal});
      -webkit-background-size: cover;
      -moz-background-size: cover;
      -o-background-size: cover;
    background-size: cover;
    background-color: white;
    background-attachment: fixed;
    background-position: center;
  }
}
@media screen and (max-width: 1023px) {
  body.frontend{
    background-image: linear-gradient(rgba(0, 0, 0, 0.5),rgba(0, 0, 0, 0.5)),url({/literal}{$backgroundImageUrl}{literal});
  }
}
{/literal}
</style>
