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



test('limit participants. Not for waiting', async() => {
  await test.step('Delete participants', async()=>{
    const response = await page.goto('/civicrm/event/search?reset=1&force=1&event=2');
    await expect(response.status()).toBe(200);
    const before_people = await page.locator('div#stat_ps>div#stat_ps_label1>ol>li>div>span.people-count').first().textContent();
    console.log('before people:', before_people);
    // check the number of participants
    if (before_people == '5'){
      // capture the newest one
      // const newest_element = await page.locator('div#participantSearch>table>tbody>tr').first();
      // console.log('the newest one locator is:', newest_element);
      await page.locator('div#participantSearch>table>tbody>tr').first().getByRole('link', { name: 'Delete' }).click();
      await page.locator('[id="_qf_Participant_next-bottom"]').click();
    }
    // check whether delete sucessfully
    await expect(page.locator('div#stat_ps>div#stat_ps_label1>ol>li>div>span.people-count').first()).toHaveText('4');
  })
  var page_title =  await utils.getPageTitle('有名額限制，不開放候補');
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