{if !$after_redirect}
{literal}
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=BIG5">
<title>Apple Pay Test Page</title>
<style type="text/css">
  
</style>
</head>
<body>

<div id="step-before-redirect">
  
  <form action="/civicrm/payment/mobile/checkout" name="print" method="post" id="redirect-form">
    <div align="center" id="submit-button">
      <p>{/literal}{ts}If your page doesn't redirect, please click 'Continue' button to act.{/ts}{literal}</p>
      <div>
        <input type="hidden" name="redirect" value="1">
        <input type="hidden" name="instrument" value="ApplePay">
        {/literal}
          {foreach from=$params key=key item=item}
            <input type="hidden" name="{$key}" value="{$item}">
          {/foreach}
        {literal}
        <input type="submit" id="submit-button" value="{/literal}{ts}Continue{/ts}{literal}" />
      </div>
    </div>
  </form>
</div>
<script type="text/javascript">
  document.forms.print.submit();
  var btn = document.getElementById("submit-button");
  btn.style.display = "none";
  setTimeout(function(){
    btn.style.display = "block";
  }, 1000);
</script>
</body>
</html>
{/literal}
{else}


{literal}
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
<script type="text/javascript">

  (function($){
    $(function(){

      window.applePayProcess = {

        cid:'{/literal}{$cid}{literal}',
        provider: '{/literal}{$provider}{literal}',
        description : '{/literal}{$description}{literal}',
        organization : '{/literal}{$organization}{literal}',
        qfKey : '{/literal}{$qfKey}{literal}',
        amount : {/literal}{$amount}{literal},
        {/literal}
          {if $pid}pid : '{$pid}', {/if}
          {if $eid}eid : '{$eid}', {/if}
        {literal}

        /*
        order:{
          merchantname : 'NewebPay',
          ordernumber : 'T2016120001',
          productname : '測試商品',
          price : '{/literal}{$amount}{literal}'
        },
        */
        doReady: function(){
          // this.total.amount = Number(this.order.price) + Number(this.shippingMethod.amount),
          dd('Loading finished.');

        //-- 檢查環境是否可執行 ApplePay
        if (window.ApplePaySession) {
            if (ApplePaySession.canMakePayments) {
                //showApplePayButton();
            }
        }

        },

        doPay : function() {
      
          try {
            request = {
              countryCode : 'TW',
              currencyCode : 'TWD',
              supportedNetworks : [ 'visa','masterCard' ],
              merchantCapabilities : [ 'supports3DS' ],
              total : {
                label : this.organization,
                amount : this.amount
              },
              lineItems : [{label:this.description, amount:this.amount}],
            };
            
            const session = new ApplePaySession(2, request);
          
            session.onvalidatemerchant = function(event) {
              var data = {
                cid : window.applePayProcess.cid,
                validationURL: event.validationURL,
                domain_name:　location.host
              };

              $.ajax({
                type: "POST",
                url: '/civicrm/ajax/applepay/validate',
                data: data,
                dataType: "json",
                success: function (result){
                  try{
                    dd("Validate Success");
                    merchantSession = window.applePayProcess.merchantSession = JSON.parse(result.merchantSession);
                    dd(merchantSession);
                    
                    session.completeMerchantValidation(merchantSession);
                  }catch(err){
                    dd("ERR");
                    dd(err);
                  }
                }
              });
            };
            session.onpaymentauthorized = function(event){
              dd(event);
              dd("Start transact");
              data = {
                cid : window.applePayProcess.cid,
                applepay_token : event.payment.token,
              };
              data.pid = window.applePayProcess.pid ? window.applePayProcess.pid : undefined;
              data.eid = window.applePayProcess.eid ? window.applePayProcess.eid : undefined;

              $.ajax({
                type: "POST",
                url: '/civicrm/ajax/applepay/transact',
                data: data,
                dataType: "json",
                success: function (result){
                  dd(result);
                  if(result.is_success){
                    dd("Transact Success");
                    dd(ApplePaySession.STATUS_SUCCESS);
                    session.completePayment(JSON.parse(ApplePaySession.STATUS_SUCCESS));

                    $('.crm-accordion-body').append("<p style='color: red;'>{/literal}{ts}Paid success!! Fresh page after 5 seconds.{/ts}{literal}</p>");
                    
                    setTimeout(function(){
                      if(window.applePayProcess.pid){
                        location.href = "/civicrm/event/register?_qf_ThankYou_display=true&qfKey="+window.applePayProcess.qfKey;
                      }else{
                        location.href = "/civicrm/contribute/transact?_qf_ThankYou_display=true&qfKey="+window.applePayProcess.qfKey;
                      }
                    },5000);
                    


                  }else{
                    dd(ApplePaySession.STATUS_FAILURE);
                    session.completePayment(JSON.parse(ApplePaySession.STATUS_FAILURE));

                  }
                }
              });


            };

            session.oncancel = function(){
              dd("Cancel");
              
            };

            session.begin();
            
          } catch (err) {
            alert(err);
            dd(err);
          }

        } // doPay = function(){ ... }
      } // window.applePayProcess = { ... }

      window.applePayProcess.doReady();

    }); // $(function(){ ... })
  })(jQuery);

  function dd(text){
    // $('dd.console').append("<div> Type:" ++"</div>");
    if(typeof text == 'object'){
      $('.console').append("<div> Type:"+(typeof text)+"</div>");
      $('.console').append("<div>"+JSON.stringify(text)+"</div>");
      return;
    }
    $('.console').append("<div>"+text+"</div>");
  }

