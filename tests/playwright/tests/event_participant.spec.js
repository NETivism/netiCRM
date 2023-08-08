const { test, expect, chromium } = require('@playwright/test');
const utils = require('./utils.js');

/** @type {import('@playwright/test').Page} */
let page, pageCreateContact;
var locator, element;
const wait_secs = 2000;

var vars = {
    first_name: utils.makeid(3),
    last_name: utils.makeid(3)
};


async function elementFoundLog(element){
    console.log('Find an element matching: ' + element);
}


test.beforeAll(async () => {
    const browser = await chromium.launch();
    page = await browser.newPage();
});

test.afterAll(async () => {
    await page.close();
    await pageCreateContact.close();
});

test.describe.serial('Participant Editing', () => {

    test.use({ storageState: 'storageState.json' });

    test('Event Participant Editing test', async () => {

        await test.step("Pick an Event.", async () =>{

            /* open CiviEvent Dashboard */
            await page.goto('/civicrm/event?reset=1');

            /* click first event */
            element = '#event_status_id table tbody tr:last-child td.crm-event-title a';
            locator = page.locator(element).nth(0);
            var event_title = await locator.textContent();
            await utils.findElement(page, element);
            await locator.click();
            await expect(page.locator('#actions')).not.toHaveCount(0);
            await expect(page, `page title is not match "${event_title}"`).toHaveTitle(new RegExp('^'+event_title));

        });

        await test.step("Register New Participant To It.", async () =>{

            /* click Register New Participant */
            element = 'ul#actions li:nth-child(2) a';
            locator = page.locator(element).nth(0);
            await utils.findElement(page, element);
            await locator.click();

            /* switch to new tab */
            const pageCreateContactPromise = page.waitForEvent('popup');
            pageCreateContact = await pageCreateContactPromise;
            await expect(pageCreateContact.locator('form#Participant')).not.toHaveCount(0);

            /* select 新增個人 */
            element = '#profiles_1';
            locator = pageCreateContact.locator(element);
            await utils.findElement(pageCreateContact, element);
            await locator.selectOption('4');
            await expect(pageCreateContact.locator('form#Edit')).not.toHaveCount(0);

            /* filled up new contact form */
            element = 'form#Edit';
            locator = pageCreateContact.locator(element);
            await utils.findElement(pageCreateContact, element);

            locator = pageCreateContact.locator('#first_name');
            await utils.fillInput(locator, vars.first_name);
            locator = pageCreateContact.locator('#last_name');
            await utils.fillInput(locator, vars.last_name);
            await pageCreateContact.locator('#_qf_Edit_next').click();
            await expect(pageCreateContact.locator('#contact_1')).toHaveValue(`${vars.first_name} ${vars.last_name}`);

            /* select Participant Status */
            element = '#status_id';
            locator = pageCreateContact.locator(element);
            await utils.findElement(pageCreateContact, element);
            await locator.selectOption('1');
            await expect(locator).toHaveValue('1');

            /* click submit */
            element = "form#Participant input[type=submit][value='Save']";
            locator = pageCreateContact.locator(element);
            await utils.findElement(pageCreateContact, element);
            await locator.click();
            await expect(page.locator('.crm-error')).toHaveCount(0);
            console.log('Fail to find element matching selector: .crm-error');

        });

        await page.reload();
        await utils.wait(wait_secs);

        await test.step("Edit Event Participant.", async () =>{

            /* click edit event */
            element = 'table.selector .row-action .action-item:nth-child(2)';
            locator = pageCreateContact.locator(element).nth(0);
            await utils.findElement(pageCreateContact, element);
            await locator.click();
            await expect(pageCreateContact.locator('form#Participant')).not.toHaveCount(0);

            /* click checkbox 志工 */
            element = "input[name='role_id[2]']";
            locator = pageCreateContact.locator(element);
            await utils.findElement(pageCreateContact, element);
            await locator.click();
            await expect(locator).toBeChecked();

            /* change Registration Date */
            element = '#register_date';
            locator = pageCreateContact.locator(element);
            await utils.findElement(pageCreateContact, element);
            await locator.click();
            await pageCreateContact.locator('select.ui-datepicker-year').selectOption('2020');
            await pageCreateContact.locator('select.ui-datepicker-month').selectOption('0');
            await pageCreateContact.locator("a.ui-state-default[data-date='1']").click();
            await expect(locator).toHaveValue('01/01/2020');

            element = '#register_date_time';
            locator = pageCreateContact.locator(element);
            await utils.findElement(pageCreateContact, element);
            await utils.fillInput(locator, '12:00PM');

            /* select Participant Status */
            element = '#status_id';
            locator = pageCreateContact.locator(element);
            await utils.findElement(pageCreateContact, element);
            await locator.selectOption('2');
            await expect(locator).toHaveValue('2');

            /* click submit */
            element = "form#Participant input[type=submit][value='Save']";
            locator = pageCreateContact.locator(element);
            await utils.findElement(pageCreateContact, element);
            await locator.click();
            await expect(page.locator('.crm-error')).toHaveCount(0);
            console.log('Fail to find element matching selector: .crm-error');

        });
    });
});