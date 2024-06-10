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

test('Normal registration', async () => {
  var page_title = await utils.getPageTitle('無名額限制，填表完成送出');
  await test.step("Check can visit page.", async () =>{
    await page.goto('/civicrm/event/register?reset=1&id=1&cid=0');
    await expect(page).toHaveTitle(page_title);
  })
  await test.step("Check register event.", async () =>{
    await utils.fillForm(utils.makeid(5) + '@fakemailevent1.com', page, 'form#Register');
    await page.locator('text=/.*Continue \\>\\>.*/').click();
    await expect(page).toHaveURL(/_qf_ThankYou_display/);
    await expect(page.locator('#help .msg-register-success')).toBeDefined();
  })
});