</script>
<style>
@supports (-webkit-appearance: -apple-pay-button) { 
    .apple-pay-button {
        display: inline-block;
        -webkit-appearance: -apple-pay-button;
    }
    .apple-pay-button-black {
        -apple-pay-button-style: black;
    }
    .apple-pay-button-white {
        -apple-pay-button-style: white;
    }
    
    .apple-pay-button-white-with-line {
        -apple-pay-button-style: white-outline;
    }
}
@supports not (-webkit-appearance: -apple-pay-button) {
    .apple-pay-button {
        display: inline-block;
        background-size: 100% 60%;
        background-repeat: no-repeat;
        background-position: 50% 50%;
        border-radius: 5px;
        padding: 0px;
        box-sizing: border-box;
        min-width: 200px;
        min-height: 32px;
        max-height: 64px;
    }
    .apple-pay-button-black {
        background-image: -webkit-named-image(apple-pay-logo-white);
        background-color: black;
    }
    .apple-pay-button-white {
        background-image: -webkit-named-image(apple-pay-logo-black);
        background-color: white;
    }
    .apple-pay-button-white-with-line {
        background-image: -webkit-named-image(apple-pay-logo-black);
        background-color: white;
        border: .5px solid black;
    } 
}
.console {
    display: none;
}
</style>

<div class="crm-container-md">
  <div class="crm-custom-data-view">
    <div class="crm-accordion-body">
      <table class="crm-info-panel">
        <tbody>
          <tr>
            <td class="label">{/literal}{ts}Payment Organization{/ts}{literal}</td>
            <td class="html-adjust">{/literal}{$organization}{literal}</td>
          </tr>
          <tr>
            <td class="label">{/literal}{ts}Subject Name{/ts}{literal}</td>
            <td class="html-adjust">{/literal}{$description}{literal}</td>
          </tr>
          <tr>
            <td class="label">{/literal}{ts}Amount{/ts}{literal}</td>
            <td class="html-adjust">{/literal}{$amount|crmMoney}{literal}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <div align="center" style="margin-top: 10px"><button class="apple-pay-button apple-pay-button-black-with-line" onclick="window.applePayProcess.doPay();"></button></div>

  <div class="console">Console message: </div>
</div>
{/literal}
{/if}
