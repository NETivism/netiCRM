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
    baseURL : port == '80' ? 'http://127.0.0.1/' : 'http://127.0.0.1:' + port + '/',
    id: makeid(5),
    ids: [],
    ids_for_check: [],
    custom_id: 0,
    text_id_for_input: [],
    radio_id_for_input: []
};

casper.on('remote.message', function (msg) {
    this.echo('remote message caught: ' + msg);
});

function fill_options(test) {
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
}

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

    /*
     * Add Set of Custom Fields
    */
    casper.then(function() {
        casper.echo('=====================================');
        casper.echo('** Step 1: Add Set of Custom Fields. **');
        casper.echo('=====================================');
    });

    // open New Custom Field Set page
    casper.thenOpen(vars.baseURL + "civicrm/admin/custom/group?action=add&reset=1");
    casper.wait(2000);

    /* sendKeys to Set Name */
    casper.waitForSelector("#title", function success() {
        test.assertExists("#title");
        this.sendKeys("#title", 'testset' + vars.id);
    }, function fail() {
        test.assertExists("#title");
    });

    /* select 'Used For' to 'Contact' */
    casper.waitForSelector("select[name='extends[0]']", function success() {
        test.assertExists("select[name='extends[0]']");
        this.evaluate(function () {
            document.querySelector("select[name='extends[0]']").value = 'Contact';
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
    casper.then(function() {
        test.assertDoesntExist('.crm-error');
    });


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
        this.sendKeys("input[name='label']", 'text' + vars.id);
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
    casper.then(function() {
        test.assertDoesntExist('.crm-error');
    });


    /* 2. Select */
    /* sendKeys to Field Label */
    casper.waitForSelector("input[name='label']", function success() {
        casper.echo('** Step 2-2: Add Select field. **');
        test.assertExists("input[name='label']");
        this.sendKeys("input[name='label']", 'select' + vars.id);
    }, function fail() {
        test.assertExists("input[name='label']");
    });

    /* select Data and Input Field Type */
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

    fill_options(test);

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
    casper.then(function() {
        test.assertDoesntExist('.crm-error');
    });


    /* 3. Radio */
    /* sendKeys to Field Label */
    casper.waitForSelector("input[name='label']", function success() {
        casper.echo('** Step 2-3: Add Radio field. **');
        test.assertExists("input[name='label']");
        this.sendKeys("input[name='label']", 'radio' + vars.id);
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

    fill_options(test);

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
    casper.then(function() {
        test.assertDoesntExist('.crm-error');
    });


    /* 4. Checkbox */
    /* sendKeys to Field Label */
    casper.waitForSelector("input[name='label']", function success() {
        casper.echo('** Step 2-4: Add Checkbox field. **');
        test.assertExists("input[name='label']");
        this.sendKeys("input[name='label']", 'checkbox' + vars.id);
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

    fill_options(test);

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
    casper.then(function() {
        test.assertDoesntExist('.crm-error');
    });


    /* 5. Multi-Select */
    /* sendKeys to Field Label */
    casper.waitForSelector("input[name='label']", function success() {
        casper.echo('** Step 2-5: Add Multi-Select field. **');
        test.assertExists("input[name='label']");
        this.sendKeys("input[name='label']", 'multi_select' + vars.id);
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

    fill_options(test);

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
    casper.then(function() {
        test.assertDoesntExist('.crm-error');
    });
    
    /* 6. Advanced Multi-Select */
    /* sendKeys to Field Label */
    casper.waitForSelector("input[name='label']", function success() {
        casper.echo('** Step 2-6: Add Advanced Multi-Select field. **');
        test.assertExists("input[name='label']");
        this.sendKeys("input[name='label']", 'advanced_multi_select' + vars.id);
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

    fill_options(test);

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
    casper.then(function() {
        test.assertDoesntExist('.crm-error');
    });

     
    /* 7. Autocomplete Select */
    /* sendKeys to Field Label */
    casper.waitForSelector("input[name='label']", function success() {
        casper.echo('** Step 2-7: Add Autocomplete Select field. **');
        test.assertExists("input[name='label']");
        this.sendKeys("input[name='label']", 'autocomplete_select' + vars.id);
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

    fill_options(test);

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
    casper.then(function() {
        test.assertDoesntExist('.crm-error');
    });

    /*
     * Add 14 different types of field
     */
    casper.then(function() {
        casper.echo('=====================================');
        casper.echo('** Step 3: Add Integer, Number, Money, Note, Date, Yes or No, State/Province, Country, File, Link, Contact Reference fields. **');
        casper.echo('=====================================');
    });

    /* 1. Integer */
    /* sendKeys to Field Label */
    casper.waitForSelector("input[name='label']", function success() {
        casper.echo('** Step 3-1: Add Integer field. **');
        test.assertExists("input[name='label']");
        this.sendKeys("input[name='label']", 'Integer' + vars.id);
    }, function fail() {
        test.assertExists("input[name='label']");
    });

    /* select Data and Input Field Type*/
    casper.waitForSelector("select[name='data_type[0]']", function success() {
        test.assertExists("select[name='data_type[0]']");
        this.evaluate(function () {
            document.querySelector("select[name='data_type[0]']").selectedIndex = 1;
            document.querySelector("select[name='data_type[0]']").onchange();
        });
    }, function fail() {
        test.assertExists("select[name='data_type[0]']");
    });

    casper.then(function() {
        // this.capture('Integer.png');
    });

    /* click submit */
    casper.waitForSelector("input[type=submit][value='Save and New']", function success() {
        test.assertExists("input[type=submit][value='Save and New']");
        this.click("input[type=submit][value='Save and New']");
    }, function fail() {
        test.assertExists("input[type=submit][value='Save and New']");
    }); /* submit form */
    casper.wait(2000);
    casper.then(function() {
        test.assertDoesntExist('.crm-error');
    });

    /* 2. Number */
    /* sendKeys to Field Label */
    casper.waitForSelector("input[name='label']", function success() {
        casper.echo('** Step 3-2: Add Number field. **');
        test.assertExists("input[name='label']");
        this.sendKeys("input[name='label']", 'Number' + vars.id);
    }, function fail() {
        test.assertExists("input[name='label']");
    });

    /* select Data and Input Field Type*/
    casper.waitForSelector("select[name='data_type[0]']", function success() {
        test.assertExists("select[name='data_type[0]']");
        this.evaluate(function () {
            document.querySelector("select[name='data_type[0]']").selectedIndex = 2;
            document.querySelector("select[name='data_type[0]']").onchange();
        });
    }, function fail() {
        test.assertExists("select[name='data_type[0]']");
    });

    casper.then(function() {
        // this.capture('Number.png');
    });

    /* click submit */
    casper.waitForSelector("input[type=submit][value='Save and New']", function success() {
        test.assertExists("input[type=submit][value='Save and New']");
        this.click("input[type=submit][value='Save and New']");
    }, function fail() {
        test.assertExists("input[type=submit][value='Save and New']");
    }); /* submit form */
    casper.wait(2000);
    casper.then(function() {
        test.assertDoesntExist('.crm-error');
    });

    /* 3. Money */
    /* sendKeys to Field Label */
    casper.waitForSelector("input[name='label']", function success() {
        casper.echo('** Step 3-3: Add Money field. **');
        test.assertExists("input[name='label']");
        this.sendKeys("input[name='label']", 'Money' + vars.id);
    }, function fail() {
        test.assertExists("input[name='label']");
    });

    /* select Data and Input Field Type*/
    casper.waitForSelector("select[name='data_type[0]']", function success() {
        test.assertExists("select[name='data_type[0]']");
        this.evaluate(function () {
            document.querySelector("select[name='data_type[0]']").selectedIndex = 3;
            document.querySelector("select[name='data_type[0]']").onchange();
        });
    }, function fail() {
        test.assertExists("select[name='data_type[0]']");
    });

    casper.then(function() {
        // this.capture('Money.png');
    });

    /* click submit */
    casper.waitForSelector("input[type=submit][value='Save and New']", function success() {
        test.assertExists("input[type=submit][value='Save and New']");
        this.click("input[type=submit][value='Save and New']");
    }, function fail() {
        test.assertExists("input[type=submit][value='Save and New']");
    }); /* submit form */
    casper.wait(2000);
    casper.then(function() {
        test.assertDoesntExist('.crm-error');
    });

    /* 4-1. Note TextArea */
    /* sendKeys to Field Label */
    casper.waitForSelector("input[name='label']", function success() {
        casper.echo('** Step 3-4-1: Add Note TextArea field. **');
        test.assertExists("input[name='label']");
        this.sendKeys("input[name='label']", 'Note_TextArea' + vars.id);
    }, function fail() {
        test.assertExists("input[name='label']");
    });

    /* select Data and Input Field Type*/
    casper.waitForSelector("select[name='data_type[0]']", function success() {
        test.assertExists("select[name='data_type[0]']");
        this.evaluate(function () {
            document.querySelector("select[name='data_type[0]']").selectedIndex = 4;
            document.querySelector("select[name='data_type[0]']").onchange();
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
        // this.capture('Note_TextArea.png');
    });

    /* click submit */
    casper.waitForSelector("input[type=submit][value='Save and New']", function success() {
        test.assertExists("input[type=submit][value='Save and New']");
        this.click("input[type=submit][value='Save and New']");
    }, function fail() {
        test.assertExists("input[type=submit][value='Save and New']");
    }); /* submit form */
    casper.wait(2000);
    casper.then(function() {
        test.assertDoesntExist('.crm-error');
    });

    /* 4-2. Note WYSIWYG Editor */
    /* sendKeys to Field Label */
    casper.waitForSelector("input[name='label']", function success() {
        casper.echo('** Step 3-4-2: Add Note WYSIWYG Editor field. **');
        test.assertExists("input[name='label']");
        this.sendKeys("input[name='label']", 'Note_WYSIWYG_Editor' + vars.id);
    }, function fail() {
        test.assertExists("input[name='label']");
    });

    /* select Data and Input Field Type*/
    casper.waitForSelector("select[name='data_type[0]']", function success() {
        test.assertExists("select[name='data_type[0]']");
        this.evaluate(function () {
            document.querySelector("select[name='data_type[0]']").selectedIndex = 4;
            document.querySelector("select[name='data_type[0]']").onchange();
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

    casper.then(function() {
        // this.capture('Note_WYSIWYG_Editor.png');
    });

    /* click submit */
    casper.waitForSelector("input[type=submit][value='Save and New']", function success() {
        test.assertExists("input[type=submit][value='Save and New']");
        this.click("input[type=submit][value='Save and New']");
    }, function fail() {
        test.assertExists("input[type=submit][value='Save and New']");
    }); /* submit form */
    casper.wait(2000);
    casper.then(function() {
        test.assertDoesntExist('.crm-error');
    });

    /* 5. Date */
    /* sendKeys to Field Label */
    casper.waitForSelector("input[name='label']", function success() {
        casper.echo('** Step 3-5: Add Date field. **');
        test.assertExists("input[name='label']");
        this.sendKeys("input[name='label']", 'Date' + vars.id);
    }, function fail() {
        test.assertExists("input[name='label']");
    });

    /* select Data and Input Field Type*/
    casper.waitForSelector("select[name='data_type[0]']", function success() {
        test.assertExists("select[name='data_type[0]']");
        this.evaluate(function () {
            document.querySelector("select[name='data_type[0]']").selectedIndex = 5;
            document.querySelector("select[name='data_type[0]']").onchange();
        });
    }, function fail() {
        test.assertExists("select[name='data_type[0]']");
    });

    /* click Data and Input Field Type to invoke onclick event */
    casper.waitForSelector("select[name='data_type[0]']", function success() {
        test.assertExists("select[name='data_type[0]']");
        this.click("select[name='data_type[0]']");
    }, function fail() {
        test.assertExists("select[name='data_type[0]']");
    });

    /* select Date Format */
    casper.waitForSelector("select[name='date_format']", function success() {
        test.assertExists("select[name='date_format']");
        this.evaluate(function () {
            document.querySelector("select[name='date_format']").selectedIndex = 1;
        });
    }, function fail() {
        test.assertExists("select[name='date_format']");
    });

    casper.then(function() {
        // this.capture('Date.png');
    });

    /* click submit */
    casper.waitForSelector("input[type=submit][value='Save and New']", function success() {
        test.assertExists("input[type=submit][value='Save and New']");
        this.click("input[type=submit][value='Save and New']");
    }, function fail() {
        test.assertExists("input[type=submit][value='Save and New']");
    }); /* submit form */
    casper.wait(2000);
    casper.then(function() {
        test.assertDoesntExist('.crm-error');
    });
        
    /* 6. Yes or No */
    /* sendKeys to Field Label */
    casper.waitForSelector("input[name='label']", function success() {
        casper.echo('** Step 3-6: Add Yes or No field. **');
        test.assertExists("input[name='label']");
        this.sendKeys("input[name='label']", 'Yes_or_No' + vars.id);
    }, function fail() {
        test.assertExists("input[name='label']");
    });

    /* select Data and Input Field Type*/
    casper.waitForSelector("select[name='data_type[0]']", function success() {
        test.assertExists("select[name='data_type[0]']");
        this.evaluate(function () {
            document.querySelector("select[name='data_type[0]']").selectedIndex = 6;
            document.querySelector("select[name='data_type[0]']").onchange();
        });
    }, function fail() {
        test.assertExists("select[name='data_type[0]']");
    });

    casper.then(function() {
        // this.capture('Yes_or_No.png');
    });

    /* click submit */
    casper.waitForSelector("input[type=submit][value='Save and New']", function success() {
        test.assertExists("input[type=submit][value='Save and New']");
        this.click("input[type=submit][value='Save and New']");
    }, function fail() {
        test.assertExists("input[type=submit][value='Save and New']");
    }); /* submit form */
    casper.wait(2000);

    casper.then(function() {
        test.assertDoesntExist('.crm-error');
    })

    /* 7-1. State/Province Select */
    /* sendKeys to Field Label */
    casper.waitForSelector("input[name='label']", function success() {
        casper.echo('** Step 3-7-1: Add State/Province Select field. **');
        test.assertExists("input[name='label']");
        this.sendKeys("input[name='label']", 'State_Province_Select' + vars.id);
    }, function fail() {
        test.assertExists("input[name='label']");
    });

    /* select Data and Input Field Type*/
    casper.waitForSelector("select[name='data_type[0]']", function success() {
        test.assertExists("select[name='data_type[0]']");
        this.evaluate(function () {
            document.querySelector("select[name='data_type[0]']").selectedIndex = 7;
            document.querySelector("select[name='data_type[0]']").onchange();
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
        // this.capture('State_Province_Select.png');
    });

    /* click submit */
    casper.waitForSelector("input[type=submit][value='Save and New']", function success() {
        test.assertExists("input[type=submit][value='Save and New']");
        this.click("input[type=submit][value='Save and New']");
    }, function fail() {
        test.assertExists("input[type=submit][value='Save and New']");
    }); /* submit form */
    casper.wait(2000);
    casper.then(function() {
        test.assertDoesntExist('.crm-error');
    });

    /* 7-2. State/Province Multi-Select */
    /* sendKeys to Field Label */
    casper.waitForSelector("input[name='label']", function success() {
        casper.echo('** Step 3-7-2: Add State/Province Multi Select field. **');
        test.assertExists("input[name='label']");
        this.sendKeys("input[name='label']", 'State_Province_Multi_Select' + vars.id);
    }, function fail() {
        test.assertExists("input[name='label']");
    });

    /* select Data and Input Field Type*/
    casper.waitForSelector("select[name='data_type[0]']", function success() {
        test.assertExists("select[name='data_type[0]']");
        this.evaluate(function () {
            document.querySelector("select[name='data_type[0]']").selectedIndex = 7;
            document.querySelector("select[name='data_type[0]']").onchange();
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

    casper.then(function() {
        // this.capture('State_Province_Multi_Select.png');
    });

    /* click submit */
    casper.waitForSelector("input[type=submit][value='Save and New']", function success() {
        test.assertExists("input[type=submit][value='Save and New']");
        this.click("input[type=submit][value='Save and New']");
    }, function fail() {
        test.assertExists("input[type=submit][value='Save and New']");
    }); /* submit form */
    casper.wait(2000);
    casper.then(function() {
        test.assertDoesntExist('.crm-error');
    });

    /* 8-1. Country Select */
    /* sendKeys to Field Label */
    casper.waitForSelector("input[name='label']", function success() {
        casper.echo('** Step 3-8-1: Add Country Select field. **');
        test.assertExists("input[name='label']");
        this.sendKeys("input[name='label']", 'Country_Select' + vars.id);
    }, function fail() {
        test.assertExists("input[name='label']");
    });

    /* select Data and Input Field Type*/
    casper.waitForSelector("select[name='data_type[0]']", function success() {
        test.assertExists("select[name='data_type[0]']");
        this.evaluate(function () {
            document.querySelector("select[name='data_type[0]']").selectedIndex = 8;
            document.querySelector("select[name='data_type[0]']").onchange();
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
        // this.capture('Country_Select.png');
    });

    /* click submit */
    casper.waitForSelector("input[type=submit][value='Save and New']", function success() {
        test.assertExists("input[type=submit][value='Save and New']");
        this.click("input[type=submit][value='Save and New']");
    }, function fail() {
        test.assertExists("input[type=submit][value='Save and New']");
    }); /* submit form */
    casper.wait(2000);
    casper.then(function() {
        test.assertDoesntExist('.crm-error');
    });

    /* 8-2. Country Multi Select */
    /* sendKeys to Field Label */
    casper.waitForSelector("input[name='label']", function success() {
        casper.echo('** Step 3-8-2: Add Country Multi Select field. **');
        test.assertExists("input[name='label']");
        this.sendKeys("input[name='label']", 'Country_Multi_Select' + vars.id);
    }, function fail() {
        test.assertExists("input[name='label']");
    });

    /* select Data and Input Field Type*/
    casper.waitForSelector("select[name='data_type[0]']", function success() {
        test.assertExists("select[name='data_type[0]']");
        this.evaluate(function () {
            document.querySelector("select[name='data_type[0]']").selectedIndex = 8;
            document.querySelector("select[name='data_type[0]']").onchange();
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

    casper.then(function() {
        // this.capture('Country_Multi_Select.png');
    });

    /* click submit */
    casper.waitForSelector("input[type=submit][value='Save and New']", function success() {
        test.assertExists("input[type=submit][value='Save and New']");
        this.click("input[type=submit][value='Save and New']");
    }, function fail() {
        test.assertExists("input[type=submit][value='Save and New']");
    }); /* submit form */
    casper.wait(2000);
    casper.then(function() {
        test.assertDoesntExist('.crm-error');
    });

    /* 9. File */
    /* sendKeys to Field Label */
    casper.waitForSelector("input[name='label']", function success() {
        casper.echo('** Step 3-9: Add File field. **');
        test.assertExists("input[name='label']");
        this.sendKeys("input[name='label']", 'File' + vars.id);
    }, function fail() {
        test.assertExists("input[name='label']");
    });

    /* select Data and Input Field Type*/
    casper.waitForSelector("select[name='data_type[0]']", function success() {
        test.assertExists("select[name='data_type[0]']");
        this.evaluate(function () {
            document.querySelector("select[name='data_type[0]']").selectedIndex = 9;
            document.querySelector("select[name='data_type[0]']").onchange();
        });
    }, function fail() {
        test.assertExists("select[name='data_type[0]']");
    });

    casper.then(function() {
        // this.capture('File.png');
    });

    /* click submit */
    casper.waitForSelector("input[type=submit][value='Save and New']", function success() {
        test.assertExists("input[type=submit][value='Save and New']");
        this.click("input[type=submit][value='Save and New']");
    }, function fail() {
        test.assertExists("input[type=submit][value='Save and New']");
    }); /* submit form */
    casper.wait(2000);
    casper.then(function() {
        test.assertDoesntExist('.crm-error');
    });
    
    /* 10. Link */
    /* sendKeys to Field Label */
    casper.waitForSelector("input[name='label']", function success() {
        casper.echo('** Step 3-10: Add Link field. **');
        test.assertExists("input[name='label']");
        this.sendKeys("input[name='label']", 'Link' + vars.id);
    }, function fail() {
        test.assertExists("input[name='label']");
    });

    /* select Data and Input Field Type*/
    casper.waitForSelector("select[name='data_type[0]']", function success() {
        test.assertExists("select[name='data_type[0]']");
        this.evaluate(function () {
            document.querySelector("select[name='data_type[0]']").selectedIndex = 10;
            document.querySelector("select[name='data_type[0]']").onchange();
        });
    }, function fail() {
        test.assertExists("select[name='data_type[0]']");
    });

    casper.then(function() {
        // this.capture('Link.png');
    });

    /* click submit */
    casper.waitForSelector("input[type=submit][value='Save and New']", function success() {
        test.assertExists("input[type=submit][value='Save and New']");
        this.click("input[type=submit][value='Save and New']");
    }, function fail() {
        test.assertExists("input[type=submit][value='Save and New']");
    }); /* submit form */
    casper.wait(2000);
    casper.then(function() {
        test.assertDoesntExist('.crm-error');
    });

    /* 11. Contact Reference */
    /* sendKeys to Field Label */
    casper.waitForSelector("input[name='label']", function success() {
        casper.echo('** Step 3-11: Add Contact Reference field. **');
        test.assertExists("input[name='label']");
        this.sendKeys("input[name='label']", 'Contact_Reference' + vars.id);
    }, function fail() {
        test.assertExists("input[name='label']");
    });

    /* select Data and Input Field Type*/
    casper.waitForSelector("select[name='data_type[0]']", function success() {
        test.assertExists("select[name='data_type[0]']");
        this.evaluate(function () {
            document.querySelector("select[name='data_type[0]']").selectedIndex = 11;
            document.querySelector("select[name='data_type[0]']").onchange();
        });
    }, function fail() {
        test.assertExists("select[name='data_type[0]']");
    });

    casper.then(function() {
        // this.capture('Contact_Reference.png');
    });

    /* click submit */
    casper.waitForSelector("input[type=submit][value='Save and New']", function success() {
        test.assertExists("input[type=submit][value='Save and New']");
        this.click("input[type=submit][value='Save and New']");
    }, function fail() {
        test.assertExists("input[type=submit][value='Save and New']");
    }); /* submit form */
    casper.wait(2000);
    casper.then(function() {
        test.assertDoesntExist('.crm-error');
    });

    
    /*
     * Add Set of Custom Fields
    */
    casper.then(function() {
        casper.echo('=====================================');
        casper.echo('** Step 4: Check Preview. **');
        casper.echo('=====================================');
    });

    /* open custom data page */
    casper.thenOpen(vars.baseURL + "civicrm/admin/custom/group?reset=1", function() {
        // this.capture('back_to_custom_data_page.png');
    });

    casper.wait(2000);

    /* go to Custom Fields page */
    casper.waitForSelector("#custom_group table", function success() {
        casper.echo('** Step 4-1: Get all expacted id. **');
        test.assertExists("#custom_group table");
        var id = this.evaluate(function () {
            return document.querySelector('#custom_group table').rows.length - 1;
        });
        this.open(vars.baseURL + "civicrm/admin/custom/group/field?reset=1&action=browse&gid=" + id);
    }, function fail() {
        test.assertExists("#custom_group table", "Custom data list exist.");
    });
    casper.wait(2000);
    casper.then(function() {
        // this.capture('Custom_Fields.png');
    });

    /* get all fields id */
    casper.then(function() {
        var urls = this.evaluate(function() {
            var all_links = document.querySelectorAll("a[title='Preview Custom Field']");
            var all_urls = [];
            for(var i = 0; i < all_links.length; i++) {
                all_urls.push(all_links[i].href);
            }
            return all_urls;
        });
        vars.ids = urls.map(function(url) {
            var splits = url.split('=');
            return splits[splits.length - 1];
        });
    });

    /* open custom data page */
    casper.thenOpen(vars.baseURL + "civicrm/admin/custom/group?reset=1", function() {
        // this.capture('back_to_custom_data_page.png');
    });

    casper.wait(2000);
    /* go to Custom Fields page */
    casper.waitForSelector("#custom_group table", function success() {
        test.assertExists("#custom_group table");
        var id = this.evaluate(function () {
            return document.querySelector('#custom_group table').rows.length - 1;
        });
        this.open(vars.baseURL + "civicrm/admin/custom/group?action=preview&reset=1&id=" + id);
    }, function fail() {
        test.assertExists("#custom_group table", "Custom data list exist.");
    });
    casper.wait(2000);
    casper.then(function() {
        // this.capture('Preview.png');
    });

    /* get all text input id */
    casper.then(function() {
        casper.echo('** Step 4-2: Get all text input id. **');
        var text_ids = this.evaluate(function() {
            var all_text = document.getElementById('Preview').querySelectorAll('input[type="text"]:not(.hiddenElement), input[type="file"]');
            var text_ids = [];
            for(var i = 0; i < all_text.length; i++){
                var sp = all_text[i].id.split('_');
                text_ids.push(sp[1]);
            }
            return text_ids;
        });
        text_ids.forEach(function(text_id) {
            vars.ids_for_check.push(text_id);
        });
    });

    /* get all select id */
    casper.then(function() {
        casper.echo('** Step 4-3: Get all select id. **');
        var select_ids = this.evaluate(function() {
            var all_select = document.getElementById('Preview').querySelectorAll('select');
            var select_ids = [];
            for(var i = 0; i < all_select.length; i++){
                var sp = all_select[i].id.split('_');
                select_ids.push(sp[1]);
            }
            return select_ids;
        });
        select_ids.forEach(function(select_id) {
            vars.ids_for_check.push(select_id);
        });
    });

    /* get all radio input id */
    casper.then(function() {
        casper.echo('** Step 4-4: Get all radio input id. **');
        var radio_ids = this.evaluate(function() {
            var all_radio = document.getElementById('Preview').querySelectorAll('input[type="radio"]');
            var radio_ids = [];
            for(var i = 0; i < all_radio.length; i++){
                var sp = all_radio[i].name.split('_');
                radio_ids.push(sp[1]);
            }
            return radio_ids;
        });
        radio_ids.forEach(function(radio_id) {
            vars.ids_for_check.push(radio_id);
        });
    });

    /* get all checkbox input id */
    casper.then(function() {
        casper.echo('** Step 4-5: Get all checkbox input id. **');
        var checkbox_ids = this.evaluate(function() {
            var all_checkbox = document.getElementById('Preview').querySelectorAll('input[type="checkbox"]');
            var checkbox_ids = [];
            for(var i = 0; i < all_checkbox.length; i++){
                var sp = all_checkbox[i].id.split('_');
                checkbox_ids.push(sp[1]);
            }
            return checkbox_ids;
        });
        checkbox_ids.forEach(function(checkbox_id) {
            vars.ids_for_check.push(checkbox_id);
        });
    });

    /* get all textarea id */
    casper.then(function() {
        casper.echo('** Step 4-6: Get all textarea id. **');
        var textarea_ids = this.evaluate(function() {
            var all_textarea = document.getElementById('Preview').querySelectorAll('textarea');
            var textarea_ids = [];
            for(var i = 0; i < all_textarea.length; i++){
                var sp = all_textarea[i].id.split('_');
                textarea_ids.push(sp[1]);
            }
            return textarea_ids;
        });
        textarea_ids.forEach(function(textarea_id) {
            vars.ids_for_check.push(textarea_id);
        });
    });

    /* check all id exist */
    casper.then(function() {
        casper.echo('** Step 4-7: Check all id exist. **');
        var id_no_duplicate = [];
        for(var i = 0; i < vars.ids_for_check.length; i++) {
            var exist_flag = false;
            for(var j = 0; j < id_no_duplicate.length; j++) {
                if(vars.ids_for_check[i] == id_no_duplicate[j]) {
                    exist_flag = true;
                    break
                }
            }
            if(!exist_flag) {
                id_no_duplicate.push(vars.ids_for_check[i]);
            }
        }
        id_no_duplicate.sort(function(a, b) {
            return a - b;
        });
        test.assertEquals(vars.ids, id_no_duplicate);
    });

    /*
     * Check Add Contact
    */
    casper.then(function() {
        casper.echo('=====================================');
        casper.echo('** Step 5: Check Add Contact. **');
        casper.echo('=====================================');
    });

    /* open custom data page */
    casper.thenOpen(vars.baseURL + "civicrm/admin/custom/group?reset=1", function() {
        // this.capture('back_to_custom_data_page.png');
    });
    casper.wait(2000);

    /* get custom data id */
    casper.waitForSelector("#custom_group table", function success() {
        casper.echo('** Step 5-1: Get custom data id. **');
        test.assertExists("#custom_group table");
        vars.custom_id = this.evaluate(function () {
            return document.querySelector('#custom_group table').rows.length - 1;
        });
    }, function fail() {
        test.assertExists("#custom_group table", "Custom data list exist.");
    });

    /* open new individual page */
    casper.thenOpen(vars.baseURL + "civicrm/contact/add?reset=1&ct=Individual", function() {
        // this.capture('add_individual.png');
    });
    casper.wait(2000);

    /* get all text input id */
    casper.then(function() {
        casper.echo('** Step 5-2: Get all text input id. **');
        var text_ids = this.evaluate(function(custom_id) {
            var all_text = document.getElementById('customData' + custom_id).querySelectorAll('input[type="text"]:not(.hiddenElement), input[type="file"]');
            var text_ids = [];
            for(var i = 0; i < all_text.length; i++){
                var sp = all_text[i].id.split('_');
                text_ids.push(sp[1]);
            }
            return text_ids;
        }, vars.custom_id);
        vars.ids_for_check = [];
        text_ids.forEach(function(text_id) {
            vars.ids_for_check.push(text_id);
        });
    });

    /* get all select id */
    casper.then(function() {
        casper.echo('** Step 5-3: Get all select id. **');
        var select_ids = this.evaluate(function(custom_id) {
            var all_select = document.getElementById('customData' + custom_id).querySelectorAll('select');
            var select_ids = [];
            for(var i = 0; i < all_select.length; i++){
                var sp = all_select[i].id.split('_');
                select_ids.push(sp[1]);
            }
            return select_ids;
        }, vars.custom_id);
        select_ids.forEach(function(select_id) {
            vars.ids_for_check.push(select_id);
        });
    });

    /* get all radio input id */
    casper.then(function() {
        casper.echo('** Step 5-4: Get all radio input id. **');
        var radio_ids = this.evaluate(function(custom_id) {
            var all_radio = document.getElementById('customData' + custom_id).querySelectorAll('input[type="radio"]');
            var radio_ids = [];
            for(var i = 0; i < all_radio.length; i++){
                var sp = all_radio[i].name.split('_');
                radio_ids.push(sp[1]);
            }
            return radio_ids;
        }, vars.custom_id);
        radio_ids.forEach(function(radio_id) {
            vars.ids_for_check.push(radio_id);
        });
    });

    /* get all checkbox input id */
    casper.then(function() {
        casper.echo('** Step 5-5: Get all checkbox input id. **');
        var checkbox_ids = this.evaluate(function(custom_id) {
            var all_checkbox = document.getElementById('customData' + custom_id).querySelectorAll('input[type="checkbox"]');
            var checkbox_ids = [];
            for(var i = 0; i < all_checkbox.length; i++){
                var sp = all_checkbox[i].id.split('_');
                checkbox_ids.push(sp[1]);
            }
            return checkbox_ids;
        }, vars.custom_id);
        checkbox_ids.forEach(function(checkbox_id) {
            vars.ids_for_check.push(checkbox_id);
        });
    });

    /* get all textarea id */
    casper.then(function() {
        casper.echo('** Step 5-6: Get all textarea id. **');
        var textarea_ids = this.evaluate(function(custom_id) {
            var all_textarea = document.getElementById('customData' + custom_id).querySelectorAll('textarea');
            var textarea_ids = [];
            for(var i = 0; i < all_textarea.length; i++){
                var sp = all_textarea[i].id.split('_');
                textarea_ids.push(sp[1]);
            }
            return textarea_ids;
        }, vars.custom_id);
        textarea_ids.forEach(function(textarea_id) {
            vars.ids_for_check.push(textarea_id);
        });
    });

    /* check all id exist */
    casper.then(function() {
        casper.echo('** Step 5-7: Check all id exist. **');
        var id_no_duplicate = [];
        for(var i = 0; i < vars.ids_for_check.length; i++) {
            var exist_flag = false;
            for(var j = 0; j < id_no_duplicate.length; j++) {
                if(vars.ids_for_check[i] == id_no_duplicate[j]) {
                    exist_flag = true;
                    break
                }
            }
            if(!exist_flag) {
                id_no_duplicate.push(vars.ids_for_check[i]);
            }
        }
        id_no_duplicate.sort(function(a, b) {
            return a - b;
        });
        test.assertEquals(vars.ids, id_no_duplicate);
    });

    /*
     * Input All Fields
    */
    casper.then(function() {
        casper.echo('=====================================');
        casper.echo('** Step 6: Input All Fields. **');
        casper.echo('=====================================');
    });

    /* filled up last name and first name */
    casper.waitForSelector("input[name='last_name']", function success() {
        casper.echo('** Step 6-1: Filled up last name and first name. **');
        this.sendKeys("input[name='last_name']", makeid(5));
    }, function fail() {
        test.assertExists("input[name='last_name']");
    });
    casper.waitForSelector("input[name='first_name']", function success() {
        this.sendKeys("input[name='first_name']", makeid(5));
    }, function fail() {
        test.assertExists("input[name='first_name']");
    });
    
    /* get all pure text input id */
    casper.then(function() {
        casper.echo('** Step 6-2: Get all pure text id. **');
        var text_ids = this.evaluate(function(custom_id) {
            var all_text = document.getElementById('customData' + custom_id).querySelectorAll('input[type="text"]:not(.hiddenElement):not(.dateplugin):not(.ac_input)');
            var text_ids = [];
            for(var i = 0; i < all_text.length; i++){
                var sp = all_text[i].id.split('_');
                text_ids.push(sp[1]);
            }
            return text_ids;
        }, vars.custom_id);
        text_ids.forEach(function(text_id) {
            vars.text_id_for_input.push(text_id);
        });
    });

    /* input all pure text */
    casper.then(function() {
        casper.echo('** Step 6-3: Input all pure text. **');
        vars.text_id_for_input.forEach(function(text_id) {
            casper.waitForSelector('input[name="custom_' + text_id + '_-1"]', function success() {
                var rand_int = Math.floor(Math.random() * 10000);
                this.sendKeys('input[name="custom_' + text_id + '_-1"]', rand_int.toString());
            }, function fail() {
                test.assertExists('input[name="custom_' + text_id + '_-1"]');
            });
        });
    });

    casper.then(function() {
        // this.capture('Filled_up_text.png');
    });


    /* get all select(not multi) id */
    var select_id_for_input = [];
    casper.then(function() {
        casper.echo('** Step 6-4: Get all select(not multi) id. **');
        var select_ids = this.evaluate(function(custom_id) {
            var all_select = document.getElementById('customData' + custom_id).querySelectorAll('select');
            var select_ids = [];
            for(var i = 0; i < all_select.length; i++){
                var sp = all_select[i].id.split('_');
                select_ids.push(sp[1]);
            }
            return select_ids;
        }, vars.custom_id);
        select_ids.forEach(function(select_id) {
            select_id_for_input.push(select_id);
        });
    });

    /* input all select(not multi) */
    casper.then(function() {
        casper.echo('** Step 6-5: Input all select(not multi). **');
        select_id_for_input.forEach(function(select_id) {
            casper.waitForSelector('#custom_' + select_id + '_-1', function success() {
                this.evaluate(function (select_id) {
                    if(document.getElementById('custom_' + select_id + '_-1').options.length >= 2){
                        document.getElementById('custom_' + select_id + '_-1').selectedIndex = 1;
                    } else {
                        document.getElementById('custom_' + select_id + '_-1').selectedIndex = 0;
                    }
                }, select_id);
            }, function fail() {
                test.assertExists('custom_' + select_id + '_-1');
            });
        });
    });

    casper.then(function() {
        // this.capture('Filled_up_select.png');
    });

    /* get all radio input id */
    casper.then(function() {
        casper.echo('** Step 6-6: Get all radio input id. **');
        var radio_ids = this.evaluate(function(custom_id) {
            var all_radio = document.getElementById('customData' + custom_id).querySelectorAll('input[type="radio"]');
            var radio_ids = [];
            for(var i = 0; i < all_radio.length; i++){
                var sp = all_radio[i].name.split('_');
                radio_ids.push(sp[1]);
            }
            return radio_ids;
        }, vars.custom_id);
        radio_ids.forEach(function(radio_id) {
            vars.radio_id_for_input.push(radio_id);
        });
    });

    /* input all radio */
    casper.then(function() {
        casper.echo('** Step 6-7: Input all radio. **');
        vars.radio_id_for_input.forEach(function(radio_id) {
            casper.waitForSelector('input[name="custom_' + radio_id + '_-1"]', function success() {
                this.evaluate(function (radio_id) {
                    document.querySelector('input[name="custom_' + radio_id + '_-1"]').checked = true;
                }, radio_id);
            }, function fail() {
                test.assertExists('input[name="custom_' + radio_id + '_-1"]');
            });
        });
    });

    /* input all checkbox */
    casper.then(function() {
        casper.echo('** Step 6-8: Input all checkbox. **');
        var checkbox_id = this.evaluate(function (custom_id) {
            return document.getElementById('customData' + custom_id).querySelectorAll('input[type="checkbox"]')[0].id;
        }, vars.custom_id);
        casper.waitForSelector('input[name="' + checkbox_id + '"]', function success() {
            this.evaluate(function (checkbox_id) {
                document.getElementById(checkbox_id).checked = true;
            }, checkbox_id);
        }, function fail() {
            test.assertExists('input[name="' + checkbox_id + '"]');
        });
    });

    casper.then(function() {
        // this.capture('Filled_up_checkbox.png');
    });

    /* input advanced multi select */
    casper.then(function () {
        casper.echo('** Step 6-9: Input advanced multi select. **');
        var adv_selector = '#customData' + vars.custom_id + ' table.advmultiselect select';
        casper.waitForSelector(adv_selector, function success() {
            this.evaluate(function(adv_selector) {
                document.querySelector(adv_selector).selectedIndex = 0;
            }, adv_selector);
        }, function fail() {
            test.assertExists(adv_selector);
        });
        
        var add_selector = '#customData' + vars.custom_id + ' table.advmultiselect input[value="Add >>"]';
        casper.waitForSelector(add_selector, function success() {
            this.click(add_selector);
        }, function fail() {
            test.assertExists(add_selector);
        });
    });

    casper.then(function() {
        // this.capture('Filled_up_adv_multi_select.png');
    });

    /* input textarea */
    casper.then(function() {
        casper.echo('** Step 6-10: Input textarea. **');
        var textarea_selector = '#customData' + vars.custom_id + ' textarea.form-textarea';
        casper.waitForSelector(textarea_selector, function success() {
            this.sendKeys(textarea_selector, makeid(5));
        }, function fail() {
            this.assertExists(textarea_selector);
        });
    });

    casper.then(function() {
        // this.capture('Filled_up_textarea.png');
    });

    /* input ckeditor */
    // refs #34197, ckeditor not support old phantomjs
    /*
    casper.then(function() {
        var cke_selector = '#customData' + vars.custom_id + ' iframe.cke_wysiwyg_frame';
        casper.waitForSelector(cke_selector, function success() {
            this.evaluate(function(cke_selector) {
                document.querySelector(cke_selector).contentWindow.document.querySelector("p").textContent = 'abc';
            }, cke_selector);
        }, function fail() {
            test.assertExists(cke_selector);
        });
    });

    casper.then(function() {
        // this.capture('Filled_up_ckeditor.png');
    });
    */

    casper.waitForSelector("#_qf_Contact_upload_view", function success() {
        casper.echo('** Step 6-11: Save data. **');
        test.assertExists("#_qf_Contact_upload_view");
        this.click("#_qf_Contact_upload_view");
    }, function fail() {
        test.assertExists("#_qf_Contact_upload_view");
    });

    casper.wait(2000);
    casper.then(function() {
        // this.capture('save_data.png');
    });
    casper.then(function() {
        test.assertDoesntExist('.crm-error');
    });

    /*
     * Check All Fields Exists in Edit Page
    */
    casper.then(function() {
        casper.echo('=====================================');
        casper.echo('** Step 7: Check All Fields Exists in Edit Page. **');
        casper.echo('=====================================');
    });

    casper.waitForSelector("a.edit", function success() {
        casper.echo('** Step 7-1: Go to edit page. **');
        test.assertExists("a.edit");
        this.click("a.edit");
    }, function fail() {
        test.assertExists("a.edit");
    });

    casper.wait(2000);

    /* get all text input id */
    casper.then(function() {
        casper.echo('** Step 7-2: Get all text input id. **');
        var text_ids = this.evaluate(function(custom_id) {
            var all_text = document.getElementById('customData' + custom_id).querySelectorAll('input[type="text"]:not(.hiddenElement), input[type="file"]');
            var text_ids = [];
            for(var i = 0; i < all_text.length; i++){
                var sp = all_text[i].id.split('_');
                text_ids.push(sp[1]);
            }
            return text_ids;
        }, vars.custom_id);
        vars.ids_for_check = [];
        text_ids.forEach(function(text_id) {
            vars.ids_for_check.push(text_id);
        });
    });

    /* get all select id */
    casper.then(function() {
        casper.echo('** Step 7-3: Get all select id. **');
        var select_ids = this.evaluate(function(custom_id) {
            var all_select = document.getElementById('customData' + custom_id).querySelectorAll('select');
            var select_ids = [];
            for(var i = 0; i < all_select.length; i++){
                var sp = all_select[i].id.split('_');
                select_ids.push(sp[1]);
            }
            return select_ids;
        }, vars.custom_id);
        select_ids.forEach(function(select_id) {
            vars.ids_for_check.push(select_id);
        });
    });

    /* get all radio input id */
    casper.then(function() {
        casper.echo('** Step 7-4: Get all radio input id. **');
        var radio_ids = this.evaluate(function(custom_id) {
            var all_radio = document.getElementById('customData' + custom_id).querySelectorAll('input[type="radio"]');
            var radio_ids = [];
            for(var i = 0; i < all_radio.length; i++){
                var sp = all_radio[i].name.split('_');
                radio_ids.push(sp[1]);
            }
            return radio_ids;
        }, vars.custom_id);
        radio_ids.forEach(function(radio_id) {
            vars.ids_for_check.push(radio_id);
        });
    });

    /* get all checkbox input id */
    casper.then(function() {
        casper.echo('** Step 7-5: Get all checkbox input id. **');
        var checkbox_ids = this.evaluate(function(custom_id) {
            var all_checkbox = document.getElementById('customData' + custom_id).querySelectorAll('input[type="checkbox"]');
            var checkbox_ids = [];
            for(var i = 0; i < all_checkbox.length; i++){
                var sp = all_checkbox[i].id.split('_');
                checkbox_ids.push(sp[1]);
            }
            return checkbox_ids;
        }, vars.custom_id);
        checkbox_ids.forEach(function(checkbox_id) {
            vars.ids_for_check.push(checkbox_id);
        });
    });

    /* get all textarea id */
    casper.then(function() {
        casper.echo('** Step 7-6: Get all textarea id. **');
        var textarea_ids = this.evaluate(function(custom_id) {
            var all_textarea = document.getElementById('customData' + custom_id).querySelectorAll('textarea');
            var textarea_ids = [];
            for(var i = 0; i < all_textarea.length; i++){
                var sp = all_textarea[i].id.split('_');
                textarea_ids.push(sp[1]);
            }
            return textarea_ids;
        }, vars.custom_id);
        textarea_ids.forEach(function(textarea_id) {
            vars.ids_for_check.push(textarea_id);
        });
    });

    /* check all id exist */
    casper.then(function() {
        casper.echo('** Step 7-7: Check all id exist. **');
        var id_no_duplicate = [];
        for(var i = 0; i < vars.ids_for_check.length; i++) {
            var exist_flag = false;
            for(var j = 0; j < id_no_duplicate.length; j++) {
                if(vars.ids_for_check[i] == id_no_duplicate[j]) {
                    exist_flag = true;
                    break
                }
            }
            if(!exist_flag) {
                id_no_duplicate.push(vars.ids_for_check[i]);
            }
        }
        id_no_duplicate.sort(function(a, b) {
            return a - b;
        });
        test.assertEquals(vars.ids, id_no_duplicate);
    });

    casper.waitForSelector("#_qf_Contact_upload_view", function success() {
        casper.echo('** Step 7-8: Save data. **');
        test.assertExists("#_qf_Contact_upload_view");
        this.click("#_qf_Contact_upload_view");
    }, function fail() {
        test.assertExists("#_qf_Contact_upload_view");
    });

    casper.wait(2000);
    casper.then(function() {
        test.assertDoesntExist('.crm-error');
    });

    casper.run(function() {
        test.done();
    });
});
