const { test, expect, chromium } = require('@playwright/test');
const utils = require('./utils.js');

/** @type {import('@playwright/test').Page} */
let page;
var locator, element;
const wait_secs = 800;

var vars = {
    id: utils.makeid(5),
    ids: [],
    ids_for_check: [],
    custom_id: 0,
    text_id_for_input: [],
    radio_id_for_input: []
};


async function fill_options(){
    await utils.wait(200);

    /* sendKeys to Multiple Choice Options*/
    element = 'input[name="option_label[1]"]';
    await utils.findElement(page, element);
    await utils.fillInput(page.locator(element), 'op1');

    element = 'input[name="option_value[1]"]';
    await utils.findElement(page, element);
    await utils.fillInput(page.locator(element), 'op1');

    element = 'input[name="option_label[2]"]';
    await utils.findElement(page, element);
    await utils.fillInput(page.locator(element), 'op2');

    element = 'input[name="option_value[2]"]';
    await utils.findElement(page, element);
    await utils.fillInput(page.locator(element), 'op2');

}

async function add_field(field_name, data_type, input_type, should_fill_options=true){

    await utils.print('Add field: ' + field_name);

    /* fill input to Field Label */
    element = 'input[name="label"]';
    await utils.findElement(page, element);
    await utils.fillInput(page.locator(element), field_name + vars.id);

    /* select Data and Input Field Type */
    element = 'select[name="data_type[0]"]';
    await utils.findElement(page, element);
    await utils.selectOption(page.locator(element), {index: data_type});

    element = 'select[name="data_type[1]"]';
    await utils.findElement(page, element);
    await utils.selectOption(page.locator(element), {index: input_type});

    if (should_fill_options){
        /* click dropdown to invoke onclick function */
        await utils.clickElement(page, page.locator(element));

        /* fill options */
        await fill_options();
    }

}

async function click_submit(button_id='#_qf_Field_next_new-bottom'){
    /* click Save and New (or Save) */
    await utils.findElement(page, button_id);
    await utils.clickElement(page, page.locator(button_id));
    await utils.wait(wait_secs);
    await expect(page.locator('.crm-error')).toHaveCount(0);
}


test.beforeAll(async () => {
    const browser = await chromium.launch();
    page = await browser.newPage();
});

test.afterAll(async () => {
    await page.close();
});


