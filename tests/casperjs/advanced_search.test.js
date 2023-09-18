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

    casper.thenOpen(vars.baseURL + "civicrm/contact/search/advanced?reset=1", function() {
    });

    casper.then(function() {
        casper.echo('=====================================');
        casper.echo('** Step 1: Fill Up Search Criteria. **');
        casper.echo('=====================================');
    });

    casper.then(function() {
        casper.echo("Step 1-1: Fill up 'Contact Information'.");
    });

    casper.waitForSelector("#group", function success() {
        this.evaluate(function () {
            document.getElementById("group").selectedIndex = 2;
        });
    }, function fail() {
        test.assertExists("#group", "Assert 'Group(s)' field does not exist.");
    });

    casper.waitForSelector("#contact_tags", function success() {
        this.evaluate(function () {
            document.getElementById("contact_tags").selectedIndex = 1;
        });
    }, function fail() {
        test.assertExists("#contact_tags", "Assert 'Select Tag(s)' field does not exist.");
    });

    casper.then(function() {
        casper.echo("Step 1-2: Fill up 'Address Fields'.");
    });

    casper.waitForSelector("#location", function success() {
        this.click("#location");
    }, function fail() {
        test.assertExists("#location", "Assert 'Address Fields' exist.");
    });
    
    casper.wait(2000);

    casper.waitForSelector("#state_province", function success() {
        test.assertExists('#state_province');
        this.evaluate(function () {
            document.querySelector("#state_province").selectedIndex = 1;
        });
    }, function fail() {
        test.assertExists('#state_province', "Assert first option of 'State / Province dropdown list' does not exist.");
    });

    casper.then(function() {
        casper.echo("Step 1-3: Fill up 'Notes'.");
    });

    casper.waitForSelector("#notes", function success() {
        this.click("#notes");
    }, function fail() {
        test.assertExists("#notes", "Assert 'Notes' exist");
    });

    casper.wait(2000);

    casper.waitForSelector("input[name='note']", function success() {
        this.sendKeys("input[name='note']", makeid(5));
    }, function fail() {
        test.assertExists("input[name='note']", "Assert 'Note Text' field exist.");
    });

    casper.then(function() {
        casper.echo("Step 1-4: Fill up 'Contribute'.");
    });

    casper.waitForSelector("#CiviContribute", function success() {
        this.click("#CiviContribute");
    }, function fail() {
        test.assertExists("#CiviContribute", "Assert 'Contributions' exist.");
    });

    casper.wait(2000);

    casper.waitForSelector("#contribution_source", function success() {
        this.sendKeys("#contribution_source", makeid(5));
    }, function fail() {
        test.assertExists("#contribution_source", "Assert 'Contribution Source' field exist.");
    });

    casper.then(function() {
        casper.echo("Step 1-5: Fill up 'Memberships'.");
    });

    casper.waitForSelector("#CiviMember", function success() {
        this.click("#CiviMember");
    }, function fail() {
        test.assertExists("#CiviMember", "Assert 'Memberships' exist.");
    });
    
    casper.wait(2000);

    casper.waitForSelector("#member_source", function success() {
        this.sendKeys("#member_source", makeid(5));
    }, function fail() {
        test.assertExists("#member_source", "Assert 'Source' field exist.");
    });

    casper.then(function() {
        casper.echo("Step 1-6: Fill up 'Events'.");
    });

    casper.waitForSelector("#CiviEvent", function success() {
        this.click("#CiviEvent");
    }, function fail() {
        test.assertExists("#CiviEvent", "Assert 'Events' exist.");
    });

    casper.wait(2000);

    casper.waitForSelector("#event_id", function success() {
        this.sendKeys("#event_id", 1);
    }, function fail() {
        test.assertExists("#event_id", "Assert 'Event Name' field exist.");
    });

    casper.then(function() {
        // this.capture("filled_up_all.png");
    });

    casper.then(function() {
        casper.echo("Step 1-7: Apply search.");
    });

    casper.waitForSelector("input[name='_qf_Advanced_refresh']", function success() {
        this.click("input[name='_qf_Advanced_refresh']");
    }, function fail() {
        test.assertExists("input[name='_qf_Advanced_refresh']", "Assert 'Search' button exist.");
    });

    casper.wait(2000);

    casper.then(function() {
        test.assertDoesntExist('.error-ci', "Assert no error.");
    });

    casper.then(function() {
        // this.capture("search_result.png");
    });

    casper.run(function() {
        test.done();
    });
});