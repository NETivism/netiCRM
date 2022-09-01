const { test, expect, chromium } = require('@playwright/test');

test('Event register page test',async () => {
  const browser = await chromium.launch({
    headless: false
  });
  const context = await browser.newContext();

  // Open new page
  const page = await context.newPage();

  await page.goto('/civicrm/event/register?reset=1&action=preview&id=1&cid=0');

  await page.locator('input[name="email-5"]').click();
  await page.locator('input[name="email-5"]').fill('test@aipvo.com');
  await page.locator('text=/.*Continue \\>\\>.*/').click();
  await expect(page).toHaveTitle('無名額限制，填表完成送出 | netiCRM');

  // ---------------------
  await context.close();
  await browser.close();
});