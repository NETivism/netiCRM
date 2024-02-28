const { expect } = require('@playwright/test');

function makeid(length) {
    var result           = '';
    var characters       = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    var charactersLength = characters.length;
    for ( var i = 0; i < length; i++ ) {
       result += characters.charAt(Math.floor(Math.random() * charactersLength));
    }
    return result;
}

async function print(s){
    console.log(s);
}

async function findElement(page, element){
    await page.locator(element).first().waitFor();
    await print('Find an element matching: ' + element);
}

async function findElementByLabel(page, label) {
    const element = await page.getByLabel(label);
    await print('Find an element matching: ' + label);
    return element;
}

async function fillInput(locator, text_input){
    await expect(locator).toBeEnabled();
    await locator.click();
    await locator.fill(text_input);
    await expect(locator).toHaveValue(text_input);
}

async function checkInput(page, locator, expectEl={}){
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

async function clickElement(page, locator, expectEl={}){
    await locator.waitFor();
    await expect(locator).toBeEnabled();
    await locator.click();

    if ('exist' in expectEl) await expect(page.locator(expectEl.exist)).not.toHaveCount(0);
    if ('notExist' in expectEl) await expect(page.locator(expectEl.notExist)).toHaveCount(0);
    if ('visible' in expectEl) await expect(page.locator(expectEl.visible)).toBeVisible();
}

function formatNumber(num, digits=2, fill='0') {
    num = num.toString();
    while (num.length < digits) num = `${fill}${num}`;
    return num;
}

async function selectDate(page, locator, year, month, day){
    await locator.click();
    await page.locator('select.ui-datepicker-year').selectOption(`${year}`);
    await page.locator('select.ui-datepicker-month').selectOption(`${month-1}`);
    await page.locator(`a.ui-state-default[data-date='${day}']`).click();
    var format = await locator.getAttribute('format');
    await expect(locator).toHaveValue(format.replace('yy', year).replace('mm', formatNumber(month)).replace('dd', formatNumber(day)));
}

function wait(ms){
    return new Promise((resolve) => setTimeout(resolve, ms));
}

async function getPageTitle(title){
    return title + " | netiCRM";
}


async function fillForm(email='test@aipvo.com', page, form_selector='form#Register'){

    await expect(page.locator(form_selector)).toBeDefined();
  
    var locator = page.locator('input[name="email-5"]');
    await fillInput(locator, email);
  
}

async function reLogin(page, user=process.env.adminUser, password=process.env.adminPwd  ){
    await page.goto('/');
    await page.locator('input[name="name"]').fill(user);
    await page.locator('input[name="pass"]').fill(password);
    await page.locator('input[value="Log in"]').click();
    // Save signed-in state to 'storageState.json'.
    await page.context().storageState({ path: 'storageState.json' });
    await expect(page).toHaveTitle(/Welcome[^|]+ \| netiCRM/);
}

async function checkParticipantNum(page, page_title, event_id){
    const response = await page.goto(`/civicrm/event/search?reset=1&force=1&event=${event_id}`);
    await expect(response.status()).toBe(200);
    await expect(page).toHaveTitle(page_title);
    const counted_people = await page.locator('div#stat_ps>div#stat_ps_label1>ol>li>div>span.people-count').first().textContent();
    console.log('counted people:', counted_people);
    // check the number of participants
    if (counted_people == '5'){
      // capture the fifth participant and delete it
      await page.locator('div#participantSearch>table>tbody>tr').first().getByRole('link', { name: 'Delete' }).click();
      await page.locator('[id="_qf_Participant_next-bottom"]').click();
    }
    // check whether it was deleted sucessfully
    await expect(page.locator('div#stat_ps>div#stat_ps_label1>ol>li>div>span.people-count').first()).toHaveText('4');
}

module.exports = {
    makeid, print, findElement, findElementByLabel, fillInput, checkInput, selectOption, clickElement, selectDate, wait, getPageTitle, fillForm ,reLogin , checkParticipantNum
}