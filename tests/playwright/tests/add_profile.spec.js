const { test, expect, chromium } = require('@playwright/test');
const utils = require('./utils.js');

/** @type {import('@playwright/test').Page} */
let page;
var locator, element;

var vars = {
    profile_url: '',
    profile_name: utils.makeid(5),
    first_name: utils.makeid(5),
    last_name: utils.makeid(5),
    legal_identifier: utils.makeid(5),
    current_employer: utils.makeid(5),
    phone: utils.makeid(5),
    city: utils.makeid(5),
    postal_code: utils.makeid(5),
    street_address: utils.makeid(5),
    email: utils.makeid(5).toLowerCase() + '@test.com',
    note: utils.makeid(5)
};



async function addField(field_type, field_name){
    await utils.wait(200);
    await utils.print(`Add Field: ${field_type} > ${field_name}`);

    /* select the option - Field Name Type  */
    element = 'select[name="field_name[0]"]';
    await utils.findElement(page, element);
    await utils.selectOption(page.locator(element), field_type);
    await expect(page.locator('#select2-field_name1-container')).toBeVisible();

    /* select the option - Field Name  */
    element = '#select2-field_name1-container';
    await utils.findElement(page, element);
    await utils.clickElement(page, page.locator(element), {exist: '#select2-field_name1-results'});
    await utils.wait(200);
    await page.keyboard.type(field_name, {delay: 100});
    await page.keyboard.press('Enter', {delay: 100});
    await expect(page.locator('input#label')).toHaveValue(field_name);

}

async function clickSaveAndNew(){
    await utils.wait(200);
    /* click Save and New */
    element = '#_qf_Field_next_new-top';
    await utils.findElement(page, element);
    await utils.clickElement(page, page.locator(element), {notExist: '.crm-error'});
}


test.beforeAll(async () => {
    const browser = await chromium.launch();
    page = await browser.newPage();
});

test.afterAll(async () => {
    await page.close();
});


