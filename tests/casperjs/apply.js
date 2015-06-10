phantom.casperPath = '/usr/local/lib/node_modules/casperjs';
phantom.injectJs('/usr/local/lib/node_modules/casperjs/bin/bootstrap.js');
var casper = require('casper').create();
var url_prefix = 'http://temp.deb:8000';

casper.start(url_prefix + '/civicrm/event/register?reset=1&action=preview&id=1', function() {
    this.echo(this.getTitle());
    this.capture("neticrm-1-0.png");
});

casper.then(function(){
  var email = 'test@netivism.com.tw';
  this.fill('#Register',{
    'email-5': email
  },true);
  this.capture("neticrm-1-1.png");
  this.echo('Fill the email : ' + email);
  this.thenClick('.form-submit');
});

casper.then(function(){
  this.capture("neticrm-1-2.png");
  this.echo(this.getTitle());
});

// 測試名額限制、不可候補

casper.thenOpen(url_prefix + '/civicrm/event/register?reset=1&id=2', function() {
    this.echo(this.getTitle());
    this.capture("neticrm-2-0.png");
});

casper.then(function(){
  var email = 'test@netivism.com.tw';
  this.fill('#Register',{
    'email-5': email
  },true);
  this.capture("neticrm-2-1.png");
  this.echo('Fill the email : ' + email);
  this.thenClick('.form-submit');
});

casper.then(function(){
  this.capture("neticrm-2-2.png");
  this.echo(this.getTitle());
  this.thenOpen(url_prefix + '/civicrm/event/register?reset=1&id=2');
});

casper.then(function(){
  this.capture("neticrm-2-3.png");
  this.echo(this.getTitle());
  this.echo('Message : ' + this.getHTML('.messages'));
});

// 測試名額限制、可候補

casper.thenOpen(url_prefix + '/civicrm/event/register?reset=1&id=3', function() {
    this.echo(this.getTitle());
    this.capture("neticrm-3-0.png");
});

casper.then(function(){
  var email = 'test@netivism.com.tw';
  this.fill('#Register',{
    'email-5': email
  },true);
  this.capture("neticrm-3-1.png");
  this.echo('Fill the email : ' + email);
  this.thenClick('.form-submit');
});

casper.then(function(){
  this.capture("neticrm-3-2.png");
  this.echo(this.getTitle());
  this.thenOpen(url_prefix + '/civicrm/event/register?reset=1&id=3');
});

casper.then(function(){
  this.capture("neticrm-3-3.png");
  this.echo(this.getTitle());
  var email = 'test2@netivism.com.tw';
  this.fill('#Register',{
    'email-5': email
  },true);
  this.capture("neticrm-3-4.png");
  // this.echo('Fill the email : ' + email);
  this.thenClick('.form-submit');
  // this.echo('Message : ' + this.getHTML('.messages'));
});

casper.then(function(){
  this.capture("neticrm-3-5.png");
  this.echo(this.getTitle());
  this.echo('Message : ' + this.getHTML('#help p'));
});


casper.run();
