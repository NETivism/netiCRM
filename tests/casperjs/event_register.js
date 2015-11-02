// phantom.casperPath = '/usr/local/lib/node_modules/casperjs';
// phantom.injectJs('/usr/local/lib/node_modules/casperjs/bin/bootstrap.js');

var system = require('system'); 
var port = system.env.RUNPORT; 

var url = (port == '80') ? 'http://127.0.0.1/' : 'http://127.0.0.1:' + port + '/';
var item = {
  url_prefix: url,
  event_name_1: '無名額限制，填表完成送出',
  event_name_2: '有名額限制，不開放候補',
  event_name_3: '有名額限制，開放候補',
  event_name_4: '有名額限制，需事先審核',
  event_name_5: '無名額限制，需事先審核',
  site_name: 'netiCRM'
}

function getPageTitle(title){
  return title + " | "+item.site_name;
}

// 1. Normal registration

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
  });

  casper.waitForUrl('_qf_ThankYou_display',function(){
    this.capture("picture/event_register_1_2.png");
    test.assertExists('#help .msg-register-success');
  });

  casper.run(function() {
    test.done();
  });

});


// 2. limit participants. Not fot waiting.

casper.test.begin('Event register page test ...',6,function(test){
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
  });

  casper.waitForUrl('_qf_ThankYou_display', function(){
    test.assertExists('#help .msg-register-success');
    this.capture("picture/event_register_2_2.png");
    this.thenOpen(item.url_prefix + '/civicrm/event/register?reset=1&id=2');
  });

  casper.then(function(){
    this.capture("picture/event_register_2_3.png");
    // test.assertExists('#help .msg-event-full');
    test.assertExists('.messages.status');
    text = this.evaluate( function(){ return __utils__.findOne('.messages.status').textContent; } );
    console.log('Messages : '+text);
    test.assertMatch(text,/額滿|full/i,'Message contains "full" words.');
  });

  casper.run(function() {
    test.done();
  });
});

// limit participants. Not fot waiting.

casper.test.begin('Event register page test ...',9,function(test){
  casper.start(item.url_prefix + '/civicrm/event/register?reset=1&id=3', function() {
    var page_title = getPageTitle(item.event_name_3);
    test.assertTitle(page_title,'Event register page: page title is OK. (' + page_title + ')');
    this.capture("picture/event_register_3_0.png");
  });


  casper.then(function(){
    test.assertExists('form#Register', 'Event register page: main form is exist.');
    var email = 'test@ovoqnj.com';
    this.fill('#Register',{
      'email-5': email
    },true);
    this.capture("picture/event_register_3_1.png");
    test.assertField('email-5', email);
  });

  casper.waitForUrl('_qf_ThankYou_display',function(){
    var page_title = getPageTitle(item.event_name_3);
    test.assertTitle(page_title,'Event register page: page title is OK. (' + page_title + ')');
    this.capture("picture/event_register_3_2.png");
    this.thenOpen(item.url_prefix + '/civicrm/event/register?reset=1&id=3');
  });


  casper.then(function(){
    var page_title = getPageTitle(item.event_name_3);
    test.assertTitle(page_title,'Event register page: page title is OK. (' + page_title + ')');
    this.capture("picture/event_register_3_3.png");
    var email = 'test2@soossovk.com';
    this.fill('#Register',{
      'email-5': email
    },true);
    this.capture("picture/event_register_3_4.png");
    test.assertField('email-5', email);
  });

  casper.waitForUrl('_qf_ThankYou_display',function(){
    var page_title = getPageTitle(item.event_name_3);
    test.assertTitle(page_title,'Event register page: page title is OK. (' + page_title + ')');
    this.capture("picture/event_register_3_5.png");
    test.assertExists('#help p');
    text = this.evaluate( function(){ return __utils__.findOne('#help').textContent; } );
    console.log('Messages : '+text);
    test.assertMatch(text,/候補|wait list/i,'Message contains "wait list" words.');
  });

  casper.run(function() {
    test.done();
  });

});

// event 4 : limit participants. Need approval.
// Checked-1 register success
// Checked-2 participant have get verify message.
// Checked-3 second participant message is correct.

