const { test, expect, chromium } = require('@playwright/test');
const utils = require('./utils.js');

/** @type {import('@playwright/test').Page} */
let page;
var locator, element;
const wait_secs = 2000;

const vars = {
    group_name: utils.makeid(5),
    group_id: "",
    mail_name: utils.makeid(5),
    mail_subject: utils.makeid(5)
}


test.beforeAll(async () => {
    const browser = await chromium.launch();
    page = await browser.newPage();
});
  
test.afterAll(async () => {
    await page.close();
});


test.describe.serial('Group Editing', () => {

    test.use({ storageState: 'storageState.json' });

    test('Group Editing', async () => {

        /* Step 1: Add New Group. */
        await test.step('Add New Group.', async () => {

            /* open "New Group" page */
            await page.goto('civicrm/group/add?reset=1');
            await utils.wait(wait_secs);
            await utils.findElement(page, 'form#Edit');

            /* fill in group "Name" */
            element = '#title';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), vars.group_name);

            /* "Group Type" select "Mailing List" */
            element = 'input[name="group_type[2]"]';
            await utils.findElement(page, element);
            await utils.checkInput(page, page.locator(element));

            /* click "Continue" */
            element = '#_qf_Edit_upload-bottom';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element));

            await utils.wait(wait_secs);
            await expect(page.locator('.crm-error')).toHaveCount(0);
            await utils.findElement(page, 'form#Basic');

        });

        /* Step 2: Add User to Group. */
        await test.step('Add User to Group.', async () => {

            element = 'form#Basic input[value="Search"]';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element));
            await expect(page, `page title is not match "Add to Group: ${vars.group_name}"`).toHaveTitle(new RegExp('^Add to Group: ' + vars.group_name));

            /* select user that have email */
            element = '.selector';
            await utils.findElement(page, element);

            const id = await page.evaluate(() => {
                const tr = document.querySelectorAll('.selector tr');
                for (let i = 1; i < tr.length; i++) {
                    if (tr[i].querySelector('td:nth-child(5)').textContent !== '') {
                        return tr[i].querySelector('td:nth-child(3)').textContent;
                    }
                }
                return -1;
            });

            await expect(id, 'Fail to get user id.').not.toBe(-1);
            await utils.print('Got user id.');

            await utils.checkInput(page, page.locator(`#rowid${id} input`));

            /* click "Add Contacts to" */
            element = 'form#Basic input[name="_qf_Basic_next_action"]';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element));

            await utils.wait(wait_secs);

            await expect(page.locator('.crm-contact-task-addtogroup-form-block-group_id td').nth(1)).toHaveText(vars.group_name);

            /* click "Confirm" */
            element = 'input[name="_qf_AddToGroup_next"]';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element));

            await utils.wait(wait_secs);

            await expect(page.locator('.crm-error')).toHaveCount(0);

            /* click "Done" */
            element = 'form#Result input[value="Done"]';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element));

            await utils.wait(wait_secs);

            await utils.findElement(page, 'table.selector tbody tr');
            
        });
        
        /* Step 3: Check User Has Been Add To Group. */
        await test.step('Check User Has Been Add To Group.', async () => {

            /* click first user */
            element = 'table.selector tbody tr:first-child td:nth-child(5) a';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element));

            await utils.wait(wait_secs);

            /* switch to Groups tab */
            element = 'a[title="Groups"]';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element));

            element = '#GroupContact table';
            await utils.findElement(page, element);

            const group_in_list = await page.evaluate((group_name) => {
                const tr = document.querySelectorAll('#GroupContact table tr');
                for(let i = 1; i < tr.length; i++) {
                    if(tr[i].querySelector('td:first-child a').text == group_name) {
                        return true;
                    }
                }
                return false;
            }, vars.group_name);

            await expect(group_in_list, 'Group has not been added.').toBe(true);
            await utils.print('Group has been added.');

        });
        
        /* Step 4: Mailing Test. */
        await test.step('Mailing Test.', async () => {

            /* Step 4-1: Get Group ID. */

            /* open "Manage Groups" page */
            await page.goto('civicrm/group?reset=1');
            await utils.wait(wait_secs);
            await utils.findElement(page, '#group table');

            /* get group id */
            vars.group_id = await page.evaluate((group_name) => {
                const tr = document.querySelectorAll('#group table tr');
                for(let i = 1; i < tr.length; i++) {
                    if(tr[i].querySelector('td:nth-child(2)').textContent == group_name) {
                        return tr[i].querySelector('td:nth-child(1)').textContent;
                    }
                }
            }, vars.group_name);

            await expect(vars.group_id, 'Assert get group id fails.').not.toBe(null);
            await utils.print('Assert get group id successfully.')

            /* Step 4-2: Select Recipients. */

            /* open "New Mailing" page */
            await page.goto('civicrm/mailing/send?reset=1');
            await utils.wait(wait_secs);
            await expect(page.locator('.crm-error')).toHaveCount(0);
            await utils.findElement(page, 'form#Group');

            /* fill in "Name Your Mailing" */
            element = 'input#name';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), vars.mail_name);

            /* select "Include Group(s)" */
            element = '.crm-mailing-group-form-block-includeGroups .select2-search__field';
            await utils.findElement(page, element);
            await page.locator(element).click();
            await page.keyboard.type(vars.group_name);
            await page.keyboard.press('Enter');
            await expect(page.locator('#includeGroups')).toHaveValue(vars.group_id);

            /* click "Next" */
            element = '#_qf_Group_next';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element));

            await utils.wait(wait_secs);

            await expect(page.locator('.crm-error')).toHaveCount(0);

            /* Step 4-3: Track and Respond. */

            /* ensure "Total Recipients" value is 1 */
            const group_num = await page.evaluate(() =>{
                return document.querySelector('.messages strong').textContent;
            });

            await expect(group_num, 'The recipient number of group is incorrect.').toBe('1');
            await utils.print('Assert recipient number of group correct.')

            /* select "Mailing Visibility" */
            element = 'select#visibility';
            await utils.findElement(page, element);
            await utils.selectOption(page.locator(element), {index: 1});

            /* click "Next" */
            element = '#_qf_Settings_next';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element));

            await utils.wait(wait_secs);

            await expect(page.locator('.crm-error')).toHaveCount(0);

            /* Step 4-4: Mailing Content. */

            /* fill in "Mailing Subject" */
            element = '#subject-editor';
            await utils.findElement(page, element);
            await page.locator(element).click();
            await page.keyboard.type(vars.mail_subject);
            await expect(page.locator('input#subject')).toHaveValue(vars.mail_subject);

            /* select "Traditional Editor" */
            element = '#CIVICRM_QFID_1_4';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element), {visible: '#footer_id'});
            await expect(page.locator('#compose_old_id')).toBeVisible();

            /* "Mailing Footer" choose the second option */
            element = '#footer_id';
            await utils.findElement(page, element);
            await utils.selectOption(page.locator(element), {index: 1});

            /* click "Next" */
            element = '#_qf_Upload_upload';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element));

            await utils.wait(wait_secs);

            await expect(page.locator('.crm-error')).toHaveCount(0);

            /* Step 4-5: Test. */

            /* check mail subject from normal and mobile preview */
            element = '.mail-subject';
            await utils.findElement(page, element);

            await utils.clickElement(page, page.locator('button[data-type="normal"]'));
            await expect(page.locator(element).nth(1)).toHaveText(vars.mail_subject);

            await utils.clickElement(page, page.locator('button[data-type="mobile"]'));
            await expect(page.locator(element).first()).toHaveText(vars.mail_subject);

            /* click "Next" */
            element = '#_qf_Test_next';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element));

            await utils.wait(wait_secs);

            await expect(page.locator('.crm-error')).toHaveCount(0);

            /* Step 4-6: Schedule or Send. */

            /* click "Submit Mailing" */
            element = '#_qf_Schedule_next';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element));

            await utils.wait(wait_secs);

            await expect(page.locator('.crm-error')).toHaveCount(0);

            /* Step 4-7: Check if mail in 'Scheduled and Sent Mailings'. */

            /* open "Scheduled and Sent Mailings" page */
            page.goto('civicrm/mailing/browse/scheduled?reset=1&scheduled=true');

            /* check if mail in list */
            element = '.selector tbody tr td:nth-child(2)';
            await utils.findElement(page, element);
            await expect(page.locator(element).first()).toContainText(vars.mail_name);

        });

    });

});