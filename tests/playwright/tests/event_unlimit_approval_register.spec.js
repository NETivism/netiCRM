const { test, expect, chromium } = require('@playwright/test');
const utils = require('./utils.js');

/** @type {import('@playwright/test').Page} */
let page;
let page_title;
const wait_secs = 2000;

test.beforeAll(async () => {
  const browser = await chromium.launch();
  page = await browser.newPage();
  page_title =  await utils.getPageTitle('無名額限制，需事先審核');
  //logout
  await utils.logoutUser(page);
});

test.afterAll(async () => {
  // re-login
  await utils.reLogin(page);
  await page.close();
});



test('Unlimit participants. Need approval', async () => {
    await test.step("Verity can register event.", async () =>{
      await page.goto('/civicrm/event/register?reset=1&id=5&cid=0');
      await expect(page).toHaveTitle(page_title);
      await utils.fillForm(utils.makeid(5) + '@fakemailevent6.com' , page);
      await page.locator('text=/.*Continue \\>\\>.*/').click();
      await expect(page).toHaveURL(/_qf_ThankYou_display/);
      await expect(page.locator('#help p')).toBeDefined();
      await expect(page.locator('.bold')).toContainText([/審核|reviewed/i]);
    });
    await test.step("Second participant message is correct", async () =>{
      await page.goto('/civicrm/event/register?reset=1&id=5&cid=0');
      await expect(page).toHaveTitle(page_title);
      await utils.fillForm(utils.makeid(5) + '@fakemailevent7.com', page);
      await page.locator('text=/.*Continue \\>\\>.*/').click();
      await expect(page).toHaveURL(/_qf_ThankYou_display/);
      await expect(page.locator('#help p')).toBeDefined();
      await expect(page.locator('.bold')).toContainText([/審核|reviewed/i]);
    });
  });
