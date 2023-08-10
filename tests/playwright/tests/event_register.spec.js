const { test, expect, chromium } = require('@playwright/test');
const utils = require('./utils.js');

/** @type {import('@playwright/test').Page} */
let page;
const wait_secs = 2000;

var item = {
  event_name_1: '無名額限制，填表完成送出',
  event_name_2: '有名額限制，不開放候補',
  event_name_3: '有名額限制，開放候補',
  event_name_4: '有名額限制，需事先審核',
  event_name_5: '無名額限制，需事先審核',
  site_name: 'netiCRM'
}

function getPageTitle(title){
  return title + " | " + item.site_name;
}

async function fillForm(email='test@aipvo.com', first_name='user', last_name='test', phone='0222233311', current_employer='test company', form_selector='form#Register'){

  await expect(page.locator(form_selector)).toBeDefined();

  var locator = page.locator('input[name="email-5"]')
  await utils.fillInput(locator, email);

}


test.beforeAll(async () => {
  const browser = await chromium.launch();
  page = await browser.newPage();
});

test.afterAll(async () => {
  await page.close();
});


test.describe.serial('Event register page', () => {
  test('Normal registration', async () => {
    var page_title = getPageTitle(item.event_name_1);
    await test.step("Check can visit page.", async () =>{
      await page.goto('/civicrm/event/register?reset=1&id=1&cid=0');
      await expect(page).toHaveTitle(page_title);
    })
    await test.step("Check register event.", async () =>{
      await fillForm(utils.makeid(5) + '@fakemailevent1.com');
      await page.locator('text=/.*Continue \\>\\>.*/').click();
      await expect(page).toHaveURL(/_qf_ThankYou_display/);
      await expect(page.locator('#help .msg-register-success')).toBeDefined();
    })
  });

  test('limit participants. Not fot waiting', async() => {
    var page_title = getPageTitle(item.event_name_2);
    await test.step('Check can register', async () => {
      await page.goto('/civicrm/event/register?cid=0&reset=1&id=2');
      await expect(page).toHaveTitle(page_title);
      await fillForm(utils.makeid(5) + '@fakemailevent2.com');
      await page.locator('text=/.*Continue \\>\\>.*/').click();
      await expect(page).toHaveURL(/_qf_ThankYou_display/);
      await expect(page.locator('#help .msg-register-success')).toBeDefined();
    });

    await test.step('Second participant message is correct.', async () => {
      await page.locator('.event_info_link-section a').click();
      await expect(page).toHaveTitle(page_title);
      await expect(page.locator('.messages.status')).toBeDefined();
      await expect(page.locator('#crm-container > div.messages.status')).toContainText([/額滿|full/i]);
    });
  });

  test('limit participants. Not fot waiting.', async () => {
    var page_title = getPageTitle(item.event_name_3);
    await test.step('Check can register', async () => {
      await page.goto('/civicrm/event/register?cid=0&reset=1&id=3');
      await expect(page).toHaveTitle(page_title);
      await fillForm(utils.makeid(5) + '@fakemailevent3.com');
      await page.locator('text=/.*Continue \\>\\>.*/').click();
      await expect(page).toHaveURL(/_qf_ThankYou_display/);
      await expect(page.locator('#help p')).toBeDefined();
    });
    await test.step('Check message hvae wait list', async () => {
      await page.goto('/civicrm/event/register?cid=0&reset=1&id=3');
      await expect(page).toHaveTitle(page_title);
      await fillForm(utils.makeid(5) + '@fakemailevent4.com');
      await page.locator('text=/.*Continue \\>\\>.*/').click();
      await expect(page.locator('.bold')).toContainText([/候補|wait list/i]);
    });
  });

  test('limit participants. Need approval', async () => {
    var page_title = getPageTitle(item.event_name_4);
    await test.step('First register success', async () => {
      await page.goto('/civicrm/event/register?cid=0&reset=1&id=4');
      await expect(page).toHaveTitle(page_title);
      await fillForm(utils.makeid(5) + '@fakemailevent5.com');
      await page.locator('text=/.*Continue \\>\\>.*/').click();
      await expect(page).toHaveURL(/_qf_ThankYou_display/);
      await expect(page.locator('#help p')).toBeDefined();
      await expect(page.locator('.bold')).toContainText([/審核|reviewed/i]);
    });
    await test.step('Second participant message is correct.', async () => {
      await page.locator('.event_info_link-section a').click();
      await expect(page).toHaveTitle(page_title);
      await expect(page.locator('.messages.status')).toBeDefined();
      await expect(page.locator('#crm-container > div.messages.status')).toContainText([/額滿|full/i]);
    });
  });

  test('Unlimit participants. Need approval', async () => {
    var page_title = getPageTitle(item.event_name_5);
    await test.step("Verity can register event.", async () =>{
      await page.goto('/civicrm/event/register?reset=1&id=5&cid=0');
      await expect(page).toHaveTitle(page_title);
      await fillForm(utils.makeid(5) + '@fakemailevent6.com');
      await page.locator('text=/.*Continue \\>\\>.*/').click();
      await expect(page).toHaveURL(/_qf_ThankYou_display/);
      await expect(page.locator('#help p')).toBeDefined();
      await expect(page.locator('.bold')).toContainText([/審核|reviewed/i]);
    })
    await test.step("Second participant message is correct", async () =>{
      await page.goto('/civicrm/event/register?reset=1&id=5&cid=0');
      await expect(page).toHaveTitle(page_title);
      await fillForm(utils.makeid(5) + '@fakemailevent7.com');
      await page.locator('text=/.*Continue \\>\\>.*/').click();
      await expect(page).toHaveURL(/_qf_ThankYou_display/);
      await expect(page.locator('#help p')).toBeDefined();
      await expect(page.locator('.bold')).toContainText([/審核|reviewed/i]);
    })
  });
});