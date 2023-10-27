const { test, expect, chromium } = require('@playwright/test');
const utils = require('./utils.js');

/** @type {import('@playwright/test').Page} */
let page;
var locator, element;
const wait_secs = 2000;

const vars = {
    href_validation_data: [
        {el_id: 'first-time-donor', href_link: 'civicrm/search/FirstTimeDonor?force=1'},
        {el_id: 'half-year-donor', href_link: 'civicrm/search/HalfYearDonor?force=1'},
        {el_id: 'failed-no-further-donate', href_link: 'civicrm/search/FailedNoFurtherDonate?force=1'},
        {el_id: 'recur-donor', href_link: 'civicrm/search/RecurDonor'},
        {el_id: 'birthdate-search', href_link: 'civicrm/search/UpcomingBirthdays'},
        {el_id: 'contrib-sybnt', href_link: 'civicrm/search/ContribSYBNT?force=1'},
        {el_id: 'single-not-recurring', href_link: 'civicrm/search/SingleNotRecurring?force=1'},
        {el_id: 'recur-search', href_link: 'civicrm/search/RecurSearch?mode=booster&force=1'},
        {el_id: 'attendee-not-donor', href_link: 'civicrm/search/AttendeeNotDonor?force=1'}
    ],
};

async function check_and_set_accordionElement() {
    const accordionElement = await page.locator('.crm-accordion-wrapper.crm-custom_search_form-accordion.crm-accordion-processed');
    const isaccordionEletFound = await accordionElement.count();
    if (isaccordionEletFound > 0) {
        const classNames = await accordionElement.getAttribute('class');
        if (classNames.includes('crm-accordion-closed')) {
            element = '.crm-accordion-header';
            await utils.clickElement(page, page.locator(element));
        }
    }
}

async function check_page_title($verifty_title) {
    // For d10 env
    const titleElement = await page.locator('h1.page-title');
    const isElementFound = await titleElement.count();
    if (isElementFound > 0) {
        await expect(page.locator('h1.page-title')).toHaveText($verifty_title);
    } else {
        // For d7 env
        await expect(page.locator('#page-title')).toHaveText($verifty_title);
    }
}


test.beforeAll(async () => {
    const browser = await chromium.launch();
    page = await browser.newPage();
});

test.afterAll(async () => {
    await page.close();
});


