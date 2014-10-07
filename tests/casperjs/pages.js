/**
 * This is a screen test script based on casper.js
 * The variable using .travis.yml on root of netiCRM
 *
 * read more at http://casperjs.org/
 */

// cow-test.js
var base_url = 'http://127.0.0.1:8080/';
var site_name = 'netiCRM';
casper.test.begin('Page output correct test', 2, function suite(test) {
  this.runpage = function(title){
    var full_title = title + ' | ' + site_name;
    test.assertTitle(full_title, 'Page title should be matched: ' + title);
    test.assertDoesntExist('.error-ci');
  }
  casper.start(base_url, function() {
    test.assertExists('form#user-login-form', "Found login form");
    this.fill('#user-login-form', {
      'name':'admin',
      'pass':'123456'
    }, true);
  });

  casper.thenOpen(base_url+'civicrm/contact/add&reset=1&ct=Individual', this.runpage('New 個人'));

  casper.run(function(){
    test.done();
  });
});
