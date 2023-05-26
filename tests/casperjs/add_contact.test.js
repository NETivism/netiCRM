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

var vars = {
    baseURL : port == '80' ? 'http://127.0.0.1/' : 'http://127.0.0.1:' + port + '/',
    first_name: first_name,
    last_name: last_name,
    user_email: first_name.toLowerCase() + last_name.toLowerCase() + '123@gmail.com',
    user_phone: '09' + Math.floor(Math.random() * 100000000).toString()
};

casper.on('page.error', function(msg, trace) {
    this.echo('Error: ' + msg, 'ERROR');
    for (var i = 0; i < trace.length; i++) {
        var step = trace[i];
        this.echo(' ' + step.file + ' (line ' + step.line + ')', 'ERROR');
    }
});

casper.test.begin('Resurrectio test', function(test) {
    casper.start(vars.baseURL, function() {
        casper.echo('=====================================');
        casper.echo('** Step 0: Login. **');
        casper.echo('=====================================');
        //this.capture('login.png');
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

casper.test.begin('Start testing...', function(test){
    casper.thenOpen(vars.baseURL + 'civicrm/contact/add?reset=1&ct=Individual', function() {
        this.capture('add_individual.png');
    });
    casper.waitForSelector("form[name=Contact]", function success() {
        casper.echo('=====================================');
        casper.echo('** Step 1: Add Individual. **');
        casper.echo('=====================================');

        this.sendKeys("input[name='last_name']", vars.last_name);
        casper.echo('Add lastname.')

        this.sendKeys("input[name='first_name']", vars.first_name);
        casper.echo('Add firstname.')

        this.sendKeys("input[name='email[1][email]']", vars.user_email);
        casper.echo('Add email.')

        this.sendKeys("input[name='phone[1][phone]']", vars.user_phone);
        casper.echo('Add phone.')

        this.evaluate(function () {
            document.querySelector("#phone_1_phone_type_id").selectedIndex = 1;
        })
        casper.echo('Change phone type.')

        this.click("form[name=Contact] input[type=submit][value='Save']");
    }, function fail() {
        test.assertExists("form[name=Contact]", "Contact form exist.");
    });

    casper.wait(2000);
    casper.then(function() {
        //this.capture('personal_info.png');
    })
    casper.then(function() {
        casper.echo('=====================================');
        casper.echo('** Step 2: Check If Personal Information Correct. **');
        casper.echo('=====================================');
        test.assertTitle(vars.first_name + ' ' + vars.last_name + ' | netiCRM');
    });
    casper.waitForSelector("#contact-summary .contactCardLeft a", function success() {
        var email = this.evaluate(function () {
            return document.querySelector("#contact-summary .contactCardLeft a").text;
        });
        test.assertEquals(email, vars.user_email);
    }, function fail() {
        test.assertExists("#contact-summary .contactCardLeft a", 'email exist.');
    });
    casper.waitForSelector("#contact-summary .contactCardRight .primary span", function success() {
        var phone = this.evaluate(function() {
            return document.querySelector("#contact-summary .contactCardRight .primary span").textContent;
        });
        test.assertEquals(phone, vars.user_phone);
    }, function fail() {
        test.assertExists("#contact-summary .contactCardRight .primary span");
    });

    casper.run(function() {
        test.done();
    });
});