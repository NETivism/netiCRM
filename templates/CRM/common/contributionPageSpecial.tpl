<script type="text/javascript">
  {literal}
  (function($){
    window.ContribPageParams = {
      backgroundImageUrl : "{/literal}{$backgroundImageUrl}{literal}",
      mobileBackgroundImageUrl : "{/literal}{$mobileBackgroundImageUrl}{literal}",
      // creditCardOnly : "{/literal}{$credit_card_only}{literal}",
      minAmount : {/literal}{if $min_amount}"{$min_amount}"{else}false{/if}{literal},
      maxAmount : {/literal}{if $max_amount}"{$max_amount}"{else}false{/if}{literal},
      {/literal}{if $thankyou_text and $payment_result_type eq 4}thankyouTitle : "{$thankyou_title}",{/if}{literal}
      ts: {
        "Single Contribution" : "{/literal}{ts}Single Contribution{/ts}{literal}",
        "Recurring contributions" : "{/literal}{ts}Recurring contributions{/ts}{literal}",
        "Other Amount" : "{/literal}{ts}Other Amount{/ts}{literal}",
        "Installments" : "{/literal}{ts}Installments{/ts}{literal}",
        "Every-Month Recurring Contribution" : "{/literal}{ts}Every-Month Recurring Contribution{/ts}{literal}",
        "Installments Recurring Contribution" : "{/literal}{ts}Installments Recurring Contribution{/ts}{literal}",
        "Amount Step" : "{/literal}{ts}Amount Step{/ts}{literal}",
        "Profile Step" : "{/literal}{ts}Profile Step{/ts}{literal}",
        "Confirm Step" : "{/literal}{ts}Confirm Step{/ts}{literal}",
        "Payment Step" : "{/literal}{ts}Payment Step{/ts}{literal}",
        "Not any" : "{/literal}{ts}Not any{/ts}{literal}",
        "Type here" : "{/literal}{ts}Type here{/ts}{literal}",
        "Choose Amount Option or Custom Amount" : "{/literal}{ts}Choose Amount Option or Custom Amount{/ts}{literal}",
        "Single or Recurring Contribution" : "{/literal}{ts}Single or Recurring Contribution{/ts}{literal}",
        "I want contribute once." : "{/literal}{ts}I want contribute once.{/ts}{literal}",
        "I want recurring contribution." : "{/literal}{ts}I want recurring contribution.{/ts}{literal}",
        "You cannot set up a recurring contribution if you are not paying online by credit card." : "{/literal}{ts}You cannot set up a recurring contribution if you are not paying online by credit card.{/ts}{literal}",
        "<< Previous" : "{/literal}{ts}<< Previous{/ts}{literal}",
        "Next >>" : "{/literal}{ts}Next >>{/ts}{literal}",
        "Contribution amount must be at least %1" : "{/literal}{ts 1=$min_amount}Contribution amount must be at least %1{/ts}{literal}",
        "Contribution amount cannot be more than %1." : "{/literal}{ts 1=$max_amount}Contribution amount cannot be more than %1.{/ts}{literal}",
        "Payment failed." : "{/literal}{ts}Payment failed.{/ts}{literal}",
        "no limit" : "{/literal}{ts}no limit{/ts}{literal}",
      }
    };
  })(jQuery);
  {/literal}
</script>
<script type="text/javascript" src="{$config->resourceBase}/js/contribution_page.js"></script>
<img class="pre-load-background-images" src="{/literal}{$backgroundImageUrl}{literal}" alt="" style="display: none;">
<img class="pre-load-background-images" src="{/literal}{$mobilBackgroundImageUrl}{literal}" alt="" style="display: none;">
<style>
{literal}
@media screen and (min-width: 480px) {
  body.frontend.page-civicrm-contribute-transact{
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
{/literal}
</style>
