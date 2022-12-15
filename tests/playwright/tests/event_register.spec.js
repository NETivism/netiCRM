const { test, expect, chromium } = require('@playwright/test');

/** @type {import('@playwright/test').Page} */
let page;

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
    await page.goto('/civicrm/event/register?reset=1&action=preview&id=1&cid=0');
    await page.locator('input[name="email-5"]').click();
    await page.locator('input[name="email-5"]').fill('test@aipvo.com');
    await page.locator('text=/.*Continue \\>\\>.*/').click();
    await expect(page).toHaveTitle('無名額限制，填表完成送出 | netiCRM');
  });

  test.describe('limit participants. Not fot waiting', () => {
    test('Check can register', async () => {
      await page.goto('/civicrm/event/register?reset=1&action=preview&id=2&cid=0');
      await page.locator('input[name="email-5"]').click();
      await page.locator('input[name="email-5"]').fill('test@aipvo.com');
      await page.locator('text=/.*Continue \\>\\>.*/').click();
      await expect(page).toHaveTitle('有名額限制，不開放候補 | netiCRM');
    });
  });

  test('Event register page test :limit participants. Not fot waiting.', async () => {
    await page.goto('/civicrm/event/register?reset=1&action=preview&id=3&cid=0');
    await page.locator('input[name="email-5"]').click();
    await page.locator('input[name="email-5"]').fill('test@aipvo.com');
    await page.locator('text=/.*Continue \\>\\>.*/').click();
    await expect(page).toHaveTitle('有名額限制，開放候補 | netiCRM');
  });

  test('limit participants. Need approval..', async () => {
    await page.goto('/civicrm/event/register?reset=1&action=preview&id=4&cid=0');
    await page.locator('input[name="email-5"]').click();
    await page.locator('input[name="email-5"]').fill('test@aipvo.com');
    await page.locator('text=/.*Continue \\>\\>.*/').click();
    await expect(page).toHaveTitle('有名額限制，需事先審核 | netiCRM');
  });
});


