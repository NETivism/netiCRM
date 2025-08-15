{*Direct Pay - TapPay Fields*}
<style>{literal}
.tp-wrapper {
  max-width: 480px;
  width: auto;
  margin: 8px auto;
  display: flex;
  justify-content: space-between;
  align-content: center;
  flex-wrap: nowrap;
  position: relative;
}
.tp-card-expiration-date {
  width: 100%;
}
.tp-label {
  margin-right: 10px;
}
.tp-field {
  height: 40px;
  border: 1px solid gray;
  margin: 5px 0;
  padding: 5px;
  position: relative;
}
.tp-card {
  width: 30px;
  height: 20px;
}
.tp-field.card-number {
  width: 100%;
}
.tp-field.card-expiration-date{
  width: 100%;
}
.tp-field.card-ccv{
  width: 80px;
}
.tp-field.fcardholder {
  width: 100%;
  padding: 0;
  height: 50px;
}
.fcardholder > input {
  border: none !important;
  width: 100%;
  height: 100%;
  background: transparent;
  outline: none;
  box-sizing: border-box;
  padding-block: 0;
  padding-inline: 0;
  padding: 5px;
}
.fcardholder > input.invalid {
  color: red;
}
.tp-field .overlay {
  position: absolute;
  width: 100%;
  height: 100%;
  z-index: 99;
  left: 0;
  top: 0;
  cursor: not-allowed;
}
.crm-button-type-upload,
.crm-button-type-upload .form-submit{
  margin:0;
  width: 100%;
  height: 40px;
}
.tappay-field-focus {
  border-color: #66afe9;
  outline: 0;
  -webkit-box-shadow: inset 0 1px 1px rgba(0, 0, 0, .075), 0 0 8px rgba(102, 175, 233, .6);
  box-shadow: inset 0 1px 1px rgba(0, 0, 0, .075), 0 0 8px rgba(102, 175, 233, .6);
}
#card-number {
  padding-left: 55px;
}
#loading {
  position: absolute;
  right: 10px;
  top: 30%;
}
.tp-wrapper > .wrapper-name {
  width: 40%;
}
.tp-wrapper > .wrapper-email {
  width: 55%;
}
{/literal}</style>
<div class="tp-wrapper">
  <div class="wrapper-name">
    <label class="tp-label">{ts}Payer Name{/ts}</label>
    <div class="tp-field fcardholder fname">
      <input type="text" name="cardholder_name" id="cardholder-name" placeholder="* {ts}Same on your credit card.{/ts}" required>
    </div>
  </div>
  <div class="wrapper-email">
    <label class="tp-label">{ts}Payer Email{/ts}</label>
    <div class="tp-field fcardholder femail">
      <input type="email" name="cardholder_email" id="cardholder-email" placeholder="* {ts}Email will verified by credit card issuer.{/ts}" value="{$cardholder_email}" required>
    </div>
  </div>
</div>
<div class="tp-wrapper">
  <label class="tp-label">{ts}Credit Card Number{/ts}</label>
  <div>
    <img src="https://js.tappaysdk.com/tpdirect/image/visa.svg" alt="" class="tp-card">
    <img src="https://js.tappaysdk.com/tpdirect/image/mastercard.svg" class="tp-card">
    <img src="https://js.tappaysdk.com/tpdirect/image/jcb.svg" alt="" class="tp-card">
  </div>
</div>
<div class="tp-wrapper">
  <div class="tp-field card-number" id="card-number">
    <img src="https://js.tappaysdk.com/tpdirect/image/card.svg" alt="" style="width: 30px; position: absolute; left: 10px;" id="card-type-img">
  </div>
</div>
<div class="tp-wrapper">
  <div {if $hideccv}class="tp-card-expiration-date"{/if}>
    <label class="tp-label">{ts}Expiration Date{/ts}</label>
    <div class="tp-field card-expiration-date" id="card-expiration-date"></div>
  </div>
  {if !$hideccv}
  <div>
    <label class="tp-label" title="{ts}Please enter a valid Credit Card Verification Number{/ts}">{ts}CCV{/ts} <i class="zmdi zmdi-help"></i></label>
    <div class="tp-field card-ccv" id="card-ccv"></div>
  </div>
  {/if}
