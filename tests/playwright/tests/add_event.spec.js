const { test, expect, chromium } = require('@playwright/test');
const utils = require('./utils.js');

/** @type {import('@playwright/test').Page} */
let page;
var locator, element;


test.beforeAll(async () => {
    const browser = await chromium.launch();
    page = await browser.newPage();
});
  
test.afterAll(async () => {
    await page.close();
});


test.describe.serial('Event Editing', () => {

    test.use({ storageState: 'storageState.json' });

    test('New Event test', async () => {

        /* Info and Settings */
        await test.step('Info and Settings.', async () =>{

            /* open add event */
            await page.goto('civicrm/event/add?reset=1&action=add');
            await utils.findElement(page, 'form#EventInfo');

            /* filled up add event form */
            element = '#event_type_id';
            await utils.findElement(page, element);
            await utils.selectOption(page.locator(element), '1');
            
            var event_title = utils.makeid(5);
            element = '#title';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), event_title);

            /* submit form */
            element = "#_qf_EventInfo_upload-bottom";
            await utils.findElement(page, element);
            await page.locator(element).click();
            await expect(page.locator('form#Location')).not.toHaveCount(0);
            await expect(page, `page title is not match "${event_title}"`).toHaveTitle(new RegExp('^'+event_title));
            console.log('Page Header Title matching: ' + event_title);

        });

        /* Event Location */
        await test.step('Event Location.', async () =>{

            /* select State/Province */
            element = '#address_1_state_province_id';
            await utils.findElement(page, element);
            await page.locator(element).selectOption({ index: 1 });
            await expect(page.locator(element)).not.toHaveValue('');

            /* click Save */
            element = "#_qf_Location_upload-bottom";
            await utils.findElement(page, element);
            await page.locator(element).click();
            await expect(page.locator('.crm-error')).toHaveCount(0);

        });

        /* Fees */
        await test.step('Fees.', async () =>{

            /* click fees */
            element = 'li#tab_fee a';
            await utils.findElement(page, element);
            await page.locator(element).click();
            await expect(page.locator('form#Fee')).not.toHaveCount(0);

            /* click Paid Event Yes */
            element = '#CIVICRM_QFID_1_2';
            await utils.findElement(page, element);
            await page.locator(element).click();
            await expect(page.locator('#contribution_type_id')).toBeVisible();

            /* select Contribution Type */
            element = '#contribution_type_id';
            await utils.findElement(page, element);
            await utils.selectOption(page.locator(element), '1');

            /* click pay later */
            element = '#is_pay_later';
            await utils.findElement(page, element);
            await utils.checkInput(page, page.locator(element));

            /* filled up Pay Later Instructions */
            element = '#pay_later_receipt';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), 'I will send payment by check');

            /* Event Level */
            /* level 1 */
            element = '#label_1';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), 'aaa');

            element = '#value_1';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), '111');

            /* level 2 */
            element = '#label_2';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), 'bbb');

            element = '#value_2';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), '222');

            /* click Save */
            element = "#_qf_Fee_upload-bottom";
            await utils.findElement(page, element);
            await page.locator(element).click();
            await expect(page.locator('.crm-error')).toHaveCount(0);

        });

        /* Online Registration */
        await test.step('Online Registration.', async () =>{

            /* click Online Registration */
            element = 'li#tab_registration a';
            await utils.findElement(page, element);
            await page.locator(element).click();
            await expect(page.locator('form#Registration')).not.toHaveCount(0);

            /* click Allow Online Registration? */
            element = '#is_online_registration';
            await utils.findElement(page, element);
            await utils.checkInput(page, page.locator(element));

            /* click Confirmation Email accordion */
            element = 'div.crm-accordion-wrapper:nth-child(6) div.crm-accordion-header';
            await utils.findElement(page, element);
            await page.locator(element).click();
            await expect(page.locator('#CIVICRM_QFID_1_2')).toBeVisible();

            /* click Send Confirmation Email? */
            element = '#CIVICRM_QFID_1_2';
            await utils.findElement(page, element);
            await page.locator(element).click();
            await expect(page.locator('#confirmEmail')).toBeVisible();

            /* filled up Confirm From Name */
            element = '#confirm_from_name';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), 'Name For Confirm');

            /* Select Option of Confirm From Email */
            element = '#confirm_from_email';
            await utils.findElement(page, element);
            locator = page.locator(element);
            await expect(locator).toBeEnabled();
            await locator.selectOption({ index: await locator.locator('option').count() - 1 });
            await expect(locator).not.toHaveValue('');

            /* click Save */
            element = "#_qf_Registration_upload-bottom";
            await utils.findElement(page, element);
            await page.locator(element).click();
            await expect(page.locator('.crm-error')).toHaveCount(0);

        });

        /* Tell a Friend */
        await test.step('Tell a Friend.', async () =>{

            /* click Online Registration */
            element = 'li#tab_friend a';
            await utils.findElement(page, element);
            await page.locator(element).click();
            await expect(page.locator('form#Event')).not.toHaveCount(0);

            /* click Allow Tell a Friend? */
            element = '#tf_is_active';
            await utils.findElement(page, element);
            await utils.checkInput(page, page.locator(element));

            /* click Save */
            element = "#_qf_Event_upload-bottom";
            await utils.findElement(page, element);
            await page.locator(element).click();
            await expect(page.locator('.crm-error')).toHaveCount(0);

        });

    });

});