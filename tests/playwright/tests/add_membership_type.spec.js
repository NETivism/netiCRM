
const { test, expect, chromium } = require('@playwright/test');
const utils = require('./utils.js');

/** @type {import('@playwright/test').Page} */
let page;
const wait_secs = 2000;

test.beforeAll(async () => {
    const browser = await chromium.launch();
    page = await browser.newPage();
});

test.afterAll(async () => {
    await page.close();
});

test.describe.serial('Create Membership Type', () => {
    var organization = utils.makeid(10);
    var membership_type = 'typeForTest';
    test('Create Organization Contact', async () => {
        await page.goto('civicrm/contact/add?reset=1&ct=Organization');
        await utils.wait(wait_secs);

        await page.getByLabel('Organization Name').fill(organization);
        await page.locator("form[name=Contact] input[type=submit][value='Save']").first().click();
        await utils.wait(wait_secs);
        await expect(page).toHaveTitle(new RegExp('^'+organization));
    });

    test('Create Membership Type', async () => {
        await page.goto('civicrm/admin/member/membershipType?action=add&reset=1');
        await utils.wait(wait_secs);

        await page.getByLabel('Name\n     *').click();
        await page.getByLabel('Name\n     *').fill(membership_type);
        await page.getByLabel('Membership Organization').fill(organization);
        await page.locator('#_qf_MembershipType_refresh').click();
        await utils.wait(wait_secs);

        await page.getByRole('combobox', { name: 'Contribution Type' }).selectOption('2');
        await page.locator('#duration_interval').click();
        await page.locator('#duration_interval').fill('1');
        await page.getByRole('combobox', { name: 'Duration' }).selectOption('year');
        await page.getByRole('combobox', { name: 'Period Type' }).selectOption('rolling');
        await page.locator('[id="_qf_MembershipType_upload-bottom"]').click();
        await expect(page.getByRole('cell', { name: membership_type })).toHaveText(membership_type);
    });

});