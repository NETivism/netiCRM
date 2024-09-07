const { test, expect, chromium } = require('@playwright/test');

/** @type {import('@playwright/test').Page} */
let page;
const priceValue = 200;
const discountedFee = 1;
const total = priceValue - discountedFee;

test.beforeAll(async () => {
    const browser = await chromium.launch();
    page = await browser.newPage();
  });
  
test.afterAll(async () => {
  await page.close();
});

test.describe.serial('Using coupon in the event page', () => {
  test('Add price set', async() => {
    await page.goto('civicrm/admin/price?reset=1&action=add');
    await expect(page).toHaveTitle('New Price Set | netiCRM');
    await page.getByLabel('Set Name\n     *').fill('test coupon');
    await page.getByLabel('Event').check();
    await page.locator('[id="_qf_Set_next-bottom"]').click();
    await page.getByLabel('Field Label\n     *').fill('A');
    await page.getByLabel('Price').fill(`${priceValue}`);
    await page.locator('[id="_qf_Field_next-bottom"]').click();
    await expect(page.locator('#crm-container')).toContainText('Price Field \'A\' has been saved.');
  });
  test('Create a new event and set the configure of fee and online registeration', async() => {
    await page.goto('civicrm/event/add?reset=1&action=add');
    await expect(page).toHaveTitle('New Event | netiCRM');
    // create new event
    await page.getByLabel('Event Type\n     *').selectOption('1');
    await page.getByLabel('Participant Role\n     *').selectOption('2');
    await page.getByLabel('Event Title\n     *').fill('test coupon');
    await page.locator('[id="_qf_EventInfo_upload-bottom"]').click();
    await expect(page).toHaveTitle('test coupon - Configure Event | netiCRM');
    // set fee
    await page.getByRole('link', { name: 'Fees' }).click();
    await page.getByLabel('Yes').check();
    await page.locator('select[name="currency"]').selectOption('TWD');
    await page.getByLabel('Contribution Type').selectOption('1');
    await page.getByLabel('Enable Pay Later option?').check();
    await page.getByLabel('Pay Later Instructions').fill('test');
    await page.getByLabel('Price Set').selectOption('test coupon');
    await page.locator('[id="_qf_Fee_upload-bottom"]').click();
    await expect(page.getByText('\'Fee\' information has been saved.')).toBeVisible();
    // open online registration
    await page.getByRole('link', { name: 'Online Registration' }).click();
    await page.getByLabel('Allow Online Registration?').check();
    await page.locator('[id="_qf_Registration_upload-bottom"]').click();
    await expect(page.getByText('\'Registration\' information has been saved.')).toBeVisible();
  });
  test('Create a new coupon of the event', async() => {
    await page.goto('civicrm/admin/coupon');
    await expect(page).toHaveTitle('Coupon | netiCRM');
    await page.getByRole('link', { name: 'Add Coupon' }).first().click();
    await page.getByLabel('Coupon Code\n     *').fill('testCoupon');
    await page.getByLabel('Description\n     *').fill('test');
    await page.getByRole('row', { name: 'Limited on Events' }).getByPlaceholder('-- Select --').click();
    await page.locator('#select2-civicrm_event-results > li').nth(0).click();
    await page.getByLabel('Discounted Fees*').fill(`${discountedFee}`);
    await page.getByLabel('Minimum Amount\n     *').fill('1');
    await page.getByRole('button', { name: 'Save' }).click();
    await expect(page.getByRole('table')).toContainText('testCoupon');
    // Use the coupon and check if it works
    // enter the event configue page
    await page.getByRole('link', { name: 'test coupon' }).click();
    const page2Promise = page.waitForEvent('popup');
    const page2 = await page2Promise;
    await expect(page2).toHaveTitle('test coupon - Configure Event | netiCRM');
    // enter event link page
    await page2.getByRole('link', { name: 'Event Links' }).click();
    await expect(page2).toHaveTitle('test coupon - Event Links | netiCRM');
    // enter online registration
    const page3Promise = page2.waitForEvent('popup');
    await page2.getByRole('link', { name: 'Online Registration (Test-drive)' }).click();
    const page3 = await page3Promise;
    await page3.getByRole('link', { name: 'Registering Yourself (admin@example.com)' }).click();
    // use coupon
    await page3.getByPlaceholder('Please enter Quantity').fill('1');
    await page3.getByPlaceholder('Enter coupon code').fill('testCoupon');
    await page3.locator('[id="\\#coupon_valid"]').click();
    await page3.getByRole('button', { name: 'Continue >>' }).click();
    await expect(page3).toHaveTitle('Confirm Your Registration Information | netiCRM');
    await expect(page3.getByText(`Total Amount: NT$ ${total}.00`)).toBeVisible();
  });
});
