const { test, expect, chromium } = require('@playwright/test');

/** @type {import('@playwright/test').Page} */
let page;
var locator, element;

function makeid(length) {
    var result           = '';
    var characters       = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    var charactersLength = characters.length;
    for ( var i = 0; i < length; i++ ) {
       result += characters.charAt(Math.floor(Math.random() * charactersLength));
    }
    return result;
}

async function findElement(element){
    await expect(page.locator(element)).not.toHaveCount(0);
    console.log('Find an element matching: ' + element);
}

async function fillInput(locator, text_input){
    await expect(locator).toBeEnabled();
    await locator.click();
    await locator.fill(text_input);
    await expect(locator).toHaveValue(text_input);
}

async function checkInput(locator, expectEl={}){
    await expect(locator).toBeEnabled();
    await locator.click();
    await expect(locator).toBeChecked();
    if ('visible' in expectEl) await expect(page.locator(expectEl.visible)).toBeVisible();
}

async function selectOption(locator, option) {
    await expect(locator).toBeEnabled();
    var expectValue;

    if (typeof option === 'object'){
        if ('index' in option){
            var optionLocator = await locator.locator('option');
            if (option.index < 0) option.index += await optionLocator.count();
            expectValue = await optionLocator.nth(option.index).getAttribute('value');
        }
    }
    else if (typeof option === 'string') expectValue = option;

    await locator.selectOption(option);
    await expect(locator).toHaveValue(expectValue);
}

async function clickElement(locator, expectEl={}){
    await expect(locator).toBeEnabled();
    await locator.click();
    if ('exist' in expectEl) await expect(page.locator(expectEl.exist)).not.toHaveCount(0);
    else if ('notExist' in expectEl) await expect(page.locator(expectEl.notExist)).toHaveCount(0);
    else if ('visible' in expectEl) await expect(page.locator(expectEl.visible)).toBeVisible();
}

function formatNumber(num, digits=2, fill='0') {
    num = num.toString();
    while (num.length < digits) num = `${fill}${num}`;
    return num;
}

