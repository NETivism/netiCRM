const { test, expect } = require('@playwright/test');
const port = process.env.RUNPORT;
const baseURL = port == '' ? 'http://127.0.0.1/' : 'http://127.0.0.1:' + port + '/';
console.log(baseURL);

test.describe('This is sample test', () => {
  test('Visit site front page', async ({ page }) => {
    await page.goto(baseURL);
    await expect(page).toHaveTitle(/^Welcome to netiCRM/);
    await page.screenshot({ path: 'test-results/screenshot-1.png', fullPage: true });
  });
});