casper.test.begin('Event register page test ...',9,function(test){
  casper.start(item.url_prefix + '/civicrm/event/register?reset=1&id=4', function() {
    var page_title = getPageTitle(item.event_name_4);
    test.assertTitle(page_title,'Event register page: page title is OK. (' + page_title + ')');
    this.capture("picture/event_register_4_0.png");
  });

  // First register.
  casper.then(function(){
    test.assertExists('form#Register', 'Event register page: main form is exist.');
    var email = 'test@vkioob.com';
    this.fill('#Register',{
      'email-5': email
    },true);
    this.capture("picture/event_register_4_1.png");
    test.assertField('email-5', email);
  });

  casper.waitForUrl('_qf_ThankYou_display',function(){
    var page_title = getPageTitle(item.event_name_4);
    // Checked-1
    test.assertTitle(page_title,'Event register page: page title is OK. (' + page_title + ')');
    this.capture("picture/event_register_4_2.png");
    // Checked-2 
    test.assertExists('#help p');
    text = this.evaluate( function(){ return __utils__.findOne('#help').textContent; } );
    console.log('Messages : '+text);
    test.assertMatch(text,/審核|reviewed/i,'Message contains "reviewed" words.');
    this.thenOpen(item.url_prefix + '/civicrm/event/register?reset=1&id=4');
  });

  // Second register.
  casper.then(function(){
    var page_title  = getPageTitle(item.event_name_4);
    test.assertTitle(page_title,'Event register page: page title is OK. (' + page_title + ')');
    this.capture("picture/event_register_4_3.png");
    test.assertExists('.messages.status');
    // Checked-3 
    text = this.evaluate( function(){ return __utils__.findOne('.messages.status').textContent; } );
    console.log('Messages : '+text);
    test.assertMatch(text,/額滿|full/i,'Message contains "full" words.');
  });

  casper.run(function() {
    test.done();
  });
});

// event 5 : unlimit participants. Need approval. 
// Checked-1 register success
// Checked-2 participant have get verify message.
// Checked-3 Second register success
// Checked-4 participant have get verify message.

casper.test.begin('Event register page test ...',12,function(test){
  casper.start(item.url_prefix + '/civicrm/event/register?reset=1&id=5', function() {
    var page_title = getPageTitle(item.event_name_5);
    test.assertTitle(page_title,'Event register page: page title is OK. (' + page_title + ')');
    this.capture("picture/event_register_5_0.png");
  });

  // First register.
  casper.then(function(){
    test.assertExists('form#Register', 'Event register page: main form is exist.');
    var email = 'test@vkioob.com';
    this.fill('#Register',{
      'email-5': email
    },true);
    this.capture("picture/event_register_5_1.png");
    test.assertField('email-5', email);
  });

  casper.waitForUrl('_qf_ThankYou_display',function(){
    var page_title = getPageTitle(item.event_name_5);
    // Checked-1
    test.assertTitle(page_title,'Event register page: page title is OK. (' + page_title + ')');
    this.capture("picture/event_register_5_2.png");
    // Checked-2 
    test.assertExists('#help p');
    text = this.evaluate( function(){ return __utils__.findOne('#help').textContent; } );
    console.log('Messages : '+text);
    test.assertMatch(text,/審核|reviewed/i,'Message contains "reviewed" words.');
    this.thenOpen(item.url_prefix + '/civicrm/event/register?reset=1&id=5');
  });

  // Second register.
  casper.then(function(){
    var page_title  = getPageTitle(item.event_name_5);
    test.assertTitle(page_title,'Event register page: page title is OK. (' + page_title + ')');
    this.capture("picture/event_register_5_3.png");
    test.assertExists('.messages');
    // Checked-3 
    var email = 'test2@xoooke.com';
    this.fill('#Register',{
      'email-5': email
    },true);
    this.capture("picture/event_register_5_4.png");
    test.assertField('email-5', email);
    
  });

  casper.waitForUrl('_qf_ThankYou_display',function(){
    var page_title = getPageTitle(item.event_name_5);
    test.assertTitle(page_title,'Event register page: page title is OK. (' + page_title + ')');
    this.capture("picture/event_register_5_5.png");
    test.assertExists('#help p');
    text = this.evaluate( function(){ return __utils__.findOne('#help').textContent; } );
    console.log('Messages : '+text);
    test.assertMatch(text,/審核|reviewed/i,'Message contains "reviewed" words.');
  });

  casper.run(function() {
    test.done();
  });
});

