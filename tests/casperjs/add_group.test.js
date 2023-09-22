var system = require('system'); 
var port = system.env.RUNPORT; 

function makeid(length) {
    var result           = '';
    var characters       = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    var charactersLength = characters.length;
    for ( var i = 0; i < length; i++ ) {
       result += characters.charAt(Math.floor(Math.random() * charactersLength));
    }
    return result;
}

var vars = {
    baseURL : port == '80' ? 'http://127.0.0.1/' : 'http://127.0.0.1:' + port + '/',
    group_name: makeid(5),
    group_id: "",
    mail_name: makeid(5)
};

casper.on('remote.message', function(msg) {
    this.echo('remote message caught: ' + msg);
});

casper.test.begin('Resurrectio test', function(test) {
    casper.start(vars.baseURL, function() {
        casper.echo('=====================================');
        casper.echo('** Step 0: Login. **');
        casper.echo('=====================================');
        // this.capture('login.png');
    });

    casper.waitForSelector("#user-login-form", function success() {
        this.fill('#user-login-form', {
          'name':'admin',
          'pass':'123456'
        }, true);
    }, function fail() {
        test.assertExists("#user-login-form", 'Login form exist.');
    });

    casper.run(function() {
        test.done();
    });
});

casper.test.begin('Start testing...', function(test) {

    /* open new group */
    casper.thenOpen(vars.baseURL + "civicrm/group/add?reset=1", function() {
        // this.capture('new_group.png');
    });

    casper.wait(2000);
    casper.then(function() {
        casper.echo('=====================================');
        casper.echo('** Step 1: Add New Group. **');
        casper.echo('=====================================');
    });

    /* sendKeys to Name */
    casper.waitForSelector("#title", function success() {
        test.assertExists("#title");
        this.sendKeys("#title", vars.group_name);
    }, function fail() {
        test.assertExists("#title");
    });

    /* click Mailing List */
    casper.waitForSelector('input[name="group_type[2]"]', function success() {
        test.assertExists('input[name="group_type[2]"]');
        this.click('input[name="group_type[2]"]');
    }, function fail() {
        test.assertExists('input[name="group_type[2]"]');
    });

    /* click Continue */
    casper.waitForSelector('input[value="Continue"]', function success() {
        test.assertExists('input[value="Continue"]');
        this.click('input[value="Continue"]');
    }, function fail() {
        test.assertExists('input[value="Continue"]');
    });

    casper.wait(2000);
    casper.then(function() {
        casper.echo('=====================================');
        casper.echo('** Step 2: Add User to Group. **');
        casper.echo('=====================================');
    });

    /* click Search */
    casper.waitForSelector('form#Basic input[value="Search"]', function success() {
        test.assertExists('form#Basic input[value="Search"]');
        this.click('form#Basic input[value="Search"]');
    }, function fail() {
        test.assertExists('form#Basic input[value="Search"]');
    });

    casper.wait(2000);

    /* select user that have email */
    casper.waitForSelector('.selector', function success() {
        this.echo('Select user that have email')
        var id = this.evaluate(function (){
            var tr = document.querySelectorAll(".selector tr");
            for(var i=1; i<tr.length; i++) {
                if(tr[i].querySelector("td:nth-child(5)").textContent != "") {
                    return tr[i].querySelector("td:nth-child(3)").textContent;
                }
            }
            return -1;
        });
        test.assertNotEquals(id, -1, 'Got user id.');
        var row_id = "#rowid" + id + " input";
        this.click(row_id);
    }, function fail() {
        test.assertExists('.selector');
    });

    /* click Add Contacts to */
    casper.waitForSelector('form#Basic input[name="_qf_Basic_next_action"]', function success() {
        test.assertExists('form#Basic input[name="_qf_Basic_next_action"]');
        this.click('form#Basic input[name="_qf_Basic_next_action"]');
    }, function fail() {
        test.assertExists('form#Basic input[name="_qf_Basic_next_action"]');
    });
    
    casper.wait(2000);
    /* click Confirm */
    casper.waitForSelector('input[name="_qf_AddToGroup_next"]', function success() {
        test.assertExists('input[name="_qf_AddToGroup_next"]');
        this.click('input[name="_qf_AddToGroup_next"]');
    }, function fail() {
        test.assertExists('input[name="_qf_AddToGroup_next"]');
    });

    casper.wait(2000);
    /* click Done */
    casper.waitForSelector('form#Result input[value="Done"]', function success() {
        test.assertExists('form#Result input[value="Done"]');
        this.click('form#Result input[value="Done"]');
    }, function fail() {
        test.assertExists('form#Result input[value="Done"]');
    });

    casper.wait(2000);
    casper.then(function() {
        casper.echo('=====================================');
        casper.echo('** Step 3: Check User Has Been Add To Group. **');
        casper.echo('=====================================');
    });
    /* click first user */
    casper.waitForSelector('table.selector tbody tr:first-child td:nth-child(5) a', function success() {
        test.assertExists('table.selector tbody tr:first-child td:nth-child(5) a');
        this.click('table.selector tbody tr:first-child td:nth-child(5) a');
    }, function fail() {
        test.assertExists('table.selector tbody tr:first-child td:nth-child(5) a');
    });

    casper.wait(2000);
    /* click Group */
    casper.waitForSelector('a[title="Groups"]', function success() {
        test.assertExists('a[title="Groups"]');
        this.click('a[title="Groups"]');
    }, function fail() {
        test.assertExists('a[title="Groups"]');
    });

    casper.wait(2000);
    /* check group has been add */
    casper.waitForSelector('#GroupContact table', function success() {
        test.assertExists('#GroupContact table');
        var group_in_list = this.evaluate(function(group_name) {
            tr = document.querySelectorAll('#GroupContact table tr');
            for(var i=1; i<tr.length; i++) {
                if(tr[i].querySelector('td:first-child a').text == group_name) {
                    return true;
                }
            }

            return false;
        }, vars.group_name);
        test.assertEquals(group_in_list, true, 'Group has been add.');
    }, function fail() {
        test.assertExists('#GroupContact table', "Assert 'Current Groups' table exist.");
    });

    casper.then(function() {
        casper.echo('=====================================');
        casper.echo('** Step 4: Mailing Test. **');
        casper.echo('=====================================');
    });

    casper.then(function() {
        casper.echo("Step 4-1: Get Group id.");
    });

    casper.thenOpen(vars.baseURL + "civicrm/group?reset=1", function() {
    });

    casper.waitForSelector("#group table", function success() {
        vars.group_id = this.evaluate(function (group_name) {
            tr = document.querySelectorAll('#group table tr');
            for(var i=1; i<tr.length; i++) {
                if(tr[i].querySelector('td:nth-child(2)').textContent == group_name) {
                    return tr[i].querySelector('td:nth-child(1)').textContent;
                }
            }
        }, vars.group_name);

        test.assertNotEquals(vars.group_id, null, "Assert get group id successfully.");
    }, function fail() {
        test.assertExists("#group table", "Assert group list table exist.");
    });

    casper.thenOpen(vars.baseURL + "civicrm/mailing/send?reset=1", function() {
        // this.capture("new_mailing.png");
    });  

    casper.then(function() {
        this.echo('Step 4-2: Select Recipients.');
    });
    
    casper.waitForSelector("input[name='name']", function success() {
        this.sendKeys("input[name='name']", vars.mail_name);
    }, function fail() {
        test.assertExists("input[name='name']", "Assert 'Name Your Mailing' field exist.");
    });

    casper.waitForSelector("#includeGroups", function success() {
        this.evaluate(function (group_id) {
            document.getElementById("includeGroups").value = group_id;
        }, vars.group_id);
    }, function fail() {
        test.assertExists("#includeGroups", "Assert 'Include Group(s)' exist.");
    });

    casper.waitForSelector("input[value='Next >>']", function success() {
        this.click("input[value='Next >>']");
    }, function fail() {
        test.assertExists("input[value='Next >>']", "Assert 'Next >>' button exist.");
    });
    casper.wait(2000);

    casper.then(function() {
        test.assertDoesntExist('.crm-error', "Assert '.crm-error' doesn't exist.");
    });

    casper.then(function() {
        this.echo('Step 4-3: Track and Respond.');
    });

    casper.waitForSelector(".messages strong", function success() {
        var group_num = this.evaluate(function () {
            return document.querySelector('.messages strong').textContent;
        });
        test.assertEquals(group_num, "1", 'Assert recipient number of group correct.')
    }, function fail() {
        test.assertExists(".messages strong", "Assert number of 'Total Recipients' exist.");
    });

    casper.waitForSelector("select#visibility", function success() {
        this.evaluate(function() {
            document.querySelector('select#visibility').selectedIndex = 1;
        });
    }, function fail() {
        test.assertExists("select#visibility", "Visibility select field exist");
    });

    casper.waitForSelector("input[value='Next >>']", function success() {
        this.click("input[value='Next >>']");
    }, function fail() {
        test.assertExists("input[value='Next >>']", "Assert 'Next >>' button exist.");
    });
    casper.wait(2000);

    casper.then(function() {
        test.assertDoesntExist('.crm-error', "Assert '.crm-error' doesn't exist.");
    });

    casper.then(function() {
        this.echo('Step 4-4: Mailing Content.');
    });

    casper.waitForSelector("input[name='subject']", function success() {
        this.evaluate(function(subject) {
            document.querySelector("input[name='subject']").value = subject;
        }, makeid(5));
    }, function fail() {
        test.assertExists("input[name='subject']", "Assert 'Mailing Subject' exist.");
    });

    casper.waitForSelector("#footer_id", function success() {
        this.evaluate(function () {
            document.getElementById('footer_id').selectedIndex = 1;
        });
    }, function fail() {
        test.assertExists("#footer_id", "Assert 'Mailing footer' exist.");
    });
    

    casper.waitForSelector("input[value='Next >>']", function success() {
        this.click("input[value='Next >>']");
    }, function fail() {
        test.assertExists("input[value='Next >>']", "Assert 'Next >>' button exist.");
    });
    casper.wait(2000);

    casper.then(function() {
        test.assertDoesntExist('.crm-error', "Assert '.crm-error' doesn't exist.");
    });

    casper.then(function() {
        this.echo("Step 4-5: Test.");
    });

    casper.waitForSelector("input[value='Next >>']", function success() {
        this.click("input[value='Next >>']");
    }, function fail() {
        test.assertExists("input[value='Next >>']", "Assert 'Next >>' button exist.");
    });
    casper.wait(2000);

    casper.then(function() {
        this.echo('Step 4-6: Schedule or Send');
    });

    casper.waitForSelector("input[value='Submit Mailing']", function success() {
        this.click("input[value='Submit Mailing']");
    }, function fail() {
        test.assertExists("input[value='Submit Mailing']", "Assert 'Submit Mailing' button exist.");
    });
    casper.wait(2000);
    casper.then(function() {
        // this.capture("click_send.png");
    });
    casper.then(function() {
        test.assertDoesntExist('.crm-error', "Assert '.crm-error' doesn't exist.");
    });
    
    casper.then(function() {
        this.echo("Step 4-7: Check if mail in 'Scheduled and Sent Mailings'.");
    });

    casper.thenOpen(vars.baseURL + "civicrm/mailing/browse/scheduled?reset=1&scheduled=true", function() {
        // this.capture("scheduled_and_sent_mailings.png");
    });

    casper.waitForSelector(".selector tbody tr td:nth-child(2)", function success() {
        var mail_name_from_page = this.evaluate(function () {
            return document.querySelector('.selector tbody tr td:nth-child(2)').textContent;
        });
        test.assertEquals(mail_name_from_page, vars.mail_name, "Assert mail name correct.");
    }, function fail() {
        test.assertExists(".selector tbody tr td:nth-child(2)", "Assert 'Mailing Name' exist.");
    });

    casper.run(function() {
        test.done();
    });
});
