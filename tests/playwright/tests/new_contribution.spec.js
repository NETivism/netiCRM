const { test, expect, chromium } = require('@playwright/test');
const utils = require('./utils.js');

/** @type {import('@playwright/test').Page} */
let page;
var locator, element;


test.beforeAll(async () => {
    const browser = await chromium.launch();
    page = await browser.newPage();
});

test.afterAll(async () => {
    await page.close();
});
  

test.describe.serial('Add Contribution', () => {

    test.use({ storageState: 'storageState.json' });

    test('Contribution Editing test', async () => {

        let trxn_id, total_amount, source, contribution_status;

        await test.step("New Contribution.", async () =>{

            /* open new contribution page */
            await page.goto('civicrm/contribute/add?reset=1&action=add&context=standalone');

            /* select 新增個人 */
            element = '#profiles_1';
            await utils.findElement(page, element);
            await utils.selectOption(page.locator(element), '4');

            /* filled up new contact form */
            var first_name = utils.makeid(3);
            var last_name = utils.makeid(3);

            element = 'form#Edit #last_name';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), last_name);

            element = 'form#Edit #first_name';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), first_name);

            await page.locator('#_qf_Edit_next').click();

            await expect(page.locator('#contact_1')).toHaveValue(`${first_name} ${last_name}`);

            /* select Contribution Type */
            element = '#contribution_type_id';
            await utils.findElement(page, element);
            await utils.selectOption(page.locator(element), '1');

            /* fill in Total Amount */
            element = '#total_amount';
            total_amount = '100';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), total_amount);

            /* fill in Source */
            element = '#source';
            source = 'hand to hand';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), source);

            /* select received date */
            element = '#receive_date';
            await utils.findElement(page, element);
            await utils.selectDate(page, page.locator(element), 2020, 1, 1);

            element = '#receive_date_time';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), '12:00PM');

            /* select Paid By */
            element = '#payment_instrument_id';
            await utils.findElement(page, element);
            await utils.selectOption(page.locator(element), '12');

            /* fill in Transaction ID */
            element = '#trxn_id';
            trxn_id = utils.makeid(8);
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), trxn_id);

            /* click Receipt */
            element = '#have_receipt';
            await utils.findElement(page, element);
            await utils.checkInput(page, page.locator(element), {visible: '#receipt-option'});

            /* select receipt date */
            element = '#receipt_date ';
            await utils.findElement(page, element);
            await utils.selectDate(page, page.locator(element), 2020, 1, 1);

            element = '#receipt_date_time';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), '12:00PM');

            /* 收據資訊 */

            /* click 需要（請寄給我紙本收據） */
            element = '#CIVICRM_QFID_1_4';
            await utils.findElement(page, element);
            await utils.checkInput(page, page.locator(element));

            /* fill in 收據抬頭 */
            element = '#custom_2_-1';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), utils.makeid(5));

            /* fill in 報稅憑證 */
            element = '#custom_3_-1';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), utils.makeid(5));

            /* sendKeys 捐款徵信名稱 */
            element = '#custom_4_-1';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), utils.makeid(5));

            /* get contribution status */
            element = '#contribution_status_id';
            await utils.findElement(page, element);
            contribution_status = await page.evaluate((element) => {
                const contribution_value = document.querySelector(element).value;
                return document.querySelector(`${element} option[value="${contribution_value}"]`).innerText;
            }, element);

            /* click Additional Details */
            element = '#AdditionalDetail';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element), {visible: '#id-additionalDetail'});

            /* select Contribution Page */
            element = '#contribution_page_id';
            await utils.findElement(page, element);
            await utils.selectOption(page.locator(element), {index: -1});

            /* click Honoree Information */
            element = '#Honoree';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element), {visible: '#id-honoree'});

            /* click 致敬 */
            element = '#CIVICRM_QFID_1_2';
            await utils.findElement(page, element);
            await utils.checkInput(page, page.locator(element), {visible: '#honorType'});

            /* select Prefix */
            element = '#honor_prefix_id';
            await utils.findElement(page, element);
            await utils.selectOption(page.locator(element), '1');

            /* fill in First Name */
            element = '#honor_first_name';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), utils.makeid(3));

            /* fill in Last Name */
            element = '#honor_last_name';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), utils.makeid(3));

            /* click Save */
            element = '#_qf_Contribution_upload-bottom';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element), {notExist: '.crm-error'});

        });


        await test.step("Check If New Contribution Success.", async () =>{

            /* check success message */
            element = '.messages';
            await utils.findElement(page, element);
            await expect(page.locator(element).first()).toHaveText('The contribution record has been saved.');

            /* check Transaction ID, Total Amount, Source, Contribution Status just filled in */
            await expect(page.locator('.selector tr:nth-child(1) td.crm-contribution-trxn-id')).toHaveText(trxn_id);
            await expect(page.locator('.selector tr:nth-child(1) td.crm-contribution-amount .nowrap')).toHaveText(`NT$ ${total_amount}.00`);
            await expect(page.locator('.selector tr:nth-child(1) td.crm-contribution-source')).toHaveText(source);
            await expect(page.locator('.selector tr:nth-child(1) td.crm-contribution-status div')).toHaveText(contribution_status);
            console.log('Subject equals the expected value');

        });


        await test.step("Edit Contribution.", async () =>{
            
            /* click edit contribution */
            element = 'table.selector .row-action .action-item:nth-child(2)';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element), {exist: 'form#Contribution'});

            /* clear receipt date */
            element = '.crm-receipt-option .crm-clear-link a';
            await utils.clickElement(page, page.locator(element));
            await expect(page.locator('#receipt_date')).toHaveValue('');
            await expect(page.locator('#receipt_date_time')).toHaveValue('');

            /* fill in Transaction ID again */
            element = '#trxn_id';
            await utils.findElement(page, element);
            trxn_id = utils.makeid(8);
            await utils.fillInput(page.locator(element), trxn_id);

            /* click submit */
            element = '#_qf_Contribution_upload-bottom';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element), {notExist: '.crm-error'});

        });


        await test.step("Check If Edit Contribution Success.", async () =>{

            /* check success message */
            element = '.messages';
            await utils.findElement(page, element);
            await expect(page.locator(element).first()).toHaveText('The contribution record has been saved.');
            
            /* check Transaction ID just filled in */
            await expect(page.locator('.selector tr:nth-child(1) td.crm-contribution-trxn-id')).toHaveText(trxn_id);
            console.log('Subject equals the expected value');

        });
        
    });
});