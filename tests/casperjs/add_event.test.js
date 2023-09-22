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
    
    casper.run(function() {
        test.done();
    });
});

casper.test.begin('Start testing...', function(test) {
    /* open add event */
    casper.thenOpen(vars.baseURL + "civicrm/event/add?reset=1&action=add", function() {
        // this.capture('1_add_event.png');
    });
    
    /*
     * Info and Settings
     */

    /* filled up add event form */
    casper.waitForSelector('form#EventInfo', function success() {
        casper.echo('=====================================');
        casper.echo('** Step 1: Info and Settings. **');
        casper.echo('=====================================');
        test.assertExists('form#EventInfo');
        this.fill('form#EventInfo', {
            'event_type_id': '1',
            'title': makeid(5)
        }, true);
    }, function fail() {
        test.assertExists('form#EventInfo');
    });
    casper.wait(2000);
    casper.then(function() {
        // this.capture('2_edit_event.png')
    });
    
    /*
     * Event Location
     */

    /* select State/Province */
    casper.waitForSelector("#address_1_state_province_id", function success() {
        casper.echo('=====================================');
        casper.echo('** Step 2: Event Location. **');
        casper.echo('=====================================');
        test.assertExists("#address_1_state_province_id");
        this.evaluate(function () {
            document.querySelector("#address_1_state_province_id").selectedIndex = 1;
        });
    }, function fail() {
        test.assertExists("#address_1_state_province_id");
    });

    /* click Save */
    casper.waitForSelector("form#Location input[type=submit][value='Save']", function success() {
        test.assertExists("form#Location input[type=submit][value='Save']");
        this.click("form#Location input[type=submit][value='Save']");
    }, function fail() {
        test.assertExists("form#Location input[type=submit][value='Save']");
    }); /* submit form */
    casper.wait(2000);
    casper.then(function() {
        // this.capture('3_location_save.png');
    });

    /*
     * Fees
     */

    /* click fees */
    casper.waitForSelector('li#tab_fee a', function success() {
        casper.echo('=====================================');
        casper.echo('** Step 3: Fees. **');
        casper.echo('=====================================');
        test.assertExists('li#tab_fee a');
        this.click('li#tab_fee a');
    }, function fail() {
        test.assertExists('li#tab_fee a');
    });
    casper.wait(2000);

    /* click Paid Event Yes */
    casper.waitForSelector('#CIVICRM_QFID_1_2', function success() {
        test.assertExists('#CIVICRM_QFID_1_2');
        this.click('#CIVICRM_QFID_1_2');
    }, function fail() {
        test.assertExists('#CIVICRM_QFID_1_2');
    });

    /* select Contribution Type */
    casper.waitForSelector("#contribution_type_id", function success() {
        test.assertExists("#contribution_type_id");
        this.evaluate(function () {
            document.querySelector("#contribution_type_id").selectedIndex = 1;
        });
    }, function fail() {
        test.assertExists("#contribution_type_id");
    });

    /* click pay later */
    casper.waitForSelector('#is_pay_later', function success() {
        test.assertExists('#is_pay_later');
        this.click('#is_pay_later');
    }, function fail() {
        test.assertExists('#is_pay_later');
    });

    /* filled up Pay Later Instructions */
    casper.waitForSelector("#pay_later_receipt", function success() {
        test.assertExists("#pay_later_receipt");
        this.sendKeys("#pay_later_receipt", "I will send payment by check");
    }, function fail() {
        test.assertExists("#pay_later_receipt");
    });

    /* Event Level */
    /* level 1 */
    casper.waitForSelector("#label_1", function success() {
        test.assertExists("#label_1");
        this.sendKeys("#label_1", "aaa");
    }, function fail() {
        test.assertExists("#label_1");
    });
    casper.waitForSelector("#value_1", function success() {
        test.assertExists("#value_1");
        this.sendKeys("#value_1", "111");
    }, function fail() {
        test.assertExists("#value_1");
    });

    /* level 2 */
    casper.waitForSelector("#label_2", function success() {
        test.assertExists("#label_2");
        this.sendKeys("#label_2", "bbb");
    }, function fail() {
        test.assertExists("#label_2");
    });
    casper.waitForSelector("#value_2", function success() {
        test.assertExists("#value_2");
        this.sendKeys("#value_2", "222");
    }, function fail() {
        test.assertExists("#value_2");
    });

    /* click Save */
    casper.waitForSelector("form#Fee input[type=submit][value='Save']", function success() {
        test.assertExists("form#Fee input[type=submit][value='Save']");
        this.click("form#Fee input[type=submit][value='Save']");
    }, function fail() {
        test.assertExists("form#Fee input[type=submit][value='Save']");
    }); /* submit form */
    casper.wait(2000);
    casper.then(function() {
        // this.capture('4_fees_save.png');
    });

    /*
     * Online Registration
     */

    /* click Online Registration */
    casper.waitForSelector('li#tab_registration a', function success() {
        casper.echo('=====================================');
        casper.echo('** Step 4: Online Registration. **');
        casper.echo('=====================================');
        test.assertExists('li#tab_registration a');
        this.click('li#tab_registration a');
    }, function fail() {
        test.assertExists('li#tab_registration a');
    });
    casper.wait(2000);

    /* click Allow Online Registration? */
    casper.waitForSelector('#is_online_registration', function success() {
        test.assertExists('#is_online_registration');
        this.click('#is_online_registration');
    }, function fail() {
        test.assertExists('#is_online_registration');
    });

    /* click Confirmation Email accordion */
    casper.waitForSelector('div.crm-accordion-wrapper:nth-child(6) div.crm-accordion-header', function success() {
        test.assertExists('div.crm-accordion-wrapper:nth-child(6) div.crm-accordion-header');
        this.click('div.crm-accordion-wrapper:nth-child(6) div.crm-accordion-header');
    }, function fail() {
        test.assertExists('div.crm-accordion-wrapper:nth-child(6) div.crm-accordion-header');
    });

    /* click Send Confirmation Email? */
    casper.waitForSelector('#CIVICRM_QFID_1_2', function success() {
        test.assertExists('#CIVICRM_QFID_1_2');
        this.click('#CIVICRM_QFID_1_2');
    }, function fail() {
        test.assertExists('#CIVICRM_QFID_1_2');
    });
    
    /* filled up Confirm From Name */
    casper.waitForSelector("#confirm_from_name", function success() {
        test.assertExists("#confirm_from_name");
        this.sendKeys("#confirm_from_name", "Name For Confirm");
    }, function fail() {
        test.assertExists("#confirm_from_name");
    });

    /* filled up Confirm From Email */
    casper.waitForSelector("#confirm_from_email", function success() {
        test.assertExists("#confirm_from_email");
        this.sendKeys("#confirm_from_email", "confirm@fakemail.com");
    }, function fail() {
        test.assertExists("#confirm_from_email");
    });

    /* click Save */
    casper.waitForSelector("form#Registration input[type=submit][value='Save']", function success() {
        test.assertExists("form#Registration input[type=submit][value='Save']");
        this.click("form#Registration input[type=submit][value='Save']");
    }, function fail() {
        test.assertExists("form#Registration input[type=submit][value='Save']");
    }); /* submit form */
    casper.wait(2000);
    casper.then(function() {
        // this.capture('5_online_reg_save.png');
    });

    /*
     * Tell a Friend
     */
    
    /* click Tell a Friend */
    casper.waitForSelector('li#tab_friend a', function success() {
        casper.echo('=====================================');
        casper.echo('** Step 5: Tell a Friend. **');
        casper.echo('=====================================');
        test.assertExists('li#tab_friend a');
        this.click('li#tab_friend a');
    }, function fail() {
        test.assertExists('li#tab_friend a');
    });
    casper.wait(2000);

    /* click Allow Online Registration? */
    casper.waitForSelector('#tf_is_active', function success() {
        test.assertExists('#tf_is_active');
        this.click('#tf_is_active');
    }, function fail() {
        test.assertExists('#tf_is_active');
    });

    /* click Save */
    casper.waitForSelector("form#Event input[type=submit][value='Save']", function success() {
        test.assertExists("form#Event input[type=submit][value='Save']");
        this.click("form#Event input[type=submit][value='Save']");
    }, function fail() {
        test.assertExists("form#Event input[type=submit][value='Save']");
    }); /* submit form */
    casper.wait(2000);
    casper.then(function() {
        // this.capture('6_friend_save.png');
    });


    casper.run(function() {
        test.done();
    });
});