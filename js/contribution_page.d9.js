(function($){

  'use strict';

  var ts = window.ContribPageParams.ts;

  $(document).one('ready', function () {

    window.ContribPage = {
      // isCreditCardOnly : window.ContribPageParams.creditCardOnly,
      currentContribType : "recurring", // "recurring", "single"
      currentContribInstrument : "creditCard", // "creditCard", "other"
      currentPage : $('#crm-container>form').attr('id'), // "Main", "Confirm", "ThankYou"
      currentPageState : "loading", // "loading", "success"
      currentPriceOption : '',
      currentPriceAmount : "0",
      currentFormStep : 1,
      defaultPriceOption : {},
      singleContribMsgText : false,
      executingAnimationCount : 0,
      complete : 0,
      installments : '',
      installmentsFrequencyUnit: 'month',

      preparePage: function(){
        if (window.ContribPageParams.mobileBackgroundImageUrl) {
          document.querySelector('body').style.setProperty('--mobile-background-url', 'url('+window.ContribPageParams.mobileBackgroundImageUrl+')');
        }

        var $content = $('#main');
        $content.prepend($('#intro_text').prepend($('h1.page-title')));
        $('.sharethis').appendTo('body');

        if (this.currentPage != 'ThankYou') {
          document.querySelector('body').classList.add('special-page-col-sticky');
        }

        if(this.currentPage == 'Main'){

          this.setDefaultValues();

          this.checkUrlParamsAction();

          this.prepareStepInfo();

          this.prepareRecurBtnMsg();

          this.prepareForm();

          this.preparePriceSetBlock();

          this.prepareContribTypeForm();

          this.introReadmore();
        }
        if(this.currentPage == 'Confirm'){
          this.prepareStepInfo();
          this.introReadmore();
          this.updateFormStep(1);
        }

        if(this.currentPage == 'ThankYou'){
          $('#intro_text>*').each(function(){
            if(!$(this).is('.page-title')){
              $(this).remove();
            }
          });
          $('.page-title').after($('#thankyou_text'));

          if(ContribPageParams.thankyouTitle){
            $('.page-title').text(ContribPageParams.thankyouTitle);
            document.title = document.title.replace(ts["Payment failed."], ContribPageParams.thankyouTitle);
          }
          if($(window).width() <= 768) {
            if ($('.messages.error').length) {
              $('#intro_text').hide();
            }
          }
        }

        this.rightColBetter();
      },

      updateExpenditureSection: function(){
        if($('#expenditure-ratio-box').is(':visible')){
          $('#intro_text').fadeIn('slow');
          $('#expenditure-ratio-box').fadeOut('slow');
        }else{
          $('#intro_text').fadeOut('slow');
          $('#expenditure-ratio-box').fadeIn('slow');
        }
      },

      setDefaultValues: function(){
        var defaultPriceOption = window.ContribPage.defaultPriceOption;
        $('[data-default="1"][data-grouping]').each(
          function(i, ele){
            var contribType = ele.dataset.grouping;
            var regExp = /NT\$ ([\d\,\.]+)/;
            var label = $(ele).next().text();
            if(regExp.test(label)){
              if(contribType == 'recurring'){
                defaultPriceOption['recurring'] = $(ele).val();
              }else if(contribType == 'non-recurring'){
                defaultPriceOption['non-recurring'] = $(ele).val();
              }else if(contribType == ''){
                defaultPriceOption['recurring'] = $(ele).val();
                defaultPriceOption['non-recurring'] = $(ele).val();
              }
            }
          }
        );

        if($('[name="is_recur"]:checked').val() == 1){
          this.currentContribType = 'recurring';
          if($('#installments').length){
            this.installments = $('#installments').val();
          }
        }else{
          this.currentContribType = 'non-recurring';
        }

        if ($('[name=frequency_unit]').length) {
          this.installmentsFrequencyUnit = $('[name=frequency_unit]').val();
        }

        if($('[name="amount"]:checked').length > 0){
          if($('[name="amount"]:checked').val() == 'amount_other_radio'){
            this.currentPriceAmount = $('#amount_other').val();
          }
          else{
            this.currentPriceOption = $('[name="amount"]:checked').val();
            var reg = new RegExp(/^NT\$ ([\d\,\.]+)/);
            var option_label = $('[name="amount"]:checked').parent().text();
            if(reg.test(option_label)){
              this.currentPriceAmount = reg.exec(option_label)[1];
            }
          }
          // 'last step' button from confirm page.
          var reg_id = new RegExp(/[\?&]?id=/);
          var reg_retry = /retry=1/;
          if(!reg_id.test(location.search) || reg_retry.test(location.search)){
            this.currentFormStep = 2;
          }
        }
        else if ($('#amount_other').val()) {
          this.currentPriceAmount = $('#amount_other').val();
        }

        if($('#footer_text').length){
          this.singleContribMsgText = $('#footer_text').html();
        }
      },

      prepareStepInfo: function(){
        var $stepInfo = $('<div class="custom-step-info"></div>');
        $stepInfo.append('<span class="step-text step-text-1">'+ts['Amount Step']+'</span>');
        $stepInfo.append('<span class="step-triangle">▶</span>');
        $stepInfo.append('<span class="step-text step-text-2 step-text-3 step-text-4">'+ts['Profile Step']+'</span>');
        $stepInfo.append('<span class="step-triangle">▶</span>');
        $stepInfo.append('<span class="step-text step-text-5">'+ts['Confirm Step']+'</span>');
        $stepInfo.append('<span class="step-triangle">▶</span>');
        $stepInfo.append('<span class="step-text step-text-6">'+ts['Payment Step']+'</span>');
        $stepInfo.find('.step-text').each(function(i, e){
          var $e = $(e);
          if( /Step/.exec($e.text()) ) {
            $e.text($e.text().replace(' Step', ''));
          }
        })
        $stepInfo.insertBefore('#content');
      },

      prepareRecurBtnMsg: function(){
        var $msgBox = ContribPage.$msgBox = $('<div class="error-msg-bg"><div class="error-msg"><div class="error-msg-inner">'+this.singleContribMsgText+'</div></div></div>');
        var $singleBtn = this.createGreyBtn(ts['One-time']);
        $singleBtn.find('a').click(function(event){
          $msgBox.animate({opacity: 0},500,function(){
            $msgBox.hide();
            $msgBox.css('opacity', 1);
            ContribPage.setContributeType('non-recurring');
          });
          event.preventDefault();
        });
        var $recurBtn = this.createBlueBtn(ts['Recurring']);
        $recurBtn.find('a').click(function(event){
          ContribPage.setContributeType('recurring');
          ContribPage.quitMsgBox();
          event.preventDefault();
        });
        $msgBox.find('a').click(function(event){
          if(event.originalEvent.target.classList.contains("error-msg-bg")){
            ContribPage.quitMsgBox();
          }
        });
        $msgBox.appendTo($('body')).find('.error-msg').append($singleBtn).append($recurBtn);
        $msgBox.hide();
      },

      quitMsgBox: function(){

        var $msgBox = ContribPage.$msgBox;
        $msgBox.animate({opacity: 0},500,function(){
          $msgBox.hide();
          $msgBox.css('opacity', 1);
        });
      },

      createBlackBtn:function(text){
        return $('<span><a class="button">'+text+'</a></span>');
      },

      createGreyBtn: function(text){
        return $('<span class="crm-button-type-cancel"><a class="button">'+text+'</a></span>');
      },

      createBlueBtn: function(text){
        return $('<span class="crm-button-type-upload"><a class="button">'+text+'</a></span>');
      },

      createBtn: function(text, className){
        return $('<div class="custom-normal-btn '+className+'">'+text+'</div>');
      },

      prepareForm: function() {
        var dom_step = '';
        for (var i = 1; i <= 3; i++) {
          dom_step += '<div class="crm-container crm-container-md contrib-step contrib-step-'+i+'"></div>';
        }

        $(dom_step).css('opacity', 0).insertBefore('.crm-contribution-main-form-block');

        if ($('[name=cms_create_account]').length >= 1) {
          var $cms_create_account = $('[name=cms_create_account]').parent();
          var $crm_user_signup = $('.crm_user_signup-section');
        }
        $('.contrib-step-1')
          .append($('.progress-block'))
          .append($cms_create_account)
          .append($crm_user_signup)
          .append($('.payment_options-group'))
          .append('<div class="custom-price-set-section">')
          .append($('.payment_processor-section'))
          .append($('#billing-payment-block'))
          .append(this.createStepBtnBlock(['next-step']));
        $('.contrib-step-1 .step-action-wrapper').addClass('hide-as-show-all');
        var exec_step = 2;
        var $contribStep2 = $('.contrib-step-'+exec_step)
        $contribStep2.append(this.createStepBtnBlock(['last-step', 'priceInfo']).addClass('crm-section'));
        if($('fieldset#for_organization').length >= 1 && $('.is_for_organization-section').length == 0){
          $contribStep2.append($('fieldset#for_organization'));
        }
        else if ($(".is_for_organization-section").length > 0) {
          $contribStep2.append($(".is_for_organization-section, #for_organization"));
        }
        if($('.custom_pre_profile-group fieldset').length >= 1){
          $contribStep2.append($('.custom_pre_profile-group'));
        }
        else {
          $contribStep2.append($('div.email-5-section'));
        }
        $contribStep2.append(this.createStepBtnBlock(['last-step', 'next-step']).addClass('hide-as-show-all'));
        exec_step += 1;
        if($('.custom_post_profile-group fieldset').length >= 1){
          $('.contrib-step-'+exec_step)
            .append(this.createStepBtnBlock(['last-step', 'priceInfo']).addClass('crm-section').addClass('hide-as-show-all'))
            .append($('.custom_post_profile-group'))
            .append(this.createStepBtnBlock(['last-step', 'next-step']).addClass('hide-as-show-all'));
          exec_step += 1;
        }
        if($('.premiums-group').length && $('.custom_post_profile-group fieldset').length){
          $('.custom_post_profile-group').after($('.premiums-group'));
        }else if($('.premiums-group').length && $('.custom_pre_profile-group fieldset').length){
          $('.custom_pre_profile-group').after($('.premiums-group'));
        }
        exec_step -= 1;
        if ($('.contrib-step-'+exec_step+' .step-action-wrapper .next-step').length > 0) {
          $('.contrib-step-'+exec_step+' .step-action-wrapper').remove();
        }
        $('.contrib-step-'+exec_step)
          .append(this.createStepBtnBlock(['last-step']).addClass('hide-as-show-all').addClass('crm-section'))
          .append($('.crm-submit-buttons'));
        $('.crm-contribution-main-form-block').hide();

        if($("#billing-payment-block").length == 0){
          $('.crm-section payment_processor-section').insertBefore($('.custom_pre_profile-group'));
        }

        /** Afraid it ban the contributor
        if(this.isCreditCardOnly){
          $('.payment_processor-section, #billing-payment-block').hide();
        }
        */

        // For login user. first_name and last_name field will be locked, and the hint will be displayed.
        if ($('body.is-civicrm-user').length) {
          $('body.is-civicrm-user .first_name-section .description').insertAfter('body.is-civicrm-user .first_name-section');
          $("body.is-civicrm-user .first_name-section").bind("DOMSubtreeModified", function() {
            $('body.is-civicrm-user .first_name-section .description').insertAfter('body.is-civicrm-user .first_name-section');
          });
        }

        $('#crm-container>form').submit(function(){
          if($('label.error').length){
            ContribPage.updateShowAllStep();
          }
        });

        this.updateFormStep();

      },

      createStepBtnBlock: function(objs){
        var $step_block = $('<div class="step-action-wrapper">');
        objs.forEach(function(obj_name){
          if(obj_name == 'last-step'){
            $step_block.append(ContribPage.createGreyBtn(ts['<< Previous']).addClass(obj_name).click(function(event){
              ContribPage.setFormStep(ContribPage.currentFormStep - 1);
              event.preventDefault();
            }));  
          }
          if(obj_name == 'next-step'){
            $step_block.append(ContribPage.createBlueBtn(ts['Next >>']).addClass(obj_name).click(function(event){
              ContribPage.setFormStep(ContribPage.currentFormStep + 1);
              event.preventDefault();
            }));
          }
          if(obj_name == 'priceInfo'){
            $step_block.append('<div class="price-selected-info priceInfo"><div class="info-is-recur"></div><div class="info-price">NTD&nbsp;<span class="info-price-amount"></span></div></div>');
          }
        });
        return $step_block;
      },

      prepareContribTypeForm: function(){
        $('.priceSet-block').before($('<div class="contrib-type-block custom-block"><label>'+ts['One-time or Recurring Contributions']+'</label><div class="contrib-type-btn"></div></div><div class="instrument-info-panel custom-block"></div>'));
        if($('[name=is_recur][value=1]').length > 0){
          var $recurBtn = this.createBtn(ts["Recurring Contributions"],"custom-recur-btn");
          $recurBtn.click(function(){
            ContribPage.setContributeType('recurring');
          });
          $('.contrib-type-btn').append($recurBtn);
        }
        if($('[name=is_recur]').length==0 || $('[name=is_recur][value=0]').length > 0){
          var $singleBtn = this.createBtn(ts["One-time Contribution"],"custom-single-btn");
          $singleBtn.click(function(){
            if(ContribPage.singleContribMsgText){
              ContribPage.$msgBox.show();
            }else{
              ContribPage.setContributeType('non-recurring');
            }
          });
          $('.contrib-type-btn').append($singleBtn);
        }
        this.updateContributeType(false);
      },

      preparePriceSetBlock: function(){
        $('<div class="priceSet-block custom-block"><label>'+ts['Choose Amount Option or Custom Amount']+'</label><div class="price-set-btn"></div></div>').appendTo($('.custom-price-set-section'));
        if($('#amount_other').length){
          var other_amount = '';
          if(!this.currentPriceOption){
            other_amount = this.currentPriceAmount;
          }
          var $other_amount_block = $('<div class="custom-other-amount-block custom-input-block"><label for="custom-other-amount">'+ts['Other Amount']+'</label><input placeholder="'+ts['Type Here']+'" name="custom-other-amount" id="custom-other-amount" type="number" min="0" class="custom-input" value="'+other_amount+'"></input></div>');
          var doClickOtherAmount = function(){
            var reg = new RegExp(/^$|^\d+$/);
            var amount = $(this).val();
            if(reg.test(amount) && parseInt(amount) > 0){
              ContribPage.setPriceOption();
              ContribPage.setPriceAmount(amount);
            }
            else if(amount != ''){
              $(this).val(0);
            }
          };
          $other_amount_block.find('input').keyup(doClickOtherAmount).click(doClickOtherAmount);
          $other_amount_block.find('input').blur(function(){
            var amount = $(this).val();
            var isSelectAmountOption = $('.amount-section label.crm-form-radio input:not([value="amount_other_radio"]):checked').length;
            if((amount == '' || amount == 0) && !isSelectAmountOption){
              var defaultOption = ContribPage.defaultPriceOption[ContribPage.currentContribType];
              ContribPage.setPriceOption(defaultOption);
            }
          });
          $('.priceSet-block').append($other_amount_block);
        }

        if($('[name=installments]').length > 0 ){
          var installments = this.installments;
          var frequencyUnitWords = ( this.installmentsFrequencyUnit == 'year' ) ? ts['Yearly Installments'] : ts['Monthly Installments'];
          var $installments_block = $('<div class="custom-installments-block custom-input-block"><label for="custom-installments">'+frequencyUnitWords+'</label><input placeholder="'+ts["No Limit"]+'" name="custom-installments" id="custom-installments" type="number" class="custom-input active" min="0" value="'+installments+'"></input></div>');
          var doClickInstallments = function(){
            var installments = $(this).val();
            if(installments == 0){
              $(this).val("");
            }
            ContribPage.setInstallments(installments);
          };
          $installments_block.find('input').keyup(doClickInstallments).click(doClickInstallments);
          $('.priceSet-block').append($installments_block);
        }

        // For frequency units
        if ($('#frequency_unit').length) {
          $('#recur-options-interval').insertAfter('.custom-installments-block');
          $('#frequency_unit').change(this.updateFrequencyUnit);
        }

        this.updatePriceSetOption();
      },

      updateFrequencyUnit: function(){
        window.ContribPage.installmentsFrequencyUnit = $(this).val();
        var frequencyUnitWords = ( window.ContribPage.installmentsFrequencyUnit == 'year' ) ? ts['Yearly Installments'] : ts['Monthly Installments'];
        $('.custom-installments-block label').text(frequencyUnitWords);
        window.ContribPage.updateContribInfoLabel();
      },

      checkUrlParamsAction: function() {
        var paramString = window.location.search.substring(1);
        var params = [];
        paramString.split("&").forEach(function(keyValue) {
          var keyValueArray = keyValue.split("=");
          var key = decodeURIComponent(keyValueArray[0]);
          var value = decodeURIComponent(keyValueArray[1]);
          params[key] = value;
        });
        var amount = this.currentPriceAmount.toString().replace(',','');

        if (params['_ppid'] && params['_ppid'] == $('.payment_processor-section input:checked').val() && 
          params['_grouping'] && params['_grouping'] == this.currentContribType && 
          params['_amt'] && params['_amt'] == amount &&
          params['_instrument'] ) {
          window.ContribPage.currentFormStep = 2;
          cj(document).ajaxComplete(function( event, xhr, settings ) {
            if(settings.url.substring(0,38) == '/civicrm/contribute/transact?snippet=4' && 
              cj(xhr.responseText).find('input[id^=civicrm-instrument-dummy]:checked').length) {
              // setTimeout(function(){
              // }, 1000);
              xhr.complete(function(){
                var interval = setInterval(function(){
                  if(cj('input[id^=civicrm-instrument-dummy]:checked').length && window.ContribPage.complete){
                    if(cj('input[id^=civicrm-instrument-dummy]:checked').val() != params['_instrument']){
                      window.ContribPage.setFormStep(1);
                      clearInterval(interval);
                    }
                  }
                }, 100);
              })
              cj(event.currentTarget).unbind('ajaxComplete');
            }
          });
        }

      },

      updatePriceSetOption: function(){
        $('.price-set-btn').html("");
        var reg = new RegExp(/^NT\$ ([\d\,\.]+) ?(.*)$/);
        var grouping_text = this.currentContribType;
        $('.amount-section label.crm-form-radio').each(function(ele){
          var $this = $(this);
          var this_grouping = $this.find('input').attr('data-grouping');
          if(this_grouping == grouping_text || this_grouping == undefined || this_grouping == ''){
            var text = $(this).find('.elem-label').text();
            if(reg.test(text)){
              var reg_result = reg.exec(text);
              var amount = reg_result[1];
              var val = $this.find('input').val();
              var words = reg_result[2];
              if(words.length > 6){
                var multitext_class = ' multitext';
              }else{
                var multitext_class = '';
              }
              var $option = $('<div data-amount="'+val+'"><span class="amount">'+amount+'</span><span class="description'+multitext_class+'">'+words+'</span></div>');
              $option.click(function(){
                ContribPage.setPriceOption($(this).attr('data-amount'));
                // ContribPage.setFormStep(2);
              });
              $('.price-set-btn').append($option);
            }
          }
        });
        this.updatePriceOption();
        this.updatePriceAmount();
      },

      setPriceOption: function(val){
        this.currentPriceOption = val;
        if(this.currentPriceOption){
          // Use cj to trigger premium block. Refs #29369
          cj('.amount-section [value="'+this.currentPriceOption+'"]').click();
          var amount = $('.price-set-btn div[data-amount='+this.currentPriceOption+'] .amount').text();
          this.setPriceAmount(amount);
        }
        else {
          $('.amount-section .crm-form-radio:last-child input').click();
        }
        this.updatePriceOption();
      },

      updatePriceOption: function(){
        $('.price-set-btn div').removeClass('active');
        if(this.currentPriceOption){
          $('.price-set-btn div[data-amount='+this.currentPriceOption+']').addClass('active');
        }
      },

      setPriceAmount: function(amount){
        if(this.currentPriceAmount != amount){
          this.currentPriceAmount = amount;
          if(!this.currentPriceOption){
            $('input#amount_other').val(this.currentPriceAmount);
            // For trigger premium block, Refs #29369
            cj('input[name=amount_other]').trigger('change');
          }
          this.updatePriceAmount();
        }
      },

      updatePriceAmount: function(){
        $('.info-price-amount').text(this.currentPriceAmount);
        if(this.currentPriceAmount && !this.currentPriceOption){
          $('input#custom-other-amount').addClass('active');
        }else{
          $('input#custom-other-amount').val('').removeClass('active');
        }
      },

      /**
       * WHEN setContributeType DO setContribInstrument 
       * @param {[type]} type [description]
       */
      setContributeType: function(type) {
        if( this.currentContribType != type ){
          this.currentContribType = type;
          if(!this.currentContribType){
            return;
          }
          if(this.currentContribType == 'non-recurring'){
            $('[name=is_recur][value=0]').click();
          }
          if(this.currentContribType == 'recurring'){
            $('[name=is_recur][value=1]').click();
          }

          this.updateContributeType(true);
        }
      },


      updateContributeType: function(isSelectDefaultOption) {
        if(this.currentContribType == 'non-recurring'){
          $('.contrib-type-btn div').removeClass('selected');
          $('.custom-single-btn').addClass('selected');
          $('.custom-installments-block').hide();
          if ($('#recur-options-interval').length) {
            $('#recur-options-interval').hide();
          }
        }
        if(this.currentContribType == 'recurring'){
          $('.contrib-type-btn div').removeClass('selected');
          $('.custom-recur-btn').addClass('selected');
          $('.custom-installments-block').show();
          if ($('#recur-options-interval').length) {
            $('#recur-options-interval').show();
          }
        }
        this.updateContribInfoLabel();
        this.updatePriceSetOption();

        if (isSelectDefaultOption) {
          var newPriceOption = this.defaultPriceOption[this.currentContribType] ? this.defaultPriceOption[this.currentContribType] : '';
          this.setPriceOption(newPriceOption);
        }
        else {
          if (this.currentPriceOption) {
            this.setPriceOption(this.currentPriceOption);
          }
          else {
            this.setPriceOption();
            this.setPriceAmount(this.currentPriceAmount);
          }
        }
      },

      updateContribInfoLabel: function(){
        if(this.currentContribType == 'non-recurring'){
          $('.info-is-recur').text(ts['One-time Contribution']);
        }
        if(this.currentContribType == 'recurring'){
          if (this.installmentsFrequencyUnit == 'year') {
            var unitText = ts['Yearly Recurring Contributions'];
            var unitSuffixText = ts['Years Recurring Contributions'];
          }
          else {
            var unitText = ts['Monthly Recurring Contributions'];
            var unitSuffixText = ts['Months Recurring Contributions'];
          }
          if(!this.installments || this.installments == "0"){
            $('.info-is-recur').text(unitText);
          }else{
            $('.info-is-recur').text(this.installments+' '+unitSuffixText);
          }
        }
      },

      setFormStep: function(step) {
        if(this.currentFormStep == 1 && step == 2){
          // Check instrument is credit card
          var error_msg = [];
          console.log(this.currentPriceAmount);
          if(!this.currentPriceAmount || this.currentPriceAmount == 0){
            error_msg.push('Please enter a valid amount.');
          }
          if(this.currentContribType == 'recurring' && $('[data-credit-card="1"]:checked').length == 0){
            error_msg.push('You cannot set up a recurring contribution if you are not paying online by credit card.');
          }
          if(window.ContribPageParams.minAmount && !this.currentPriceOption && this.currentPriceAmount < parseInt(window.ContribPageParams.minAmount)){
            error_msg.push('Contribution amount must be at least %1');
            }
          if(window.ContribPageParams.maxAmount && !this.currentPriceOption && this.currentPriceAmount > parseInt(window.ContribPageParams.maxAmount)){
            error_msg.push('Contribution amount cannot be more than %1.');
          }

          if(error_msg.length){
            error_msg.forEach(function(term){
              $('.contrib-step-1 .step-action-wrapper').before($('<label generated="true" class="error" style="color: rgb(238, 85, 85); padding-left: 10px;">'+ts[term]+'</label>'))
            });
            setTimeout(function(){
              $('.contrib-step-1 .error').remove();
            }, 5000);
            return;
          }
        }

        $('.hide-as-show-all').show();
        if(this.currentFormStep != step){
          this.currentFormStep = step;
          this.updateFormStep(1);
        }
      },

      updateFormStep: function(isScrollAnimate) {
        var currentStepClassName = 'contrib-step-'+this.currentFormStep;
        $('[class*=contrib-step-]').each(function(){
          var $this = $(this);
          /**
          class name use type:
            * type-is-back
            * type-is-front
          */
          if($this.hasClass('type-is-front') && !$this.hasClass(currentStepClassName)){
            /** first scroll to top 0.5 second */
            window.ContribPage.executingAnimationCount++;
            setTimeout(function(){
              $this.removeClass('type-is-front').addClass('type-is-fade-out').css({'opacity': 1});
              /** then fade change */
              $this.animate({'opacity': 0} ,300, function(){
                window.ContribPage.executingAnimationCount--;
                $this.removeClass('type-is-fade-out').addClass('type-is-back');
              });
            }, 500);
          }
          else if($this.hasClass(currentStepClassName)){
            /** first scroll to top 0.5 second */
            window.ContribPage.executingAnimationCount++;
            setTimeout(function(){
              $this.removeClass('type-is-back').addClass('type-is-fade-in').css({'opacity': 0});
              /** then fade change */
              $this.animate({'opacity': 1} ,300,  function(){
                window.ContribPage.executingAnimationCount--;
                $this.removeClass('type-is-fade-in').addClass('type-is-front');
              });
            }, 500);
          }
          else if(!$this.hasClass('type-is-back')){
            $this.addClass('type-is-back');
          }
        });

        if (isScrollAnimate) {
          var topPosition = $('#content-main').offset().top - 30;
          $('html,body').animate({ scrollTop: topPosition }, 500);
        }

        $('.step-text').removeClass('active');
        if(this.currentPage == 'Main'){
          $('.step-text-' + this.currentFormStep).addClass('active');
          if($(window).width() <= 480){
            $('.custom-step-info').scrollLeft($('.custom-step-info span.active').offset().left-$('.custom-step-info span').offset().left);
          }
        }else if(this.currentPage == 'Confirm'){
          $('.custom-step-info').scrollLeft($('.step-text-5').offset().left);
          $('.step-text-5').addClass('active');
        }
      },

      updateShowAllStep: function(){
        $('.hide-as-show-all').hide();
        $('[class*=contrib-step-]').each(function(){
          var $this = $(this);
          if($this.hasClass('contrib-step-1') && $this.find('.error').length == 0) {
            return ;
          }
          else {
            // First step need to show.
            $('.contrib-step-2 .step-action-wrapper.crm-section').hide();
          }
          $this.removeClass('type-is-back').addClass('type-is-front').css({opacity: 1});
        });
        ContribPage.currentFormStep = 2;
      },

      setPageState: function(state) {
        this.currentPageState = state;
        this.updatePageState();
      },

      updatePageState: function() {

      },

      setInstallments: function(installments) {
        if(installments) {
          installments = parseInt(installments);
        }
        if(this.installments != installments){
          this.installments = installments;
          $('#installments').val(installments)
          this.updateInstallments();
        }
      },

      updateInstallments: function(){
        this.updateContribInfoLabel();
      },

      isArraysEqual: function(a, b) {
        // refs: https://stackoverflow.com/questions/3115982/how-to-check-if-two-arrays-are-equal-with-javascript
        if (a === b) return true;
        if (a == null || b == null) return false;
        if (a.length != b.length) return false;

        // If you don't care about the order of the elements inside
        // the array, you should sort both arrays here.

        for (var i = 0; i < a.length; ++i) {
          if (a[i] !== b[i]) return false;
        }
        return true;
      },

      /**
       * refs #28603. Added read more feature to intro text.
       */
      introReadmore: function() {
        var introMaxHeight = 450;
        if ($('#intro_text').height() > introMaxHeight) {
          var readmoreText = {
            open: window.ContribPageParams.ts['Read more'],
            close: window.ContribPageParams.ts['Close']
          };

          $('#intro_text').wrapInner('<div class="intro_text-inner"><div class="intro_text-content is-collapsed"></div></div>');
          $(".intro_text-content").after('<button class="intro_text-readmore-btn" type="button">' + readmoreText.open + '</button>');

          $('#intro_text').on('click', '.intro_text-readmore-btn', function(e) {
            e.preventDefault();

            var $trigger = $(this),
                $target = $('.intro_text-content');

            if ($target.length) {
              if ($target.hasClass('is-collapsed')) {
                $target.removeClass('is-collapsed').addClass('is-expanded');
                $trigger.addClass('is-active').text(readmoreText.close);
              }
              else {
                $target.removeClass('is-expanded').addClass('is-collapsed');
                $trigger.removeClass('is-active').text(readmoreText.open);
              }
            }
          });
        }
      },

      /**
       * refs #28603. Improve the interface on the right column when device is desktop.
       * Detecting device is through the CSS media query (contribution_page.css).
       */
      rightColBetter: function() {
        var leftCol = document.querySelector('#intro_text'),
            rightCol = document.querySelector('#main-inner') ? document.querySelector('#main-inner') : document.querySelector('#main .row-offcanvas');

        if (leftCol && rightCol) {
          var leftColOuterHeight = leftCol.offsetHeight,
              rightColOuterHeight = rightCol.offsetHeight,
              rightColStyle = getComputedStyle(rightCol);

          // refs #28603 28f.
          // Added state class to body by column height,
          // and we can use CSS to add scrollbar to the specified column.
          if (leftColOuterHeight > rightColOuterHeight) {
            document.querySelector('body').classList.add('special-page-left-col-higher');
          }
          else if (leftColOuterHeight == rightColOuterHeight) {
            document.querySelector('body').classList.add('special-page-col-equal');
          }
          else {
            document.querySelector('body').classList.add('special-page-right-col-higher');
          }

          // If the height of the left column is greater than the right column,
          // add state class to right column when sticky is triggered,
          // so that we can limit the height of the right column
          // and enable the scrollbar by CSS (contribution_page.css).
          if (leftColOuterHeight > rightColOuterHeight && rightColStyle['position'] == 'sticky') {
            var rightColTop = rightColStyle['top'],
                rightColTopNum = parseFloat(rightColTop),
                buffer = 5,
                minTop = 0,
                maxTop = rightColTopNum + buffer;

            var ioCallback = function(entries) {
              entries.forEach(function(entry) {
                entry.target.classList.toggle('is-sticky', entry.intersectionRect.top >= minTop && entry.intersectionRect.top <= maxTop);
              });
            }

            var ioOptions = {
              threshold: []
            };

            for (var i = 0; i <= 1; i += 0.005) {
              ioOptions.threshold.push(i);
            }

            var observer = new IntersectionObserver(ioCallback, ioOptions);
            observer.observe(rightCol);
          }
        }
      },

      prepareAfterAll: function(){
        $('body').addClass('special-page-finish');
        setTimeout(function(){
          $('.loading-placeholder-wrapper').remove();
        }, 1000);
        $('.payment_options-group').hide();
        $('#page').css('background', 'none').css('height','unset');
        var interval = setInterval(function(){
          if (window.ContribPage.executingAnimationCount == 0) {
            window.ContribPage.complete = 1;
            clearInterval(interval);
          }
        }, 100);
      }


    };

    try{
      window.ContribPage.preparePage();
    }catch(e){
      console.log(e);
      window.ContribPage.prepareAfterAll();
    }
    window.ContribPage.prepareAfterAll();
  });
})(cj);
