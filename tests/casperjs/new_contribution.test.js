/* basic setting */
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

    /* open new contribution page */
    casper.thenOpen(vars.baseURL + "civicrm/contribute/add?reset=1&action=add&context=standalone", function() {
        casper.echo('=====================================');
        casper.echo('** Step 1: New Contribution. **');
        casper.echo('=====================================');
    });

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

    /* select Contribution Type */
    casper.waitForSelector("#contribution_type_id", function success() {
        test.assertExists("#contribution_type_id");
        this.evaluate(function () {
            document.querySelector("#contribution_type_id").selectedIndex = 1;
        });
    }, function fail() {
        test.assertExists("#contribution_type_id");
    });

    /* sendKeys to Total Amount */
    casper.waitForSelector("#total_amount", function success() {
        test.assertExists("#total_amount");
        this.sendKeys("#total_amount", '100');
    }, function fail() {
        test.assertExists("#total_amount");
    });

    /* sendKeys to Source */
    casper.waitForSelector("#source", function success() {
        test.assertExists("#source");
        this.sendKeys("#source", 'hand to hand');
    }, function fail() {
        test.assertExists("#source");
    });

    /* select received date */
    casper.waitForSelector("#receive_date", function success() {
        test.assertExists("#receive_date");
        this.evaluate(function () {
            document.querySelector("#receive_date").value = "01/01/2020";
        });
    }, function fail() {
        test.assertExists("#receive_date");
    });
    casper.waitForSelector("#receive_date_time", function success() {
        test.assertExists("#receive_date_time");
        this.sendKeys("#receive_date_time", "12:00PM");
    }, function fail() {
        test.assertExists("#receive_date_time");
    });

    /* select Paid By */
    casper.waitForSelector('#payment_instrument_id', function success() {
        test.assertExists('#payment_instrument_id');
        this.evaluate(function () {
            document.querySelector("#payment_instrument_id").selectedIndex = 1;
        });
    }, function fail() {
        test.assertExists('#payment_instrument_id');
    });

    /* sendKeys to Transaction ID */
    casper.waitForSelector("#trxn_id", function success() {
        test.assertExists("#trxn_id");
        this.sendKeys("#trxn_id", makeid(8));
    }, function fail() {
        test.assertExists("#trxn_id");
    });

    /* click Receipt */
    casper.waitForSelector('#have_receipt', function success() {
        test.assertExists('#have_receipt');
        this.click('#have_receipt');
    }, function fail() {
        test.assertExists('#have_receipt');
    });

    /* select receipt date */
    casper.waitForSelector("#receipt_date", function success() {
        test.assertExists("#receipt_date");
        this.evaluate(function () {
            document.querySelector("#receipt_date").value = "01/01/2020";
        });
    }, function fail() {
        test.assertExists("#receipt_date");
    });
    casper.waitForSelector("#receipt_date_time", function success() {
        test.assertExists("#receipt_date_time");
        this.sendKeys("#receipt_date_time", "12:00PM");
    }, function fail() {
        test.assertExists("#receipt_date_time");
    });
    
    /* 收據資訊 */

    /* click 需要（請寄給我紙本收據） */
    casper.waitForSelector('#CIVICRM_QFID_1_4', function success() {
        test.assertExists('#CIVICRM_QFID_1_4');
        this.click('#CIVICRM_QFID_1_4');
    }, function fail() {
        test.assertExists('#CIVICRM_QFID_1_4');
    });    

    /* sendKeys to 收據抬頭 */
    casper.waitForSelector("#custom_2_-1", function success() {
        test.assertExists("#custom_2_-1");
        this.sendKeys("#custom_2_-1", makeid(5));
    }, function fail() {
        test.assertExists("#custom_2_-1");
    });

    /* sendKeys to 報稅憑證 */
    casper.waitForSelector("#custom_3_-1", function success() {
        test.assertExists("#custom_3_-1");
        this.sendKeys("#custom_3_-1", makeid(5));
    }, function fail() {
        test.assertExists("#custom_3_-1");
    });

    /* sendKeys 捐款徵信名稱 */
    casper.waitForSelector("#custom_4_-1", function success() {
        test.assertExists("#custom_4_-1");
        this.sendKeys("#custom_4_-1", makeid(5));
    }, function fail() {
        test.assertExists("#custom_4_-1");
    });
    
    /* click Additional Details */
    casper.waitForSelector('#AdditionalDetail', function success() {
        test.assertExists('#AdditionalDetail');
        this.click('#AdditionalDetail');
    }, function fail() {
        test.assertExists('#AdditionalDetail');
    });

    /* select Contribution Page */
    casper.waitForSelector("#contribution_page_id", function success() {
        test.assertExists("#contribution_page_id");
        this.evaluate(function () {
            var num = document.querySelector("#contribution_page_id").options.length;
            document.querySelector("#contribution_page_id").selectedIndex = num - 1; // choose last one
        });
    }, function fail() {
        test.assertExists("#contribution_page_id");
    });

    /* click Honoree Information */
    casper.waitForSelector('#Honoree', function success() {
        test.assertExists('#Honoree');
        this.click('#Honoree');
    }, function fail() {
        test.assertExists('#Honoree');
    });

    /* click 致敬 */
    casper.waitForSelector('#CIVICRM_QFID_1_2', function success() {
        test.assertExists('#CIVICRM_QFID_1_2');
        this.click('#CIVICRM_QFID_1_2');
    }, function fail() {
        test.assertExists('#CIVICRM_QFID_1_2');
    });

    /* select Prefix */
    casper.waitForSelector("#honor_prefix_id", function success() {
        test.assertExists("#honor_prefix_id");
        this.evaluate(function () {
            document.querySelector("#honor_prefix_id").selectedIndex = 1;
        });
    }, function fail() {
        test.assertExists("#honor_prefix_id");
    });
    
    /* sendKeys to First Name */
    casper.waitForSelector("#honor_first_name", function success() {
        test.assertExists("#honor_first_name");
        this.sendKeys("#honor_first_name", makeid(3));
    }, function fail() {
        test.assertExists("#honor_first_name");
    });

    /* sendKeys to Last Name */
    casper.waitForSelector("#honor_last_name", function success() {
        test.assertExists("#honor_last_name");
        this.sendKeys("#honor_last_name", makeid(3));
    }, function fail() {
        test.assertExists("#honor_last_name");
    });

    casper.then(function() {
        // this.capture('1_form_done.png');
    });
    
    /* click Save */
    casper.waitForSelector("form#Contribution input[type=submit][value='Save']", function success() {
        test.assertExists("form#Contribution input[type=submit][value='Save']");
        this.click("form#Contribution input[type=submit][value='Save']");
    }, function fail() {
        test.assertExists("form#Contribution input[type=submit][value='Save']");
    }); /* submit form */
    casper.wait(2000);

    casper.then(function() {
        // this.capture('2_new_contribution_done.png');
    });
    
    /* check success message */
    casper.waitForSelector(".messages", function success() {
        casper.echo('=====================================');
        casper.echo('** Step 2: Check If New Contribution Success. **');
        casper.echo('=====================================');
        test.assertExists(".messages");
        var message = this.evaluate(function () {
            return document.querySelector(".messages").textContent;
        });
        test.assertEquals(message.trim(), "The contribution record has been saved.");
    }, function fail() {
        test.assertExists(".messages");
    });

    /* click edit contribution */
    casper.waitForSelector('table.selector .row-action .action-item:nth-child(2)', function success() {
        casper.echo('=====================================');
        casper.echo('** Step 3: Edit Contribution. **');
        casper.echo('=====================================');
        test.assertExists('table.selector .row-action .action-item:nth-child(2)');
        this.click('table.selector .row-action .action-item:nth-child(2)');
    }, function fail() {
        test.assertExists('table.selector .row-action .action-item:nth-child(2)');
    });
    casper.wait(2000);

    /* select received date */
    casper.waitForSelector("#receive_date", function success() {
        test.assertExists("#receive_date");
        this.evaluate(function () {
            document.querySelector("#receive_date").value = "01/01/2020";
        });
    }, function fail() {
        test.assertExists("#receive_date");
    });
    casper.waitForSelector("#receive_date_time", function success() {
        test.assertExists("#receive_date_time");
        this.sendKeys("#receive_date_time", "12:00PM");
    }, function fail() {
        test.assertExists("#receive_date_time");
    });

    /* clear receipt date */
    casper.waitForSelector('.crm-clear-link a', function success() {
        test.assertExists('.crm-clear-link a');
        this.click('.crm-clear-link a');
    }, function fail() {
        test.assertExists('.crm-clear-link a');
    });

    casper.then(function() {
        // this.capture('3_edit_form_done.png');
    });
    
    /* click submit */
    casper.waitForSelector("form#Contribution input[type=submit][value='Save']", function success() {
        test.assertExists("form#Contribution input[type=submit][value='Save']");
        this.click("form#Contribution input[type=submit][value='Save']");
    }, function fail() {
        test.assertExists("form#Contribution input[type=submit][value='Save']");
    }); /* submit form */
    casper.wait(2000);

    /* check success message */
    casper.waitForSelector(".messages", function success() {
        casper.echo('=====================================');
        casper.echo('** Step 4: Check If Edit Contribution Success. **');
        casper.echo('=====================================');
        test.assertExists(".messages");
        var message = this.evaluate(function () {
            return document.querySelector(".messages").textContent;
        });
        test.assertEquals(message.trim(), "The contribution record has been saved.");
    }, function fail() {
        test.assertExists(".messages");
    });
    
    casper.then(function() {
        // this.capture('4_edit_done.png');
    });
    
    casper.run(function() {
        test.done();
    });
});
