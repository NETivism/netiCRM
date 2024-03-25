const { test, expect, chromium } = require('@playwright/test');
const utils = require('./utils.js');

/** @type {import('@playwright/test').Page} */
let page;
var locator, element;
const wait_secs = 2000;

var vars = {
    organization_name: utils.makeid(5),
    group_name: utils.makeid(5),
    smart_group_name: utils.makeid(5),
    individual_name: utils.makeid(3),
    merge_search: 'merge_test',
    delete_search: 'delete_test'
}
/**
 * Check the checkboxes of the top n rows in the search results table (Contact Type: Individual) of "Find Contacts" page
 * @param {Object} page - The Playwright page object.
 * @param {number} top_n - The number of top rows to check the checkboxes.
 * @return {Promise<void>}
 */
async function select_top_n(page, top_n){
    // Check the radio button
    element = "input#CIVICRM_QFID_ts_all_4";
    await utils.findElement(page, element);
    await utils.checkInput(page, page.locator(element));
    // Check the checkboxes of the top n rows
    for (let j = 1; j <= top_n; j++) {
        element = `table.selector tr:nth-child(${j}) td:nth-child(1) input`;
        await utils.findElement(page, element);
        await utils.checkInput(page, page.locator(element));
    }
}

/**
 * Check the checkboxes of the top n rows in the search results table(Contact Type: Individual) of "Find Contacts" page
 * @param {Object} page - The Playwright page object.
 * @param {number} top_n - The number of top rows to check the checkboxes.
 * @return {Promise<void>}
 */
async function list_contacts_and_select_top_n(page, top_n=3) {
    
    /* Step 1: Open the "Find Contacts" page URL and wait for 2 seconds */
    await page.goto('civicrm/contact/search?reset=1');
    await utils.wait(wait_secs);

    /* Step 2: Select the first option in the Contact Type(is...) dropdown */
    element = '#contact_type';
    await utils.findElement(page, element);
    await utils.selectOption(page.locator(element), { index: 1 });

    /* Step 3: Click the "Search" button and wait for 2 seconds */
    element = '#_qf_Basic_refresh';
    await utils.findElement(page, element);
    await utils.clickElement(page, page.locator(element));
    await utils.wait(wait_secs);

    /* Step 4: Check the checkboxes of the top n rows */
    await select_top_n(page, top_n);
    
}

/**
 * Select an option from the "Actions" dropdown and clicks the "Go" button.
 * @param {Object} page - The Playwright page object.
 * @param {number} option_index - The index of the option to be selected.
 * @param {string} expect_element - The CSS selector of the element expected to appear on the page.
 * @return {Promise<void>}
 */
async function select_action_and_go(page, option_index, expect_element) {

    /* select option from the "Actions" dropdown */
    element = '#task';
    await utils.findElement(page, element);
    await utils.selectOption(page.locator(element), { index: option_index });

    /* click "Go" button */
    element = 'input#Go';
    await utils.findElement(page, element);
    await utils.clickElement(page, page.locator(element).first());

    await utils.wait(wait_secs);

    await utils.findElement(page, expect_element);

}

/**
 * Input keyword to search some specific contacts in "Find Contacts" page.
 * @param {Object} page - The Playwright page object.
 * @param {string} contactName - Keyword that we want to search.
 * @return {Promise<void>}
 */
async function search_contacts(page, contactName){
    // go to search page
    await page.goto('civicrm/contact/search?reset=1');
    await utils.wait(wait_secs);    
    // input search contact name
    var searchLocator='#sort_name';
    await utils.findElement(page, searchLocator);
    await utils.fillInput(page.locator(searchLocator), contactName);
    // search and wait for the result
    var searchLocator='#_qf_Basic_refresh';
    await utils.findElement(page, searchLocator);
    await utils.clickElement(page, page.locator(searchLocator));
    await utils.wait(wait_secs);
}


/**
 * Create new contants and search them for merging function.
 * @param {Object} page - The Playwright page object.
 * @param {number} contactNum - The number of the contacts that we want to create.
 * @return {Promise<void>}
 */

async function create_contacts(page, contactNum, contactName){
    // create new contact data
    
    // Open the "New Individul" page URL and wait for 2 seconds
    await page.goto('/civicrm/contact/add?reset=1&ct=Individual');
    await utils.wait(wait_secs);
    
    // Create new contants
    for (let i=1; i <= contactNum; i++ ){
        // fill data
        var emailLocator='input#email_1_email';
        await utils.findElement(page, emailLocator);
        await utils.fillInput(page.locator(emailLocator), contactName + `_${utils.makeid(3)}@example.com`);
        // save
        var saveLocator='#_qf_Contact_upload_new';
        await utils.findElement(page, saveLocator);
        await utils.clickElement(page, page.locator(saveLocator).first());
        // check
        var messageLocator = '#crm-container>div.messages.status';
        await utils.findElement(page, messageLocator);
    }
    
}


