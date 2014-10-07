/**
 * This is a screen test script based on casper.js
 *
 * read more at http://casperjs.org/
 */
function run(){
  var title = this.getTitle();
  casper.log("Testing... "+title, 'info');
  if(this.exists('.error-ci')){
    var error = this.getHTML('.error-ci');
    casper.log(error, 'error');
  }
}
var casper = require('casper').create({
  verbose: true,
  logLevel: 'debug'
});
var base_url = 'http://127.0.0.1:8080/';

// login for test
casper.start(base_url, function() {
  casper.log("Start casper debug", 'debug');
  this.fill('#user-login-form', {
    'name':'admin',
    'pass':'123456'
  }, true);
});

// run pages
// add individual
casper.thenOpen(base_url+'civicrm/contact/add&reset=1&ct=Individual', run);

// contribute
casper.thenOpen(base_url+'civicrm/contribute/add&reset=1&action=add&context=standalone', run);

// event
casper.thenOpen(base_url+'civicrm/event&reset=1', run);
casper.thenOpen(base_url+'civicrm/participant/add&reset=1&action=add&context=standalone', run);

casper.run();
casper.exit();
