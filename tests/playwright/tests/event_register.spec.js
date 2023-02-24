const { test, expect, chromium } = require('@playwright/test');

/** @type {import('@playwright/test').Page} */
let page;

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

async function fillInput(locator, text_input){
  await expect(locator).toBeEnabled();
  await locator.click();
  await locator.fill(text_input);
  await expect(locator).toHaveValue(text_input);
}

async function fillForm(email='test@aipvo.com', first_name='user', last_name='test', phone='0222233311', current_employer='test company', form_selector='form#Register'){
  
  await expect(page.locator(form_selector)).toBeDefined();

  var locator = page.locator('input[name="email-5"]')
  await fillInput(locator, email);

  locator = page.locator('input[name="first_name"]');
  await fillInput(locator, first_name);

  locator = page.locator('input[name="last_name"]');
  await fillInput(locator, last_name);

  locator = page.locator('input[name="phone-1-2"]');
  await fillInput(locator, phone);

  locator = page.locator('input[name="current_employer"]');
  await fillInput(locator, current_employer);
}

test.beforeAll(async () => {
  const browser = await chromium.launch({"headless": false});
  page = await browser.newPage();
});

test.afterAll(async () => {
  await page.close();
});

test.describe.serial('Event register page', () => {
  test.use({ storageState: 'storageState.json' });
  test('Normal registration', async () => {
    var page_title = getPageTitle(item.event_name_1);
    await test.step("Check can visit page.", async () =>{
      await page.goto('/civicrm/event/register?reset=1&action=preview&id=1&cid=0');
      await expect(page).toHaveTitle(page_title);
    })
    await test.step("Check register event.", async () =>{
      await fillForm();
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
      await fillForm('test@kvien.com');
      await page.locator('text=/.*Continue \\>\\>.*/').click();
      await expect(page).toHaveURL(/_qf_ThankYou_display/);
      await expect(page.locator('#help .msg-register-success')).toBeDefined();
    });
    await test.step('Second participant message is correct.', async () => {
      await page.goto('/civicrm/event/register?cid=0&reset=1&id=2');
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
      await fillForm('test@ovoqnj.com');
      await page.locator('text=/.*Continue \\>\\>.*/').click();
      await expect(page).toHaveURL(/_qf_ThankYou_display/);
      await expect(page.locator('#help p')).toBeDefined();
    });
    await test.step('Check message hvae wait list', async () => {
      await page.goto('/civicrm/event/register?cid=0&reset=1&id=3');
      await expect(page).toHaveTitle(page_title);
      await fillForm('test2@soossovk.com');
      await page.locator('text=/.*Continue \\>\\>.*/').click();
      await expect(page.locator('.bold')).toContainText([/候補|wait list/i]);
    });
  });

  test('limit participants. Need approval', async () => {
    var page_title = getPageTitle(item.event_name_4);
    await test.step('First register success', async () => {
      await page.goto('/civicrm/event/register?cid=0&reset=1&id=4');
      await expect(page).toHaveTitle(page_title);
      await fillForm('test@vkioob.com');
      await page.locator('text=/.*Continue \\>\\>.*/').click();
      await expect(page).toHaveURL(/_qf_ThankYou_display/);
      await expect(page.locator('#help p')).toBeDefined();
      await expect(page.locator('.bold')).toContainText([/審核|reviewed/i]);
    });
    await test.step('Second participant message is correct.', async () => {
      await page.goto('/civicrm/event/register?cid=0&reset=1&id=4');
      await expect(page).toHaveTitle(page_title);
      await expect(page.locator('.messages.status')).toBeDefined();
      await expect(page.locator('#crm-container > div.messages.status')).toContainText([/額滿|full/i]);
    });    
  });

  test('Unlimit participants. Need approval', async () => {
    var page_title = getPageTitle(item.event_name_5);
    await test.step("Verity can register event.", async () =>{
      await page.goto('/civicrm/event/register?reset=1&action=preview&id=5&cid=0');
      await expect(page).toHaveTitle(page_title);
      await fillForm('test@vkioob.com');
      await page.locator('text=/.*Continue \\>\\>.*/').click();
      await expect(page).toHaveURL(/_qf_ThankYou_display/);
      await expect(page.locator('#help p')).toBeDefined();
      await expect(page.locator('.bold')).toContainText([/審核|reviewed/i]);
    })
    await test.step("Second participant message is correct", async () =>{
      await page.goto('/civicrm/event/register?reset=1&action=preview&id=5&cid=0');
      await expect(page).toHaveTitle(page_title);
      await fillForm('test2@aipvo.com');
      await page.locator('text=/.*Continue \\>\\>.*/').click();
      await expect(page).toHaveURL(/_qf_ThankYou_display/);
      await expect(page.locator('#help p')).toBeDefined();
      await expect(page.locator('.bold')).toContainText([/審核|reviewed/i]);
    })
  });
});