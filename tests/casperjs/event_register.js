// phantom.casperPath = '/usr/local/lib/node_modules/casperjs';
// phantom.injectJs('/usr/local/lib/node_modules/casperjs/bin/bootstrap.js');
var url_prefix = 'http://temp.deb:8000';
var item = {
  url_prefix: 'http://temp.deb:8000',
  event_name_1: '測試活動',
  event_name_2: '測試有名額、不可候補',
  event_name_3: '測試有名額、可候補',
  event_name_4: '有名額限制，需事先審核',
  event_name_5: '無名額限制，需事先審核',
  site_name: 'Drupal'
}

function getPageTitle(title){
  return title + " | "+item.site_name;
}

// 1. Normal apply
casper.test.begin('Event register page test ...',4,function(test){
  casper.start(item.url_prefix + '/civicrm/event/register?reset=1&action=preview&id=1', function() {
    var page_title = getPageTitle(item.event_name_1);
    test.assertTitle(page_title,'Event register page: page title is OK. (' + page_title + ')');
    casper.capture("picture/event_register_1_0.png");
  });

  casper.then(function(){
    test.assertExists('form#Register', 'Event register page: main form is exist.');
    var email = 'test@aipvo.com';
    this.fill('#Register',{
      'email-5': email
    },true);
    this.capture("picture/event_register_1_1.png");
    test.assertField('email-5', email);
    this.thenClick('.form-submit');
  });

  casper.waitForUrl('_qf_ThankYou_display',function(){
    var page_title = getPageTitle('感謝您的報名');
    test.assertTitle(page_title,'Event register page: page title is OK. (' + page_title + ')');
    this.capture("picture/event_register_1_2.png");
  });

  casper.run(function() {
    test.done();
  });

});

// 測試名額限制、不可候補

casper.test.begin('Event register page test ...',7,function(test){
  casper.start(item.url_prefix + '/civicrm/event/register?reset=1&id=2', function() {
    var page_title = getPageTitle(item.event_name_2);
    test.assertTitle(page_title,'Event register page: page title is OK. (' + page_title + ')');
    this.capture("picture/event_register_2_0.png");
  });

  casper.then(function(){
    test.assertExists('form#Register', 'Event register page: main form is exist.');
    var email = 'test@kvien.com';
    this.fill('#Register',{
      'email-5': email
    },true);
    this.capture("picture/event_register_2_1.png");
    test.assertField('email-5', email);
    this.thenClick('.form-submit');
  });

  casper.then(function(){
    var page_title = getPageTitle('感謝您的報名');
    test.assertTitle(page_title,'Event register page: page title is OK. (' + page_title + ')');
    this.capture("picture/event_register_2_2.png");
    this.thenOpen(item.url_prefix + '/civicrm/event/register?reset=1&id=2');
  });

  casper.then(function(){
    var page_title = getPageTitle(item.event_name_2);
    test.assertTitle(page_title,'Event register page: page title is OK. (' + page_title + ')');
    this.capture("picture/event_register_2_3.png");
    test.assertExists('.messages');
    test.assertSelectorHasText('.messages','此活動目前已報名額滿。');
  });

  casper.run(function() {
    test.done();
  });
});

// 測試名額限制、可候補

casper.test.begin('Event register page test ...',10,function(test){
  casper.start(item.url_prefix + '/civicrm/event/register?reset=1&id=3', function() {
    var page_title = getPageTitle(item.event_name_3);
    test.assertTitle(page_title,'Event register page: page title is OK. (' + page_title + ')');
    this.capture("event_register_3_0.png");
  });

  casper.then(function(){
    test.assertExists('form#Register', 'Event register page: main form is exist.');
    var email = 'test@ovoqnj.com';
    this.fill('#Register',{
      'email-5': email
    },true);
    this.capture("picture/event_register_3_1.png");
    test.assertField('email-5', email);
    this.thenClick('.form-submit');
  });

  casper.then(function(){
    var page_title = getPageTitle('感謝您的報名');
    test.assertTitle(page_title,'Event register page: page title is OK. (' + page_title + ')');
    this.capture("picture/event_register_3_2.png");
    this.thenOpen(item.url_prefix + '/civicrm/event/register?reset=1&id=3');
  });

  casper.then(function(){
    var page_title = getPageTitle(item.event_name_3);
    test.assertTitle(page_title,'Event register page: page title is OK. (' + page_title + ')');
    this.capture("picture/event_register_3_3.png");
    var email = 'test2@soosovk.com';
    this.fill('#Register',{
      'email-5': email
    },true);
    this.capture("picture/event_register_3_4.png");
    test.assertField('email-5', email);
    this.thenClick('.form-submit');
  });

  casper.then(function(){
    var page_title = getPageTitle('感謝您的報名');
    test.assertTitle(page_title,'Event register page: page title is OK. (' + page_title + ')');
    this.capture("picture/event_register_3_5.png");
    test.assertExists('#help p');
    test.assertSelectorHasText('#help p','已為您加入到本活動的的候補名單中。');
    test.assertSelectorHasText('#help p','若活動尚有名額，您將會收到一封電子郵件，您可以點選信件中的連結網址以完成報名程序。');
  });
  casper.run(function() {
    test.done();
  });

});