async function selectDate(locator, year, month, day, p=page){
    await locator.click();
    await p.locator('select.ui-datepicker-year').selectOption(`${year}`);
    await p.locator('select.ui-datepicker-month').selectOption(`${month-1}`);
    await p.locator(`a.ui-state-default[data-date='${day}']`).click();
    var format = await locator.getAttribute('format');
    await expect(locator).toHaveValue(format.replace('yy', year).replace('mm', formatNumber(month)).replace('dd', formatNumber(day)));
}

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
            await findElement(element);
            await selectOption(page.locator(element), '4');

            /* filled up new contact form */
            var first_name = makeid(3);
            var last_name = makeid(3);

            element = 'form#Edit #last_name';
            await findElement(element);
            await fillInput(page.locator(element), last_name);

            element = 'form#Edit #first_name';
            await findElement(element);
            await fillInput(page.locator(element), first_name);

            await page.locator('#_qf_Edit_next').click();

            await expect(page.locator('#contact_1')).toHaveValue(`${first_name} ${last_name}`);

            /* select Contribution Type */
            element = '#contribution_type_id';
            await findElement(element);
            await selectOption(page.locator(element), '1');

            /* fill in Total Amount */
            element = '#total_amount';
            total_amount = '100';
            await findElement(element);
            await fillInput(page.locator(element), total_amount);

            /* fill in Source */
            element = '#source';
            source = 'hand to hand';
            await findElement(element);
            await fillInput(page.locator(element), source);

            /* select received date */
            element = '#receive_date';
            await findElement(element);
            await selectDate(page.locator(element), 2020, 1, 1);

            element = '#receive_date_time';
            await findElement(element);
            await fillInput(page.locator(element), '12:00PM');

            /* select Paid By */
            element = '#payment_instrument_id';
            await findElement(element);
            await selectOption(page.locator(element), '12');

            /* fill in Transaction ID */
            element = '#trxn_id';
            trxn_id = makeid(8);
            await findElement(element);
            await fillInput(page.locator(element), trxn_id);

            /* click Receipt */
            element = '#have_receipt';
            await findElement(element);
            await checkInput(page.locator(element), {visible: '#receipt-option'});

            /* select receipt date */
            element = '#receipt_date ';
            await findElement(element);
            await selectDate(page.locator(element), 2020, 1, 1);

            element = '#receipt_date_time';
            await findElement(element);
            await fillInput(page.locator(element), '12:00PM');

            /* 收據資訊 */

            /* click 需要（請寄給我紙本收據） */
            element = '#CIVICRM_QFID_1_4';
            await findElement(element);
            await checkInput(page.locator(element));

            /* fill in 收據抬頭 */
            element = '#custom_2_-1';
            await findElement(element);
            await fillInput(page.locator(element), makeid(5));

            /* fill in 報稅憑證 */
            element = '#custom_3_-1';
            await findElement(element);
            await fillInput(page.locator(element), makeid(5));

            /* sendKeys 捐款徵信名稱 */
            element = '#custom_4_-1';
            await findElement(element);
            await fillInput(page.locator(element), makeid(5));

            /* get contribution status */
            element = '#contribution_status_id option';
            await findElement(element);
            contribution_status = await page.locator(element).nth(0).innerText();

            /* click Additional Details */
            element = '#AdditionalDetail';
            await findElement(element);
            await clickElement(page.locator(element), {visible: '#id-additionalDetail'});

            /* select Contribution Page */
            element = '#contribution_page_id';
            await findElement(element);
            await selectOption(page.locator(element), {index: -1});

            /* click Honoree Information */
            element = '#Honoree';
            await findElement(element);
            await clickElement(page.locator(element), {visible: '#id-honoree'});

            /* click 致敬 */
            element = '#CIVICRM_QFID_1_2';
            await findElement(element);
            await checkInput(page.locator(element), {visible: '#honorType'});

            /* select Prefix */
            element = '#honor_prefix_id';
            await findElement(element);
            await selectOption(page.locator(element), '1');

            /* fill in First Name */
            element = '#honor_first_name';
            await findElement(element);
            await fillInput(page.locator(element), makeid(3));

            /* fill in Last Name */
            element = '#honor_last_name';
            await findElement(element);
            await fillInput(page.locator(element), makeid(3));

            /* click Save */
            element = '#_qf_Contribution_upload-bottom';
            await findElement(element);
            await clickElement(page.locator(element), {notExist: '.crm-error'});

        });


        await test.step("Check If New Contribution Success.", async () =>{

            /* check success message */
            element = '.messages';
            await findElement(element);
            await expect(page.locator(element)).toHaveText('The contribution record has been saved.');

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
            await findElement(element);
            await clickElement(page.locator(element), {exist: 'form#Contribution'});

            /* clear receipt date */
            element = '.crm-receipt-option .crm-clear-link a';
            await clickElement(page.locator(element));
            await expect(page.locator('#receipt_date')).toHaveValue('');
            await expect(page.locator('#receipt_date_time')).toHaveValue('');

            /* fill in Transaction ID again */
            element = '#trxn_id';
            await findElement(element);
            trxn_id = makeid(8);
            await fillInput(page.locator(element), trxn_id);

            /* click submit */
            element = '#_qf_Contribution_upload-bottom';
            await findElement(element);
            await clickElement(page.locator(element), {notExist: '.crm-error'});

        });


        await test.step("Check If Edit Contribution Success.", async () =>{

            /* check success message */
            element = '.messages';
            await findElement(element);
            await expect(page.locator(element)).toHaveText('The contribution record has been saved.');
            
            /* check Transaction ID just filled in */
            await expect(page.locator('.selector tr:nth-child(1) td.crm-contribution-trxn-id')).toHaveText(trxn_id);
            console.log('Subject equals the expected value');

        });
        
    });
});