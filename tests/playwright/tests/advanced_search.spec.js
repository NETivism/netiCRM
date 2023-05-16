const { test, expect, chromium } = require('@playwright/test');
const utils = require('./utils.js');

/** @type {import('@playwright/test').Page} */
let page;
var locator, element;
const wait_secs = 2000;

var vars = {
    note: utils.makeid(5),
    contribution_source: utils.makeid(5),
    member_source: utils.makeid(5),
    event_name: '無名額限制，填表完成送出'
}


test.beforeAll(async () => {
    const browser = await chromium.launch();
    page = await browser.newPage();
});
  
test.afterAll(async () => {
    await page.close();
});


test.describe.serial('Advanced Search', () => {

    test.use({ storageState: 'storageState.json' });

    test('Advanced Search', async () => {

        /* Step 1: Fill Up Search Criteria. */
        await test.step('Fill Up Search Criteria.', async () =>{

            /* open Advanced Search page */
            await page.goto('civicrm/contact/search/advanced?reset=1');
            await utils.wait(wait_secs);
            await utils.findElement(page, 'form#Advanced');

            /* Step 1-1: Fill up 'Contact Information'. */

            element = '#crmasmSelect1';
            await utils.findElement(page, element);
            await page.locator(element).selectOption('2');
            await expect(page.locator('#group')).toHaveValue('2');

            element = '#crmasmSelect2';
            await utils.findElement(page, element);
            await page.locator(element).selectOption('4');
            await expect(page.locator('#contact_tags')).toHaveValue('4');

            /* Step 1-2: Fill up 'Address Fields'. */

            element = '#location';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element));
            await expect(page.locator('.location')).toBeVisible();

            await utils.wait(wait_secs);

            element = '#state_province';
            await utils.findElement(page, element);
            await utils.selectOption(page.locator(element), {index: 1});

            /* Step 1-3: Fill up 'Notes'. */

            element = '#notes';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element));
            await expect(page.locator('.notes')).toBeVisible();

            await utils.wait(wait_secs);

            element = 'input[name="note"]';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), vars.note);

            /* Step 1-4: Fill up 'Contribute'. */

            element = '#CiviContribute';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element));
            await expect(page.locator('.CiviContribute')).toBeVisible();

            await utils.wait(wait_secs);

            element = '#contribution_source';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), vars.contribution_source);

            /* Step 1-5: Fill up 'Memberships'. */

            element = '#CiviMember';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element));
            await expect(page.locator('.CiviMember')).toBeVisible();

            await utils.wait(wait_secs);

            element = '#member_source';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), vars.member_source);

            /* Step 1-6: Fill up 'Events'. */

            element = '#CiviEvent';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element));
            await expect(page.locator('.CiviEvent')).toBeVisible();

            await utils.wait(wait_secs);

            element = '.token-input-box';
            await utils.findElement(page, element);

            await utils.wait(wait_secs);
            
            await page.locator(element).first().click();
            await page.keyboard.type(vars.event_name);
            await page.keyboard.press('Alt');
            await utils.wait(wait_secs);
            await page.keyboard.press('Enter');
            // Validate since the selected event value is 1
            await expect(page.locator('#event_id')).toHaveValue('1');

            /* Step 1-7: Apply search. */

            element = '#_qf_Advanced_refresh';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element));

            await utils.wait(wait_secs);

            await expect(page.locator('.crm-error')).toHaveCount(0);
            await utils.print('page has no error');

        });

    });

});