</div>
<script src="https://js.tappaysdk.com/sdk/tpdirect/v5.20.0"></script>
<script>{literal}
cj(document).ready(function($){
  var appID = '{/literal}{$payment_processor.signature}{literal}';
  var appKey = '{/literal}{$payment_processor.subject}{literal}';
  var qfKey = '{/literal}{$qfKey}{literal}';
  var className = '{/literal}{$class_name}{literal}';
  var $button = $("input[name={/literal}{$button_name}{literal}]");
  var $backButton = $("input[name={/literal}{$back_button_name}{literal}]");
  $button.prop("disabled", 1);
  //var redirect = '{/literal}{$redirect}{literal}';
  //var failRedirect = '{/literal}{$fail_redirect}{literal}';
  var lock = false;
  var submitted = getCookie(qfKey);
  if (submitted == "1" || submitted >= 1) {
    $button.remove();
    $('.tp-wrapper').hide();
    return;
  }
  else {
    $button.show();
  }

  if (appID.length <= 0 && appKey.length <= 0) {
    return;
  }

  // prevent close tab
  window.onbeforeunload = function(){
    return true;
  };

  appID = parseInt(appID);
  TPDirect.setupSDK(appID, appKey, '{/literal}{if $payment_processor.is_test}sandbox{else}production{/if}{literal}');
  var cardFields = {
    number: {
      // css selector
      element: '#card-number',
      placeholder: '____ ____ ____ ____'
    },
    expirationDate: {
      // DOM object
      element: document.getElementById('card-expiration-date'),
      placeholder: 'MM / YY'
    },
    ccv: {
      element: '#card-ccv',
      placeholder: '000'
    }
  };

  // delete ccv if needed
  {/literal}{if $hideccv}delete cardFields['ccv'];{/if}{literal}

  TPDirect.card.setup({
    fields: cardFields,
    styles: {
      // Style all elements
      'input': {
        'color': '#555555'
      },
      // Styling ccv field
      'input.ccv': {
        'font-size': '16px'
      },
      // Styling expiration-date field
      'input.expiration-date': {
        'font-size': '16px'
      },
      // Styling card-number field
      'input.card-number': {
        'font-size': '16px'
      },
      // style focus state
      ':focus': {
        'color': 'black'
      },
      // style valid state
      '.valid': {
        'color': 'green'
      },
      // style invalid state
      '.invalid': {
        'color': 'red'
      },
      // Media queries
      // Note that these apply to the iframe, not the root window.
      '@media screen and (max-width: 400px)': {
        'input': {
          'color': 'orange'
        }
      }
    }
  });


  function isValidEmail(email) {
    var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  }

  function isValidName(name) {
    var nameRegex = /^[0-9a-zA-Z,.\'\s]+$/;
    return nameRegex.test(name);
  }

  function checkRequiredFields() {
    var cardholderName = $("#cardholder-name").val().trim();
    var cardholderEmail = $("#cardholder-email").val().trim();
    var $emailInput = $("#cardholder-email");
    var $nameInput = $("#cardholder-name");
    
    var nameValid = cardholderName.length > 0 && isValidName(cardholderName);
    var emailValid = cardholderEmail.length > 0 && isValidEmail(cardholderEmail);
    
    if (cardholderName.length > 0 && !isValidName(cardholderName)) {
      $nameInput.addClass('invalid').removeClass('valid');
    } else if (cardholderName.length > 0) {
      $nameInput.addClass('valid').removeClass('invalid');
    } else {
      $nameInput.removeClass('valid invalid');
    }
    
    if (cardholderEmail.length > 0 && !isValidEmail(cardholderEmail)) {
      $emailInput.addClass('invalid').removeClass('valid');
    } else if (cardholderEmail.length > 0) {
      $emailInput.addClass('valid').removeClass('invalid');
    } else {
      $emailInput.removeClass('valid invalid');
    }
    
    return nameValid && emailValid;
  }

  TPDirect.card.onUpdate(function (update) {
    var fieldsValid = checkRequiredFields();
    if (update.canGetPrime && !lock && fieldsValid) {
      $button.prop("disabled", false);
      $backButton.prop("disabled", 1);
    }
    else {
      $button.prop("disabled", 1);
      $backButton.prop("disabled", false);
    }

    // cardTypes = ['mastercard', 'visa', 'jcb', 'amex', 'unknown']
    var src = $("#card-type-img").prop("src").replace(/[^\/]*\.svg$/, '');
    if (typeof update.cardType === 'string' && update.cardType !== 'unknown' && update.cardType.length > 0) {
      // Handle card type visa.
      $("#card-type-img").prop("src", src + update.cardType + '.svg');
    }
    else {
      $("#card-type-img").prop("src", src + 'card.svg');
    }
  });

  $("#cardholder-name, #cardholder-email").on('input', function() {
    var fieldsValid = checkRequiredFields();
    var tappayStatus = TPDirect.card.getTappayFieldsStatus();
    if (tappayStatus.canGetPrime && !lock && fieldsValid) {
      $button.prop("disabled", false);
      $backButton.prop("disabled", 1);
    }
    else {
      $button.prop("disabled", 1);
      $backButton.prop("disabled", false);
    }
  });
	$("#Confirm").bind("keypress", function (e) {
    if (e.keyCode == 13) {
      e.preventDefault();
      return false;
    }
	});

  $button.click(function(){
    if (!$button.prop("disabled")) {
      $button.data('clicked', true);
    }
  });

  $('#Confirm').on('submit', function(e){
    if ($button.data('clicked')) {
      e.preventDefault();
    }
    $button.prop("disabled", 1); // prevet double submit

    const tappayStatus = TPDirect.card.getTappayFieldsStatus();
    if (tappayStatus.canGetPrime === true) {
      window.onbeforeunload = null;
      TPDirect.card.getPrime(function(result)  {
        window.onbeforeunload = function(){ return true; };
        if (result.status !== 0) {
          $button.prop("disabled", false);
          $button.data('clicked', null);
        }
        else {
          window.onbeforeunload = null;
          lock = true;
          $button.prop("disabled", 1); // prevet double submit
          $("form#Confirm").append('<i class="zmdi zmdi-spinner zmdi-hc-spin" id="loading"></i>');
          $(".tp-field").prepend('<div class="overlay"></div>');
          $("input[name=prime]").val(result.card.prime);
          $('form#Confirm').unbind('submit').submit();
        }
      });
    }
  });
});
{/literal}</script>
<!--TapPay.tpl-->
