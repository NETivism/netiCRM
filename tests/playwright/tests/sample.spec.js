const { test, expect, chromium } = require('@playwright/test');

/** @type {import('@playwright/test').Page} */
let page;

test.beforeAll(async () => {
  const browser = await chromium.launch();
  page = await browser.newPage();
});

test.afterAll(async () => {
  await page.close();
});

test.describe('This is sample test', () => {
  test('Visit site front page', async ({ page }) => {
    await page.goto('/');
    await expect(page).toHaveTitle(/Welcome.*netiCRM$/);
    await page.screenshot({ path: 'test-results/screenshot-1.png', fullPage: true });
  });
});