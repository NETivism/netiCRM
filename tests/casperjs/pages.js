/**
 * This is a screen test script based on casper.js
 * The variable using .travis.yml on root of netiCRM
 *
 * read more at http://casperjs.org/
 */

var base_url = 'http://127.0.0.1:8080/';
var site_name = 'netiCRM';
var url = [
  {title:"Administer 支持者關係系統", url:"civicrm/admin?reset=1"},
  {title:"首頁", url:"civicrm/dashboard"},
  {title:"Administer", url:"admin"},
  {title:"Content Management", url:"admin/content"},
  {title:"Reports", url:"admin/reports"},
  {title:"Site Building", url:"admin/build"},
  {title:"Custom Data", url:"civicrm/admin/custom/group?reset=1"},
  {title:"支持者關係系統 Profile", url:"civicrm/admin/uf/group?reset=1"},
  {title:"支持者關係系統 Home", url:"civicrm/civicrm/admin/configtask?reset=1"},
  {title:"User Management", url:"admin/user"},
  {title:"Synchronize Users to Contacts", url:"civicrm/admin/synchUser?reset=1"}
];
var lookup_title = function(u){
  for(var i in url){
    if(url[i].url == u){
      return url[i].title;
    }
  }
}
casper.test.begin('Page output correct test', url.length*2+1, function suite(test) {
  casper.start(base_url, function() {
    test.assertExists('#user-login-form', "Found login form");
    this.fill('#user-login-form', {
      'name':'admin',
      'pass':'123456'
    }, true);
    for(var i in url){
      casper.thenOpen(base_url+url[i].url, function(obj){
        if(obj.url){
          var url = obj.url.replace(base_url, '');
          var title = lookup_title(url);
          var full_title = title + ' | ' + site_name;
          test.assertTitle(full_title, 