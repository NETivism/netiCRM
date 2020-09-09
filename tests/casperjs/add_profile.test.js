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

    casper.thenOpen(baseURL + "civicrm/admin/uf/group?reset=1", function() {
    });

    casper.waitForSelector("#newCiviCRMProfile-top", function success() {
        this.click("#newCiviCRMProfile-top");
    }, function fail() {
        test.assertExists("#newCiviCRMProfile-top", "Assert 'Add Profile' button exist.");
    });

    casper.wait(2000);

    casper.then(function() {
        casper.echo('=====================================');
        casper.echo('** Step 1: New Profile. **');
        casper.echo('=====================================');
    });

    casper.waitForSelector("input[name='title']", function success() {
        this.sendKeys("input[name='title']", makeid(5));
    }, function fail() {
        test.assertExists("input[name='title']", "Assert 'Profile Name' field exist.");
    });

    casper.waitForSelector("input[name='uf_group_type_user[User Registration]']", function success() {
        this.click("input[name='uf_group_type_user[User Registration]']");
    }, function fail() {
        test.assertExists("input[name='uf_group_type_user[User Registration]']", "Assert 'Drupal User Registration' exist.");
    });

    casper.waitForSelector("input[name='uf_group_type_user[User Account]']", function success() {
        this.click("input[name='uf_group_type_user[User Account]']");
    }, function fail() {
        test.assertExists("input[name='uf_group_type_user[User Account]']", "Assert 'View/Edit Drupal User Account' exist.");
    });

    casper.waitForSelector("input[value='Save']", function success() {
        this.click("input[value='Save']");
    }, function fail() {
        test.assertExists("input[value='Save']", "Assert 'Save' button exist.");
    });

    casper.wait(2000);

    casper.then(function() {
        casper.echo('=====================================');
        casper.echo('** Step 2: Add Fields. **');
        casper.echo('=====================================');
    });

    casper.then(function() {
        casper.echo("Step 2-1: Add individual: Fist Name.");
    });

    casper.waitForSelector("select[name='field_name[0]']", function success() {
        this.evaluate(function () {
            document.querySelector('select[name="field_name[0]"]').value = "Individual";
            var s = document.querySelector('select[name="field_name[0]"]');
            swapOptions(s.form, 'field_name', 0, 4, 'hs_field_name');
        });
    }, function fail() {
        test.assertExists("select[name='field_name[0]']", "Assert 'Please select a field name' exist.");
    });

    casper.waitForSelector("select[name='field_name[1]']", function success() {
        this.evaluate(function () {
            document.querySelector('select[name="field_name[1]"]').value = "first_name";
        });
    }, function fail() {
        test.assertExists("select[name='field_name[1]']", "Assert individual options exist.");
    });

    casper.waitForSelector("input[name='label']", function success() {
        this.sendKeys("input[name='label']", "First Name");
    }, function fail() {
        test.assertExists("input[name='label']", "Assert 'Field Label' field exist.");
    });

    casper.waitForSelector("input[value='Save and New']", function success() {
        this.click("input[value='Save and New']");
    }, function fail() {
        test.assertExists("input[value='Save and New']", "Assert 'Save and New' button exist.");
    });

    casper.wait(1000);

    casper.then(function() {
        this.capture("test.png");
    });

    casper.run(function() {
        test.done();
    });
});