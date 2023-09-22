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
    organization_name: makeid(5)
};

function list_contacts_and_select_three(test) {
    /* find contacts */
    casper.thenOpen(vars.baseURL + "civicrm/contact/search?reset=1", function() {
        // his.capture('find_contacts.png');
    });
    casper.waitForSelector('#contact_type', function success() {
        test.assertExists('#contact_type');
        this.evaluate(function () {
            document.querySelector("#contact_type").selectedIndex = 1;
        });
    }, function fail() {
        test.assertExists('#contact_type');
    });
    casper.then(function() {
        // this.capture('click_individual.png');
    });
    casper.waitForSelector('#_qf_Basic_refresh', function success() {
        test.assertExists('#_qf_Basic_refresh');
        this.click('#_qf_Basic_refresh');
    }, function fail() {
        test.assertExists('#_qf_Basic_refresh"]');
    });

    /* all contacts */
    casper.wait(2000);
    casper.then(function() {
        // this.capture('all_contacts.png');
    });
    
    /* check top 3 checkbox */
    casper.waitForSelector('table.selector tr:nth-child(1) td:nth-child(1) input', function success() {
        test.assertExists('table.selector tr:nth-child(1) td:nth-child(1) input');
        this.click('table.selector tr:nth-child(1) td:nth-child(1) input');
    }, function fail() {
        test.assertExists('table.selector tr:nth-child(1) td:nth-child(1) input');
    });
    casper.waitForSelector('table.selector tr:nth-child(2) td:nth-child(1) input', function success() {
        test.assertExists('table.selector tr:nth-child(2) td:nth-child(1) input');
        this.click('table.selector tr:nth-child(2) td:nth-child(1) input');
    }, function fail() {
        test.assertExists('table.selector tr:nth-child(2) td:nth-child(1) input');
    });
    casper.waitForSelector('table.selector tr:nth-child(3) td:nth-child(1) input', function success() {
        test.assertExists('table.selector tr:nth-child(3) td:nth-child(1) input');
        this.click('table.selector tr:nth-child(3) td:nth-child(1) input');
    }, function fail() {
        test.assertExists('table.selector tr:nth-child(3) td:nth-child(1) input');
    });
    casper.then(function() {
        // this.capture('check_3.png');
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
    
    
    /* 
     * Add to organization
     */
casper.test.begin('Start testing...', function(test){
    /* add organization */
    casper.thenOpen(vars.baseURL + "civicrm/contact/add?reset=1&ct=Organization", function() {
        casper.echo('=====================================');
        casper.echo('** Step 1: Add to Organization. **');
        casper.echo('=====================================');
        // this.capture('add_organization.png');
    });
    casper.waitForSelector("form[name=Contact] input[name='organization_name']", function success() {
        test.assertExists("form[name=Contact] input[name='organization_name']");
        this.click("form[name=Contact] input[name='organization_name']");
    }, function fail() {
        test.assertExists("form[name=Contact] input[name='organization_name']");
    });
    casper.waitForSelector("input[name='organization_name']", function success() {
        this.sendKeys("input[name='organization_name']", vars.organization_name);
    }, function fail() {
        test.assertExists("input[name='organization_name']");
    });
    casper.then(function() {
        // this.capture('form_write_done.png');
    });
    casper.waitForSelector("form[name=Contact] input[type=submit][value='Save']", function success() {
        test.assertExists("form[name=Contact] input[type=submit][value='Save']");
        this.click("form[name=Contact] input[type=submit][value='Save']");
    }, function fail() {
        test.assertExists("form[name=Contact] input[type=submit][value='Save']");
    }); /* submit form */

    /* organization page */
    casper.wait(2000);
    casper.then(function() {
        // this.capture('organization_info.png');
    })
    casper.then(function() {
        test.assertTitle(vars.organization_name + ' | netiCRM');
    });

    list_contacts_and_select_three(test);

    /* select contact to 組織 */
    casper.waitForSelector("#task", function success() {
        test.assertExists("#task");
        this.evaluate(function () {
            document.querySelector("#task").selectedIndex = 6;
        });
    }, function fail() {
        test.assertExists("#task");
    });
    casper.then(function() {
        // this.capture('select_add_to_organization.png');
    });
    casper.waitForSelector("form[name=Basic] input[type=submit][value='Go']", function success() {
        test.assertExists("form[name=Basic] input[type=submit][value='Go']");
        this.click("form[name=Basic] input[type=submit][value='Go']");
    }, function fail() {
        test.assertExists("form[name=Basic] input[type=submit][value='Go']");
    }); /* submit form */
    casper.wait(2000);
    casper.then(function() {
        // this.capture('add_to_organization.png');
    });

    /* filled up add to organization form */
    casper.waitForSelector("#relationship_type_id", function success() {
        test.assertExists("#relationship_type_id");
        this.evaluate(function () {
            document.querySelector("#relationship_type_id").selectedIndex = 2;
        });
    }, function fail() {
        test.assertExists("#relationship_type_id");
    });
    casper.waitForSelector("#name", function() {
        test.assertExists("#name");
        this.sendKeys("#name", vars.organization_name);
    }, function fail() {
        test.assertExists("#name");
    });
    casper.waitForSelector("form[name=AddToOrganization] input[type=submit][value='Search']", function success() {
        test.assertExists("form[name=AddToOrganization] input[type=submit][value='Search']");
        this.click("form[name=AddToOrganization] input[type=submit][value='Search']");
    }, function fail() {
        test.assertExists("form[name=AddToOrganization] input[type=submit][value='Search']");
    }); /* submit form */
    casper.wait(2000);
    casper.then(function() {
        // this.capture("found_org.png");
    });
    
    /* click add to organization */
    casper.waitForSelector("form[name=AddToOrganization] input[type=submit][value='Add to Organization']", function success() {
        test.assertExists("form[name=AddToOrganization] input[type=submit][value='Add to Organization']");
        this.click("form[name=AddToOrganization] input[type=submit][value='Add to Organization']");
    }, function fail() {
        test.assertExists("form[name=AddToOrganization] input[type=submit][value='Add to Organization']");
    }); /* submit form */
    casper.wait(2000);
    casper.then(function() {
        // this.capture("add_to_org_success.png");
    });

    /* 
     * Record Activity 
     */

    casper.then(function() {
        casper.echo('=====================================');
        casper.echo('** Step 2: Record Activity. **');
        casper.echo('=====================================');
    });

    list_contacts_and_select_three(test);

    /* select Record Activity for Contacts */
    casper.waitForSelector("#task", function success() {
        test.assertExists("#task");
        this.evaluate(function () {
            document.querySelector("#task").selectedIndex = 7;
        });
    }, function fail() {
        test.assertExists("#task");
    });
    casper.then(function() {
        // this.capture('select_record_activity.png');
    });
    casper.waitForSelector("form[name=Basic] input[type=submit][value='Go']", function success() {
        test.assertExists("form[name=Basic] input[type=submit][value='Go']");
        this.click("form[name=Basic] input[type=submit][value='Go']");
    }, function fail() {
        test.assertExists("form[name=Basic] input[type=submit][value='Go']");
    }); /* submit form */
    casper.wait(2000);
    casper.then(function() {
        // this.capture('record_activity_form.png');
    });

    /* select Activity Type */
    casper.waitForSelector("#activity_type_id", function success() {
        test.assertExists("#activity_type_id");
        this.evaluate(function () {
            document.querySelector("#activity_type_id").selectedIndex = 1;
        });
    }, function fail() {
        test.assertExists("#activity_type_id");
    });

    /* click save */
    casper.waitForSelector("form[name=Activity] input[type=submit][value='Save']", function success() {
        test.assertExists("form[name=Activity] input[type=submit][value='Save']");
        this.click("form[name=Activity] input[type=submit][value='Save']");
    }, function fail() {
        test.assertExists("form[name=Activity] input[type=submit][value='Save']");
    }); /* submit form */
    casper.wait(2000);
    casper.then(function() {
        // this.capture("record_activity_success.png");
    });

    /*
     * Batch Profile Update for Contact
     */
    
    casper.then(function() {
        casper.echo('=====================================');
        casper.echo('** Step 3: Batch Profile Update for Contact. **');
        casper.echo('=====================================');
    });

    list_contacts_and_select_three(test);

    /* select Batch Profile Update for Contact */
    casper.waitForSelector("#task", function success() {
        test.assertExists("#task");
        this.evaluate(function () {
            document.querySelector("#task").selectedIndex = 8;
        });
    }, function fail() {
        test.assertExists("#task");
    });
    casper.waitForSelector("form[name=Basic] input[type=submit][value='Go']", function success() {
        test.assertExists("form[name=Basic] input[type=submit][value='Go']");
        this.click("form[name=Basic] input[type=submit][value='Go']");
    }, function fail() {
        test.assertExists("form[name=Basic] input[type=submit][value='Go']");
    }); /* submit form */
    casper.wait(2000);

    /* Select Profile */
    casper.waitForSelector("#uf_group_id", function success() {
        test.assertExists("#uf_group_id");
        this.evaluate(function () {
            document.querySelector("#uf_group_id").selectedIndex = 3;
        });
    }, function fail() {
        test.assertExists("#uf_group_id");
    });
    casper.waitForSelector("form[name=PickProfile] input[type=submit][value='Continue >>']", function success() {
        test.assertExists("form[name=PickProfile] input[type=submit][value='Continue >>']");
        this.click("form[name=PickProfile] input[type=submit][value='Continue >>']");
    }, function fail() {
        test.assertExists("form[name=PickProfile] input[type=submit][value='Continue >>']");
    }); /* submit form */
    casper.wait(2000);
    casper.then(function() {
        // this.capture('batch_update.png');
    });

    /* user1 */
    casper.waitForSelector("tr:nth-child(1) td:nth-child(2) input", function success() {
        test.assertExists("tr:nth-child(1) td:nth-child(2) input");
        this.sendKeys("tr:nth-child(1) td:nth-child(2) input", makeid(3));
    }, function fail() {
        test.assertExists("tr:nth-child(1) td:nth-child(2) input");
    });
    casper.waitForSelector("tr:nth-child(1) td:nth-child(3) input", function success() {
        test.assertExists("tr:nth-child(1) td:nth-child(3) input");
        this.sendKeys("tr:nth-child(1) td:nth-child(3) input", makeid(3));
    }, function fail() {
        test.assertExists("tr:nth-child(1) td:nth-child(3) input");
    });

    /* user2 */
    casper.waitForSelector("tr:nth-child(2) td:nth-child(2) input", function success() {
        test.assertExists("tr:nth-child(2) td:nth-child(2) input");
        this.sendKeys("tr:nth-child(2) td:nth-child(2) input", makeid(3));
    }, function fail() {
        test.assertExists("tr:nth-child(2) td:nth-child(2) input");
    });
    casper.waitForSelector("tr:nth-child(2) td:nth-child(3) input", function success() {
        test.assertExists("tr:nth-child(2) td:nth-child(3) input");
        this.sendKeys("tr:nth-child(2) td:nth-child(3) input", makeid(3));
    }, function fail() {
        test.assertExists("tr:nth-child(2) td:nth-child(3) input");
    });

    /* user3 */
    casper.waitForSelector("tr:nth-child(3) td:nth-child(2) input", function success() {
        test.assertExists("tr:nth-child(3) td:nth-child(2) input");
        this.sendKeys("tr:nth-child(3) td:nth-child(2) input", makeid(3));
    }, function fail() {
        test.assertExists("tr:nth-child(3) td:nth-child(2) input");
    });
    casper.waitForSelector("tr:nth-child(3) td:nth-child(3) input", function success() {
        test.assertExists("tr:nth-child(3) td:nth-child(3) input");
        this.sendKeys("tr:nth-child(3) td:nth-child(3) input", makeid(3));
    }, function fail() {
        test.assertExists("tr:nth-child(3) td:nth-child(3) input");
    });

    casper.then(function() {
        // this.capture('batch_form_done.png');
    });
    casper.waitForSelector("form[name=Batch] input[type=submit][value='Update Contacts']", function success() {
        test.assertExists("form[name=Batch] input[type=submit][value='Update Contacts']");
        this.click("form[name=Batch] input[type=submit][value='Update Contacts']");
    }, function fail() {
        test.assertExists("form[name=Batch] input[type=submit][value='Update Contacts']");
    }); /* submit form */
    casper.wait(2000);
    casper.then(function() {
        // this.capture('batch_update_success.png');
    });

    /*
     * Export Contacts
     */

    casper.then(function() {
        casper.echo('=====================================');
        casper.echo('** Step 4: Export Contacts. **');
        casper.echo('=====================================');
    });

    list_contacts_and_select_three(test);

    /* select Export Contacts */
    casper.waitForSelector("#task", function success() {
        test.assertExists("#task");
        this.evaluate(function () {
            document.querySelector("#task").selectedIndex = 9;
        });
    }, function fail() {
        test.assertExists("#task");
    });
    casper.waitForSelector("form[name=Basic] input[type=submit][value='Go']", function success() {
        test.assertExists("form[name=Basic] input[type=submit][value='Go']");
        this.click("form[name=Basic] input[type=submit][value='Go']");
    }, function fail() {
        test.assertExists("form[name=Basic] input[type=submit][value='Go']");
    }); /* submit form */
    casper.wait(2000);

    /* click continue >> */
    casper.waitForSelector("form[name=Select] input[type=submit][value='Continue >>']", function success() {
        test.assertExists("form[name=Select] input[type=submit][value='Continue >>']");
        this.click("form[name=Select] input[type=submit][value='Continue >>']");
    }, function fail() {
        test.assertExists("form[name=Select] input[type=submit][value='Continue >>']");
    }); /* submit form */
    casper.wait(2000);
    casper.then(function() {
        // this.capture('continue.png');
    });
    /* select record type */
    casper.waitForSelector("form[name=Map] tr:nth-child(2) select", function success() {
        test.assertExists("form[name=Map] tr:nth-child(2) select");
        this.evaluate(function () {
            document.querySelector("form[name=Map] tr:nth-child(2) select").selectedIndex = 1;
        });
    }, function fail() {
        test.assertExists("form[name=Map] tr:nth-child(2) select");
    });

    /* click Export >> */
    casper.waitForSelector("form[name=Map] input[type=submit][value='Done']", function success() {
        test.assertExists("form[name=Map] input[type=submit][value='Done']");
        this.click("form[name=Map] input[type=submit][value='Done']");
    }, function fail() {
        test.assertExists("form[name=Map] input[type=submit][value='Done']");
    }); /* submit form */
    casper.wait(2000);
    casper.then(function() {
        // this.capture('export_done.png');
    });

    /*
     * Merge Contacts - 1 Merge
     */
    
    /* find contacts */
    casper.thenOpen(vars.baseURL + "civicrm/contact/search?reset=1", function() {
        casper.echo('=====================================');
        casper.echo('** Step 5-1: Merge Contacts - Merge. **');
        casper.echo('=====================================');
        // this.capture('find_contacts.png');
    });
    casper.waitForSelector('#contact_type', function success() {
        test.assertExists('#contact_type');
        this.evaluate(function () {
            document.querySelector("#contact_type").selectedIndex = 1;
        });
    }, function fail() {
        test.assertExists('#contact_type');
    });
    casper.then(function() {
        // this.capture('click_individual.png');
    });
    casper.waitForSelector('#_qf_Basic_refresh', function success() {
        test.assertExists('#_qf_Basic_refresh');
        this.click('#_qf_Basic_refresh');
    }, function fail() {
        test.assertExists('#_qf_Basic_refresh"]');
    });

    /* all contacts */
    casper.wait(2000);
    casper.then(function() {
        // this.capture('all_contacts.png');
    });
    
    /* check top 2 checkbox */
    casper.waitForSelector('table.selector tr:nth-child(1) td:nth-child(1) input', function success() {
        test.assertExists('table.selector tr:nth-child(1) td:nth-child(1) input');
        this.click('table.selector tr:nth-child(1) td:nth-child(1) input');
    }, function fail() {
        test.assertExists('table.selector tr:nth-child(1) td:nth-child(1) input');
    });
    casper.waitForSelector('table.selector tr:nth-child(2) td:nth-child(1) input', function success() {
        test.assertExists('table.selector tr:nth-child(2) td:nth-child(1) input');
        this.click('table.selector tr:nth-child(2) td:nth-child(1) input');
    }, function fail() {
        test.assertExists('table.selector tr:nth-child(2) td:nth-child(1) input');
    });

    /* select Merge Contacts */
    casper.waitForSelector("#task", function success() {
        test.assertExists("#task");
        this.evaluate(function () {
            document.querySelector("#task").selectedIndex = 10;
        });
    }, function fail() {
        test.assertExists("#task");
    });
    casper.waitForSelector("form[name=Basic] input[type=submit][value='Go']", function success() {
        test.assertExists("form[name=Basic] input[type=submit][value='Go']");
        this.click("form[name=Basic] input[type=submit][value='Go']");
    }, function fail() {
        test.assertExists("form[name=Basic] input[type=submit][value='Go']");
    }); /* submit form */
    casper.wait(2000);
    casper.then(function() {
        // this.capture('merge_contacts.png');
    });

    /* click Merge */
    casper.waitForSelector("form[name=Merge] input[type=submit][value='Merge']", function success() {
        test.assertExists("form[name=Merge] input[type=submit][value='Merge']");
        this.click("form[name=Merge] input[type=submit][value='Merge']");
    }, function fail() {
        test.assertExists("form[name=Merge] input[type=submit][value='Merge']");
    }); /* submit form */
    casper.wait(2000);
    casper.then(function() {
        test.assertDoesntExist('.crm-error');
    });
    

    /*
     * Merge Contacts - 2 Mark this pair as not a duplicate
     */

    /* find contacts */
    casper.thenOpen(vars.baseURL + "civicrm/contact/search?reset=1", function() {
        casper.echo('=====================================');
        casper.echo('** Step 5-2: Merge Contacts - Mark this pair as not a duplicate. **');
        casper.echo('=====================================');
        // this.capture('find_contacts.png');
    });
    casper.waitForSelector('#contact_type', function success() {
        test.assertExists('#contact_type');
        this.evaluate(function () {
            document.querySelector("#contact_type").selectedIndex = 1;
        });
    }, function fail() {
        test.assertExists('#contact_type');
    });
    casper.then(function() {
        // this.capture('click_individual.png');
    });
    casper.waitForSelector('#_qf_Basic_refresh', function success() {
        test.assertExists('#_qf_Basic_refresh');
        this.click('#_qf_Basic_refresh');
    }, function fail() {
        test.assertExists('#_qf_Basic_refresh"]');
    });

    /* all contacts */
    casper.wait(2000);
    casper.then(function() {
        // this.capture('all_contacts.png');
    });
    
    /* check top 2 checkbox */
    casper.waitForSelector('table.selector tr:nth-child(1) td:nth-child(1) input', function success() {
        test.assertExists('table.selector tr:nth-child(1) td:nth-child(1) input');
        this.click('table.selector tr:nth-child(1) td:nth-child(1) input');
    }, function fail() {
        test.assertExists('table.selector tr:nth-child(1) td:nth-child(1) input');
    });
    casper.waitForSelector('table.selector tr:nth-child(2) td:nth-child(1) input', function success() {
        test.assertExists('table.selector tr:nth-child(2) td:nth-child(1) input');
        this.click('table.selector tr:nth-child(2) td:nth-child(1) input');
    }, function fail() {
        test.assertExists('table.selector tr:nth-child(2) td:nth-child(1) input');
    });

    /* select Merge Contacts */
    casper.waitForSelector("#task", function success() {
        test.assertExists("#task");
        this.evaluate(function () {
            document.querySelector("#task").selectedIndex = 10;
        });
    }, function fail() {
        test.assertExists("#task");
    });
    casper.waitForSelector("form[name=Basic] input[type=submit][value='Go']", function success() {
        test.assertExists("form[name=Basic] input[type=submit][value='Go']");
        this.click("form[name=Basic] input[type=submit][value='Go']");
    }, function fail() {
        test.assertExists("form[name=Basic] input[type=submit][value='Go']");
    }); /* submit form */
    casper.wait(2000);

    /* click Mark this pair as not a duplicate. */
    casper.waitForSelector('#notDuplicate', function success() {
        test.assertExists('#notDuplicate');
        this.click('#notDuplicate');
    }, function fail() {
        test.assertExists('#notDuplicate');
    });

    /* click something */
    casper.waitForSelector('div.ui-dialog-buttonset button:nth-child(2)', function success() {
        test.assertExists('div.ui-dialog-buttonset button:nth-child(2)');
        this.click('div.ui-dialog-buttonset button:nth-child(2)');
    }, function fail() {
        test.assertExists('div.ui-dialog-buttonset button:nth-child(2)');
    });
    casper.wait(2000);
    casper.then(function() {
        test.assertDoesntExist('.crm-error');
    });

    /*
     * Tag Contacts 
     */
    
    casper.then(function() {
        casper.echo('=====================================');
        casper.echo('** Step 6: Tag Contacts. **');
        casper.echo('=====================================');
    });
    
    list_contacts_and_select_three(test);
    
    /* select Tag Contacts */
    casper.waitForSelector("#task", function success() {
        test.assertExists("#task");
        this.evaluate(function () {
            document.querySelector("#task").selectedIndex = 11;
        });
    }, function fail() {
        test.assertExists("#task");
    });
    casper.waitForSelector("form[name=Basic] input[type=submit][value='Go']", function success() {
        test.assertExists("form[name=Basic] input[type=submit][value='Go']");
        this.click("form[name=Basic] input[type=submit][value='Go']");
    }, function fail() {
        test.assertExists("form[name=Basic] input[type=submit][value='Go']");
    }); /* submit form */
    casper.wait(2000);

    /* click something */
    casper.waitForSelector('tr.crm-contact-task-addtotag-form-block-tag div.listing-box div:first-child input', function success() {
        test.assertExists('tr.crm-contact-task-addtotag-form-block-tag div.listing-box div:first-child input');
        this.click('tr.crm-contact-task-addtotag-form-block-tag div.listing-box div:first-child input');
    }, function fail() {
        test.assertExists('tr.crm-contact-task-addtotag-form-block-tag div.listing-box div:first-child input');
    });
    casper.then(function() {
        // this.capture('tag_contacts.png');
    });

    /* click submit */
    casper.waitForSelector("form#AddToTag input[type=submit][value='Tag Contacts']", function success() {
        test.assertExists("form#AddToTag input[type=submit][value='Tag Contacts']");
        this.click("form#AddToTag input[type=submit][value='Tag Contacts']");
    }, function fail() {
        test.assertExists("form#AddToTag input[type=submit][value='Tag Contacts']");
    }); /* submit form */
    casper.wait(2000);
    casper.then(function() {
        test.assertDoesntExist('.crm-error');
    });
    casper.then(function() {
        // this.capture('tag_success.png');
    });
    
    /*
     * Add Contacts to Group - 1 Add Contact To Existing Group
     */

    casper.then(function() {
        casper.echo('=====================================');
        casper.echo('** Step 7-1: Add Contacts to Group - Add Contact To Existing Group. **');
        casper.echo('=====================================');
    });

    list_contacts_and_select_three(test);

    /* select Add Contacts to Group */
    casper.waitForSelector("#task", function success() {
        test.assertExists("#task");
        this.evaluate(function () {
            document.querySelector("#task").selectedIndex = 2;
        });
    }, function fail() {
        test.assertExists("#task");
    });
    casper.waitForSelector("form[name=Basic] input[type=submit][value='Go']", function success() {
        test.assertExists("form[name=Basic] input[type=submit][value='Go']");
        this.click("form[name=Basic] input[type=submit][value='Go']");
    }, function fail() {
        test.assertExists("form[name=Basic] input[type=submit][value='Go']");
    }); /* submit form */
    casper.wait(2000);

    /* select dropdown */
    casper.waitForSelector("#group_id", function success() {
        test.assertExists("#group_id");
        this.evaluate(function () {
            document.querySelector("#group_id").selectedIndex = 1;
        });
    }, function fail() {
        test.assertExists("#group_id");
    });
    casper.then(function() {
        // this.capture('select_group.png');
    });
    
    /* click submit */
    casper.waitForSelector("form#AddToGroup input[type=submit][value='Confirm']", function success() {
        test.assertExists("form#AddToGroup input[type=submit][value='Confirm']");
        this.click("form#AddToGroup input[type=submit][value='Confirm']");
    }, function fail() {
        test.assertExists("form#AddToGroup input[type=submit][value='Confirm']");
    }); /* submit form */
    casper.wait(2000);
    casper.then(function() {
        test.assertDoesntExist('.crm-error');
    });
    casper.then(function() {
        // this.capture('add_to_group_success.png');
    });

    /*
     * Add Contacts to Group - 2 Create New Group
     */

    casper.then(function() {
        casper.echo('=====================================');
        casper.echo('** Step 7-2: Add Contacts to Group - Create New Group. **');
        casper.echo('=====================================');
    });

    list_contacts_and_select_three(test);

    /* select Add Contacts to Group */
    casper.waitForSelector("#task", function success() {
        test.assertExists("#task");
        this.evaluate(function () {
            document.querySelector("#task").selectedIndex = 2;
        });
    }, function fail() {
        test.assertExists("#task");
    });
    casper.waitForSelector("form[name=Basic] input[type=submit][value='Go']", function success() {
        test.assertExists("form[name=Basic] input[type=submit][value='Go']");
        this.click("form[name=Basic] input[type=submit][value='Go']");
    }, function fail() {
        test.assertExists("form[name=Basic] input[type=submit][value='Go']");
    }); /* submit form */
    casper.wait(2000);

    /* click Create New Group */
    casper.waitForSelector('#CIVICRM_QFID_1_4', function success() {
        test.assertExists('#CIVICRM_QFID_1_4');
        this.click('#CIVICRM_QFID_1_4');
    }, function fail() {
        test.assertExists('#CIVICRM_QFID_1_4');
    });

    /* sendKeys to Group Name */
    casper.waitForSelector("#title", function success() {
        test.assertExists("#title");
        this.sendKeys("#title", makeid(5));
    }, function fail() {
        test.assertExists("#title");
    });
    casper.then(function() {
        // this.capture('create_new_group.png');
    });

    /* click Confirm */
    casper.waitForSelector("form#AddToGroup input[type=submit][value='Confirm']", function success() {
        test.assertExists("form#AddToGroup input[type=submit][value='Confirm']");
        this.click("form#AddToGroup input[type=submit][value='Confirm']");
    }, function fail() {
        test.assertExists("form#AddToGroup input[type=submit][value='Confirm']");
    }); /* submit form */
    casper.wait(2000);
    casper.then(function() {
        test.assertDoesntExist('.crm-error');
    });
    casper.then(function() {
        // this.capture('create_new_group_success.png');
    });

    /*
     * New Smart Group
     */

    casper.then(function() {
        casper.echo('=====================================');
        casper.echo('** Step 8: New Smart Group. **');
        casper.echo('=====================================');
    });

    list_contacts_and_select_three(test);

    /* click All records */
    casper.waitForSelector('#CIVICRM_QFID_ts_all_4', function success() {
        test.assertExists('#CIVICRM_QFID_ts_all_4');
        this.click('#CIVICRM_QFID_ts_all_4');
    }, function fail() {
        test.assertExists('#CIVICRM_QFID_ts_all_4');
    });

    /* select Add Contacts to Group */
    casper.waitForSelector("#task", function success() {
        test.assertExists("#task");
        this.evaluate(function () {
            document.querySelector("#task").selectedIndex = 4;
        });
    }, function fail() {
        test.assertExists("#task");
    });
    casper.waitForSelector("form[name=Basic] input[type=submit][value='Go']", function success() {
        test.assertExists("form[name=Basic] input[type=submit][value='Go']");
        this.click("form[name=Basic] input[type=submit][value='Go']");
    }, function fail() {
        test.assertExists("form[name=Basic] input[type=submit][value='Go']");
    }); /* submit form */
    casper.wait(2000);

    /* sendKeys */
    casper.waitForSelector("#title", function success() {
        test.assertExists("#title");
        this.sendKeys("#title", makeid(5));
    }, function fail() {
        test.assertExists("#title");
    });
    casper.then(function() {
        // this.capture('add_to_smart_group.png');
    });

    /* click submit */
    casper.waitForSelector("form#SaveSearch input[type=submit][value='Save Smart Group']", function success() {
        test.assertExists("form#SaveSearch input[type=submit][value='Save Smart Group']");
        this.click("form#SaveSearch input[type=submit][value='Save Smart Group']");
    }, function fail() {
        test.assertExists("form#SaveSearch input[type=submit][value='Save Smart Group']");
    }); /* submit form */
    casper.wait(2000);
    casper.then(function() {
        test.assertDoesntExist('.crm-error');
    });
    
    casper.then(function() {
        // this.capture('smart_group_success.png');
    });

    /* click Done */
    casper.waitForSelector("form#Result input[type=submit][value='Done']", function success() {
        test.assertExists("form#Result input[type=submit][value='Done']");
        this.click("form#Result input[type=submit][value='Done']");
    }, function fail() {
        test.assertExists("form#Result input[type=submit][value='Done']");
    }); /* submit form */
    casper.wait(2000);

    /*
     * Delete Contacts 
     */
    
    casper.then(function() {
        casper.echo('=====================================');
        casper.echo('** Step 9: Delete Contacts. **');
        casper.echo('=====================================');
    });
    
    list_contacts_and_select_three(test);

    /* select Add Contacts to Group */
    casper.waitForSelector("#task", function success() {
        test.assertExists("#task");
        this.evaluate(function () {
            document.querySelector("#task").selectedIndex = 17;
        });
    }, function fail() {
        test.assertExists("#task");
    });
    casper.waitForSelector("form[name=Basic] input[type=submit][value='Go']", function success() {
        test.assertExists("form[name=Basic] input[type=submit][value='Go']");
        this.click("form[name=Basic] input[type=submit][value='Go']");
    }, function fail() {
        test.assertExists("form[name=Basic] input[type=submit][value='Go']");
    }); /* submit form */
    casper.wait(2000);

    /* click submit */
    casper.waitForSelector("form#Delete input[type=submit][value='Delete Contact(s)']", function success() {
        test.assertExists("form#Delete input[type=submit][value='Delete Contact(s)']");
        this.click("form#Delete input[type=submit][value='Delete Contact(s)']");
    }, function fail() {
        test.assertExists("form#Delete input[type=submit][value='Delete Contact(s)']");
    }); /* submit form */
    casper.wait(2000);
    casper.then(function() {
        test.assertDoesntExist('.crm-error');
    });
    casper.then(function() {
        // this.capture('delete_contacts_success.png');
    });
    
    casper.run(function() {
        test.done();
    });
});