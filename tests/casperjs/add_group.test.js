var system = require('system'); 
var port = system.env.RUNPORT; 
var baseURL = port == '80' ? 'http://127.0.0.1/' : 'http://127.0.0.1:' + port + '/';

function makeid(length) {
    var result           = '';
    var characters       = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    var charactersLength = characters.length;
    for ( var i = 0; i < length; i++ ) {
       result += characters.charAt(Math.floor(Math.random() * charactersLength));
    }
    return result;
}

casper.test.begin('Resurrectio test', function(test) {
    casper.start(baseURL, function() {
        casper.echo('=====================================');
        casper.echo('** Step 0: Login. **');
        casper.echo('=====================================');
        // this.capture('login.png');
    });
    casper.waitForSelector("form#user-login-form input[name='name']", function success() {
        test.assertExists("form#user-login-form input[name='name']");
        this.click("form#user-login-form input[name='name']");
    }, function fail() {
        test.assertExists("form#user-login-form input[name='name']");
    });
    casper.waitForSelector("input[name='name']", function success() {
        this.sendKeys("input[name='name']", "admin");
    }, function fail() {
        test.assertExists("input[name='name']");
    });
    casper.waitForSelector("input[name='pass']", function success() {
        this.sendKeys("input[name='pass']", "123456");
    }, function fail() {
        test.assertExists("input[name='pass']");
    });
    casper.waitForSelector("form#user-login-form input[type=submit][value='Log in']", function success() {
        test.assertExists("form#user-login-form input[type=submit][value='Log in']");
        this.click("form#user-login-form input[type=submit][value='Log in']");
    }, function fail() {
        test.assertExists("form#user-login-form input[type=submit][value='Log in']");
    }); /* submit form */

    /* open new group */
    casper.thenOpen(baseURL + "civicrm/group/add?reset=1", function() {
        // this.capture('new_group.png');
    });

    casper.wait(2000);
    casper.then(function() {
        casper.echo('=====================================');
        casper.echo('** Step 1: Add New Group. **');
        casper.echo('=====================================');
    });

    /* sendKeys to Name */
    var group_name = makeid(5);
    casper.waitForSelector("#title", function success() {
        test.assertExists("#title");
        this.sendKeys("#title", group_name);
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

    /* click first user */
    casper.waitForSelector('table.selector tbody tr:first-child input', function success() {
        test.assertExists('table.selector tbody tr:first-child input');
        this.click('table.selector tbody tr:first-child input');
    }, function fail() {
        test.assertExists('table.selector tbody tr:first-child input');
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
    casper.waitForSelector('table#option11 tbody tr:last-child td:first-child a', function success() {
        test.assertExists('table#option11 tbody tr:last-child td:first-child a');
        var group_name_from_page = this.evaluate(function() {
            return document.querySelector('table#option11 tbody tr:last-child td:first-child a').text;
        });
        test.assertEquals(group_name_from_page, group_name)
    }, function fail() {
        test.assertExists('table#option11 tbody tr:last-child td:first-child a');
    });

    casper.then(function() {
        casper.echo('=====================================');
        casper.echo('** Step 4: Mailing Test. **');
        casper.echo('=====================================');
    });

    casper.thenOpen(baseURL + "civicrm/mailing/send?reset=1", function() {
        // this.capture("new_mailing.png");
    });

    casper.wait(2000);    

    casper.then(function() {
        this.echo('Step 4-1: Select Recipients.');
    });
    
    var mail_name = makeid(5);
    casper.waitForSelector("input[name='name']", function success() {
        this.sendKeys("input[name='name']", mail_name);
    }, function fail() {
        test.assertExists("input[name='name']", "Assert 'Name Your Mailing' field exist.");
    });

    // casper.waitForSelector("#includeGroups_chzn input", function success() {
    //     this.click("#includeGroups_chzn input");
    // }, function fail() {
    //     test.assertExists("#includeGroups_chzn input", "Assert 'Include Group(s)' exist.");
    // });

    // casper.waitForSelector("#includeGroups_chzn_o_0", function success() {
    //     this.click("#includeGroups_chzn_o_0");
    // }, function fail() {
    //     test.assertExists("#includeGroups_chzn_o_0", "Assert first option of contact group exist.");
    // });

    // casper.waitForSelector(".search-choice", function success() {
    //     var selected_group_name = this.evaluate(function () {
    //         return document.querySelector('.search-choice span').textContent;
    //     });
    // }, function fail() {
    //     test.assertExists(".search-choice", "Group not selected");
    // });

    casper.waitForSelector("#includeGroups", function success() {
        this.evaluate(function () {
            document.getElementById("includeGroups").selectedIndex = 0;
        });
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
        this.echo('Step 4-2: Track and Respond.');
    });

    casper.waitForSelector(".messages strong", function success() {
        var group_num = this.evaluate(function () {
            return document.querySelector('.messages strong').textContent;
        });
        test.assertEquals(group_num, "1", 'Assert recipient number of group correct.')
    }, function fail() {
        test.assertExists(".messages strong", "Assert number of 'Total Recipients' exist.");
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
        this.echo('Step 4-3: Mailing Content.');
    });

    casper.waitForSelector("input[name='subject']", function success() {
        this.sendKeys("input[name='subject']", makeid(5));
    }, function fail() {
        test.assertExists("input[name='subject']", "Assert 'Mailing Subject' exist.");
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
        this.echo("Step 4-4: Test.");
    });

    casper.waitForSelector("input[value='Next >>']", function success() {
        this.click("input[value='Next >>']");
    }, function fail() {
        test.assertExists("input[value='Next >>']", "Assert 'Next >>' button exist.");
    });
    casper.wait(2000);

    casper.then(function() {
        this.echo('Step 4-5: Schedule or Send');
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
        this.echo("Step 4-6: Check if mail in 'Scheduled and Sent Mailings'.");
    });

    casper.thenOpen(baseURL + "civicrm/mailing/browse/scheduled?reset=1&scheduled=true", function() {
        // this.capture("scheduled_and_sent_mailings.png");
    });

    casper.waitForSelector(".selector tbody tr td:nth-child(2)", function success() {
        var mail_name_from_page = this.evaluate(function () {
            return document.querySelector('.selector tbody tr td:nth-child(2)').textContent;
        });
        test.assertEquals(mail_name_from_page, mail_name, "Assert mail name correct.");
    }, function fail() {
        test.assertExists(".selector tbody tr td:nth-child(2)", "Assert 'Mailing Name' exist.");
    });

    casper.run(function() {
        test.done();
    });
});