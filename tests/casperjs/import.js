// phantom.casperPath = '/usr/local/lib/node_modules/casperjs';
// phantom.injectJs('/usr/local/lib/node_modules/casperjs/bin/bootstrap.js');

var system = require('system');
var port = system.env.RUNPORT;

var url = (port == '80') ? 'http://127.0.0.1/' : 'http://127.0.0.1:' + port + '/';
var item = {
  url_prefix: url,
  task: [
    {
      type: 'contact',
      url: '/civicrm/import/contact?reset=1', 
      fields: {
        'mapper[0][0]': 'last_name',
        'mapper[1][0]': 'first_name',
        'mapper[2][0]': 'email',
        'mapper[2][1]': '1',
      },
      form_name: 'DataSource',
      /*
      preview_fields: {
        'newGroupName': 'Test from Casper',
      },
      */ 
    },
    {
      type: 'contribute',
      url: '/civicrm/contribute/import?reset=1',
      fields: {
        'mapper[0][0]': 'last_name',
        'mapper[1][0]': 'first_name',
        'mapper[2][0]': 'email',
        'mapper[2][1]': '1',
        'mapper[5][0]': 'contribution_type',
        'mapper[6][0]': 'total_amount',
      }
    },
    {
      type: 'activity',
      url: '/civicrm/import/activity?reset=1',
      fields: {
        'mapper[0][0]': 'last_name',
        'mapper[1][0]': 'first_name',
        'mapper[2][0]': 'email',
        'mapper[5][0]': 'activity_subject',
        'mapper[7][0]': 'activity_type_id',
        'mapper[8][0]': 'activity_date_time',
      }
    },
    /*
    {
      type: 'participant',
      url: '/civicrm/event/import?reset=1',
    },
    {
      type: 'member',
      url: '/civicrm/member/import?reset=1'
    },
    */
  ],
  // site_name: 'netiCRM'
}

// 0. Login
casper.test.begin('Page output correct test', 1, function suite(test) {
  casper.start(item.url_prefix + '/user', function() {
    casper.capture("picture/import_task_login_0.png");
    test.assertExists('[id^="user-login-"]', "Found login form");
    this.fill('[id^="user-login-"]', {
      'name':'admin',
      'pass':'123456'
    }, true);
  });

  casper.waitForUrl(/user/, function(test){
    casper.capture("picture/import_task_login_1.png");
  });

  casper.run(function() {
    test.done();
  });
});

// 1. Import Tasks

item.task.forEach(function(task, i){

  casper.test.begin('Import task '+i+' : '+task.type, 3, function(test){
    casper.start(item.url_prefix + task.url, function() {
      casper.echo('=====================================');
      casper.echo('** Step 1: Enter Upload File Page. **');
      casper.echo('=====================================');
      test.assertExists('#savedMapping', 'Save Mapping Field is Exist.');
      casper.capture("picture/import_task_"+i+"_1.png");
    });

    casper.then(function(){
      casper.echo('  - Upload File.');
      casper.page.uploadFile('#uploadFile', 'files/import.csv');
      casper.capture("picture/import_task_"+i+"_2.png");
    });

    casper.then(function(){
      casper.echo('  - Click next button.');
      var form_name = (task.form_name ? task.form_name : 'UploadFile');
      casper.click('input[name="_qf_'+form_name+'_upload"]');
      casper.capture("picture/import_task_"+i+"_3.png");
    })

    casper.waitForUrl(/_qf_MapField_display/, function(){
      casper.echo('==================================');
      casper.echo('** Step 2: Enter MapField page. **');
      casper.echo('==================================');
      test.assertExists('#_qf_MapField_next-top', 'Success enter MapField Page!!!');
      casper.capture("picture/import_task_"+i+"_4.png");
    });

    casper.then(function(){
      if(task.fields){
        casper.echo('  - Select the mapping field.');
        this.fill('form#MapField', task.fields, false);
      }
      casper.capture("picture/import_task_"+i+"_5.png");
      casper.echo('  - Click next button.');
      this.click('input[name="_qf_MapField_next"]');
    })

    casper.waitForUrl(/_qf_Preview_display/, function(){
      casper.echo('=================================');
      casper.echo('** Step 3: Enter Preview page. **');
      casper.echo('=================================');
      casper.capture("picture/import_task_"+i+"_6.png");
      test.assertExists('input[name="_qf_Preview_next"]', 'Success enter Preview Page!!!');
    });
/*
    casper.then(function(){
      // Click confirm Botton
      casper.page.onConfirm = function(msg) {
        casper.echo('CONFIRM: ' + msg);
        casper.echo('Click Confirm Botton;');
        return true;
      };
      casper.click('input[name="_qf_Preview_next"]');
      casper.capture("picture/import_task_"+i+"_5.png");
    });

    casper.waitForUrl(/_qf_Summary_display/, function(){
      casper.capture("picture/import_task_0_6.png");
      test.assertExists('#_qf_Summary_next-top', 'Success enter Summary Page!!!');
    }, function timeout(){
      casper.capture("picture/import_task_"+i+"_6_.png");
      casper.echo('Fail to import.');
    }, 10000);
*/
    casper.run(function() {
      test.done();
    });

  });

})
