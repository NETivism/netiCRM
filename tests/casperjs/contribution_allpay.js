casper.options.waitTimeout = 10000;

var s = {
  testNum : 13,
  baseURL : 'http://d7.neticrm.deb:8080/civicrm/contribute/transact?reset=1&action=preview&id=2',
  cPage : 'CasperJS測試金流 | d7.neticrm.deb',
  userEmail : 'youremail@test.tw',
  amountValue : '579',
  amountLabel : 'NT$ 100.00 ',
  allpayCpage : '信用卡資料填寫-歐付寶allPay第三方支付',
  allpayVpage : 'OTP刷卡簡訊驗證-歐付寶allPay第三方支付'
};

// Step 1: Contribution page
casper.test.begin('Contribution page test (payment processors: allpay)...', s.testNum, function suite(test) {
  casper.start(s.baseURL, function() {
    test.assertTitle(s.cPage, 'Contribution page: page title is OK. (' + s.cPage + ')');
    test.assertExists('div.crm-contribution-main-form-block', 'Contribution page: main form block is exist.');
    test.assertExists('form#Main', 'Contribution page: main form is exist.');
    this.captureSelector('contribution_page_1.png', 'div.page');
    this.fill('form[action="/civicrm/contribute/transact"]', {
      'email-5': s.userEmail,
      'amount': s.amountValue,
    },
    true);
  });

  // Step 2: Contribution Confirm
  casper.waitForUrl(/_qf_Confirm_display/, function(){
    test.assertUrlMatch(/_qf_Confirm_display=true/, 'Contribution Confirm');
    test.assertExists('.amount_display-group .display-block strong', 'Contribution Confirm: amount field is exist.');
    test.assertSelectorHasText('.amount_display-group .display-block strong', s.amountLabel, 'Contribution Confirm: amount label is OK. (' + s.amountLabel + ')');
    test.assertExists('.contributor_email-section .content', 'Contribution Confirm: email field is exist.');
    test.assertSelectorHasText('.contributor_email-section .content', s.userEmail, 'Contribution Confirm: email value is OK. (' + s.userEmail + ')');
    this.captureSelector('contribution_page_2.png', 'div.page');
    this.click('input[name="_qf_Confirm_next"]');
  });

  // Step 3: Allpay CreditCard Info
  casper.waitForUrl('http://pay-stage.allpay.com.tw/CreditPayment/CreateCreditCardInfo', function(){
    test.assertUrlMatch(/CreateCreditCardInfo/, "Allpay CreditCard Info");
    test.assertTitle(s.allpayCpage, 'Allpay CreditCard Info: page title is OK. (' + s.allpayCpage + ')');
    test.assertExists('form[action="/CreditPayment/CreateCreditCardInfo"]', 'Allpay CreditCard Info: form is exist.');
    this.captureSelector('contribution_page_3.png', 'div.pay_content');
    this.fill('form[action="/CreditPayment/CreateCreditCardInfo"]', {
      'CardHolder': 'Poliphilo',
      'Cellphone': 'your cellphone number',
      'CardType': 'VISA',
      'CardNoPart1': '4311',
      'CardNoPart2': '9522',
      'CardNoPart3': '2222',
      'CardLastFourDigit': '2222',
      'CardValidMM': '09',
      'CardValidYY': '31',
      'CardAuthCode': '222',
      'Agree': '1',
    },
    true);
  });

  // Step 4: Allpay VerifySMS
  casper.waitForUrl('http://pay-stage.allpay.com.tw/CreditPayment/VerifySMS', function(){
    test.assertUrlMatch(/VerifySMS/, "Allpay VerifySMS");
    test.assertTitle(s.allpayVpage, 'Allpay VerifySMS: page title is OK. (' + s.allpayVpage + ')');
  });

  casper.run(function() {
    test.done();
  });
});
