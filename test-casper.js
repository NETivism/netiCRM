/**
 * This is a screen test script based on casper.js
 *
 * read more at http://casperjs.org/
 */
function run(){
  this.echo(this.getTitle());
}
var casper = require('casper').create();
var base_url = 'http://127.0.0.1:8080/';

// login for test
casper.start(base_url, function() {
  this.echo(this.getTitle());
  this.fill('#user-login-form', {
    'name':'admin',
    'pass':'123456'
  }, true);
});
// run pages
casper.thenOpen(base_url+'civicrm/contact/add&reset=1&ct=Individual', run);

// contribute
casper.thenOpen(base_url+'civicrm/contribute/add&reset=1&action=add&context=standalone', run);

// event
casper.thenOpen(base_url+'civicrm/event&reset=1', run);
casper.thenOpen(base_url+'civicrm/participant/add&reset=1&action=add&context=standalone', run);

casper.run();