test.beforeAll(async () => {
    const browser = await chromium.launch();
    page = await browser.newPage();
});
  
test.afterAll(async () => {
    await page.close();
});


test.describe.serial('Batch Action', () => {

    test.use({ storageState: 'storageState.json' });

    test('Batch Action - 1', async () => {

        /* Step 1: Add to Organization. */
        await test.step('Add to Organization.', async () =>{

            /* open "New Organization" page */
            await page.goto('civicrm/contact/add?reset=1&ct=Organization');
            await utils.wait(wait_secs);
            await utils.findElement(page, 'form#Contact');

            /* fill in "Organization Name" */
            element = 'input[name="organization_name"]';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), vars.organization_name);

            /* click "Save" button */
            element = 'input#_qf_Contact_upload_view';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element).first());

            await utils.wait(wait_secs);

            await expect(page).toHaveTitle(vars.organization_name + ' | netiCRM');

            await list_contacts_and_select_top_n(page);

            /* select "Add Contact to Organization" and click "Go" */
            await select_action_and_go(page, 6, 'form#AddToOrganization')

            /* select "Relationship Type" */
            element = '#relationship_type_id';
            await utils.findElement(page, element);
            await utils.selectOption(page.locator(element), { index: 2 });

            /* fill in "Find Target Organization" */
            element = '#name';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), vars.organization_name);

            /* click "Search" button */
            element = '#_qf_AddToOrganization_refresh';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element).first());

            await utils.wait(wait_secs);

            await utils.findElement(page, '#AddToOrganization fieldset');

            element = '#AddToOrganization fieldset tr:nth-child(2) td';
            await expect(page.locator(element).nth(1)).toHaveText(vars.organization_name);
            await expect(page.locator(element).nth(0).locator('input')).toBeChecked();

            /* click "Add to Organization" button */
            element = '#_qf_AddToOrganization_next-bottom';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element).first());

            await utils.wait(wait_secs);

            await expect(page.locator('.crm-error')).toHaveCount(0);
            await expect(page.locator('.messages ul').first().locator('li')).toHaveText('Total Selected Contact(s): 3');
            await expect(page.locator('.messages ul').nth(1).locator('li'), 'Failed to create new relationship record(s).').toHaveText('New relationship record(s) created: 3.');
            await utils.print('New relationship record(s) created successfully.');

        });

        /* Step 2: Record Activity. */
        await test.step('Record Activity.', async () =>{

            await list_contacts_and_select_top_n(page);

            /* select "Record Activity for Contacts" and click "Go" */
            await select_action_and_go(page, 7, 'form#Activity');

            /* select "Activity Type" */
            element = '#activity_type_id';
            await utils.findElement(page, element);
            await utils.selectOption(page.locator(element), { index: 1 });

            /* click "Save" button */
            element = '#_qf_Activity_upload-bottom';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element).first());

            await utils.wait(wait_secs);

            await expect(page.locator('.crm-error')).toHaveCount(0);

            await expect(page.locator('.messages').first()).toHaveText('Activity has been saved. .');
            await utils.print('Activity has been saved.');

        });

        /* Step 3: Batch Profile Update for Contact. */
        await test.step('Batch Profile Update for Contact.', async () =>{

            await list_contacts_and_select_top_n(page);

            /* select "Batch Update via Profile" and click "Go" */
            await select_action_and_go(page, 8, 'form#PickProfile');

            /* select "Profile" */
            element = '#uf_group_id';
            await utils.findElement(page, element);
            await utils.selectOption(page.locator(element), { index: 3 });

            /* click "Continue" button */
            element = '#_qf_PickProfile_next';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element).first());

            await utils.wait(wait_secs);

            await utils.findElement(page, 'form#Batch');

            /* fill in the incomplete data of 3 users with random values */
            for (let i = 1; i <= 3; i++) {

                /* Home Phone */
                element = `tr:nth-child(${i}) td:nth-child(2) input`;
                if (await page.locator(element).first().evaluate(el => el.value) == '') {
                    await utils.fillInput(page.locator(element), utils.makeid(10));
                }

                /* Home Mobile */
                element = `tr:nth-child(${i}) td:nth-child(3) input`;
                if (await page.locator(element).first().evaluate(el => el.value) == '') {
                    await utils.fillInput(page.locator(element), utils.makeid(10));
                }
                
                /* Primary Address */
                element = `tr:nth-child(${i}) td:nth-child(4) input`;
                if (await page.locator(element).first().evaluate(el => el.value) == '') {
                    await utils.fillInput(page.locator(element), utils.makeid(5));
                }
                
                /* City */
                element = `tr:nth-child(${i}) td:nth-child(5) input`;
                if (await page.locator(element).first().evaluate(el => el.value) == '') {
                    await utils.fillInput(page.locator(element), utils.makeid(3));
                }
                
                /* State */
                element = `tr:nth-child(${i}) td:nth-child(6) select`;
                if (await page.locator(element).first().evaluate(el => el.selectedIndex) == 0) {
                    await utils.selectOption(page.locator(element), { index: 1 });
                }
                
                /* Postal Code */
                element = `tr:nth-child(${i}) td:nth-child(7) input`;
                if (await page.locator(element).first().evaluate(el => el.value) == '') {
                    await utils.fillInput(page.locator(element), utils.makeid(3));
                }

                /* Primary Email */
                element = `tr:nth-child(${i}) td:nth-child(8) input`;
                if (await page.locator(element).first().evaluate(el => el.value) == '') {
                    await utils.fillInput(page.locator(element), `${utils.makeid(5)}@example.com`);
                }

                /* Group */
                element = `tr:nth-child(${i}) td:nth-child(9) input[value="1"]`;
                locator = page.locator(element).first();
                if (await locator.evaluate(el => el.checked) == false) {
                    await utils.checkInput(page, locator);
                }

                /* Tag */
                element = `tr:nth-child(${i}) td:nth-child(10) input`;
                locator = page.locator(element).nth(2);
                if (await locator.evaluate(el => el.checked) == false) {
                    await utils.checkInput(page, locator);
                }                
                
            }

            /* click "Update Contacts" button */
            element = '#_qf_Batch_next';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element).first());

            await utils.wait(wait_secs);

            await expect(page.locator('.crm-error')).toHaveCount(0);

            await expect(page.locator('.messages').first()).toHaveText('Your updates have been saved.');
            await utils.print('Contacts updated successfully.');

        });

        /* Step 4: Export Contacts. */
        await test.step('Export Contacts.', async () =>{

            await list_contacts_and_select_top_n(page);

            /* select "Export Contacts" and click "Go" */
            await select_action_and_go(page, 9, 'form#Select');

            /* click "Continue" button */
            element = '#_qf_Select_next-top';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element).first());

            await utils.wait(wait_secs);

            await expect(page.locator('.crm-error')).toHaveCount(0);

            await utils.findElement(page, 'form#Map');

            /* select record type */
            element = 'form#Map tr:nth-child(2) select';
            await utils.findElement(page, element);
            await utils.selectOption(page.locator(element).first(), { index: 1 });

            /* click "Export" button */
            element = '#_qf_Map_next-bottom';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element).first());

            await utils.wait(wait_secs);

            await expect(page.locator('.crm-error')).toHaveCount(0);

        });

    });

    test('Batch Action - 2', async () => {

        // /* Merge Contacts - 1 Merge */

        /* Step 5-1: Merge Contacts - Merge. */
        await test.step('Merge Contacts - Merge.', async () =>{
            
            /* create new contact data and select top two*/
            await create_contacts(page, 2, vars.merge_search);  
            await search_contacts(page, vars.merge_search);
            await select_top_n(page, 2);
        
            /* select "Merge Contacts" and click "Go" */
            await select_action_and_go(page, 10, 'form#Merge');
 
            /* click "Merge" button */
            element = '#_qf_Merge_next-bottom';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element).first());

        //     await utils.wait(wait_secs);

        //     await expect(page.locator('.crm-error')).toHaveCount(0);

        // });

        // /* Merge Contacts - 2 Mark this pair as not a duplicate */

        // /* Step 5-2: Merge Contacts - Mark this pair as not a duplicate. */
        // await test.step('Merge Contacts - Mark this pair as not a duplicate.', async () =>{

            /* create new contact data and select top two*/
            await create_contacts(page, 2, vars.merge_search);
            await search_contacts(page, vars.merge_search);
            await select_top_n(page, 2);

        //     /* select "Merge Contacts" and click "Go" */
        //     await select_action_and_go(page, 10, 'form#Merge');

        //     /* click "Mark this pair as not a duplicate" button */
        //     element = '#notDuplicate';
        //     await utils.findElement(page, element);
        //     await utils.clickElement(page, page.locator(element).first());

        //     /* click "Ok" */
        //     element = 'div.ui-dialog-buttonset button:nth-child(2)';
        //     await utils.findElement(page, element);
        //     await utils.clickElement(page, page.locator(element).first());

            await utils.wait(wait_secs);
            
            await expect(page.locator('.crm-error')).toHaveCount(0);

        // });

        /* Step 6: Tag Contacts. */
        await test.step('Tag Contacts.', async () =>{

            await list_contacts_and_select_top_n(page);

            /* select "Tag Contacts (assign tags)" and click "Go" */
            await select_action_and_go(page, 11, 'form#AddToTag')

            /* click scrollbar */
            element = 'tr.crm-contact-task-addtotag-form-block-tag div.listing-box div:first-child input';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element).first());

            /* click "Tag Contacts" */
            element = '#_qf_AddToTag_next-bottom';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element).first());

            await utils.wait(wait_secs);

            await expect(page.locator('.crm-error')).toHaveCount(0);
            await expect(page.locator('.messages h3').first()).toHaveText('Contact(s) tagged as: 主要捐款者');
            await expect(page.locator('.messages ul').nth(1).locator('li')).toHaveText('Total Contact(s) tagged: 3');
            await utils.print('Contact(s) tagged successfully.');

        });

        /* Add Contacts to Group - 1 Add Contact To Existing Group */

        /* Step 7-1: Add Contacts to Group - Add Contact To Existing Group. */
        await test.step('Add Contacts to Group - Add Contact To Existing Group.', async () =>{

            await list_contacts_and_select_top_n(page);

            /* select "Add Contacts to Group" and click "Go" */
            await select_action_and_go(page, 2, 'form#AddToGroup');

            /* select first option from "Select Group" dropdown */
            element = '#group_id';
            await utils.findElement(page, element);
            await utils.selectOption(page.locator(element), { index: 1 });

            /* click "Confirm" */
            element = '#_qf_AddToGroup_next-bottom';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element).first());

            await utils.wait(wait_secs);

            await expect(page.locator('.crm-error')).toHaveCount(0);
            await expect(page.locator('.messages').first()).toHaveText("Added Contact(s) to 管理員Total Selected Contact(s): 3Total Contact(s) added to group: 3");
            await utils.print('Successfully added contact to existing group.');

        });

        /* Add Contacts to Group - 2 Create New Group */

        /* Step 7-2: Add Contacts to Group - Create New Group. */
        await test.step('Add Contacts to Group - Create New Group.', async () =>{

            await list_contacts_and_select_top_n(page);

            /* select "Add Contacts to Group" and click "Go" */
            await select_action_and_go(page, 2, 'form#AddToGroup');

            /* click "Create New Group" */
            element = '#CIVICRM_QFID_1_4';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element).first());

            /* fill in "Group Name" */
            element = '#title';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element).first(), vars.group_name);

            /* click "Confirm" */
            element = '#_qf_AddToGroup_next-bottom';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element).first());

            await utils.wait(wait_secs);

            await expect(page.locator('.crm-error')).toHaveCount(0);
            await expect(page.locator('.messages').first()).toHaveText(`Added Contact(s) to ${vars.group_name}Total Selected Contact(s): 3Total Contact(s) added to group: 3`);
            await utils.print('Successfully added contact to existing group.');

        });

        /* New Smart Group */

        /* Step 8: New Smart Group. */
        await test.step('New Smart Group.', async () =>{

            await list_contacts_and_select_top_n(page);

            /* click "All records" */
            element = '#CIVICRM_QFID_ts_all_4';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element).first());

            /* select "New Smart Group" and click "Go" */
            await select_action_and_go(page, 4, 'form#SaveSearch');

            /* fill in "Name" */
            element = '#title';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element).first(), vars.smart_group_name);

            /* click "Save Smart Group" */
            element = '#_qf_SaveSearch_next-bottom';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element).first());

            await utils.wait(wait_secs);

            await expect(page.locator('.crm-error')).toHaveCount(0);
            await expect(page.locator('.messages').first()).toHaveText(`Your smart group has been saved as '${vars.smart_group_name}'.`);

            /* click "Done" */
            element = '#_qf_Result_done';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element).first());

            await utils.wait(wait_secs);

            await utils.print('Successfully saved smart group.');

        });

        /* Delete Contacts */

        /* Step 9: Delete Contacts. */
        await test.step('Delete Contacts.', async () =>{
            /* create new contact data and select top two*/
            const deleteContact = 'delete_test';
            await create_contacts(page, 2, deleteContact);
            await search_contacts(page, deleteContact);
            await select_top_n(page, 2);

            /* select "Delete Contacts" and click "Go" */
            await select_action_and_go(page, 17, 'form#Delete');

            /* click "Delete Contact(s)" */
            element = '#_qf_Delete_done';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element).first());

            await utils.wait(wait_secs);

            await expect(page.locator('.crm-error')).toHaveCount(0);
            await utils.print('Successfully deleted contacts.');

        });

    });

});