test.describe.serial('Custom Data', () => {

    test.use({ storageState: 'storageState.json' });

    test('Add Set of Custom Fields - part 1', async () => {

        /* Step 1: Add Set of Custom Fields. */
        await test.step('Add Set of Custom Fields.', async () =>{

            /* open New Custom Field Set page */
            await page.goto('civicrm/admin/custom/group?action=add&reset=1');
            await utils.wait(wait_secs);
            await utils.findElement(page, 'form#Group');

            /* fill input to Set Name */
            element = 'input[name="title"]';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), 'testset' + vars.id);

            /* select 'Used For' to 'Contact' */
            element = 'select[name="extends[0]"]';
            await utils.findElement(page, element);
            await utils.selectOption(page.locator(element), 'Contact');

            /* click Save */
            element = '#_qf_Group_next-bottom';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element), {notExist: '.crm-error'});

        });

        /* Step 2: Add Alphanumeric field, including Text, Select, Radio, CheckBox, Multi-Select, Advanced Multi-Select, Autocomplete Select. */
        await test.step('Add Alphanumeric field, including Text, Select, Radio, CheckBox, Multi-Select, Advanced Multi-Select, Autocomplete Select.', async () =>{

            /* Step 2-1: Add Text field. */
            await add_field('Text', 0, 0, false);
            await click_submit();

            /* Step 2-2: Add Select field. */
            await add_field('Select', 0, 1);
            await click_submit();

            /* Step 2-3: Add Radio field. */
            await add_field('Radio', 0, 2);
            await click_submit();

            /* Step 2-4: Add Checkbox field. */
            await add_field('Checkbox', 0, 3);
            await click_submit();

            /* Step 2-5: Add Multi-Select field. */
            await add_field('Multi_Select', 0, 4);
            await click_submit();

            /* Step 2-6: Add Advanced Multi-Select field. */
            await add_field('Advanced_Multi_Select', 0, 5);
            await click_submit();

            /* Step 2-7: Add Autocomplete Select field. */
            await add_field('Autocomplete_Select', 0, 6);
            await click_submit();

        });

    });

    test('Add Set of Custom Fields - part 2', async () => {

        /* Step 3: Add Integer, Number, Money, Note, Date, Yes or No, State/Province, Country, File, Link, Contact Reference fields. */
        await test.step('Add Integer, Number, Money, Note, Date, Yes or No, State/Province, Country, File, Link, Contact Reference fields.', async () =>{

            /* Step 3-1: Add Integer field. */
            await add_field('Integer', 1, 0, false);
            await click_submit();

            /* Step 3-2: Add Number field. */
            await add_field('Number', 2, 0, false);
            await click_submit();

            /* Step 3-3: Add Money field. */
            await add_field('Money', 3, 0, false);
            await click_submit();

            /* Step 3-4-1: Add Note TextArea field. */
            await add_field('Note_TextArea', 4, 0, false);
            await click_submit();

            /* Step 3-4-2: Add Note WYSIWYG Editor field. */
            await add_field('Note_WYSIWYG_Editor', 4, 1, false);
            await click_submit();

            /* Step 3-5: Add Date field. */
            await add_field('Date', 5, 0, false);
            await utils.clickElement(page, page.locator('select[name="data_type[0]"]'));

            /* select Date Format */
            element = 'select[name="date_format"]';
            await utils.findElement(page, element);
            await utils.selectOption(page.locator(element), {index: 1});

            await click_submit();

            /* Step 3-6: Add Yes or No field. */
            await add_field('Yes_or_No', 6, 0, false);
            await click_submit();

            /* Step 3-7-1: Add State/Province Select field. */
            await add_field('State_Province_Select', 7, 0, false);
            await click_submit();

            /* Step 3-7-2: Add State/Province Multi Select field. */
            await add_field('State_Province_Multi_Select', 7, 1, false);
            await click_submit();

            /* Step 3-8-1: Add Country Select field. */
            await add_field('Country_Select', 8, 0, false);
            await click_submit();

            /* Step 3-8-2: Add Country Multi Select field. */
            await add_field('Country_Multi_Select', 8, 1, false);
            await click_submit();

            /* Step 3-9: Add File field. */
            await add_field('File', 9, 0, false);
            await click_submit();

            /* Step 3-10: Add Link field. */
            await add_field('Link', 10, 0, false);
            await click_submit();

            /* Step 3-11: Add Contact Reference field. */
            await add_field('Contact_Reference', 11, 0, false);
            await click_submit();


        });

    });

    test('Check Preview and Add Contact', async () => {
        /* Step 4: Check Preview. */
        await test.step('Check Preview.', async () =>{

            /* Step 4-1: Get all expected id. */

            /* open custom data page */
            await page.goto('civicrm/admin/custom/group?reset=1');
            await utils.wait(wait_secs);
            element = '#custom_group table';
            await utils.findElement(page, element);
            var id = (await page.locator(element).locator('tr').count()) - 1;

            /* go to Custom Fields Edit page */
            await page.goto('civicrm/admin/custom/group/field?reset=1&action=browse&gid=' + id);
            await utils.wait(wait_secs);

            element = 'a[title="Preview Custom Field"]';
            await utils.findElement(page, element);

            /* get all fields id */
            vars.ids = await page.evaluate((element) => {
              const all_links = Array.from(document.querySelectorAll(element));
              const all_urls = all_links.map(link => link.href);
              return all_urls.map(url => url.split('=').pop());
            }, element);
            await expect(vars.ids.length).toEqual(21);

            /* go to Custom Fields Preview page */
            await page.goto('civicrm/admin/custom/group?action=preview&reset=1&id=' + id);
            await utils.wait(wait_secs);
            await expect(page.locator('legend')).toContainText('testset' + vars.id);

            /* Step 4-2: Get all text input id. */
            const text_ids = await page.evaluate(() => {
                const all_text = Array.from(document.querySelectorAll('#Preview input[type="text"]:not(.hiddenElement), #Preview input[type="file"]'));
                const text_ids = [];
                for (let i = 0; i < all_text.length; i++) {
                    const sp = all_text[i].id.split('_');
                    text_ids.push(sp[1]);
                }
                return text_ids;
            });

            text_ids.forEach((text_id) => {
                vars.ids_for_check.push(text_id);
            });

            await expect(text_ids.length).not.toEqual(0);

            /* Step 4-3: Get all select id. */
            const select_ids = await page.evaluate(() => {
                const all_select = Array.from(document.querySelectorAll('#Preview select'));
                const select_ids = [];
                for (let i = 0; i < all_select.length; i++) {
                    const sp = all_select[i].id.split('_');
                    select_ids.push(sp[1]);
                }
                return select_ids;
            });

            select_ids.forEach((select_id) => {
                vars.ids_for_check.push(select_id);
            });

            await expect(select_ids.length).not.toEqual(0);

            /* Step 4-4: Get all radio input id. */
            const radio_ids = await page.evaluate(() => {
                const all_radio = document.getElementById('Preview').querySelectorAll('input[type="radio"]');
                const radio_ids = [];
                for (let i = 0; i < all_radio.length; i++) {
                    const sp = all_radio[i].name.split('_');
                    radio_ids.push(sp[1]);
                }
                return radio_ids;
            });

            radio_ids.forEach((radio_id) => {
                vars.ids_for_check.push(radio_id);
            });

            await expect(radio_ids.length).not.toEqual(0);

            /* Step 4-5: Get all checkbox input id. */
            const checkbox_ids = await page.evaluate(() => {
                const all_checkbox = Array.from(document.querySelectorAll('#Preview input[type="checkbox"]'));
                const checkbox_ids = [];
                for (let i = 0; i < all_checkbox.length; i++) {
                    const sp = all_checkbox[i].id.split('_');
                    checkbox_ids.push(sp[1]);
                }
                return checkbox_ids;
            });

            checkbox_ids.forEach((checkbox_id) => {
                vars.ids_for_check.push(checkbox_id);
            });

            await expect(checkbox_ids.length).not.toEqual(0);

            /* Step 4-6: Get all textarea id. */
            const textarea_ids = await page.evaluate(() => {
                const all_textarea = Array.from(document.querySelectorAll('#Preview textarea'));
                const textarea_ids = [];
                for (let i = 0; i < all_textarea.length; i++) {
                    const sp = all_textarea[i].id.split('_');
                    textarea_ids.push(sp[1]);
                }
                return textarea_ids;
            });

            textarea_ids.forEach((textarea_id) => {
                vars.ids_for_check.push(textarea_id);
            });

            await expect(textarea_ids.length).not.toEqual(0);

            /* Step 4-7: Check all id exist. */
            var id_no_duplicate = Array.from(new Set(vars.ids_for_check));
            id_no_duplicate.sort((a, b) => a - b);

            await expect(id_no_duplicate).toEqual(vars.ids);

        });

        /* Step 5: Check Add Contact. */
        await test.step('Check Add Contact.', async () => {

            /* open custom data page */
            await page.goto('civicrm/admin/custom/group?reset=1');
            await utils.wait(wait_secs);

            /* Step 5-1: Get custom data id. */
            element = '#custom_group table';
            await utils.findElement(page, element);

            /* get custom data id */
            vars.custom_id = (await page.locator(element).locator('tr').count()) - 1;

            /* open new individual page */
            await page.goto('civicrm/contact/add?reset=1&ct=Individual');
            await utils.wait(wait_secs);

            element = 'customData' + vars.custom_id;
            await utils.findElement(page, '#' + element);

            /* Step 5-2: Get all text input id. */
            const text_ids = await page.evaluate((element) => {
                const all_text = document.getElementById(element).querySelectorAll('input[type="text"]:not(.hiddenElement), input[type="file"]');
                const text_ids = [];

                for (let i = 0; i < all_text.length; i++) {
                    const sp = all_text[i].id.split('_');
                    text_ids.push(sp[1]);
                }
                return text_ids;
            }, element);

            vars.ids_for_check = [];
            text_ids.forEach((text_id) => {
                vars.ids_for_check.push(text_id);
            });

            await expect(text_ids.length).not.toEqual(0);

            /* Step 5-3: Get all select id. */
            const select_ids = await page.evaluate((element) => {
                const all_select = document.getElementById(element).querySelectorAll('select');
                const select_ids = [];
                for (let i = 0; i < all_select.length; i++) {
                    const sp = all_select[i].id.split('_');
                    select_ids.push(sp[1]);
                }
                return select_ids;
            }, element);

            select_ids.forEach((select_id) => {
                vars.ids_for_check.push(select_id);
            });

            await expect(select_ids.length).not.toEqual(0);

            /*  Step 5-4: Get all radio input id */
            const radio_ids = await page.evaluate((element) => {
                const all_radio = document.getElementById(element).querySelectorAll('input[type="radio"]');
                const radio_ids = [];
                for (let i = 0; i < all_radio.length; i++) {
                    const sp = all_radio[i].name.split('_');
                    radio_ids.push(sp[1]);
                }
                return radio_ids;
            }, element);

            radio_ids.forEach((radio_id) => {
                vars.ids_for_check.push(radio_id);
            });

            await expect(radio_ids.length).not.toEqual(0);

            /* Step 5-5: Get all checkbox input id. */
            const checkbox_ids = await page.evaluate((element) => {
                const all_checkbox = document.getElementById(element).querySelectorAll('input[type="checkbox"]');
                const checkbox_ids = [];
                for (let i = 0; i < all_checkbox.length; i++) {
                    const sp = all_checkbox[i].id.split('_');
                    checkbox_ids.push(sp[1]);
                }
                return checkbox_ids;
            }, element);

            checkbox_ids.forEach((checkbox_id) => {
                vars.ids_for_check.push(checkbox_id);
            });

            await expect(checkbox_ids.length).not.toEqual(0);

            /* Step 5-6: Get all textarea id. */
            const textarea_ids = await page.evaluate((element) => {
                const all_textarea = document.getElementById(element).querySelectorAll('textarea');
                const textarea_ids = [];
                for (let i = 0; i < all_textarea.length; i++) {
                    const sp = all_textarea[i].id.split('_');
                    textarea_ids.push(sp[1]);
                }
                return textarea_ids;
            }, element);

            textarea_ids.forEach((textarea_id) => {
                vars.ids_for_check.push(textarea_id);
            });

            await expect(textarea_ids.length).not.toEqual(0);

            /* Step 5-7: Check all id exist. */
            var id_no_duplicate = Array.from(new Set(vars.ids_for_check));
            id_no_duplicate.sort((a, b) => a - b);

            if (JSON.stringify(vars.ids) === JSON.stringify(id_no_duplicate)) {
                await utils.print('All ids exist and match.');
            } else {
                await utils.print('Ids do not match or have duplicates.');
            }

        });

        /* Step 6: Input All Fields. */
        await test.step('Input All Fields.', async () => {

            /* 6-1: Filled up last name and first name. */
            element = '#last_name';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), utils.makeid(5));

            element = '#first_name';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), utils.makeid(5));

            /* Step 6-2: Get all pure text id. */
            const text_ids = await page.evaluate((custom_id) => {
                const all_text = document.getElementById(`customData${custom_id}`).querySelectorAll('input[type="text"]:not(.hiddenElement):not(.dateplugin):not(.ac_input)');
                const text_ids = [];
                for (let i = 0; i < all_text.length; i++) {
                    const sp = all_text[i].id.split('_');
                    text_ids.push(sp[1]);
                }
                return text_ids;
            }, vars.custom_id);

            text_ids.forEach((text_id) => {
                vars.text_id_for_input.push(text_id);
            });

            await expect(vars.text_id_for_input.length).not.toEqual(0);

            /* Step 6-3: Input all pure text. */
            for (var text_id of vars.text_id_for_input) {
                var element_name = `custom_${text_id}_-1`;
                element = `input[name="${element_name}"]`;
                await utils.findElement(page, element);

                var field_name = await page.locator(`label[for="${element_name}"]`).textContent();
                if(field_name.startsWith('Link')) {
                    await utils.fillInput(page.locator(element), 'https://example.com');
                } else {
                    var rand_int = Math.floor(Math.random() * 10000).toString();
                    await utils.fillInput(page.locator(element), rand_int);
                }
            }

            /* Step 6-4: Get all select(not multi) id. */
            var select_id_for_input = [];
            const select_ids = await page.evaluate((custom_id) => {
                const select_ids = [];
                const adv_selector_ids_array = [];
                const adv_selector_ele = `#customData${custom_id} table.advmultiselect select`;
                const adv_selector_ids = document.querySelectorAll(adv_selector_ele);
                for(let i = 0; i < adv_selector_ids.length; i++) {
                    const adv_selector_array = adv_selector_ids[i].id.split('_');
                    adv_selector_ids_array.push(adv_selector_array[1]);
                }

                const all_select = document.getElementById(`customData${custom_id}`).querySelectorAll('select');
                for (let i = 0; i < all_select.length; i++) {
                    const sp = all_select[i].id.split('_');
                    if (!adv_selector_ids_array.includes(sp[1])) {
                        select_ids.push(sp[1]);
                    }
                }
                return select_ids;
            }, vars.custom_id);

            select_ids.forEach((select_id) => {
                select_id_for_input.push(select_id);
            });

            await expect(select_id_for_input.length).not.toEqual(0);

            /* Step 6-5: Input all select(not multi). */
            for (var select_id of select_id_for_input) {
                element = `#custom_${select_id}_-1`;
                try {
                    await utils.findElement(page, element);
                    locator = page.locator(element);
                    if (await locator.locator('option').count() >= 2) {
                        await utils.selectOption(locator, {index: 1});
                    } else {
                        await utils.selectOption(locator, {index: 0});
                    }
                } catch (error) {
                    await utils.print(element+': No select option.');
                }
            }

            /* Step 6-6: Get all radio input id. */
            const radio_ids = await page.evaluate((custom_id) => {
                const all_radio = document.getElementById(`customData${custom_id}`).querySelectorAll('input[type="radio"]');
                const radio_ids = [];
                for (let i = 0; i < all_radio.length; i++) {
                    const sp = all_radio[i].name.split('_');
                    radio_ids.push(sp[1]);
                }
                return radio_ids;
            }, vars.custom_id);

            radio_ids.forEach((radio_id) => {
                vars.radio_id_for_input.push(radio_id);
            });

            await expect(vars.radio_id_for_input.length).not.toEqual(0);

            /* Step 6-7: Input all radio. */
            for (var radio_id of vars.radio_id_for_input) {
                element = `input[name="custom_${radio_id}_-1"]`;
                await utils.findElement(page, element);
                await utils.checkInput(page, page.locator(element).first());
            }

            /* Step 6-8: Input all checkbox. */
            const checkbox_id = await page.evaluate((custom_id) => {
                return document.getElementById(`customData${custom_id}`).querySelectorAll('input[type="checkbox"]')[0].id;
            }, vars.custom_id);

            element = `input.form-checkbox[name="${checkbox_id}"]`;
            await utils.findElement(page, element);
            await utils.checkInput(page, page.locator(element).first());

            /* Step 6-9: Input advanced multi select. */
            const adv_selector = `#customData${vars.custom_id} table.advmultiselect select`;
            await utils.findElement(page, adv_selector);
            await page.locator(adv_selector).first().selectOption('op1');

            const add_selector = `#customData${vars.custom_id} table.advmultiselect input[value="Add >>"]`;
            await utils.findElement(page, add_selector);
            await utils.clickElement(page, page.locator(add_selector));
            await expect(page.locator(adv_selector).first().locator('option[value="op1"]')).toHaveCount(0);

            /* Step 6-10: Input textarea. */
            const textarea_selector = `#customData${vars.custom_id} textarea.form-textarea`;
            await utils.findElement(page, textarea_selector);
            await utils.fillInput(page.locator(textarea_selector), utils.makeid(5));

            /* Step 6-11: Save data. */
            element = '#_qf_Contact_upload_view';
            utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element).first(), {notExist: '.crm-error'});

        });

        /* Step 7: Check All Fields Exists in Edit Page. */
        await test.step('Check All Fields Exists in Edit Page', async () => {

            /* Step 7-1: Go to edit page. */
            element = 'a.edit';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element).first());
            await expect(page.locator('.crm-error')).toHaveCount(0);

            await utils.wait(wait_secs);

            /* Step 7-2: Check all pure text. */
            const text_ids = await page.evaluate((custom_id) => {
                const all_text = document.getElementById(`customData${custom_id}`).querySelectorAll('input[type="text"]:not(.hiddenElement), input[type="file"]');
                const text_ids = [];
                for (let i = 0; i < all_text.length; i++) {
                    const sp = all_text[i].id.split('_');
                    text_ids.push(sp[1]);
                }
                return text_ids;
            }, vars.custom_id);

            vars.ids_for_check = [...text_ids];

            /* Step 7-3: Get all select id. */
            const select_ids = await page.evaluate((custom_id) => {
                const all_select = document.getElementById(`customData${custom_id}`).querySelectorAll('select');
                const select_ids = [];
                for (let i = 0; i < all_select.length; i++) {
                    const sp = all_select[i].id.split('_');
                    select_ids.push(sp[1]);
                }
                return select_ids;
            }, vars.custom_id);

            select_ids.forEach((selectId) => {
                vars.ids_for_check.push(selectId);
            });

            /* Step 7-4: Get all radio input id. */
            const radio_ids = await page.evaluate((custom_id) => {
                const all_radio = document.getElementById(`customData${custom_id}`).querySelectorAll('input[type="radio"]');
                const radio_ids = [];
                for (let i = 0; i < all_radio.length; i++) {
                    const sp = all_radio[i].name.split('_');
                    radio_ids.push(sp[1]);
                }
                return radio_ids;
            }, vars.custom_id);

            radio_ids.forEach((radio_id) => {
                vars.ids_for_check.push(radio_id);
            });

            /* Step 7-5: Get all checkbox input id. */
            const checkbox_ids = await page.evaluate((custom_id) => {
                const all_checkbox = document.getElementById(`customData${custom_id}`).querySelectorAll('input[type="checkbox"]');
                const checkbox_ids = [];
                for (let i = 0; i < all_checkbox.length; i++) {
                    const sp = all_checkbox[i].id.split('_');
                    checkbox_ids.push(sp[1]);
                }
                return checkbox_ids;
            }, vars.custom_id);

            checkbox_ids.forEach((checkbox_id) => {
                vars.ids_for_check.push(checkbox_id);
            });

            /* Step 7-6: Get all textarea id. */
            const textarea_ids = await page.evaluate((custom_id) => {
                const all_textarea = document.getElementById(`customData${custom_id}`).querySelectorAll('textarea');
                const textarea_ids = [];
                for (let i = 0; i < all_textarea.length; i++) {
                    const sp = all_textarea[i].id.split('_');
                    textarea_ids.push(sp[1]);
                }
                return textarea_ids;
            }, vars.custom_id);

            textarea_ids.forEach((textarea_id) => {
                vars.ids_for_check.push(textarea_id);
            });

            /* Step 7-7: Check all id exists. */
            const id_no_duplicate = Array.from(new Set(vars.ids_for_check));

            id_no_duplicate.sort((a, b) => a - b);

            await expect(vars.ids).toEqual(id_no_duplicate);

            /* Step 7-8: Save data. */
            element = '#_qf_Contact_upload_view';
            utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element).first(), {notExist: '.crm-error'});

        });

    });

});