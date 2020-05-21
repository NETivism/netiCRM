/* basic setting */
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

    /* open custom data page */
    casper.thenOpen(baseURL + "civicrm/admin/custom/group?reset=1", function() {
        // this.capture('custom_data.png');
    });

    
    /*
     * Add Set of Custom Fields
    */

    /* click something */
    casper.waitForSelector('#newCustomDataGroup', function success() {
        test.assertExists('#newCustomDataGroup');
        this.click('#newCustomDataGroup');
    }, function fail() {
        test.assertExists('#newCustomDataGroup');
    });

    casper.wait(2000);
    casper.then(function() {
        casper.echo('=====================================');
        casper.echo('** Step 1: Add Set of Custom Fields. **');
        casper.echo('=====================================');
    });

    var id = makeid(5);

    /* sendKeys to Set Name */
    casper.waitForSelector("#title", function success() {
        test.assertExists("#title");
        this.sendKeys("#title", 'testset' + id);
    }, function fail() {
        test.assertExists("#title");
    });

    /* select Used For */
    casper.waitForSelector("select[name='extends[0]']", function success() {
        test.assertExists("select[name='extends[0]']");
        this.evaluate(function () {
            document.querySelector("select[name='extends[0]']").selectedIndex = 1;
        });
    }, function fail() {
        test.assertExists("select[name='extends[0]']");
    });
    casper.then(function() {
        // this.capture('add_set_of_custom.png');
    });
    
    /* click Save */
    casper.waitForSelector('input[type=submit][value="Save"]', function success() {
        test.assertExists('input[type="submit"][value="Save"]');
        this.click('input[type="submit"][value="Save"]');
    }, function fail() {
        test.assertExists('input[type="submit"][value="Save"]');
    });

    casper.wait(2000);
    test.assertDoesntExist('.crm-error');


    /*
     * Add 7 alphanumeric fields
     */
    casper.then(function() {
        casper.echo('=====================================');
        casper.echo('** Step 2: Add Alphanumeric field, including Text, Select, Radio, CheckBox, Multi-Select, Advanced Multi-Select, Autocomplete Select. **');
        casper.echo('=====================================');
    });
    

    /* 1. Text */
    /* sendKeys to Field Label */
    casper.waitForSelector("input[name='label']", function success() {
        casper.echo('** Step 2-1: Add Text field. **');
        test.assertExists("input[name='label']");
        this.sendKeys("input[name='label']", 'text' + id);
    }, function fail() {
        test.assertExists("input[name='label']");
    });

    /* select Data and Input Field Type*/
    casper.waitForSelector("select[name='data_type[0]']", function success() {
        test.assertExists("select[name='data_type[0]']");
        this.evaluate(function () {
            document.querySelector("select[name='data_type[0]']").selectedIndex = 0;
        });
    }, function fail() {
        test.assertExists("select[name='data_type[0]']");
    });
    casper.waitForSelector("select[name='data_type[1]']", function success() {
        test.assertExists("select[name='data_type[1]']");
        this.evaluate(function () {
            document.querySelector("select[name='data_type[1]']").selectedIndex = 0;
        });
    }, function fail() {
        test.assertExists("select[name='data_type[1]']");
    });

    casper.then(function() {
        // this.capture('Text.png');
    });

    /* click submit */
    casper.waitForSelector("input[type=submit][value='Save and New']", function success() {
        test.assertExists("input[type=submit][value='Save and New']");
        this.click("input[type=submit][value='Save and New']");
    }, function fail() {
        test.assertExists("input[type=submit][value='Save and New']");
    }); /* submit form */
    casper.wait(2000);
    test.assertDoesntExist('.crm-error');


    /* 2. Select */
    /* sendKeys to Field Label */
    casper.waitForSelector("input[name='label']", function success() {
        casper.echo('** Step 2-2: Add Select field. **');
        test.assertExists("input[name='label']");
        this.sendKeys("input[name='label']", 'select' + id);
    }, function fail() {
        test.assertExists("input[name='label']");
    });

    /* select Data and Input Field Type*/
    casper.waitForSelector("select[name='data_type[0]']", function success() {
        test.assertExists("select[name='data_type[0]']");
        this.evaluate(function () {
            document.querySelector("select[name='data_type[0]']").selectedIndex = 0;
        });
    }, function fail() {
        test.assertExists("select[name='data_type[0]']");
    });
    casper.waitForSelector("select[name='data_type[1]']", function success() {
        test.assertExists("select[name='data_type[1]']");
        this.evaluate(function () {
            document.querySelector("select[name='data_type[1]']").selectedIndex = 1;
        });
    }, function fail() {
        test.assertExists("select[name='data_type[1]']");
    });

    /* click dropdown to invoke onclick function */
    casper.waitForSelector("select[name='data_type[1]']", function success() {
        test.assertExists("select[name='data_type[1]']");
        this.click("select[name='data_type[1]']");
    }, function fail() {
        test.assertExists("select[name='data_type[1]']");
    });    

    /* sendKeys to Multiple Choice Options*/
    casper.waitForSelector("input[name='option_label[1]']", function success() {
        test.assertExists("input[name='option_label[1]']");
        this.sendKeys("input[name='option_label[1]']", "op1");
    }, function fail() {
        test.assertExists("input[name='option_label[1]']");
    });
    casper.waitForSelector("input[name='option_value[1]']", function success() {
        test.assertExists("input[name='option_value[1]']");
        this.sendKeys("input[name='option_value[1]']", "op1");
    }, function fail() {
        test.assertExists("input[name='option_value[1]']");
    });
    casper.waitForSelector("input[name='option_label[2]']", function success() {
        test.assertExists("input[name='option_label[2]']");
        this.sendKeys("input[name='option_label[2]']", "op2");
    }, function fail() {
        test.assertExists("input[name='option_label[2]']");
    });
    casper.waitForSelector("input[name='option_value[2]']", function success() {
        test.assertExists("input[name='option_value[2]']");
        this.sendKeys("input[name='option_value[2]']", "op2");
    }, function fail() {
        test.assertExists("input[name='option_value[2]']");
    });

    casper.then(function() {
        // this.capture('Select.png');
    });

    /* click submit */
    casper.waitForSelector("input[type=submit][value='Save and New']", function success() {
        test.assertExists("input[type=submit][value='Save and New']");
        this.click("input[type=submit][value='Save and New']");
    }, function fail() {
        test.assertExists("input[type=submit][value='Save and New']");
    }); /* submit form */
    casper.wait(2000);
    test.assertDoesntExist('.crm-error');


    /* 3. Radio */
    /* sendKeys to Field Label */
    casper.waitForSelector("input[name='label']", function success() {
        casper.echo('** Step 2-3: Add Radio field. **');
        test.assertExists("input[name='label']");
        this.sendKeys("input[name='label']", 'radio' + id);
    }, function fail() {
        test.assertExists("input[name='label']");
    });

    /* select Data and Input Field Type*/
    casper.waitForSelector("select[name='data_type[0]']", function success() {
        test.assertExists("select[name='data_type[0]']");
        this.evaluate(function () {
            document.querySelector("select[name='data_type[0]']").selectedIndex = 0;
        });
    }, function fail() {
        test.assertExists("select[name='data_type[0]']");
    });
    casper.waitForSelector("select[name='data_type[1]']", function success() {
        test.assertExists("select[name='data_type[1]']");
        this.evaluate(function () {
            document.querySelector("select[name='data_type[1]']").selectedIndex = 2;
        });
    }, function fail() {
        test.assertExists("select[name='data_type[1]']");
    });

    /* click dropdown to invoke onclick function */
    casper.waitForSelector("select[name='data_type[1]']", function success() {
        test.assertExists("select[name='data_type[1]']");
        this.click("select[name='data_type[1]']");
    }, function fail() {
        test.assertExists("select[name='data_type[1]']");
    });    

    /* sendKeys to Multiple Choice Options*/
    casper.waitForSelector("input[name='option_label[1]']", function success() {
        test.assertExists("input[name='option_label[1]']");
        this.sendKeys("input[name='option_label[1]']", "op1");
    }, function fail() {
        test.assertExists("input[name='option_label[1]']");
    });
    casper.waitForSelector("input[name='option_value[1]']", function success() {
        test.assertExists("input[name='option_value[1]']");
        this.sendKeys("input[name='option_value[1]']", "op1");
    }, function fail() {
        test.assertExists("input[name='option_value[1]']");
    });
    casper.waitForSelector("input[name='option_label[2]']", function success() {
        test.assertExists("input[name='option_label[2]']");
        this.sendKeys("input[name='option_label[2]']", "op2");
    }, function fail() {
        test.assertExists("input[name='option_label[2]']");
    });
    casper.waitForSelector("input[name='option_value[2]']", function success() {
        test.assertExists("input[name='option_value[2]']");
        this.sendKeys("input[name='option_value[2]']", "op2");
    }, function fail() {
        test.assertExists("input[name='option_value[2]']");
    });

    casper.then(function() {
        // this.capture('Radio.png');
    });

    /* click submit */
    casper.waitForSelector("input[type=submit][value='Save and New']", function success() {
        test.assertExists("input[type=submit][value='Save and New']");
        this.click("input[type=submit][value='Save and New']");
    }, function fail() {
        test.assertExists("input[type=submit][value='Save and New']");
    }); /* submit form */
    casper.wait(2000);
    test.assertDoesntExist('.crm-error');


    /* 4. Checkbox */
    /* sendKeys to Field Label */
    casper.waitForSelector("input[name='label']", function success() {
        casper.echo('** Step 2-4: Add Checkbox field. **');
        test.assertExists("input[name='label']");
        this.sendKeys("input[name='label']", 'checkbox' + id);
    }, function fail() {
        test.assertExists("input[name='label']");
    });

    /* select Data and Input Field Type*/
    casper.waitForSelector("select[name='data_type[0]']", function success() {
        test.assertExists("select[name='data_type[0]']");
        this.evaluate(function () {
            document.querySelector("select[name='data_type[0]']").selectedIndex = 0;
        });
    }, function fail() {
        test.assertExists("select[name='data_type[0]']");
    });
    casper.waitForSelector("select[name='data_type[1]']", function success() {
        test.assertExists("select[name='data_type[1]']");
        this.evaluate(function () {
            document.querySelector("select[name='data_type[1]']").selectedIndex = 3;
        });
    }, function fail() {
        test.assertExists("select[name='data_type[1]']");
    });

    /* click dropdown to invoke onclick function */
    casper.waitForSelector("select[name='data_type[1]']", function success() {
        test.assertExists("select[name='data_type[1]']");
        this.click("select[name='data_type[1]']");
    }, function fail() {
        test.assertExists("select[name='data_type[1]']");
    });    

    /* sendKeys to Multiple Choice Options*/
    casper.waitForSelector("input[name='option_label[1]']", function success() {
        test.assertExists("input[name='option_label[1]']");
        this.sendKeys("input[name='option_label[1]']", "op1");
    }, function fail() {
        test.assertExists("input[name='option_label[1]']");
    });
    casper.waitForSelector("input[name='option_value[1]']", function success() {
        test.assertExists("input[name='option_value[1]']");
        this.sendKeys("input[name='option_value[1]']", "op1");
    }, function fail() {
        test.assertExists("input[name='option_value[1]']");
    });
    casper.waitForSelector("input[name='option_label[2]']", function success() {
        test.assertExists("input[name='option_label[2]']");
        this.sendKeys("input[name='option_label[2]']", "op2");
    }, function fail() {
        test.assertExists("input[name='option_label[2]']");
    });
    casper.waitForSelector("input[name='option_value[2]']", function success() {
        test.assertExists("input[name='option_value[2]']");
        this.sendKeys("input[name='option_value[2]']", "op2");
    }, function fail() {
        test.assertExists("input[name='option_value[2]']");
    });

    casper.then(function() {
        // this.capture('Checkbox.png');
    });

    /* click submit */
    casper.waitForSelector("input[type=submit][value='Save and New']", function success() {
        test.assertExists("input[type=submit][value='Save and New']");
        this.click("input[type=submit][value='Save and New']");
    }, function fail() {
        test.assertExists("input[type=submit][value='Save and New']");
    }); /* submit form */
    casper.wait(2000);
    test.assertDoesntExist('.crm-error');


    /* 5. Multi-Select */
    /* sendKeys to Field Label */
    casper.waitForSelector("input[name='label']", function success() {
        casper.echo('** Step 2-5: Add Multi-Select field. **');
        test.assertExists("input[name='label']");
        this.sendKeys("input[name='label']", 'multi_select' + id);
    }, function fail() {
        test.assertExists("input[name='label']");
    });

    /* select Data and Input Field Type*/
    casper.waitForSelector("select[name='data_type[0]']", function success() {
        test.assertExists("select[name='data_type[0]']");
        this.evaluate(function () {
            document.querySelector("select[name='data_type[0]']").selectedIndex = 0;
        });
    }, function fail() {
        test.assertExists("select[name='data_type[0]']");
    });
    casper.waitForSelector("select[name='data_type[1]']", function success() {
        test.assertExists("select[name='data_type[1]']");
        this.evaluate(function () {
            document.querySelector("select[name='data_type[1]']").selectedIndex = 4;
        });
    }, function fail() {
        test.assertExists("select[name='data_type[1]']");
    });

    /* click dropdown to invoke onclick function */
    casper.waitForSelector("select[name='data_type[1]']", function success() {
        test.assertExists("select[name='data_type[1]']");
        this.click("select[name='data_type[1]']");
    }, function fail() {
        test.assertExists("select[name='data_type[1]']");
    });    

    /* sendKeys to Multiple Choice Options*/
    casper.waitForSelector("input[name='option_label[1]']", function success() {
        test.assertExists("input[name='option_label[1]']");
        this.sendKeys("input[name='option_label[1]']", "op1");
    }, function fail() {
        test.assertExists("input[name='option_label[1]']");
    });
    casper.waitForSelector("input[name='option_value[1]']", function success() {
        test.assertExists("input[name='option_value[1]']");
        this.sendKeys("input[name='option_value[1]']", "op1");
    }, function fail() {
        test.assertExists("input[name='option_value[1]']");
    });
    casper.waitForSelector("input[name='option_label[2]']", function success() {
        test.assertExists("input[name='option_label[2]']");
        this.sendKeys("input[name='option_label[2]']", "op2");
    }, function fail() {
        test.assertExists("input[name='option_label[2]']");
    });
    casper.waitForSelector("input[name='option_value[2]']", function success() {
        test.assertExists("input[name='option_value[2]']");
        this.sendKeys("input[name='option_value[2]']", "op2");
    }, function fail() {
        test.assertExists("input[name='option_value[2]']");
    });

    casper.then(function() {
        // this.capture('Multi-Select.png');
    });

    /* click submit */
    casper.waitForSelector("input[type=submit][value='Save and New']", function success() {
        test.assertExists("input[type=submit][value='Save and New']");
        this.click("input[type=submit][value='Save and New']");
    }, function fail() {
        test.assertExists("input[type=submit][value='Save and New']");
    }); /* submit form */
    casper.wait(2000);
    test.assertDoesntExist('.crm-error');
    
    /* 6. Advanced Multi-Select */
    /* sendKeys to Field Label */
    casper.waitForSelector("input[name='label']", function success() {
        casper.echo('** Step 2-6: Add Advanced Multi-Select field. **');
        test.assertExists("input[name='label']");
        this.sendKeys("input[name='label']", 'advanced_multi_select' + id);
    }, function fail() {
        test.assertExists("input[name='label']");
    });

    /* select Data and Input Field Type*/
    casper.waitForSelector("select[name='data_type[0]']", function success() {
        test.assertExists("select[name='data_type[0]']");
        this.evaluate(function () {
            document.querySelector("select[name='data_type[0]']").selectedIndex = 0;
        });
    }, function fail() {
        test.assertExists("select[name='data_type[0]']");
    });
    casper.waitForSelector("select[name='data_type[1]']", function success() {
        test.assertExists("select[name='data_type[1]']");
        this.evaluate(function () {
            document.querySelector("select[name='data_type[1]']").selectedIndex = 5;
        });
    }, function fail() {
        test.assertExists("select[name='data_type[1]']");
    });

    /* click dropdown to invoke onclick function */
    casper.waitForSelector("select[name='data_type[1]']", function success() {
        test.assertExists("select[name='data_type[1]']");
        this.click("select[name='data_type[1]']");
    }, function fail() {
        test.assertExists("select[name='data_type[1]']");
    });    

    /* sendKeys to Multiple Choice Options*/
    casper.waitForSelector("input[name='option_label[1]']", function success() {
        test.assertExists("input[name='option_label[1]']");
        this.sendKeys("input[name='option_label[1]']", "op1");
    }, function fail() {
        test.assertExists("input[name='option_label[1]']");
    });
    casper.waitForSelector("input[name='option_value[1]']", function success() {
        test.assertExists("input[name='option_value[1]']");
        this.sendKeys("input[name='option_value[1]']", "op1");
    }, function fail() {
        test.assertExists("input[name='option_value[1]']");
    });
    casper.waitForSelector("input[name='option_label[2]']", function success() {
        test.assertExists("input[name='option_label[2]']");
        this.sendKeys("input[name='option_label[2]']", "op2");
    }, function fail() {
        test.assertExists("input[name='option_label[2]']");
    });
    casper.waitForSelector("input[name='option_value[2]']", function success() {
        test.assertExists("input[name='option_value[2]']");
        this.sendKeys("input[name='option_value[2]']", "op2");
    }, function fail() {
        test.assertExists("input[name='option_value[2]']");
    });

    casper.then(function() {
        // this.capture('Advanced_Multi-Select.png');
    });

    /* click submit */
    casper.waitForSelector("input[type=submit][value='Save and New']", function success() {
        test.assertExists("input[type=submit][value='Save and New']");
        this.click("input[type=submit][value='Save and New']");
    }, function fail() {
        test.assertExists("input[type=submit][value='Save and New']");
    }); /* submit form */
    casper.wait(2000);
    test.assertDoesntExist('.crm-error');

     
    /* 7. Autocomplete Select */
    /* sendKeys to Field Label */
    casper.waitForSelector("input[name='label']", function success() {
        casper.echo('** Step 2-7: Add Autocomplete Select field. **');
        test.assertExists("input[name='label']");
        this.sendKeys("input[name='label']", 'autocomplete_select' + id);
    }, function fail() {
        test.assertExists("input[name='label']");
    });

    /* select Data and Input Field Type*/
    casper.waitForSelector("select[name='data_type[0]']", function success() {
        test.assertExists("select[name='data_type[0]']");
        this.evaluate(function () {
            document.querySelector("select[name='data_type[0]']").selectedIndex = 0;
        });
    }, function fail() {
        test.assertExists("select[name='data_type[0]']");
    });
    casper.waitForSelector("select[name='data_type[1]']", function success() {
        test.assertExists("select[name='data_type[1]']");
        this.evaluate(function () {
            document.querySelector("select[name='data_type[1]']").selectedIndex = 6;
        });
    }, function fail() {
        test.assertExists("select[name='data_type[1]']");
    });

    /* click dropdown to invoke onclick function */
    casper.waitForSelector("select[name='data_type[1]']", function success() {
        test.assertExists("select[name='data_type[1]']");
        this.click("select[name='data_type[1]']");
    }, function fail() {
        test.assertExists("select[name='data_type[1]']");
    });    

    /* sendKeys to Multiple Choice Options*/
    casper.waitForSelector("input[name='option_label[1]']", function success() {
        test.assertExists("input[name='option_label[1]']");
        this.sendKeys("input[name='option_label[1]']", "op1");
    }, function fail() {
        test.assertExists("input[name='option_label[1]']");
    });
    casper.waitForSelector("input[name='option_value[1]']", function success() {
        test.assertExists("input[name='option_value[1]']");
        this.sendKeys("input[name='option_value[1]']", "op1");
    }, function fail() {
        test.assertExists("input[name='option_value[1]']");
    });
    casper.waitForSelector("input[name='option_label[2]']", function success() {
        test.assertExists("input[name='option_label[2]']");
        this.sendKeys("input[name='option_label[2]']", "op2");
    }, function fail() {
        test.assertExists("input[name='option_label[2]']");
    });
    casper.waitForSelector("input[name='option_value[2]']", function success() {
        test.assertExists("input[name='option_value[2]']");
        this.sendKeys("input[name='option_value[2]']", "op2");
    }, function fail() {
        test.assertExists("input[name='option_value[2]']");
    });

    casper.then(function() {
        // this.capture('Autocomplete_Select.png');
    });

    /* click submit */
    casper.waitForSelector("input[type=submit][value='Save and New']", function success() {
        test.assertExists("input[type=submit][value='Save and New']");
        this.click("input[type=submit][value='Save and New']");
    }, function fail() {
        test.assertExists("input[type=submit][value='Save and New']");
    }); /* submit form */
    casper.wait(2000);
    test.assertDoesntExist('.crm-error');

    casper.run(function() {
        test.done();
    });
});
