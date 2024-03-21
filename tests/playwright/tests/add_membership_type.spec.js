
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
    var membership_type = utils.makeid(5);
    var element;
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
    test('Create Membership', async ()=> {
        var firstName = 'test_firstName';
        var lastName = 'test_lastName';
        var name = firstName + ' ' + lastName;
        // go to create membership page
        await page.goto('/civicrm/member/add?reset=1&action=add&context=standalone');
        
        // create individual data
        await page.locator('#profiles_1').selectOption('4');
        await expect(page.getByRole('dialog')).toBeVisible();

        element = await utils.findElementByLabel(page, '名字\n     *');
        await utils.fillInput(element, firstName);
        element = await utils.findElementByLabel(page, '姓氏\n     *');
        await utils.fillInput(element, lastName);
        await page.locator('#_qf_Edit_next').click();
        await expect(page.locator('#contact_1')).toHaveValue(name);

        // select the first option in the membership type and orginization
        element = '[id="membership_type_id\\[0\\]"]';
        await utils.selectOption(page.locator(element), { index: 0 }); 
        element = '[id="membership_type_id\\[1\\]"]';
        await utils.selectOption(page.locator(element), { index: 1 }); 
        // pick the first date
        await page.locator('#join_date').click();
        await page.getByRole('link', { name: '1', exact: true }).click();
        await page.locator('#start_date').click();
        await page.getByRole('link', { name: '1', exact: true }).click();
        await page.locator('[id="_qf_Membership_upload-bottom"]').click();
        await utils.wait(wait_secs);
        await expect(page).toHaveTitle(name + ' | netiCRM');
        await expect(page.locator('#option11>tbody')).toContainText(membership_type);
    });
});