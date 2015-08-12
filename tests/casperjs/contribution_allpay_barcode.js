casper.options.waitTimeout = 30000;

var system = require('system');
var port = system.env.RUNPORT;
var vars = {
  testNum: 15,
  baseURL: port == '80' ? 'http://127.0.0.1/' : 'http://127.0.0.1:' + port + '/',
  path: 'civicrm/contribute/transact',
  query: 'reset=1&action=preview&id=1',
  siteName: 'netiCRM',

// You should add your own testing variables below
  pageTitle: 'CasperJS測試金流',
  userEmail: 'youremail@test.tw',
  amountValue: '100',
  amountLabel: '100',
  allpayCpage: '付款選擇支付方式-歐付寶allPay第三方支付',
  paymentType: 'barcodePay',
  paymentName: '超商條碼',
  allpayFpage: '訂單成立-歐付寶allPay第三方支付',
};

// Step 1: Contribution page
casper.test.begin('Contribution page test (payment processors: allpay, payment type: ' + vars.paymentType + ')...', vars.testNum, function suite(test) {
  casper.start(vars.baseURL + vars.path + '?' + vars.query, function() {
    var pageTitle = vars.pageTitle + ' | ' + vars.siteName;
    test.assertTitle(pageTitle, 'Contribution page: page title is ' + this.getTitle());
    test.assertExists('div.crm-contribution-main-form-block', 'Contribution page: main form block is exist.');
    test.assertExists('form#Main', 'Contribution page: main form is exist.');
    this.waitForSelector('input[name="payment_processor"][value="4"]', function() { // Wait for non credit card option
      this.click('input[name="payment_processor"][value="4"]');
    });
    this.waitForSelector('input[name="civicrm_instrument_id_dummy"][value="11"]', function() { // Wait for barcode option
      this.click('input[name="civicrm_instrument_id_dummy"][value="11"]');
      this.fill('form[action="/civicrm/contribute/transact"]', {
        'email-5': vars.userEmail,
        'amount_other': vars.amountValue,
      },
      true);
    });
  });

  // Step 2: Contribution Confirm
  casper.waitForUrl(/_qf_Confirm_display/, function() {
    test.assertUrlMatch(/_qf_Confirm_display=true/, 'Contribution Confirm');
    test.assertExists('.amount_display-group .display-block strong', 'Contribution Confirm: amount field is exist.');
    test.assertSelectorHasText('.amount_display-group .display-block strong', vars.amountLabel, 'Contribution Confirm: amount label is OK. (' + vars.amountLabel + ')');
    test.assertExists('.contributor_email-section .content', 'Contribution Confirm: email field is exist.');
    test.assertSelectorHasText('.contributor_email-section .content', vars.userEmail, 'Contribution Confirm: email value is OK. (' + vars.userEmail + ')');
    this.click('input[name="_qf_Confirm_next"]');
  });

  // Step 3: Allpay AioTransaction
  casper.waitForUrl('http://payment-stage.allpay.com.tw/AioTransaction/AioPaymentTransaction', function(){
    test.assertUrlMatch(/AioPaymentTransaction/, "Allpay AioPaymentTransaction");
    test.assertTitle(vars.allpayCpage, 'Allpay AioPaymentTransaction: page title is OK. (' + vars.allpayCpage + ')');
    this.waitForSelector('div.BarcodePay', function() {
      test.assertExists('div.BarcodePay', 'Allpay AioPaymentTransaction: payment type is ' + vars.paymentType + '.');
      test.assertSelectorHasText('div.BarcodePay', vars.paymentName, 'Allpay AioPaymentTransaction: payment name is ' + vars.paymentName + '.');
    });
    this.waitForSelector('#BarcodePaySubmit', function() {
      test.assertExists('#BarcodePaySubmit', 'Allpay AioPaymentTransaction: BarcodePaySubmit is exist.');
      this.click('#BarcodePaySubmit'); // First click to trigger the alert message
      this.click('#BarcodePaySubmit'); // Second click to submit form
    });
  });

  // Step 4: Allpay BarcodePaymentInfo
  casper.waitForUrl('http://payment-stage.allpay.com.tw/PaymentRule/BarcodePaymentInfo', function() {
    test.assertUrlMatch(/BarcodePaymentInfo/, "Allpay BarcodePaymentInfo");
    test.assertTitle(vars.allpayFpage, 'Allpay BarcodePaymentInfo: page title is OK. (' + vars.allpayFpage + ')');
  });

  casper.on('remote.alert', function(message) {
    this.echo('Alert message: ' + message); // Shows each alert message
  });

  casper.run(function() {
    test.done();
  });
});