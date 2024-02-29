const { test, expect, chromium } = require('@playwright/test');
const utils = require('./utils.js');

/** @type {import('@playwright/test').Page} */
let page;
const wait_secs = 2000;
let page_title;


test.beforeAll(async () => {
  const browser = await chromium.launch();
  page = await browser.newPage();
  page_title = await utils.getPageTitle('有名額限制，不開放候補');
  await utils.setParticipantNum(page, page_title, 2, '5', '4');
  // logout
  await page.goto('/user/logout');
});

test.afterAll(async () => {
  // re-login
  await utils.reLogin(page);
  await page.close();
});



test('limit participants. Not for waiting', async() => {
  await test.step('Check can register and second participant message is correct.', async () => {
    await page.goto('/civicrm/event/register?cid=0&reset=1&id=2');
    await expect(page).toHaveTitle(page_title);
    const email = await utils.makeid(5) + '@fakemailevent2.com';
    await utils.fillForm(email , page);
    await page.locator('text=/.*Continue \\>\\>.*/').click();
    await utils.wait(wait_secs);
    await expect(page).toHaveURL(/_qf_ThankYou_display/);
    await expect(page.locator('#help .msg-register-success')).toBeDefined();
    await page.locator('.event_info_link-section a').click();
    await expect(page).toHaveTitle(page_title);
    await expect(page.locator('.messages.status')).toBeDefined();
    await expect(page.locator('#crm-container > div.messages.status')).toContainText([/額滿|full/i]);
  });
});