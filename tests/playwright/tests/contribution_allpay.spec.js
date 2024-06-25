const { test, expect, chromium } = require('@playwright/test');
const utils = require('./utils.js');

/** @type {import('@playwright/test').Page} */
let page;
let element;

var vars = {
    test_num: 11,
    path: 'civicrm/contribute/transact',
    query: 'reset=1&action=preview&id=1',
    site_name: 'netiCRM',

    // you should add your own testing variables below
    page_title: '捐款贊助',
    user_email: 'youremail@test.tw',
    first_name: utils.makeid(3),
    last_name: utils.makeid(3),
    amount: '101',
    ECPay_title: '選擇支付方式|綠界科技',
};


test.beforeAll(async () => {
    const browser = await chromium.launch();
    page = await browser.newPage();
});

test.afterAll(async () => {
    await page.close();
});


test.describe.serial('ALLPAY', () => {

    test.use({ storageState: 'storageState.json' });

    test('Payment', async () => {

        await test.step('Contribution page', async () => {

            /* Step 1: Contribution page */
            await page.goto('/user/logout');
            await page.goto(`${vars.path}?${vars.query}`);
            const page_title = `${vars.page_title} | ${vars.site_name}`;
            await expect(page).toHaveTitle(page_title);
            await utils.findElement(page, 'form#Main');
            await utils.findElement(page, 'div.crm-contribution-main-form-block');

            element = 'input[name="amount_other"]';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), vars.amount);

            element = await utils.findElementByLabel(page, 'ALLPAY金流');
            await utils.clickElement(page, element);
            await expect(element).toBeChecked();

            element = 'input[name="email-5"]';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), vars.user_email, true);

            element = 'input[name="first_name"]';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), vars.first_name, true);

            element = 'input[name="last_name"]';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), vars.last_name, true);

            element = 'input[name="custom_1"][value="0"]';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element));

            element = 'input[name="receipt_name"][value="r_name_full"]';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element));

            element = '#_qf_Main_upload-bottom';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element), {notExist: '.crm-error'});

        });

        await test.step('Contribution Confirm', async () => {

            /* Step 2: Contribution Confirm */
            await page.waitForURL(/_qf_Confirm_display/);
            await expect(page).toHaveURL(/_qf_Confirm_display=true/);

            element = '.amount_display-group .display-block strong';
            await utils.findElement(page, element);
            await expect(page.locator(element).first()).toHaveText(`$ ${vars.amount}.00`);

            element = '.contributor_email-section .content';
            await utils.findElement(page, element);
            await expect(page.locator(element)).toHaveText(vars.user_email);

            element = '.first_name-section .content';
            await utils.findElement(page, element);
            await expect(page.locator(element)).toHaveText(vars.first_name);

            element = '.last_name-section .content';
            await utils.findElement(page, element);
            await expect(page.locator(element)).toHaveText(vars.last_name);

            element = '#_qf_Confirm_next-bottom';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element), {notExist: '.crm-error'});

        });

        await test.step('ECPay Payment Page Check', async () => {
            /* Step 3: ECPay Payment Page Check */
            await page.waitForURL('https://payment-stage.ecpay.com.tw/Cashier/AioCheckOut');
            await expect(page).toHaveURL(/https:\/\/payment-stage\.ecpay\.com\.tw/);
        });

        await test.step('Re-login', async () => {
            /* Step 4: Re-login */
            await page.goto('/');
            await page.locator('input[name="name"]').fill(process.env.adminUser);
            await page.locator('input[name="pass"]').fill(process.env.adminPwd);
            await page.locator('input[value="Log in"]').click();

            // Save signed-in state to 'storageState.json'.
            await page.context().storageState({ path: 'storageState.json' });
            await expect(page).toHaveTitle(/Welcome[^|]+ \| netiCRM/);

        });

    });

});