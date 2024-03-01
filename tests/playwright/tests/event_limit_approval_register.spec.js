const { test, expect, chromium } = require('@playwright/test');
const utils = require('./utils.js');

/** @type {import('@playwright/test').Page} */
let page;
let page_title;
const wait_secs = 2000;

test.beforeAll(async () => {
  const browser = await chromium.launch();
  page = await browser.newPage();
  page_title =  await utils.getPageTitle('有名額限制，需事先審核');
  await utils.setParticipantNum(page, page_title, 4);
  //logout
  await page.goto('/user/logout');
});

test.afterAll(async () => {
  await utils.reLogin(page);
  await page.close();
});



test('limit participants. Need approval', async () => {
    await test.step('First register success and second participant message is correct', async () => {
      await page.goto('/civicrm/event/register?cid=0&reset=1&id=4');
      await expect(page).toHaveTitle(page_title);
      await utils.fillForm(utils.makeid(5) + '@fakemailevent5.com', page);
      await page.locator('text=/.*Continue \\>\\>.*/').click();
      await utils.wait(wait_secs);
      await expect(page).toHaveURL(/_qf_ThankYou_display/);
      await expect(page.locator('#help p')).toBeDefined();
      await expect(page.locator('.bold')).toContainText([/審核|reviewed/i]);
      await page.locator('.event_info_link-section a').click();
      await expect(page).toHaveTitle(page_title);
      await expect(page.locator('.messages.status')).toBeDefined();
      await expect(page.locator('#crm-container > div.messages.status')).toContainText([/額滿|full/i]);
    });
});