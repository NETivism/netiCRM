const { test, expect, chromium } = require('@playwright/test');
const utils = require('./utils.js');
const fs = require('fs');
const readline = require('readline');
/** @type {import('@playwright/test').Page} */
let page;
var locator, element;
const wait_secs = 2000;
let vars = {
    'urls': []
};
test.beforeAll(async () => {
    const browser = await chromium.launch();
    page = await browser.newPage();
});

test.afterAll(async () => {
    await page.close();
});
test.describe.serial('Version Check', () => {
    // test.use({ storageState: 'storageState.json' });
    test('Check Pages', async () => {
        const rl = readline.createInterface({
            input: fs.createReadStream('tests/files/sites.csv'),
            crlfDelay: Infinity
        });
        for await (const line of rl) {
            vars.urls.push(line.replace(/^,+|,+$/g, ''));
        }
        for (const url of vars.urls) {
            const userResponse = await page.goto('https://' + url + '/user');
            await utils.wait(wait_secs);
            await expect(userResponse.status()).toBe(200);
            await utils.print(`- ${await page.title()} -> Ok`);
            const mailingResponse = await page.goto('https://' + url + '/civicrm/mailing/subscribe?reset=1');
            await utils.wait(wait_secs);
            await expect(mailingResponse.status()).toBe(200);
            await utils.findElement(page, '#email');
            await utils.print(`- ${await page.title()} -> Ok`);
        }
    });
});