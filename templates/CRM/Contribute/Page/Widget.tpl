{literal}
<style>
  .crm-contribute-widget {
    font-size:1.0em;
    font-family:inherit;
    padding:10px;
    width:280px;
    background: #EEF2EA;
  }
  .crm-contribute-widget h3 {
    font-size:1.0em;
    padding: 3px;
    margin: 0px 0px 10px 0;
    text-align:center;
  }

  .crm-amount {
    margin-bottom: .8em;
  }
  .crm-contribute-widget .crm-amount-raised {
    font-size: 1.6em;
  }
  .crm-contribute-widget .crm-amount-high {
    font-size: .8em;
  }
  .crm-contribute-widget .crm-amount-bar {
    background-color:#FFF;
    width:100%;
    display:block;
    border:1px solid #CECECE;
    border-radius:4px;
    margin-bottom:.8em;
  }
  .crm-contribute-widget .crm-amount-fill {
    background-color: #76CC1E;
    height:1.5em;
    display:block;
    border-radius: 4px 0px 0px 4px;
    text-align: right;
  }
  .crm-contribute-widget .crm-percentage {
    color: #FFF;
    font-size: .8em;
  }
  .crm-contribute-widget .crm-amount-raised-wrapper {
      margin-bottom:.8em;
  }
  .crm-contribute-widget .crm-amount-raised {
    font-weight:bold;
  }
  .crm-contribute-widget .crm-comments,
  .crm-contribute-widget .crm-campaign {
    font-size:.9em;
    margin-bottom:.8em;
    color: #aaa;
    text-align: center;
  }
  .crm-contribute-widget .crm-logo {
    text-align:center;
  }
  .crm-contribute-widget .crm-contribute-button {
    display:block;
    background-color:#CECECE;
    text-align:center;
    margin:0px 10% .8em 10%;
    text-decoration:none;
    color:#556C82;
    padding:2px;
    font-size:1.6em;
  }
</style>
<style>
  .crm-contribute-widget { 
      background-color: {/literal}{$form.color_main.value}{literal}; /* background color */
      border-color:{/literal}{$form.color_bg.value}{literal}; /* border color */
  }

  .crm-contribute-widget h3 {
      color: {/literal}{$form.color_title.value}{literal};
      background-color: {/literal}{$form.color_main_bg.value}{literal};
  } /* title */

  .crm-contribute-widget .crm-amount-raised { color:#000; }
  .crm-contribute-widget .crm-amount-fill { 
      background-color:{/literal}{$form.color_bar.value}{literal};
  }
  .crm-contribute-widget .crm-amount-bar {  /* progress bar */
      background-color:#F3F3F3;
      border-color:#CECECE;
  }

  .crm-contribute-widget a.crm-contribute-button { /* button color */
      background-color:{/literal}{$form.color_button.value}{literal};
  }

  .crm-contribute-widget .crm-contribute-button-inner { /* button text color */
      padding:2px;
      display:block;
      color:{/literal}{$form.color_about_link.value}{literal};
  }

  .crm-contribute-widget .crm-comments,
  .crm-contribute-widget .crm-campaign {
      color:{/literal}{$form.color_main_text.value}{literal} /* other color*/
  }
  
  .crm-contribute-widget .crm-home-url {
      color:{/literal}{$form.color_homepage_link.value}{literal} /* home page link color*/
  }
</style>
{/literal}

<div id="crm_cpid_{$cpageId}" class="crm-contribute-widget">
    <div id="crm_cpid_{$cpageId}_campaign" class="crm-campaign"></div>
    <h3 id="crm_cpid_{$cpageId}_title"></h3>
    <div class="crm-amount">
        <div id="crm_cpid_{$cpageId}_amt_raised" class="crm-amount-raised"></div>
        <div id="crm_cpid_{$cpageId}_amt_hi" class="crm-amount-high"></div>
    </div>
    <div class="crm-amount-bar">
        <div class="crm-amount-fill" id="crm_cpid_{$cpageId}_amt_fill"><span id="crm_cpid_{$cpageId}_percentage" class="crm-percentage"></span></div>
    </div>
    {if $form.url_logo.value}
        <div class="crm-logo"><img src="{$form.url_logo.value}" alt={ts}Logo{/ts}></div>
    {/if}
    <div id="crm_cpid_{$cpageId}_comments" class="crm-comments"></div>
    <div class="crm-contribute-button-wrapper" id="crm_cpid_{$cpageId}_button">
        <a href='{crmURL p="civicrm/contribute/transact" q="reset=1&id=$cpageId" h=0 a=1}' class="crm-contribute-button"><span class="crm-contribute-button-inner" id="crm_cpid_{$cpageId}_btn_txt"></span></a>
    </div>
</div>

{literal}

<script type="text/javascript">
    //create onDomReady Event
    window.onDomReady = DomReady;

    //Setup the event
    function DomReady(fn) { //W3C
        if(document.addEventListener) {
            document.addEventListener("DOMContentLoaded", fn, false);
        } else { //IE
            document.onreadystatechange = function(){readyState(fn)}
        }
    }

    //IE execute function
    function readyState(fn) {
        //dom is ready for interaction
        if(document.readyState == "interactive") {
            fn();
        }
    }

    window.onDomReady(onReady);

    function onReady( ) {
        var crmCurrency = jsondata.currencySymbol;
        var cpid        = {/literal}{$cpageId}{literal};
        document.getElementById('crm_cpid_'+cpid+'_title').innerHTML        = jsondata.title;
        if ( jsondata.money_target > 0 ) {
            document.getElementById('crm_cpid_'+cpid+'_amt_hi').innerHTML       = jsondata.money_target_display;
        }
        document.getElementById('crm_cpid_'+cpid+'_amt_raised').innerHTML   = jsondata.money_raised;
        document.getElementById('crm_cpid_'+cpid+'_comments').innerHTML     = jsondata.about;
        document.getElementById('crm_cpid_'+cpid+'_btn_txt').innerHTML      = jsondata.button_title;
        document.getElementById('crm_cpid_'+cpid+'_campaign').innerHTML     = jsondata.campaign_start;
        if ( jsondata.money_raised_percentage ) {
            document.getElementById('crm_cpid_'+cpid+'_amt_fill').style.width   = parseInt(jsondata.money_raised_percentage) > 100 ? "100%" : jsondata.money_raised_percentage;
            document.getElementById('crm_cpid_'+cpid+'_percentage').innerHTML   = jsondata.money_raised_percentage;
        }
        if ( !jsondata.is_active ) {
            document.getElementById('crm_cpid_'+cpid+'_button').innerHTML   = jsondata.home_url;
            document.getElementById('crm_cpid_'+cpid+'_button').style.color = 'red';
        }
    }
    
</script>
{/literal}
<script type="text/javascript" src="{$config->userFrameworkResourceURL}/extern/widget.php?cpageId={$cpageId}&widgetId={$widget_id}&language={$tsLocale}"></script>

