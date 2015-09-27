casper.options.waitTimeout = 30000;

var system = require('system'); 
var port = system.env.RUNPORT; 

var vars = {
  testNum: 11,
  baseURL: port == '80' ? 'http://127.0.0.1/' : 'http://127.0.0.1:' + port + '/',
  path: 'civicrm/contribute/transact',
  query: 'reset=1&action=preview&id=1',
  siteName: 'netiCRM',

// you should add your own testing variables below
  pageTitle: '捐款贊助',
  userEmail: 'youremail@test.tw',
  amountValue: '101',
  amountLabel: '101',
  allpayCpage: '信用卡資料填寫-歐付寶allPay第三方支付',
  allpayVpage: 'OTP刷卡簡訊驗證-歐付寶allPay第三方支付'
};

// Step 1: Contribution page
casper.test.begin('Contribution page test (payment processors: allpay)...', vars.testNum, function suite(test) {
  casper.start(vars.baseURL+vars.path+'?'+vars.query, function() {
    var pageTitle = vars.pageTitle + ' | ' + vars.siteName;
    test.assertTitle(pageTitle, 'Contribution page: page title is ' + this.getTitle());
    test.assertExists('div.crm-contribution-main-form-block', 'Contribution page: main form block is exist.');
    test.assertExists('form#Main', 'Contribution page: main form is exist.');
    this.waitForSelector('input[name="payment_processor"]', function(){
      this.fill('form[action="/civicrm/contribute/transact"]', {
        'email-5': vars.userEmail,
        'amount_other': vars.amountValue,
        'payment_processor': "2",
      },
      true);
    });
  });

  // Step 2: Contribution Confirm
  casper.waitForUrl(/_qf_Confirm_display/, function(){
    test.assertUrlMatch(/_qf_Confirm_display=true/, 'Contribution Confirm');
    test.assertExists('.amount_display-group .display-block strong', 'Contribution Confirm: amount field is exist.');
    test.assertSelectorHasText('.amount_display-group .display-block strong', vars.amountLabel, 'Contribution Confirm: amount label is OK. (' + vars.amountLabel + ')');
    test.assertExists('.contributor_email-section .content', 'Contribution Confirm: email field is exist.');
    test.assertSelectorHasText('.contributor_email-section .content', vars.userEmail, 'Contribution Confirm: email value is OK. (' + vars.userEmail + ')');
    this.click('input[name="_qf_Confirm_next"]');
  });

  // Step 3: Allpay CreditCard Info
  casper.waitForUrl('http://pay-stage.allpay.com.tw/CreditPayment/CreateCreditCardInfo', function(){
    test.assertUrlMatch(/CreateCreditCardInfo/, "Allpay CreditCard Info");
    test.assertTitle(vars.allpayCpage, 'Allpay CreditCard Info: page title is OK. (' + vars.allpayCpage + ')');
    test.assertExists('form[action="/CreditPayment/CreateCreditCardInfo"]', 'Allpay CreditCard Info: form is exist.');
    this.fill('form[action="/CreditPayment/CreateCreditCardInfo"]', {
      'Cellphone': '123456789',
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

  casper.run(function() {
    test.done();
  });
});
