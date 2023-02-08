const { test, expect, chromium } = require('@playwright/test');

/** @type {import('@playwright/test').Page} */
let page;

var item = {
  event_name_1: '無名額限制，填表完成送出',
  event_name_2: '有名額限制，不開放候補',
  event_name_3: '有名額限制，開放候補',
  event_name_4: '有名額限制，需事先審核',
  event_name_5: '無名額限制，需事先審核',
  site_name: 'netiCRM'
}

function getPageTitle(title){
  return title + " | "+item.site_name;
}

test.beforeAll(async () => {
  const browser = await chromium.launch({"headless": false});
  page = await browser.newPage();
});

test.afterAll(async () => {
  console.log('After tests');
  await page.close();
});

test.describe.serial('Event register page test', () => {
  test.use({ storageState: 'storageState.json' });
  test('Normal registration', async () => {
    var page_title = getPageTitle(item.event_name_1);
    await page.goto('/civicrm/event/register?reset=1&action=preview&id=1&cid=0');
    await expect(page).toHaveTitle(page_title);
    await page.locator('input[name="email-5"]').click();
    await page.locator('input[name="email-5"]').fill('test@aipvo.com');
    await page.locator('text=/.*Continue \\>\\>.*/').click();
    await expect(page).toHaveURL(/_qf_ThankYou_display/);
  });

  test.describe('limit participants. Not fot waiting', () => {
    var page_title = getPageTitle(item.event_name_2);
    test('Check can register', async () => {
      await page.goto('/civicrm/event/register?cid=0&reset=1&id=2');
      await expect(page).toHaveTitle(page_title);
      await page.locator('input[name="email-5"]').click();
      await page.locator('input[name="email-5"]').fill('test@aipvo.com');
      await page.locator('text=/.*Continue \\>\\>.*/').click();
      await expect(page).toHaveURL(/_qf_ThankYou_display/);
    });
    test('second participant message is correct.', async () => {
      await page.goto('/civicrm/event/register?cid=0&reset=1&id=2');
      await expect(page).toHaveTitle(page_title);
      await expect(page.locator('#crm-container > div.messages.status')).toContainText([/額滿|full/i]);
    });
  });

  test.describe('limit participants. Not fot waiting.', async () => {
    var page_title = getPageTitle(item.event_name_3);
    test('Check can register', async () => {
      await page.goto('/civicrm/event/register?cid=0&reset=1&id=3');
      await expect(page).toHaveTitle(page_title);
      await page.locator('input[name="email-5"]').click();
      await page.locator('input[name="email-5"]').fill('test@aipvo.com');
      await page.locator('text=/.*Continue \\>\\>.*/').click();
      await expect(page).toHaveURL(/_qf_ThankYou_display/);
    });
    test('Check message hvae wait list', async () => {
      await page.goto('/civicrm/event/register?cid=0&reset=1&id=3');
      await expect(page).toHaveTitle(page_title);
      await page.locator('input[name="email-5"]').click();
      await page.locator('input[name="email-5"]').fill('test2@soossovk.com');
      await page.locator('text=/.*Continue \\>\\>.*/').click();
      await expect(page.locator('.bold')).toContainText([/候補|wait list/i]);
    });
  });

  test.describe('limit participants. Need approval.', async () => {
    var page_title = getPageTitle(item.event_name_4);
    test('First register success', async () => {
      await page.goto('/civicrm/event/register?cid=0&reset=1&id=4');
      await expect(page).toHaveTitle(page_title);
      await page.locator('input[name="email-5"]').click();
      await page.locator('input[name="email-5"]').fill('test@aipvo.com');
      await page.locator('text=/.*Continue \\>\\>.*/').click();
      await expect(page).toHaveURL(/_qf_ThankYou_display/);
      await expect(page.locator('.bold')).toContainText([/審核|reviewed/i]);
    });
    test('Second participant message is correct.', async () => {
      await page.goto('/civicrm/event/register?cid=0&reset=1&id=4');
      await expect(page).toHaveTitle(page_title);
      await expect(page.locator('#crm-container > div.messages.status')).toContainText([/額滿|full/i]);
    });    
  });

  test('No limit participants. Need approval.', async () => {
    var page_title = getPageTitle(item.event_name_5);
    await page.goto('/civicrm/event/register?reset=1&action=preview&id=5&cid=0');
    
    //Verity login to event page.
    await expect(page).toHaveTitle(page_title);
    await page.locator('input[name="email-5"]').click();
    await page.locator('input[name="email-5"]').fill('test@aipvo.com');
    await page.locator('text=/.*Continue \\>\\>.*/').click();
    await expect(page).toHaveURL(/_qf_ThankYou_display/);

    //Verify register successful
    await expect(page.locator('.bold')).toContainText([/審核|reviewed/i]);
    await page.goto('/civicrm/event/register?reset=1&action=preview&id=5&cid=0');

    //Second register
    await expect(page).toHaveTitle(page_title);
    await page.locator('input[name="email-5"]').click();
    await page.locator('input[name="email-5"]').fill('test2@aipvo.com');
    await page.locator('text=/.*Continue \\>\\>.*/').click();
    await expect(page).toHaveURL(/_qf_ThankYou_display/);
    await expect(page.locator('.bold')).toContainText([/審核|reviewed/i]);
  });
});