test.describe.serial('Contribution Booster', () => {

    test.use({ storageState: 'storageState.json' });

    test('Donor Filtering', async () => {

        await test.step('Contribution Booster page check.', async () => {

            /* Step 1: open "Contribution Booster" page */
            await page.goto('civicrm/contribute/booster?reset=1');
            await utils.wait(wait_secs);
            await expect(page.locator('.crm-error')).toHaveCount(0);
            await utils.findElement(page, '.crm-contribute-booster');

            /* Step 2: check titles */
            await check_page_title('Contribution Booster');
            await expect(page.locator('.crm-section-title').first()).toHaveText('Connect Exists Donors');
            await expect(page.locator('.crm-section-title').nth(1)).toHaveText('Potential Donors');

            /* Step 3: check href links */
            for (var i = 0; i < vars.href_validation_data.length; i++){
                var el_id = vars.href_validation_data[i].el_id;
                var href_link = '/' + vars.href_validation_data[i].href_link;
                await expect(page.locator(`#${el_id} a`)).toHaveAttribute('href', href_link);
            }

        });

        /* Connect Exists Donors */

        await test.step('First time donation donors page check.', async () => {

            /* Step 1: open "First time donation donors" page */
            await page.goto(vars.href_validation_data[0].href_link);
            await utils.wait(wait_secs);
            await expect(page).toHaveURL('/civicrm/contact/search/custom?force=1&reset=1&csid=18');

            /* Step 2: check titles */
            await check_page_title('找到首次捐款人');

            await check_and_set_accordionElement();
            /* select date from */
            element = '#receive_date_from';
            await utils.findElement(page, element);
            await utils.selectDate(page, page.locator(element), 2023, 3, 1);

            /* select date to */
            element = '#receive_date_to';
            await utils.findElement(page, element);
            await utils.selectDate(page, page.locator(element), 2023, 3, 31);

            /* select "All" from "Recurring Contribution" */
            element = '#CIVICRM_QFID_2_2';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element));

            /* click "Search" button */
            element = '#_qf_Custom_refresh-bottom';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element));

            /* Step 4: check search results */
            await expect(page.locator('.crm-error')).toHaveCount(0);

        });

        await test.step('Donor who donate in last N month page check.', async () => {

            /* Step 1: open "Donor who donate in last N month" page */
            await page.goto(vars.href_validation_data[1].href_link);
            await utils.wait(wait_secs);
            await expect(page).toHaveURL('/civicrm/contact/search/custom?force=1&reset=1&csid=19');

            /* Step 2: check titles */
            await check_page_title('Donor who donate in last 6 months');

            await check_and_set_accordionElement();
            /* Step 3: check search form */
            element = '.crm-accordion-body';
            await expect(page.locator(element).first()).toBeVisible();
            expect(await page.evaluate((element) => {
                const text = document.querySelector(element).textContent;
                return text.includes('Donor who donate in last') && text.includes('month(s)');
            }, element)).toBeTruthy();

            /* select days */
            element = '#month';
            await utils.findElement(page, element);
            await utils.selectOption(page.locator(element), '3');

            /* click "Search" button */
            element = '#_qf_Custom_refresh-top';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element));

            /* Step 4: check search results */
            await expect(page.locator('.crm-error')).toHaveCount(0);
            await check_page_title('Donor who donate in last 3 months');
        });

        await test.step('After payment failed but not retry in N days page check.', async () => {

            /* Step 1: open "After payment failed but not retry in days" page */
            await page.goto(vars.href_validation_data[2].href_link);
            await utils.wait(wait_secs);
            await expect(page).toHaveURL('/civicrm/contact/search/custom?force=1&reset=1&csid=20');

            /* Step 2: check titles */
            await check_page_title('After payment failed but not retry in 7 days');

            await check_and_set_accordionElement();
            /* Step 3: check search form */
            element = '.crm-accordion-body';
            await expect(page.locator(element).first()).toBeVisible();
            expect(await page.evaluate((element) => {
                const text = document.querySelector(element).textContent;
                return text.includes('After payment failed but not retry in') && text.includes('days');
            }, element)).toBeTruthy();

            /* select days */
            element = '#days';
            await utils.findElement(page, element);
            await utils.selectOption(page.locator(element), '3');

            /* click "Search" button */
            element = '#_qf_Custom_refresh-top';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element));

            /* Step 4: check search results */
            await expect(page.locator('.crm-error')).toHaveCount(0);
            await check_page_title('After payment failed but not retry in 3 days');

        });

        await test.step('Recurring Donors Search page check.', async () => {

            /* Step 1: open "Recurring Donors Search" page */
            await page.goto(vars.href_validation_data[3].href_link);
            await utils.wait(wait_secs);
            await expect(page).toHaveURL('/civicrm/contact/search/custom?reset=1&csid=23');

            /* Step 2: check titles */
            await check_page_title('Recurring Donors Search');

            await check_and_set_accordionElement();
            /* Step 3: check search form */
            await expect(page.locator('.crm-accordion-body').first()).toBeVisible();
            element = 'table tr.crm-contact-custom-search-form-row-search_criteria td.label label[for="search_criteria"]';
            expect(await page.evaluate((element) => {
                const text = document.querySelector(element).textContent;
                return text.includes('Recurring Donors Search');
            }, element)).toBeTruthy();

            /* select "Recurring Donors Search" */
            element = '#search_criteria';
            await utils.findElement(page, element);
            await utils.selectOption(page.locator(element), {index: 1});

            /* click "Search" button */
            element = '#_qf_Custom_refresh-top';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element));

            /* Step 4: check search results */
            await expect(page.locator('.crm-error')).toHaveCount(0);

        });

        await test.step('Birth Date Search page check.', async () => {

            /* Step 1: open "Birth Date Search" page */
            await page.goto(vars.href_validation_data[4].href_link);
            await utils.wait(wait_secs);
            await expect(page).toHaveURL('/civicrm/contact/search/custom?reset=1&csid=24');

            /* Step 2: check titles */
            await check_page_title('Birth Date Search');

            await check_and_set_accordionElement();
            /* Step 3: check search form */
            await expect(page.locator('.crm-accordion-body').first()).toBeVisible();
            await expect(page.locator('table tr.crm-contact-custom-search-form-row-limit_groups td.label label[for="limit_groups"]')).toHaveText('Groups');

            /* click "Search" button */
            element = '#_qf_Custom_refresh-top';

            /* Step 4: check search results */
            await expect(page.locator('.crm-error')).toHaveCount(0);

        });

        /* Potential Donors */

        await test.step('Last year but not this year donors page check.', async () => {

            /* Step 1: open "Last year but not this year donors" page */
            await page.goto(vars.href_validation_data[5].href_link);
            await utils.wait(wait_secs);
            await expect(page).toHaveURL('/civicrm/contact/search/custom?force=1&reset=1&csid=13');

            /* Step 2: check titles */
            await check_page_title('Last year but not this year donors');

            await check_and_set_accordionElement();
            /* Step 3: check search form */
            await expect(page.locator('.crm-accordion-body').first()).toBeVisible();
            await expect(page.locator('table tr.crm-contact-custom-search-contribSYBNT-form-block-contribution_type_id td.label label[for="contribution_type_id"]')).toHaveText('Contribution Type');

            /* fill in "Total Receive Amount" - Min */
            element = '#include_min_amount';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), '10000');

            /* fill in "Total Receive Amount" - Max */
            element = '#include_max_amount';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), '20000');

            /* click "Search" button */
            element = '#_qf_Custom_refresh-bottom';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element));

            /* Step 4: check search results */
            await expect(page.locator('.crm-error')).toHaveCount(0);

        });

        // TODO: add test for "Single donation over N times"

        await test.step('End of recurring contribution page check.', async () => {

            /* Step 1: open "End of recurring contribution" page */
            await page.goto(vars.href_validation_data[7].href_link);
            await utils.wait(wait_secs);
            await expect(page).toHaveURL('/civicrm/contact/search/custom?mode=booster&force=1&reset=1&csid=17');

            /* Step 2: check titles */
            await check_page_title('End of recurring contribution');

            await check_and_set_accordionElement();
            /* Step 3: check search form */
            await expect(page.locator('.crm-accordion-body').first()).toBeVisible();
            await expect(page.locator('table tr.crm-contact-custom-search-form-row-status td.label label')).toHaveText('Recurring Status');

            /* select "Recurring Status" */
            element = '#CIVICRM_QFID_2_4';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element));

            /* click "Search" button */
            element = '#_qf_Custom_refresh-top';
            await utils.findElement(page, element);

            /* Step 4: check search results */
            await expect(page.locator('.crm-error')).toHaveCount(0);

        });

        // TODO: add test for "Attendee but not donor"

    });

});