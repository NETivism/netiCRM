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
    baseURL : port == '80' ? 'http://127.0.0.1/' : 'http://127.0.0.1:' + port + '/'
};

casper.test.begin('Resurrectio test', function(test) {
    casper.start(vars.baseURL, function() {
        casper.echo('=====================================');
        casper.echo('** Step 0-0: Login. **');
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

    /* open add contribution page */
    casper.thenOpen(vars.baseURL + "civicrm/admin/contribute/add?reset=1&action=add", function() {
        casper.echo('=====================================');
        casper.echo('** Step 0-1: Enter "New Contribution Page" Page. **');
        casper.echo('=====================================');
    });
    
    casper.run(function() {
        test.done();
    });
});

casper.test.begin('Start testing...', function(test) {

    /*
     * Step 1: Title
     */

    /* sendKeys to Title */
    casper.waitForSelector("form#Settings input[name='title']", function success() {
        casper.echo('=====================================');
        casper.echo('** Step 1: Title. **');
        casper.echo('=====================================');
        test.assertExists("form#Settings input[name='title']");
        this.sendKeys("form#Settings input[name='title']", makeid(10));
    }, function fail() {
        test.assertExists("form#Settings input[name='title']");
    });

    casper.then(function() {
        // this.capture('1_Title.png');
    });

    /* click Continue >> */
    casper.waitForSelector("form#Settings input[type=submit][value='Continue >>']", function success() {
        test.assertExists("form#Settings input[type=submit][value='Continue >>']");
        this.click("form#Settings input[type=submit][value='Continue >>']");
    }, function fail() {
        test.assertExists("form#Settings input[type=submit][value='Continue >>']");
    }); /* submit form */
    casper.wait(2000);

    /*
     * Step 2: Amounts
     */

    /* click pay later */
    casper.waitForSelector('#is_pay_later', function success() {
        casper.echo('=====================================');
        casper.echo('** Step 2: Amounts. **');
        casper.echo('=====================================');
        test.assertExists('#is_pay_later');
        this.click('#is_pay_later');
    }, function fail() {
        test.assertExists('#is_pay_later');
    });

    /* sendKeys to Pay later instructions */
    casper.waitForSelector("#pay_later_receipt", function success() {
        test.assertExists("#pay_later_receipt");
        this.sendKeys("#pay_later_receipt", "I will send payment by check");
    }, function fail() {
        test.assertExists("#pay_later_receipt");
    });
    
    /* sendKeys to Fixed Contribution Options */
    casper.waitForSelector("form#Amount input[name='label[1]']", function success() {
        test.assertExists("form#Amount input[name='label[1]']");
        this.sendKeys("form#Amount input[name='label[1]']", '100');
    }, function fail() {
        test.assertExists("form#Amount input[name='label[1]']");
    });
    casper.waitForSelector("form#Amount input[name='value[1]']", function success() {
        test.assertExists("form#Amount input[name='value[1]']");
        this.sendKeys("form#Amount input[name='value[1]']", '100');
    }, function fail() {
        test.assertExists("form#Amount input[name='value[1]']");
    });

    casper.waitForSelector("form#Amount input[name='label[2]']", function success() {
        test.assertExists("form#Amount input[name='label[2]']");
        this.sendKeys("form#Amount input[name='label[2]']", '200');
    }, function fail() {
        test.assertExists("form#Amount input[name='label[2]']");
    });
    casper.waitForSelector("form#Amount input[name='value[2]']", function success() {
        test.assertExists("form#Amount input[name='value[2]']");
        this.sendKeys("form#Amount input[name='value[2]']", '200');
    }, function fail() {
        test.assertExists("form#Amount input[name='value[2]']");
    });
    
    casper.then(function() {
        // this.capture('2_Amount.png');
    });

    /* click submit */
    casper.waitForSelector("form#Amount input[type=submit][value='Continue >>']", function success() {
        test.assertExists("form#Amount input[type=submit][value='Continue >>']");
        this.click("form#Amount input[type=submit][value='Continue >>']");
    }, function fail() {
        test.assertExists("form#Amount input[type=submit][value='Continue >>']");
    }); /* submit form */
    casper.wait(2000);
    
    /*
    * Step 3: Memberships
    */
    
    casper.then(function() {
        casper.echo('=====================================');
        casper.echo('** Step 3: Memberships. **');
        casper.echo('=====================================');
        // this.capture('3_Memberships.png');
    });
    /* click submit */
    casper.waitForSelector("form#MembershipBlock input[type=submit][value='Continue >>']", function success() {
        test.assertExists("form#MembershipBlock input[type=submit][value='Continue >>']");
        this.click("form#MembershipBlock input[type=submit][value='Continue >>']");
    }, function fail() {
        test.assertExists("form#MembershipBlock input[type=submit][value='Continue >>']");
    }); /* submit form */
    casper.wait(2000);

    /*
    * Step 4: Thanks
    */

    /* sendKeys to Thank-you Page Title */
    casper.waitForSelector("#thankyou_title", function success() {
        casper.echo('=====================================');
        casper.echo('** Step 4: Thanks. **');
        casper.echo('=====================================');
        test.assertExists("#thankyou_title");
        this.sendKeys("#thankyou_title", makeid(10));
    }, function fail() {
        test.assertExists("#thankyou_title");
    });

    /* sendKeys to Payment Notification From Email */
    casper.waitForSelector("#receipt_from_email", function success() {
        test.assertExists("#receipt_from_email");
        this.sendKeys("#receipt_from_email", makeid(5) + "@fakemail.com");
    }, function fail() {
        test.assertExists("#receipt_from_email");
    });

    casper.then(function() {
        // this.capture('4_Thanks.png');
    });

    /* click submit */
    casper.waitForSelector("form#ThankYou input[type=submit][value='Continue >>']", function success() {
        test.assertExists("form#ThankYou input[type=submit][value='Continue >>']");
        this.click("form#ThankYou input[type=submit][value='Continue >>']");
    }, function fail() {
        test.assertExists("form#ThankYou input[type=submit][value='Continue >>']");
    }); /* submit form */
    casper.wait(2000);

    /*
    * Step 5: Tell
    */

    /* click Tell a Friend enabled? */
    casper.waitForSelector('#tf_is_active', function success() {
        casper.echo('=====================================');
        casper.echo('** Step 5: Tell. **');
        casper.echo('=====================================');
        test.assertExists('#tf_is_active');
        this.click('#tf_is_active');
    }, function fail() {
        test.assertExists('#tf_is_active');
    });

    casper.then(function() {
        // this.capture('5_Tell.png');
    });
    
    /* click submit */
    casper.waitForSelector("form#Contribute input[type=submit][value='Continue >>']", function success() {
        test.assertExists("form#Contribute input[type=submit][value='Continue >>']");
        this.click("form#Contribute input[type=submit][value='Continue >>']");
    }, function fail() {
        test.assertExists("form#Contribute input[type=submit][value='Continue >>']");
    }); /* submit form */
    casper.wait(2000);

    /*
     * Step 6: Include
     */

    /* select Include Profile(top of page) */
    casper.waitForSelector("#custom_pre_id", function success() {
        casper.echo('=====================================');
        casper.echo('** Step 6: Include. **');
        casper.echo('=====================================');
        test.assertExists("#custom_pre_id");
        this.evaluate(function () {
            document.querySelector("#custom_pre_id").selectedIndex = 1;
        });
    }, function fail() {
        test.assertExists("#custom_pre_id");
    });

    /* select Include Profile(bottom of page)*/
    casper.waitForSelector("#custom_post_id", function success() {
        test.assertExists("#custom_post_id");
        this.evaluate(function () {
            document.querySelector("#custom_post_id").selectedIndex = 1;
        });
    }, function fail() {
        test.assertExists("#custom_post_id");
    });

    casper.then(function() {
        // this.capture('6_Include.png');
    });
    
    /* click submit */
    casper.waitForSelector("form#Custom input[type=submit][value='Continue >>']", function success() {
        test.assertExists("form#Custom input[type=submit][value='Continue >>']");
        this.click("form#Custom input[type=submit][value='Continue >>']");
    }, function fail() {
        test.assertExists("form#Custom input[type=submit][value='Continue >>']");
    }); /* submit form */
    casper.wait(2000);

    /*
    * Step 7: Premimums
    */

    /* click Premiums Section Enabled? */
    casper.waitForSelector('#premiums_active', function success() {
        casper.echo('=====================================');
        casper.echo('** Step 7: Premimums. **');
        casper.echo('=====================================');
        test.assertExists('#premiums_active');
        this.click('#premiums_active');
    }, function fail() {
        test.assertExists('#premiums_active');
    });

    /* sendKeys to Title */
    casper.waitForSelector("#premiums_intro_title", function success() {
        test.assertExists("#premiums_intro_title");
        this.sendKeys("#premiums_intro_title", makeid(10));
    }, function fail() {
        test.assertExists("#premiums_intro_title");
    });

    casper.then(function() {
        // this.capture('7_Premimums.png');
    });

    /* click submit */
    casper.waitForSelector("form#Premium input[type=submit][value='Continue >>']", function success() {
        test.assertExists("form#Premium input[type=submit][value='Continue >>']");
        this.click("form#Premium input[type=submit][value='Continue >>']");
    }, function fail() {
        test.assertExists("form#Premium input[type=submit][value='Continue >>']");
    }); /* submit form */
    casper.wait(2000);
    
    /*
    * Step 8: Widget
    */

    /* click Enable Widget? */
    casper.waitForSelector('#is_active', function success() {
        casper.echo('=====================================');
        casper.echo('** Step 8: Widget. **');
        casper.echo('=====================================');
        test.assertExists('#is_active');
        // refs #34197, ckeditor not support old phantomjs
        // this.click('#is_active');
    }, function fail() {
        test.assertExists('#is_active');
    });

    /* filled up About(ckeditor) */
    /* refs #34197, ckeditor not support old phantomjs
    casper.waitForSelector("iframe.cke_wysiwyg_frame", function success() {
        test.assertExists("iframe.cke_wysiwyg_frame");
        this.evaluate(function() {
            document.querySelector('iframe.cke_wysiwyg_frame').contentWindow.document.querySelector("p").textContent = 'widget test';
        });
    }, function fail() {
        test.assertExists("iframe.cke_wysiwyg_frame");
    });
    */

    /* click Save and Preview */
    casper.waitForSelector('#_qf_Widget_refresh', function success() {
        test.assertExists('#_qf_Widget_refresh');
        this.click('#_qf_Widget_refresh');
    }, function fail() {
        test.assertExists('#_qf_Widget_refresh');
    });

    casper.wait(2000);
    
    /* check if widget iframe exist */
    casper.waitForSelector('iframe.crm-container-embed', function success() {
        test.assertExists('iframe.crm-container-embed');
    }, function fail() {
        test.assertExists('iframe.crm-container-embed');
    });

    casper.then(function() {
        // this.capture('8_Widget.png');
    });

    /* click submit */
    casper.waitForSelector("form#Widget input[type=submit][value='Continue >>']", function success() {
        test.assertExists("form#Widget input[type=submit][value='Continue >>']");
        this.click("form#Widget input[type=submit][value='Continue >>']");
    }, function fail() {
        test.assertExists("form#Widget input[type=submit][value='Continue >>']");
    }); /* submit form */
    casper.wait(2000);
    
    /*
     * Step 9: Enable
     */

    /* click Enable Personal Campaign Pages (for this contribution page)? */
    casper.waitForSelector('#is_active', function success() {
        casper.echo('=====================================');
        casper.echo('** Step 9: Enable. **');
        casper.echo('=====================================');
        test.assertExists('#is_active');
        this.click('#is_active');
    }, function fail() {
        test.assertExists('#is_active');
    });

    /* sendKeys to Notify Email */
    casper.waitForSelector("#notify_email", function success() {
        test.assertExists("#notify_email");
        this.sendKeys("#notify_email", makeid(5) + "@fakemail.com");
    }, function fail() {
        test.assertExists("#notify_email");
    });

    casper.then(function() {
        // this.capture('9_Enable.png');
    });

    /* click submit */
    casper.waitForSelector("form#PCP input[type=submit][value='Continue >>']", function success() {
        test.assertExists("form#PCP input[type=submit][value='Continue >>']");
        this.click("form#PCP input[type=submit][value='Continue >>']");
    }, function fail() {
        test.assertExists("form#PCP input[type=submit][value='Continue >>']");
    }); /* submit form */
    casper.wait(2000);

    casper.run(function() {
        test.done();
    });
});