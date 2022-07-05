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
    baseURL: port == '80' ? 'http://127.0.0.1/' : 'http://127.0.0.1:' + port + '/'
};

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

    /* open CiviEvent Dashboard */
    casper.thenOpen(vars.baseURL + "civicrm/event?reset=1", function() {
        casper.echo('=====================================');
        casper.echo('** Step 1: Pick an Event. **');
        casper.echo('=====================================');
    });

    /* click sort by id */
    casper.waitForSelector('#event_status_id table thead th:first-child', function success() {
        test.assertExists('#event_status_id table thead th:first-child');
        this.click('#event_status_id table thead th:first-child');
    }, function fail() {
        test.assertExists('#event_status_id table thead th:first-child');
    });

    /* click latest event */
    casper.waitForSelector('#event_status_id table tbody tr:last-child td.crm-event-title a', function success() {
        test.assertExists('#event_status_id table tbody tr:last-child td.crm-event-title a');
        this.click('#event_status_id table tbody tr:last-child td.crm-event-title a');
    }, function fail() {
        test.assertExists('#event_status_id table tbody tr:last-child td.crm-event-title a');
    });
    
    casper.wait(2000);
    casper.then(function() {
        // this.capture('1_event_page.png');
    });

    /* click Register New Participant */
    casper.waitForSelector('ul#actions li:nth-child(2) a', function success() {
        casper.echo('=====================================');
        casper.echo('** Step 2: Register New Participant To It. **');
        casper.echo('=====================================');
        test.assertExists('ul#actions li:nth-child(2) a');
        this.click('ul#actions li:nth-child(2) a');
    }, function fail() {
        test.assertExists('ul#actions li:nth-child(2) a');
    });

    /* switch to new tab */
    casper.waitForPopup(0, function() {
    });
    casper.withPopup(0, function() {
        casper.wait(2000);
        casper.then(function() {
            // this.capture('2_New_Event_Registration.png');
        })
        
        /* select 新增個人 */
        casper.waitForSelector("#profiles_1", function success() {
            test.assertExists("#profiles_1");
            this.evaluate(function () {
                document.querySelector("#profiles_1").selectedIndex = 1;
                document.querySelector("#profiles_1").onchange(); // workaround for test
            });
        }, function fail() {
            test.assertExists("#profiles_1");
        });

        /* filled up new contact form */
        casper.waitForSelector('form#Edit', function success() {
            test.assertExists('form#Edit');
            this.fill('form#Edit', {
                'first_name': makeid(3),
                'last_name': makeid(3)
            }, true);
        }, function fail() {
            test.assertExists('form#Edit');
        });
        casper.then(function() {
            // this.capture('3_filled_up_contact.png');
        });

        /* select Participant Status */
        casper.waitForSelector("#status_id", function success() {
            test.assertExists("#status_id");
            this.evaluate(function () {
                document.querySelector("#status_id").selectedIndex = 1;
            });
        }, function fail() {
            test.assertExists("#status_id");
        });
        
        /* click submit */
        casper.waitForSelector("form#Participant input[type=submit][value='Save']", function success() {
            test.assertExists("form#Participant input[type=submit][value='Save']");
            this.click("form#Participant input[type=submit][value='Save']");
        }, function fail() {
            test.assertExists("form#Participant input[type=submit][value='Save']");
        }); /* submit form */
        casper.wait(2000);
        casper.then(function() {
            // this.capture('4_new_participant_done.png');
            test.assertDoesntExist('.crm-error');
        });

        /* click edit event */
        casper.waitForSelector('table.selector .row-action .action-item:nth-child(2)', function success() {
            casper.echo('=====================================');
            casper.echo('** Step 3: Edit Event Participant. **');
            casper.echo('=====================================');
            test.assertExists('table.selector .row-action .action-item:nth-child(2)');
            this.click('table.selector .row-action .action-item:nth-child(2)');
        }, function fail() {
            test.assertExists('table.selector .row-action .action-item:nth-child(2)');
        });
        casper.wait(2000);

        /* click checkbox志工 */
        casper.waitForSelector("input[name='role_id[2]']", function success() {
            test.assertExists("input[name='role_id[2]']");
            this.click("input[name='role_id[2]']");
        }, function fail() {
            test.assertExists("input[name='role_id[2]']");
        });

        /* change Registration Date */
        casper.waitForSelector("#register_date", function success() {
            test.assertExists("#register_date");
            this.evaluate(function () {
                document.querySelector("#register_date").value = "01/01/2020";
            });
        }, function fail() {
            test.assertExists("#register_date");
        });
        casper.waitForSelector("#register_date_time", function success() {
            test.assertExists("#register_date_time");
            this.sendKeys("#register_date_time", "12:00PM");
        }, function fail() {
            test.assertExists("#register_date_time");
        });

        /* select Participant Status */
        casper.waitForSelector("#status_id", function success() {
            test.assertExists("#status_id");
            this.evaluate(function () {
                document.querySelector("#status_id").selectedIndex = 2;
            });
        }, function fail() {
            test.assertExists("#status_id");
        });

        /* click submit */
        casper.waitForSelector("form#Participant input[type=submit][value='Save']", function success() {
            test.assertExists("form#Participant input[type=submit][value='Save']");
            this.click("form#Participant input[type=submit][value='Save']");
        }, function fail() {
            test.assertExists("form#Participant input[type=submit][value='Save']");
        }); /* submit form */
        casper.wait(2000);
        casper.then(function() {
            // this.capture('5_edit_participant_done.png');
            test.assertDoesntExist('.crm-error');
        });

    });

    casper.run(function() {
        test.done();
    });

});