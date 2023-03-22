// global-setup.js
const { expect,chromium } = require('@playwright/test');

module.exports = async config => {
  const browser = await chromium.launch();
  const page = await browser.newPage();

  const { baseURL, storageState } = config.projects[0].use;
  await page.goto(baseURL);
  await page.locator('input[name="name"]').fill(process.env.adminUser);
  await page.locator('input[name="pass"]').fill(process.env.adminPwd);
  await page.locator('input[value="Log in"]').click();

  // Save signed-in state to 'storageState.json'.
  await page.context().storageState({ path: storageState });
  await expect(page).toHaveTitle(/Welcome[^|]+ \| netiCRM/);
  await browser.close();
};
