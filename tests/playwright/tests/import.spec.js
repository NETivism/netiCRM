const { test, expect, chromium } = require('@playwright/test');
const utils = require('./utils.js');

/** @type {import('@playwright/test').Page} */
let page;
let element;
const wait_secs = 2000;

const item = {
    task: [
        {
            type: 'contact',
            url: 'civicrm/import/contact?reset=1',
            fields: {
                'mapper[0][0]': 'last_name',
                'mapper[1][0]': 'first_name',
                'mapper[2][0]': 'email',
                'mapper[2][1]': '1',
                'mapper[3][0]': 'do_not_import',
                'mapper[4][0]': 'do_not_import',
                'mapper[5][0]': 'do_not_import',
                'mapper[6][0]': 'do_not_import',
                'mapper[7][0]': 'do_not_import',
                'mapper[8][0]': 'do_not_import',
                'mapper[9][0]': 'do_not_import',
                'mapper[10][0]': 'do_not_import',
                'mapper[11][0]': 'do_not_import',
            },
            form_name: 'DataSource',
        },
        {
            type: 'contribute',
            url: 'civicrm/contribute/import?reset=1',
            fields: {
                'mapper[0][0]': 'last_name',
                'mapper[1][0]': 'first_name',
                'mapper[2][0]': 'email',
                'mapper[2][1]': '1',
                'mapper[3][0]': 'doNotImport',
                'mapper[4][0]': 'doNotImport',
                'mapper[5][0]': 'total_amount',
                'mapper[6][0]': 'contribution_type',
                'mapper[7][0]': 'doNotImport',
                'mapper[8][0]': 'doNotImport',
                'mapper[9][0]': 'doNotImport',
                'mapper[10][0]': 'doNotImport',
                'mapper[11][0]': 'doNotImport',
            }
        },
        {
            type: 'activity',
            url: 'civicrm/import/activity?reset=1',
            fields: {
                'mapper[0][0]': 'doNotImport',
                'mapper[1][0]': 'doNotImport',
                'mapper[2][0]': 'email',
                'mapper[3][0]': 'doNotImport',
                'mapper[4][0]': 'doNotImport',
                'mapper[5][0]': 'doNotImport',
                'mapper[6][0]': 'activity_subject',
                'mapper[7][0]': 'activity_type_id',
                'mapper[8][0]': 'activity_date_time',
                'mapper[9][0]': 'doNotImport',
                'mapper[10][0]': 'doNotImport',
                'mapper[11][0]': 'doNotImport',
            }
        },
        {
            type: 'participant',
            url: 'civicrm/event/import?reset=1',
            fields: {
                'mapper[0][0]': 'doNotImport',
                'mapper[1][0]': 'doNotImport',
                'mapper[2][0]': 'email',
                'mapper[3][0]': 'doNotImport',
                'mapper[4][0]': 'doNotImport',
                'mapper[5][0]': 'doNotImport',
                'mapper[6][0]': 'doNotImport',
                'mapper[7][0]': 'event_id',
                'mapper[8][0]': 'doNotImport',
                'mapper[9][0]': 'participant_status_id',
                'mapper[10][0]': 'doNotImport',
                'mapper[11][0]': 'doNotImport',
            }
        },
        {
            type: 'member',
            url: 'civicrm/member/import?reset=1',
            fields: {
                'mapper[0][0]': 'last_name',
                'mapper[1][0]': 'first_name',
                'mapper[2][0]': 'email',
                'mapper[3][0]': 'doNotImport',
                'mapper[4][0]': 'doNotImport',
                'mapper[5][0]': 'doNotImport',
                'mapper[6][0]': 'doNotImport',
                'mapper[7][0]': 'doNotImport',
                'mapper[8][0]': 'doNotImport',
                'mapper[9][0]': 'doNotImport',
                'mapper[10][0]': 'membership_type_id',
                'mapper[11][0]': 'membership_start_date',
            }
        },
    ],
};

async function fillForm(selector, fields) {
    for (const field in fields) {
        await utils.selectOption(page.locator(`${selector} [name="${field}"]`), fields[field]);
    }
}


test.beforeAll(async () => {
    const browser = await chromium.launch();
    page = await browser.newPage();
});

test.afterAll(async () => {
    await page.close();
});


