const { test, expect, chromium } = require('@playwright/test');
const utils = require('./utils.js');

/** @type {import('@playwright/test').Page} */
let page;
var locator, element;

var vars = {
    first_name: utils.makeid(5),
    last_name: utils.makeid(5),
    user_phone: '09' + Math.floor(Math.random() * 100000000).toString()
};
vars['user_email'] = `${vars.first_name.toLowerCase()}${vars.last_name.toLowerCase()}123@gmail.com`;


test.beforeAll(async () => {
    const browser = await chromium.launch();
    page = await browser.newPage();
});

test.afterAll(async () => {
    await page.close();
});


test.describe.serial('Edit Contact', () => {

    test.use({ storageState: 'storageState.json' });

    test('Contact Editing test', async () => {
        
        await test.step("Edit Contact.", async () =>{

            /* go to drupal user page */
            await page.goto('user');
            await expect(page.locator('#crm-container')).toBeVisible();
            await utils.print('Landing on correct page.');

            /* go to crm contact page */
            element = '#user-page-contact a';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element), {exist: '#mainTabContainer'});

            /* click Edit button */
            element = 'a.edit';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element), {exist: '#Contact'});

            /* fill in last name */
            element = '#last_name';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), vars.last_name);

            /* fill in first name */
            element = '#first_name';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), vars.first_name);

            /* fill in email */
            element = '#email_1_email';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), vars.user_email);

            /* choose phone type */
            element = '#phone_1_phone_type_id';
            await utils.findElement(page, element);
            await utils.selectOption(page.locator(element), {index: 0});

            /* fill in phone number */
            element = '#phone_1_phone';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), vars.user_phone);

            /* click Save */
            element = '#_qf_Contact_upload_view';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element).first(), {notExist: '.crm-error'});

        });

        // check fields in personal information page
        await test.step("Check If Contact Information Correct.", async () =>{

            /* check name */
            var full_name = `${vars.first_name} ${vars.last_name} | netiCRM`;
            await expect(page, `Page title is not match "${full_name}"`).toHaveTitle(full_name);
            await utils.print(`Page title is: "${full_name}"`)

            /* check email */
            element = '#contact-summary > div.contact_details > div:nth-child(1) > div.contactCardLeft > table > tbody > tr:nth-child(1) > td:nth-child(2) > span > a';
            await utils.findElement(page, element);
            await expect(page.locator(element)).toHaveText(vars.user_email);
            await utils.print('Subject equals the expected value');

            /* check phone number */
            element = '#contact-summary > div.contact_details > div:nth-child(1) > div.contactCardRight > table > tbody > tr > td.primary > span';
            await utils.findElement(page, element);
            await expect(page.locator(element)).toHaveText(vars.user_phone);
            await utils.print('Subject equals the expected value');

        });
    
    });

});