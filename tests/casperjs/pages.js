/**
 * This is a screen test script based on casper.js
 * The variable using .travis.yml on root of netiCRM
 *
 * read more at http://casperjs.org/
 */

// cow-test.js
var base_url = 'http://127.0.0.1:8080/';
var site_name = 'netiCRM';
var url = [
 {title:'New 個人', url:'civicrm/contact/add&reset=1&ct=Individual'}
];
casper.test.begin('Page output correct test', url.length*2+1, function suite(test) {
  casper.start(base_url, function() {
    test.assertExists('#user-login-form', "Found login form");
    this.fill('#user-login-form', {
      'name':'admin',
      'pass':'123456'
    }, true);
    for(var i in url){
      casper.thenOpen(base_url+url[i].url, function(){
        var title = url[i].title;
        var full_title = title + ' | ' + site_name;
        test.assertTitle(full_title, 'Page title should be matched: ' + title);
        test.assertDoesntExist('.error-ci');
      });
    }
  });

  casper.run(function(){
    test.done();
  });
});