test.describe.serial('Import Records', () => {

    test.use({ storageState: 'storageState.json' });

    test('Check Membership Type exists', async () => {
        await test.step('Check Membership Types exists', async () => {
            element = '.crm-membership-type-type_name';
            await page.goto('civicrm/admin/member/membershipType?reset=1');
            await expect(page.getByRole('cell', { name: 'typeForTest' })).toHaveCount(1);
        });
    });

    let i = 1;
    for (const task of item.task) {

        test(`Import task ${i++} : ${task.type}`, async () => {

            page.once('dialog', dialog => dialog.accept());

            /* Step 1: Enter Upload File Page. */
            await test.step('Enter Upload File Page', async () => {

                await page.goto(task.url);
                await utils.wait(wait_secs);

                await expect(page.locator('.crm-error')).toHaveCount(0);

                /* Upload File */
                await utils.print('- Upload File.');
                const fileChooserPromise = page.waitForEvent('filechooser');
                element = '#uploadFile';
                await utils.findElement(page, element);
                await utils.clickElement(page, page.locator(element));
                const fileChooser = await fileChooserPromise;
                await fileChooser.setFiles('./tests/files/import.csv');

                await expect(page.locator('#uploadFile')).toHaveValue(/import.csv/);

                /* Skip Column Header */
                element = '#skipColumnHeader';
                await utils.findElement(page, element);
                await utils.checkInput(page, page.locator(element));

                /* Select Import Mode */
                if (task.type == 'contribute'){
                    await utils.print('- Select import mode.');
                    element = 'input[name="onDuplicate"][value="1"]';
                    await utils.findElement(page, element);
                    await utils.clickElement(page, page.locator(element));
                }

                /* Click Continue */
                await utils.print('- Click next button.');
                var form_name = (task.form_name ? task.form_name : 'UploadFile');
                element = `#_qf_${form_name}_upload-bottom`;
                await utils.findElement(page, element);
                await utils.clickElement(page, page.locator(element));

                await utils.wait(wait_secs);

                await expect(page.locator('.crm-error')).toHaveCount(0);

            });

            /* Step 2: Enter MapField Page. */
            await test.step('Enter MapField Page', async () => {

                await expect(page).toHaveURL(/_qf_MapField_display/);

                /* Match mapping fields */
                await utils.print('- Select the mapping field.');
                element = 'form#MapField';
                await utils.findElement(page, element);
                await fillForm(element, task.fields);

                /* Click Continue */
                await utils.print('- Select next button.');
                element = '#_qf_MapField_next-bottom';
                await utils.findElement(page, element);
                await utils.clickElement(page, page.locator(element));

                await utils.wait(wait_secs);

                await expect(page.locator('.crm-error')).toHaveCount(0);

            });

            /* Step 3: Enter Preview Page. */
            await test.step('Enter Preview Page', async () => {

                await expect(page).toHaveURL(/_qf_Preview_display/);

                /* Check valid rows */
                await utils.print('- Check valid rows.');

                const isEqual = await page.evaluate(() => {
                    const table = document.getElementById('preview-counts');
                    const rows = table.getElementsByTagName('tr');
                    let result = {};

                    let totalRows, validRows;

                    for (let i = 0; i < rows.length; i++) {
                        const labelCell = rows[i].getElementsByClassName('label')[0];
                        if (labelCell) {
                            const labelText = labelCell.textContent.trim();
                            const dataCell = rows[i].getElementsByClassName('data')[0];

                            if(labelText === 'Total Rows') {
                                totalRows = parseInt(dataCell.textContent.trim(), 10);
                            }
                            else if(labelText === 'Valid Rows') {
                                validRows = parseInt(dataCell.textContent.trim(), 10);
                            }
                        }
                    }
                    result['totalRows'] = totalRows;
                    result['validRows'] = validRows;
                    return result;
                });
                await utils.print(isEqual);
                await expect(isEqual['result']).toBe(true,isEqual);
                await utils.print('Total Rows are equal to Valid Rows.');
            });

            /* Step 4: Import Rows. */
            await test.step('Import Rows', async () => {

                await utils.print('- Import Rows.');
                element = '#_qf_Preview_next-top';
                await utils.findElement(page, element);
                await utils.clickElement(page, page.locator(element));

                await utils.wait(wait_secs);

                await expect(page.locator('.crm-error')).toHaveCount(0);

                /* Click Done */
                await utils.print('- Click Done.');
                element = '#_qf_Summary_next-bottom';
                await utils.findElement(page, element);
                await utils.clickElement(page, page.locator(element));

                await utils.wait(wait_secs);

                await expect(page.locator('.crm-error')).toHaveCount(0);

            });

        });

    }

});