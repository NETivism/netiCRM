const { test, expect, chromium } = require('@playwright/test');
const utils = require('./utils.js');

/** @type {import('@playwright/test').Page} */
let page;
var locator;


var first_name = utils.makeid(5);
var last_name = utils.makeid(5);

var vars = {
  first_name: first_name,
  last_name: last_name,
  user_email: first_name.toLowerCase() + last_name.toLowerCase() + '123@gmail.com',
  user_phone: '09' + Math.floor(Math.random() * 100000000).toString()
};


test.beforeAll(async () => {
  const browser = await chromium.launch();
  page = await browser.newPage();
});

test.afterAll(async () => {
  await page.close();
});


test.describe.serial('Add Contact', () => {
  test.use({ storageState: 'storageState.json' });
  test('Add Contact test', async () => {

    await test.step('Add Individual.', async () =>{
      await page.goto('/civicrm/contact/add?reset=1&ct=Individual');
      await expect(page.locator('form[name=Contact]'), 'form[name=Contact] is undefined.').toBeDefined();
      
      locator = page.locator("input[name='last_name']");
      await utils.fillInput(locator, vars.last_name);
      console.log("Add lastname.");

      locator = page.locator("input[name='first_name']");
      await utils.fillInput(locator, vars.first_name);
      console.log("Add firstname.");

      locator = page.locator('#email_1_email');
      await utils.fillInput(locator, vars.user_email);
      console.log("Add email.");

      locator = page.locator('#phone_1_phone');
      await utils.fillInput(locator, vars.user_phone);
      console.log("Add phone.");

      await page.locator('#phone_1_phone_type_id').selectOption('1');
      console.log("Change phone type.");

      await page.locator("form[name=Contact] input[type=submit][value='Save']").nth(0).click();
      
      await expect(page).toHaveURL(/civicrm\/contact\/view\?reset=1&cid=/);
      await expect(page.locator('.crm-error')).toHaveCount(0);
    });

    await test.step('Check If Personal Information Correct.', async () =>{
      var page_title = `${vars.first_name} ${vars.last_name} | netiCRM`;
      await expect(page).toHaveTitle(page_title);
      console.log(`Page title is: "${page_title}"`);
      await expect(page.locator('#contact-summary .contactCardLeft a'), `Element(#contact-summary .contactCardLeft a) doesn't have text("${vars.user_email}").`).toHaveText(vars.user_email);
      console.log('Subject(Email) equals the expected value');
      await expect(page.locator('#contact-summary .contactCardRight .primary span'), `Element(#contact-summary .contactCardRight .primary span) doesn't have text("${vars.user_phone}").`).toHaveText(vars.user_phone);
      console.log('Subject(Phone) equals the expected value');
    });

  });
});