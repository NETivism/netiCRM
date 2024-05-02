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


test.describe.serial('Report Check', () => {
    test('Check report pages', async() => {
        var title_locator;
        var url_locator, url_text, report_content_locator;
        var err_message_locator, err_message;
        for (let i=1; i<=26; i++){
            await page.goto('/civicrm/report/list?reset=1');
            await expect(page.locator('#crm-container>div>div>div.crm-accordion-header').first()).toContainText('Contact Report');
            // click the url button
            url_locator = `#row_${i}>td>a>strong`;
            url_text = await page.locator(url_locator).textContent();
            await page.locator(url_locator).click();
            title_locator = '#block-olivero-page-title';
            await expect(page.locator(title_locator)).toContainText(url_text);
            console.log('Enter ', url_text, ' page.');
            
            // check page content
            report_content_locator = '#crm-container>form>div>div>div>div.crm-accordion-header';
            await expect(page.locator(report_content_locator)).not.toHaveCount(0);
            // check error message and print button
            err_message_locator = '#crm-container>form>div>div.messages.status';
            err_message = await page.locator(err_message_locator).count();
            if (err_message != 0){
                // error message
                console.log('%d. there is an error message, skip.', i);
                continue;
            }
            else{
                // no error message and press print button
                console.log('%d. there is no error message.', i);
                await page.getByText('Print Report', {exact: true}).click();
                // await expect(page).toHaveURL(/instance/g);
                await expect(page.locator('#crm-container>div>h1')).toHaveText(url_text);
            }
        }
    });
});