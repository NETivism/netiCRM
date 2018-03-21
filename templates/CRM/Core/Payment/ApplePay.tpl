{literal}
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=BIG5">
<title>Apple Pay Test Page</title>
{/literal}

{if !$after_redirect}
{literal}
<style type="text/css">
  
</style>
</head>
<body>

<div id="step-before-redirect">
  
  <form action="/civicrm/payment/mobile/checkout" name="print" method="post" id="redirect-form">
    <div align="center" id="submit-button">
      <p>若未自動轉向繳款頁面，請您按「下一步」來繼續進行動作</p>
      <div>
        <input type="hidden" name="redirect" value="1">
        <input type="hidden" name="instrument" value="ApplePay">
        {/literal}
          {foreach from=$params key=key item=item}
            <input type="hidden" name="{$key}" value="{$item}">
          {/foreach}
        {literal}
        <input type="submit" id="submit-button" value="下一步" />
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
{/literal}
{else}


{literal}
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
<script type="text/javascript">

  (function($){
    $(function(){

      window.applePayProcess = {

        cid:'{/literal}{$cid}{literal}',
        provider: "{/literal}{$provider}{literal}",
        description : '{/literal}{$description}{literal}',
        amount : {/literal}{$amount}{literal},

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
          this.request.total = this.total

          $(function(){
              dd('Loading finished.');
          });

        //-- 檢查環境是否可執行 ApplePay
        if (window.ApplePaySession) {
            if (ApplePaySession.canMakePayments) {
                //showApplePayButton();
            }
        }

        },

        doPay : function() {
      
          try {
            //--依據新版 PCI DSS3.1規範，TLSv1.0通訊協定不得再繼續使用，支援協定 TLSv1.1 and TLSv1.2
            //-- 1).建立 Apple Pay request 
            request = {
              countryCode : 'TW', // 國別
              currencyCode : 'TWD', // 幣別
              supportedNetworks : [ 'visa','masterCard' ], //支援卡別
              merchantCapabilities : [ 'supports3DS' ],
              total : {
                label : this.description,
                amount : this.amount
              } //總金額
              // lineItems : [{label:'運費', amount:window.applePayProcess.shippingMethod.amount}], // 細項
              // requiredBillingContactFields : ["postalAddress","name"],           // 要求付款人資訊 (備註：Apple說明,為提供最佳使用者體驗,應避免要求使用者填寫不必要資訊)
              // requiredShippingContactFields : ["postalAddress","name","phone", "email"], // 要求收件人資訊 (備註：Apple說明,為提供最佳使用者體驗,應避免要求使用者填寫不必要資訊)
            };
            
            //-- 2).建立 Apple Pay Session
            const session = new ApplePaySession(2, request);
          
            //-- 3).驗證商家
            session.onvalidatemerchant = function(event) {
              /*
                3-1.取得 event.validationURL 傳送至商家server進行商家驗證.
                3-2.驗證完成後取得 merchant session,並執行 session.completeMerchantValidation(merchantSession)//-- 參數型別為JSON物件
              */

              var data = {
                provider: window.applePayProcess.provider,
                validationURL: event.validationURL
              };

              dd('s2:準備進行商店驗證，傳入資訊');
              dd(JSON.stringify(data));



              // 將validationURL拋到Server端，由Server端與Apple Server做商店驗證
              
              $.ajax({
                type: "POST",
                url: '/civicrm/ajax/applepay/validate',
                data: data,
                dataType: "json",
                success: function (result){

                  //alert(merchantSession);

                  try{
                  dd("Validate Success");
                  merchantSession = result.merchantSession;
                  dd(merchantSession);
                  dd(JSON.parse(merchantSession));
                  dd(session);
                  dd(session.completeMerchantValidation);

                  // if(apple_pay_params.debug_mode == 'yes')
                  // {
                  //   console.log('s3:商店驗證回傳結果');
                  //   console.log(merchantSession);
                  //   console.log(JSON.parse(merchantSession));
                  // }

                  // if(apple_pay_params.debug_mode == 'yes')
                  // {
                  //   console.log('s4:提示付款，按壓指紋');
                  // }
                  session.completeMerchantValidation(JSON.parse(merchantSession));
                  }catch(err){
                    dd(err);
                  }
                }
              });
              




            };
            
            //-- 4).Payment授權完成
            session.onpaymentauthorized = function(event){
              /*
                4-1.將 event.payment.token 傳回商家 server 進行授權
                4-2.將授權結果傳入 session.completePayment(ApplePaySession.STATUS_SUCCESS)
              */
              dd("Start transact");
              data = {

              };

              $.ajax({
                type: "POST",
                url: '/civicrm/ajax/applepay/transact',
                data: data,
                dataType: "json",
                success: function (merchantSession){

                  //alert(merchantSession);

                  dd("Transact Success");

                  // if(apple_pay_params.debug_mode == 'yes')
                  // {
                  //   console.log('s3:商店驗證回傳結果');
                  //   console.log(merchantSession);
                  //   console.log(JSON.parse(merchantSession));
                  // }

                  // if(apple_pay_params.debug_mode == 'yes')
                  // {
                  //   console.log('s4:提示付款，按壓指紋');
                  // }
                  dd(ApplePaySession.STATUS_SUCCESS);
                  session.completePayment(JSON.parse(ApplePaySession.STATUS_SUCCESS));
                }
              });


            };

            //-- 系統自動取消
            session.oncancel = function(){
              dd("Cancel");
              
            };
            
            //開始進行 Apple Pay 支付
            session.begin();
            
          } catch (err) {
            alert(err);
            dd(err);
          }

        } // doPay = function(){ ... }
      } // window.applePayProcess = { ... }
    }); // $(function(){ ... })
  })(jQuery);

  function dd(text){
    // $('dd.console').append("<div> Type:" ++"</div>");
    if(typeof text == 'object'){
      $('dd.console').append("<div> Type:"+(typeof text)+"</div>");
      $('dd.console').append("<div>"+JSON.stringify(text)+"</div>");
      return;
    }
    $('dd.console').append("<div>"+text+"</div>");
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
</style>
</head>
<body>
<div id="step-after-redirect" style="width:320px;margin:0 auto;">
  <div align="center"><p>歡迎使用藍新金流</p></div>

  <div style="border: 2px solid; border-radius: 10px; margin-left:20px; margin-right:20px;">
    <dl>
      <dd>
        <span>訂單編號:</span>
        <span>T2016120001</span>
      </dd>
      <dd>
        <span>商品:</span>
        <span>測試商品</span>
      </dd>
      <dd style="margin-top:10px;">
        <span>總金額:</span>
        <span>{/literal}{$amount}{literal}</span>
      </dd>
    </dl>
  </div>
  
  <div style="margin-left:20px;"><p>選擇支付工具:</p></div>
  <div align="center" style="margin-top: 10px"><button class="apple-pay-button apple-pay-button-white-with-line" onclick="window.applePayProcess.doPay();"></button></div>

  <dd class="console">Console message: </dd>
</div>
{/literal}
{/if}

</body>
</html>