test.describe.serial('Profile Editing', () => {

    test.use({ storageState: 'storageState.json' });

    test('New Profile test', async () => {

        /* Step 1: New Profile. */
        await test.step('New Profile.', async () =>{

            /* open add profile page */
            await page.goto('civicrm/admin/uf/group?reset=1');
            await utils.findElement(page, '#newCiviCRMProfile-top');

            /* click Add CiviCRM Profile button */
            element = '#newCiviCRMProfile-top';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element), {exist: 'form#Group'});

            /* fill in Profile Name */
            element = 'input#title';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), vars.profile_name);

            /* check the box - Drupal User Registration */
            element = 'input[name="uf_group_type_user[User Registration]"]';
            await utils.findElement(page, element);
            await utils.checkInput(page, page.locator(element));
            // await utils.wait(200);
            await expect(page.locator('input[name="uf_group_type[Profile]"]')).toBeChecked();

            /* check the box - View/Edit Drupal User Account */
            element = 'input[name="uf_group_type_user[User Account]"]';
            await utils.findElement(page, element);
            await utils.checkInput(page, page.locator(element));

            /* click Save */
            element = '#_qf_Group_next-bottom';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element), {notExist: '.crm-error', exist: 'form#Field'});

        });


        /* Step 2: Add Fields. */
        await test.step('Add Fields.', async () =>{

            /* Step 2-1: Add individual: Fist Name. */
            await addField('Individual', 'First Name');
            await clickSaveAndNew();

            /* Step 2-2: Add individual: Last Name. */
            await addField('Individual', 'Last Name');
            await clickSaveAndNew();

            /* Step 2-3: Add individual: Legal Identifier. */
            await addField('Individual', 'Legal Identifier');
            await clickSaveAndNew();

            /* Step 2-4: Add individual: Current Employer. */
            await addField('Individual', 'Current Employer');
            await clickSaveAndNew();

            /* Step 2-5: Add Contact info: Phone. */
            await addField('Contact', 'Phone');
            await clickSaveAndNew();

            /* Step 2-6: Add Contact info: State. */
            await addField('Contact', 'State');
            await clickSaveAndNew();

            /* Step 2-7: Add Contact info: City. */
            await addField('Contact', 'City');
            await clickSaveAndNew();

            /* Step 2-8: Add Contact info: Postal Code. */
            await addField('Contact', 'Postal Code');
            await clickSaveAndNew();

            /* Step 2-9: Add Contact info: Street Address. */
            await addField('Contact', 'Street Address');
            await clickSaveAndNew();

            /* Step 2-10: Add Contact info: Email. */
            await addField('Contact', 'Email');
            await clickSaveAndNew();

            /* Step 2-11: Add Contact info: Do Not Email. */
            await addField('Contact', 'Do Not Email');
            await clickSaveAndNew();


            /* Step 2-12: Add Contact info: Image Url. */
            await addField('Contact', 'Image Url');
            await clickSaveAndNew();


            /* Step 2-13: Add Contact info: Note(s). */
            await addField('Contact', 'Note(s)');
            await clickSaveAndNew();

        });

    });


    test('Check Profile', async () => {

        /* Step 3: Check All Fields Exists. */
        await test.step('Check All Fields Exists.', async () =>{

            /* open profile list page */
            await page.goto('civicrm/admin/uf/group?reset=1');
            await utils.findElement(page, '#user-profiles');

            /* check if profile is in the list */
            vars.profile_id = await page.evaluate( (vars) => {
                var tr_list = document.querySelectorAll("#user-profiles table tr");
                for(var i=1; i<tr_list.length; i++) {
                    if(tr_list[i].querySelector('td:first-child span').textContent == vars.profile_name) {
                        return tr_list[i].querySelector('td:nth-child(3)').textContent ;
                    }
                }
            }, vars);
            await expect(vars.profile_id).toBeDefined();
            await utils.print('Got profile id: ' + vars.profile_id);

            /* click preview */
            element = `#UFGroup-${vars.profile_id} a:nth-child(3)`;
            await utils.findElement(page, element);
            utils.clickElement(page, page.locator(element), {exist: 'form#Preview', notExist: '.error-ci'});
            await utils.print('page has no error');
            await expect(page.locator('#crm-container-inner h3')).toHaveText(vars.profile_name);

            /* check if fields just added exist */
            var assert_exist_field_names = ['First Name', 'Last Name', 'Legal Identifier', 'Current Employer', 'Phone', 'State', 'City', 'Postal Code', 'Street Address', 'Email', 'Do Not Email', "Image Url", 'Note(s)'];
            var fields = page.locator('#Preview .form-layout-compressed .label');
            
            await expect(await fields.count(), 'Some field lost.').toEqual(assert_exist_field_names.length);
            for (var i=0; i < await fields.count(); i++) {
                var field_name = await fields.nth(i).innerText();
                await expect(assert_exist_field_names.indexOf(field_name), `Field '${field_name}' not found in the page.`).not.toEqual(-1);
                await utils.print(`Field '${field_name}' had been added.`);
            }

        });


        /* Step 4: Check If "Publish Online Profile" Work. */
        await test.step('Check If "Publish Online Profile" Work.', async () =>{

            /* open profile list page */
            await page.goto('civicrm/admin/uf/group?reset=1');
            await utils.findElement(page, 'table.display');

            /* click more */
            element = `#more_${vars.profile_id}`;
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element), {visible: `#panel_more_${vars.profile_id}`});

            /* click Publish Online Profile */
            element = `#panel_more_${vars.profile_id} a:nth-child(1)`;
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element).first(), {notExist: '.crm-error', exist: '#crm-container'});

            /* check if url_to_copy is correct */
            element = 'textarea[name="url_to_copy"]';
            await utils.findElement(page, element);
            var publish_url = (await page.locator(element).inputValue()).split('/').slice(3).join('/');
            await expect(publish_url).toEqual(`civicrm/profile/create?gid=${vars.profile_id}&reset=1`);
            vars.profile_url = publish_url;            

        });


        /* Step 5: Check All Field Exist On Public Page. */
        await test.step('Check All Field Exist On Public Page.', async () =>{

            /* open profile public page */
            await page.goto(vars.profile_url);
            await utils.findElement(page, 'form#Edit');
            await expect(page.locator('.error-ci')).toHaveCount(0);
            await utils.print('page has no error');

            /* check if fields just added exist */
            var assert_exist_field_names = ['First Name', 'Last Name', 'Legal Identifier', 'Current Employer', 'Phone', 'State', 'City', 'Postal Code', 'Street Address', 'Email', 'Do Not Email', "Image Url", 'Note(s)'];
            var fields = page.locator('#Edit .form-layout-compressed .label');
            
            await expect(await fields.count(), 'Some field lost.').toEqual(assert_exist_field_names.length);
            for (var i=0; i < await fields.count(); i++) {
                var field_name = await fields.nth(i).innerText();
                await expect(assert_exist_field_names.indexOf(field_name), `Field '${field_name}' not found in the page.`).not.toEqual(-1);
                await utils.print(`Field '${field_name}' exists in public profile.`);
            }

        });

    });


    test('Add Contact with Profile test', async () => {

        /* Step 6: Input Data to Profile Form. */
        await test.step('Input Data to Profile Form.', async () =>{
            
            /* 6-1: Input First Name. */
            element = '#first_name';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), vars.first_name);

            /* 6-2: Input Last Name. */
            element = '#last_name';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), vars.last_name);

            /* 6-3: Input Legal Identifier. */
            element = '#legal_identifier';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), vars.legal_identifier);

            /* 6-4: Input Current Employer. */
            element = '#current_employer';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), vars.current_employer);

            /* 6-5: Input Phone. */
            element = '#phone-Primary-2';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), vars.phone);
            
            /* 6-6: Input State. */
            element = '#state_province-Primary';
            await utils.findElement(page, element);
            await utils.selectOption(page.locator(element), {index: 1});

            /* 6-7: Input City. */
            element = '#city-Primary';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), vars.city);

            /* 6-8: Input Postal Code. */
            element = '#postal_code-Primary';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), vars.postal_code);

            /* 6-9: Input Street Address. */
            element = '#street_address-Primary';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), vars.street_address);

            /* 6-10: Input Email. */
            element = '#email-Primary';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), vars.email);

            /* 6-11: Input Do Not Email. */
            element = '#do_not_email';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element));

            /* 6-12: Input Note(s). */
            element = '#note';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), vars.note);

            /* 6-13: Click Save. */
            element = '#_qf_Edit_upload';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element), {notExist: '.error-ci'});
            await utils.print('page has no error');

        });


        /* Step 7: Check If Contact Had Been Created Successfully. */
        await test.step('Check If Contact Had Been Created Successfully.', async () =>{
        
            /* 7-1: Search for new contact. */
            await page.goto('civicrm/contact/search?reset=1');
            await utils.findElement(page, 'form#Basic');

            /* Input Full Name. */
            await utils.fillInput(page.locator('#sort_name'), `${vars.first_name} ${vars.last_name}`);

            /* Click Search. */
            await utils.clickElement(page, page.locator('#_qf_Basic_refresh'), {notExist: '.messages.status'});

            await utils.wait(1000);

            /* 7-2: Go to new contact page, check all data correct. */
            element = 'table.selector tbody tr td.crm-search-display_name a';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element).first(), {exist: '#contact-summary'});

            /* check first name and last name */
            await expect(page, `First Name and Last Name errors: ${vars.first_name} ${vars.last_name}"`).toHaveTitle(new RegExp('^' + `${vars.first_name} ${vars.last_name}`));
            await utils.print('First Name and Last Name correct.');

            /* check legal identifier */
            element = '#record-log div';
            await utils.findElement(page, element);
            const regex = new RegExp(`Legal Identifier: ${vars.legal_identifier}(?!\\S)`);
            await expect(page.locator(element).first(), 'Legal Identifier occurs errors.').toHaveText(regex);
            await utils.print('Legal Identifier correct.');

            /* check current employer */
            element = 'a[title="view current employer"]';
            await utils.findElement(page, element);
            await expect(page.locator(element), 'Current Employer occurs errors.').toHaveText(vars.current_employer);
            await utils.print('Current Employer correct.');

            /* check phone */
            element = 'td.primary span';
            await utils.findElement(page, element);
            await expect(page.locator(element), 'Phone occurs errors.').toHaveText(vars.phone);
            await utils.print('Phone correct.');

            /* check state */
            element = 'span.region';
            await utils.findElement(page, element);
            await expect(page.locator(element), 'State occurs errors.').toHaveText('AL');
            await utils.print('State correct.');

            /* check city */
            element = 'span.locality';
            await utils.findElement(page, element);
            await expect(page.locator(element), 'City occurs errors.').toHaveText(vars.city);
            await utils.print('City correct.');

            /* check postal code */
            element = 'span.postal-code';
            await utils.findElement(page, element);
            await expect(page.locator(element), 'Postal Code occurs errors.').toHaveText(vars.postal_code);
            await utils.print('Postal Code correct.');

            /* check street address */
            element = 'span.street-address';
            await utils.findElement(page, element);
            await expect(page.locator(element), 'Street Address occurs errors.').toHaveText(vars.street_address);
            await utils.print('Street Address correct.');

            /* check email */
            element = 'span.do-not-email a';
            await utils.findElement(page, element);
            await expect(page.locator(element), 'Email occurs errors.').toHaveText(vars.email);
            await utils.print('Email correct.');

            /* check note */
            element = 'a[title="Notes"]';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element), {exist: '#notes'});
            await expect(page.locator('#notes tr td:nth-child(2)'), 'Note occurs errors.').toHaveText(vars.note);
            await utils.print('Note correct.');            

        });

    });
});