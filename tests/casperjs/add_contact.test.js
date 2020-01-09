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

var first_name = makeid(5);
var last_name = makeid(5);
var user_email = first_name.toLowerCase() + last_name.toLowerCase() + '123@gmail.com'
var user_phone = '09' + Math.floor(Math.random() * 100000000).toString();

casper.on('page.error', function(msg, trace) {
    this.echo('Error: ' + msg, 'ERROR');
    for (var i = 0; i < trace.length; i++) {
        var step = trace[i];
        this.echo(' ' + step.file + ' (line ' + step.line + ')', 'ERROR');
    }
});

casper.test.begin('Resurrectio test', function(test) {
    casper.start('http://127.0.0.1:' + port, function() {
        //this.capture('login.png');
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
        this.sendKeys("input[name='pass']", "123456\r");
    }, function fail() {
        test.assertExists("input[name='pass']");
    });
    casper.waitForSelector("form#user-login-form input[type=submit][value='Log in']", function success() {
        test.assertExists("form#user-login-form input[type=submit][value='Log in']");
        this.click("form#user-login-form input[type=submit][value='Log in']");
    }, function fail() {
        test.assertExists("form#user-login-form input[type=submit][value='Log in']");
    }); /* submit form */

    casper.thenOpen('http://127.0.0.1:' + port + '/civicrm/contact/add?reset=1&ct=Individual', function() {
        //this.capture('add_individual.png');
    });
    casper.waitForSelector("input[name='last_name']", function success() {
        this.sendKeys("input[name='last_name']", last_name);
    }, function fail() {
        test.assertExists("input[name='last_name']");
    });
    casper.waitForSelector("form[name=Contact] input[name='first_name']", function success() {
        test.assertExists("form[name=Contact] input[name='first_name']");
        this.click("form[name=Contact] input[name='first_name']");
    }, function fail() {
        test.assertExists("form[name=Contact] input[name='first_name']");
    });
    casper.waitForSelector("input[name='first_name']", function success() {
        this.sendKeys("input[name='first_name']", first_name);
    }, function fail() {
        test.assertExists("input[name='first_name']");
    });
    casper.waitForSelector("form[name=Contact] input[name='email[1][email]']", function success() {
        test.assertExists("form[name=Contact] input[name='email[1][email]']");
        this.click("form[name=Contact] input[name='email[1][email]']");
    }, function fail() {
        test.assertExists("form[name=Contact] input[name='email[1][email]']");
    });
    casper.waitForSelector("input[name='email[1][email]']", function success() {
        this.sendKeys("input[name='email[1][email]']", user_email);
    }, function fail() {
        test.assertExists("input[name='email[1][email]']");
    });
    casper.waitForSelector("form[name=Contact] input[name='phone[1][phone]']", function success() {
        test.assertExists("form[name=Contact] input[name='phone[1][phone]']");
        this.click("form[name=Contact] input[name='phone[1][phone]']");
    }, function fail() {
        test.assertExists("form[name=Contact] input[name='phone[1][phone]']");
    });
    casper.waitForSelector("input[name='phone[1][phone]']", function success() {
        this.sendKeys("input[name='phone[1][phone]']", user_phone);
    }, function fail() {
        test.assertExists("input[name='phone[1][phone]']");
    });
    casper.waitForSelector("#phone_1_phone_type_id", function success() {
        test.assertExists("#phone_1_phone_type_id");
        this.evaluate(function () {
            document.querySelector("#phone_1_phone_type_id").selectedIndex = 1;
        })
    }, function fail() {
        test.assertExists("#phone_1_phone_type_id");
    });
    casper.then(function() {
        //this.capture('form_write_done.png');
    });
    casper.waitForSelector("form[name=Contact] input[type=submit][value='Save']", function success() {
        test.assertExists("form[name=Contact] input[type=submit][value='Save']");
        this.click("form[name=Contact] input[type=submit][value='Save']");
    }, function fail() {
        test.assertExists("form[name=Contact] input[type=submit][value='Save']");
    }); /* submit form */

    casper.wait(2000);
    casper.then(function() {
        //this.capture('personal_info.png');
    })
    casper.then(function() {
        test.assertTitle(first_name + ' ' + last_name + ' | netiCRM');
    });
    casper.waitForSelector("#contact-summary > div.contact_details > div:nth-child(1) > div.contactCardLeft > table > tbody > tr:nth-child(1) > td:nth-child(2) > span > a", function success() {
        test.assertExists("#contact-summary > div.contact_details > div:nth-child(1) > div.contactCardLeft > table > tbody > tr:nth-child(1) > td:nth-child(2) > span > a");
        var email = this.evaluate(function () {
            return document.querySelector("#contact-summary > div.contact_details > div:nth-child(1) > div.contactCardLeft > table > tbody > tr:nth-child(1) > td:nth-child(2) > span > a").text;
        });
        test.assertEquals(email, user_email);
    }, function fail() {
        test.assertExists("#contact-summary > div.contact_details > div:nth-child(1) > div.contactCardLeft > table > tbody > tr:nth-child(1) > td:nth-child(2) > span > a");
    });
    casper.waitForSelector("#contact-summary > div.contact_details > div:nth-child(1) > div.contactCardRight > table > tbody > tr > td.primary > span", function success() {
        test.assertExists("#contact-summary > div.contact_details > div:nth-child(1) > div.contactCardRight > table > tbody > tr > td.primary > span");
        var phone = this.evaluate(function() {
            return document.querySelector("#contact-summary > div.contact_details > div:nth-child(1) > div.contactCardRight > table > tbody > tr > td.primary > span").textContent;
        });
        test.assertEquals(phone, user_phone);
    }, function fail() {
        test.assertExists("#contact-summary > div.contact_details > div:nth-child(1) > div.contactCardRight > table > tbody > tr > td.primary > span");
    });
    casper.run(function() {
        test.done();
    });
});