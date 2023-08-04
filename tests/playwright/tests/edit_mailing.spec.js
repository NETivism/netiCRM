const { test, expect, chromium } = require('@playwright/test');
const utils = require('./utils.js');

/** @type {import('@playwright/test').Page} */
let page;
var locator, element;
const wait_secs = 2000;

const vars = {
    mail_name: utils.makeid(5),
    mail_subject: utils.makeid(5),
    title_block_text: utils.makeid(5),
    paragraph_block_text: utils.makeid(10),
    button_block_text: utils.makeid(5),
}

/**
 * Check the count of different elements on a webpage for a given locator.
 *
 * @param {object} locator The locator object for finding web element.
 * @param {number} title_count The expected count of Title block.
 * @param {number} paragraph_count The expected count of Paragraph elements.
 * @param {number} image_count The expected count of Image elements.
 * @param {number} button_count The expected count of Button elements.
 * @param {number} col1_count The expected count of Rich Content: 1 Column elements.
 * @param {number} col2_count The expected count of Rich Content: 2 Column elements.
 * @param {number} float_count The expected count of Rich Content: Float elements.
 * @return {Promise<void>}
 */
async function checkElementsCount(locator, title_count, paragraph_count, image_count, button_count, col1_count, col2_count, float_count) {
    await expect(locator.locator('.nmee-title')).toHaveCount(title_count);
    await expect(locator.locator('.nmee-paragraph')).toHaveCount(paragraph_count);
    await expect(locator.locator('.nmee-image')).toHaveCount(image_count);
    await expect(locator.locator('.nmee-button')).toHaveCount(button_count);
    await expect(locator.locator('.nmee-rc-col-1')).toHaveCount(col1_count);
    await expect(locator.locator('.nmee-rc-col-2')).toHaveCount(col2_count);
    await expect(locator.locator('.nmee-rc-float')).toHaveCount(float_count);
}

/**
 * Clicks the "Preview" button, checks the elements count in "Normal" and "Mobile" preview modes, and closes the preview.
 *
 * @param {Page} page The current page object.
 * @param {number} title_count The expected count of Title block.
 * @param {number} paragraph_count The expected count of Paragraph elements.
 * @param {number} image_count The expected count of Image elements.
 * @param {number} button_count The expected count of Button elements.
 * @param {number} col1_count The expected count of Rich Content: 1 Column elements.
 * @param {number} col2_count The expected count of Rich Content: 2 Column elements.
 * @param {number} float_count The expected count of Rich Content: Float elements.
 * @return {Promise<void>}
 */
async function previewCheck(page, title_count, paragraph_count, image_count, button_count, col1_count, col2_count, float_count) {
    /* click "Preview" */
    element = '.switch-toggle-slider';
    await utils.findElement(page, element);
    await utils.clickElement(page, page.locator(element));

    await expect(page.locator('#nme-preview-popup')).toBeVisible();

    /* check "Normal" preview */
    element = 'button.nme-preview-mode-btn[data-mode="desktop"]';
    await utils.findElement(page, element);
    await utils.clickElement(page, page.locator(element).first());

    element = '#nme-preview-iframe-desktop';
    await utils.findElement(page, element);
    await expect(page.locator(element)).toBeVisible();

    locator = page.frameLocator(element).first();

    await checkElementsCount(locator, title_count, paragraph_count, image_count, button_count, col1_count, col2_count, float_count);

    /* check "Mobile" preview */
    element = 'button.nme-preview-mode-btn[data-mode="mobile"]';
    await utils.findElement(page, element);
    await utils.clickElement(page, page.locator(element).first());

    element = '#nme-preview-iframe-mobile';
    await utils.findElement(page, element);
    await expect(page.locator(element)).toBeVisible();

    locator = page.frameLocator(element).first();

    await checkElementsCount(locator, title_count, paragraph_count, image_count, button_count, col1_count, col2_count, float_count);

    /* close preview */
    element = 'button.nme-preview-close';
    await utils.findElement(page, element);
    await utils.clickElement(page, page.locator(element).first());

    await expect(page.locator('#nme-preview-popup')).toBeHidden();
}

/**
 * Clicks on a tab of setting panel with the given data_target_id.
 *
 * @param {string} data_target_id The value of the data-target-id attribute of the tab to click.
 * @return {Promise<void>}
 */
async function switchTab(data_target_id) {
    element = `.nme-setting-panels-header a[data-target-id="${data_target_id}"]`;
    await utils.findElement(page, element);
    await page.evaluate((element) => {
        document.querySelector(element).click();
    }, element);
}


test.beforeAll(async () => {
    const browser = await chromium.launch();
    page = await browser.newPage();
});

test.afterAll(async () => {
    await page.close();
});


test.describe.serial('Mailing Editing', () => {

    test.use({ storageState: 'storageState.json' });

    test('Mailing Editing', async () => {

        page.on('dialog', dialog => dialog.accept());

        await test.step('Mailing Test.', async () => {

            /* Step 1: open "New Mailing" page */
            await page.goto('civicrm/mailing/send');
            await utils.wait(wait_secs);
            await expect(page.locator('.crm-error')).toHaveCount(0);
            await utils.findElement(page, 'form#Group');

            /* Step 2: Select Recipients. */

            /* fill in "Name Your Mailing" */
            element = 'input#name';
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), vars.mail_name);

            /* select "Include Group(s)" */
            element = '.crm-mailing-group-form-block-includeGroups .select2-search__field';
            await utils.findElement(page, element);
            await page.locator(element).click();
            await page.keyboard.press('Enter');

            /* click "Next" */
            element = '#_qf_Group_next';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element));

            await utils.wait(wait_secs);

            await expect(page.locator('.crm-error')).toHaveCount(0);

            /* Step 3: Track and Respond. */

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

        });

        await test.step('Mailing Content Test - Blocks.', async () => {

            /* Step 4: Mailing Content - Blocks. */

            /* Step 4-1: fill in "Mailing Subject" */
            element = '#subject-editor';
            await utils.findElement(page, element);
            await page.locator(element).click();
            await page.keyboard.type(vars.mail_subject);
            await expect(page.locator('input#subject')).toHaveValue(vars.mail_subject);

            /* Step 4-2: Title */

            /* add "Title" block */
            element = '.nme-add-block-btn[data-type="title"]';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element).first());

            await expect(page.locator('.nmee-title')).toHaveCount(4);

            /* edit new added "Title" block */

            /* get "Title" block data-id */
            const title_data_id = await page.evaluate(() => {
                return document.querySelectorAll('.nmee-title')[3].getAttribute('data-id');
            });

            expect(title_data_id).not.toBeNull();
            expect(title_data_id).toBeDefined();

            /* click "Title" block */
            element = `.nmee-title[data-id="${title_data_id}"]`;
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element));

            /* edit "Title" block */
            element = `table[data-id="${title_data_id}"] .ql-editor[contenteditable="true"]`;
            await utils.findElement(page, element);
            await page.locator(element).click();
            await page.evaluate((args) => {
                document.querySelector(args.element).textContent = args.title_block_text;
            }, {element: element, title_block_text: vars.title_block_text});

            element = `table[data-id="${title_data_id}"] .editable-submit`;
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element));

            await expect(page.locator(`.nmee-title[data-id="${title_data_id}"]`)).toHaveText(vars.title_block_text);

            /* Step 4-3: Paragraph */

            /* add "Paragraph" block */
            element = '.nme-add-block-btn[data-type="paragraph"]';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element).first());

            await expect(page.locator('.nmee-paragraph')).toHaveCount(5);

            /* edit new added "Paragraph" block */

            /* get "Paragraph" block data-id */
            const paragraph_data_id = await page.evaluate(() => {
                return document.querySelectorAll('.nmee-paragraph')[4].getAttribute('data-id');
            });

            expect(paragraph_data_id).not.toBeNull();
            expect(paragraph_data_id).toBeDefined();

            /* click "Paragraph" block */
            element = `.nmee-paragraph[data-id="${paragraph_data_id}"]`;
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element));

            /* edit "Paragraph" block */
            element = `table[data-id="${paragraph_data_id}"] .ql-editor[contenteditable="true"]`;
            await utils.findElement(page, element);
            await page.locator(element).click();
            await page.evaluate((args) => {
                document.querySelector(args.element).textContent = args.paragraph_block_text;
            }, {element: element, paragraph_block_text: vars.paragraph_block_text});

            element = `table[data-id="${paragraph_data_id}"] .editable-submit`;
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element));

            await expect(page.locator(`.nmee-paragraph[data-id="${paragraph_data_id}"]`)).toHaveText(vars.paragraph_block_text);

            /* Step 4-4: Image */

            /* add "Image" block */
            element = '.nme-add-block-btn[data-type="image"]';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element).first());

            await expect(page.locator('.nmee-image')).toHaveCount(5);

            /* edit new added "Image" block */

            /* get "Image" block data-id */
            const image_data_id = await page.evaluate(() => {
                return document.querySelectorAll('.nmee-image')[4].getAttribute('data-id');
            });

            expect(image_data_id).not.toBeNull();
            expect(image_data_id).toBeDefined();

            /* click "Image" block */
            element = `.nmee-image[data-id="${image_data_id}"]`;
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element));

            /* click "Edit Link" */
            const image_block_control_el = `button#image-${image_data_id.replace('image-','')}-handle-link`;
            await page.evaluate((image_block_control_el) => {
                document.querySelector(image_block_control_el).click();
            }, image_block_control_el);

            /* edit link */
            const edit_link_el = '.edit-link';
            await utils.findElement(page, edit_link_el);
            await utils.fillInput(page.locator(edit_link_el), process.env.localUrl);

            /* click "Save" */
            const save_link_el = '.nme-edit-actions a[data-type="submit"]';
            await utils.findElement(page, save_link_el);
            await utils.clickElement(page, page.locator(save_link_el));

            /* check link edited */
            await expect(page.locator(`img[data-id="${image_data_id}"]`)).toHaveAttribute('data-link', process.env.localUrl);

            /* Step 4-5: Button */

            /* add "Button" block */
            element = '.nme-add-block-btn[data-type="button"]';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element).first());

            await expect(page.locator('.nmee-button')).toHaveCount(4);

            /* edit new added "Button" block */

            /* get "Button" block data-id */
            const button_data_id = await page.evaluate(() => {
                return document.querySelectorAll('.nmee-button')[3].getAttribute('data-id');
            });

            await expect(button_data_id).not.toBeNull();
            await expect(button_data_id).toBeDefined();

            /* click "Button" block */
            element = `.nmee-button[data-id="${button_data_id}"]`;
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element));

            /* edit "Button" block */
            element = `table[data-id="${button_data_id}"] .editable-input input`;
            await utils.findElement(page, element);
            await utils.fillInput(page.locator(element), vars.button_block_text);

            element = `table[data-id="${button_data_id}"] .editable-submit`;
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element));

            await expect(page.locator(`.nmee-button[data-id="${button_data_id}"]`)).toHaveText(vars.button_block_text);

            /* Step 4-6: Rich Content: 1 Column */

            /* add "Rich Content: 1 Column" block */
            element = '.nme-add-block-btn[data-type="rc-col-1"]';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element).first());

            await expect(page.locator('.nmee-rc-col-1')).toHaveCount(4);

            /* edit new added "Rich Content: 1 Column" block */

            /* get "Rich Content: 1 Column" block data-id */
            const rc_col_1_data_id = await page.evaluate(() => {
                const elements = document.querySelectorAll('.nmee-rc-col-1');
                for (let el of elements) {
                    if (el.textContent.includes('You can write a title here')) {
                        return el.getAttribute('data-id');
                    }
                }
            });

            expect(rc_col_1_data_id).not.toBeNull();
            expect(rc_col_1_data_id).toBeDefined();

            /* check "Rich Content: 1 Column" block elements */
            const rc_col_1_block_el = `.nmee-rc-col-1[data-id="${rc_col_1_data_id}"]`;
            await utils.findElement(page, rc_col_1_block_el);

            await checkElementsCount(page.locator(rc_col_1_block_el), 1, 1, 1, 1, 0, 0, 0);

            /* click "Edit Background of Block" */
            const rc_col_1_block_control_el = `button#rc-col-1-${rc_col_1_data_id.replace('rc-col-1-','')}-handle-block-bg`;
            await page.evaluate((rc_col_1_block_control_el) => {
                document.querySelector(rc_col_1_block_control_el).click();
            }, rc_col_1_block_control_el);

            /* pick color */
            const color_picker_el = '.pcr-app.visible';
            await utils.findElement(page, color_picker_el);
            await utils.fillInput(page.locator(`${color_picker_el} .pcr-interaction .pcr-result`), '#673AB7');

            /* check "Rich Content: 1 Column" block background color */
            const rc_col_1_title_bgc = await page.evaluate((rc_col_1_block_el) => {
                return document.querySelector(`${rc_col_1_block_el} .nmeb-inner`).style.backgroundColor;
            }, rc_col_1_block_el);

            await expect(rc_col_1_title_bgc).toBe('rgb(103, 58, 183)');

            const rc_col_1_paragraph_bgc = await page.evaluate((rc_col_1_block_el) => {
                return document.querySelector(`${rc_col_1_block_el} .nmeb-inner`).style.backgroundColor;
            }, rc_col_1_block_el);

            await expect(rc_col_1_paragraph_bgc).toBe('rgb(103, 58, 183)');

            const rc_col_1_button_bgc = await page.evaluate((rc_col_1_block_el) => {
                return document.querySelector(`${rc_col_1_block_el} .nmeb-inner`).style.backgroundColor;
            }, rc_col_1_block_el);

            await expect(rc_col_1_button_bgc).toBe('rgb(103, 58, 183)');

            /* Step 4-7: Rich Content: 2 Column */

            /* add "Rich Content: 2 Column" block */
            element = '.nme-add-block-btn[data-type="rc-col-2"]';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element).first());

            await expect(page.locator('.nmee-rc-col-2')).toHaveCount(1);

            /* edit new added "Rich Content: 2 Column" block */

            /* get "Rich Content: 2 Column" block data-id */
            const rc_col_2_data_id = await page.evaluate(() => {
                return document.querySelector('.nmee-rc-col-2').getAttribute('data-id');
            });

            expect(rc_col_2_data_id).not.toBeNull();
            expect(rc_col_2_data_id).toBeDefined();

            /* check "Rich Content: 2 Column" block elements */
            const rc_col_2_columns = ['.col-1', '.col-2'];

            for (const col of rc_col_2_columns) {
                const element = `.nmee-rc-col-2[data-id="${rc_col_2_data_id}"] ${col}`;
                await utils.findElement(page, element);

                await expect(page.locator(element).locator('.nmee-image')).toHaveCount(1);
                await expect(page.locator(element).locator('.nmee-paragraph')).toHaveCount(1);
                await expect(page.locator(element).locator('.nmee-button')).toHaveCount(1);
            }

            /* click "Edit Background of Block" */
            const rc_col_2_block_control_el = `button#rc-col-2-${rc_col_2_data_id.replace('rc-col-2-','')}-handle-block-bg`;
            await page.evaluate((rc_col_2_block_control_el) => {
                document.querySelector(rc_col_2_block_control_el).click();
            }, rc_col_2_block_control_el);

            /* pick color */
            await utils.findElement(page, color_picker_el);
            await utils.fillInput(page.locator(`${color_picker_el} .pcr-interaction .pcr-result`), '#CDDC39');

            /* check "Rich Content: 2 Column" block background color */
            for (const col of rc_col_2_columns) {
                const element = `.nmee-rc-col-2[data-id="${rc_col_2_data_id}"] ${col}`;
                await utils.findElement(page, element);

                const title_bgc = await page.evaluate((element) => {
                    return document.querySelector(`${element} .nmeb-inner`).style.backgroundColor;
                }, element);

                await expect(title_bgc).toBe('rgb(205, 220, 57)');

                const paragraph_bgc = await page.evaluate((element) => {
                    return document.querySelector(`${element} .nmeb-inner`).style.backgroundColor;
                }, element);

                await expect(paragraph_bgc).toBe('rgb(205, 220, 57)');

                const button_bgc = await page.evaluate((element) => {
                    return document.querySelector(`${element} .nmeb-inner`).style.backgroundColor;
                }, element);

                await expect(button_bgc).toBe('rgb(205, 220, 57)');
            }

            /* Step 4-8: Rich Content: Float */

            /* add "Rich Content: Float" block */
            element = '.nme-add-block-btn[data-type="rc-float"]';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element).first());

            await expect(page.locator('.nmee-rc-float')).toHaveCount(1);

            /* edit new added "Rich Content: Float" block */

            /* get "Rich Content: Float" block data-id */
            const rc_float_data_id = await page.evaluate(() => {
                return document.querySelector('.nmee-rc-float').getAttribute('data-id');
            });

            expect(rc_float_data_id).not.toBeNull();
            expect(rc_float_data_id).toBeDefined();

            /* check "Rich Content: Float" block elements */
            element = `.nmee-rc-float[data-id="${rc_float_data_id}"] .img-col`;
            await utils.findElement(page, element);
            await expect(page.locator(element).locator('.nmee-image')).toHaveCount(1);

            element = `.nmee-rc-float[data-id="${rc_float_data_id}"] .text-col`;
            await utils.findElement(page, element);
            await expect(page.locator(element).locator('.nmee-paragraph')).toHaveCount(1);
            await expect(page.locator(element).locator('.nmee-button')).toHaveCount(1);

            /* click "Edit Background of Block" */
            const rc_float_block_control_el = `button#rc-float-${rc_float_data_id.replace('rc-float-','')}-handle-block-bg`;
            await page.evaluate((rc_float_block_control_el) => {
                document.querySelector(rc_float_block_control_el).click();
            }, rc_float_block_control_el);

            /* pick color */
            await utils.findElement(page, color_picker_el);
            await utils.fillInput(page.locator(`${color_picker_el} .pcr-interaction .pcr-result`), '#FF9800');

            /* check "Rich Content: Float" block background color */
            element = `.nmee-rc-float[data-id="${rc_float_data_id}"]`;
            await utils.findElement(page, element);

            const rc_float_title_bgc = await page.evaluate((element) => {
                return document.querySelector(`${element} .nmeb-inner`).style.backgroundColor;
            }, element);

            await expect(rc_float_title_bgc).toBe('rgb(255, 152, 0)');

            const rc_float_paragraph_bgc = await page.evaluate((element) => {
                return document.querySelector(`${element} .nmeb-inner`).style.backgroundColor;
            }, element);

            await expect(rc_float_paragraph_bgc).toBe('rgb(255, 152, 0)');

            /* Step 4-9: Preview */
            await previewCheck(page, 5, 9, 9, 8, 4, 1, 1)

        });

        await page.reload();
        await utils.wait(wait_secs);

        await test.step('Mailing Content Test - Templates', async () => {

            /* Step 5: Mailing Content - Templates. */

            /* Step 5-1: fill in "Mailing Subject" */
            element = '#subject-editor';
            await utils.findElement(page, element);
            await page.locator(element).click();
            await page.keyboard.type(vars.mail_subject);
            await expect(page.locator('input#subject')).toHaveValue(vars.mail_subject);

            /* Step 5-2: 1 Column */

            /* switch to "Templates" tab */
            await switchTab('nme-select-tpl');

            await expect(page.locator('.nme-select-tpl-list.nme-setting-item-list')).toBeVisible();

            /* add "1 Column" block */
            element = '.nme-select-tpl-btn[data-name="col-1-full-width"]';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element).first());

            /* check elements from "1 Column" block */
            await checkElementsCount(page.locator('#nme-mail'), 3, 4, 4, 3, 3, 0, 0);

            /* switch to "Blocks" tab */
            await switchTab('nme-add-block');

            /* add "Title" block */
            element = '.nme-add-block-btn[data-type="title"]';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element).first());

            /* preview */
            await previewCheck(page, 4, 4, 4, 3, 3, 0, 0);

            /* Step 5-3: 1:2 Columns */

            /* switch to "Templates" tab */
            await switchTab('nme-select-tpl');

            await expect(page.locator('.nme-select-tpl-list.nme-setting-item-list')).toBeVisible();

            /* add "1:2 Columns" block */
            element = '.nme-select-tpl-btn[data-name="col-1-col-2"]';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element).first());

            /* check elements from "1:2 Columns" block */
            await checkElementsCount(page.locator('#nme-mail'), 1, 6, 6, 5, 1, 2, 0);

            /* switch to "Blocks" tab */
            await switchTab('nme-add-block');

            /* add "Title" block */
            element = '.nme-add-block-btn[data-type="title"]';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element).first());

            /* preview */
            await previewCheck(page, 2, 6, 6, 5, 1, 2, 0);

            /* Step 5-4: 1 Column + Float */

            /* switch to "Templates" tab */
            await switchTab('nme-select-tpl');

            await expect(page.locator('.nme-select-tpl-list.nme-setting-item-list')).toBeVisible();

            /* add "1 Column + Float" block */
            element = '.nme-select-tpl-btn[data-name="col-1-float"]';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element).first());

            /* check elements from "1 Column + Float" block */
            await checkElementsCount(page.locator('#nme-mail'), 1, 6, 6, 5, 1, 0, 4);

            /* switch to "Blocks" tab */
            await switchTab('nme-add-block');

            /* add "Title" block */
            element = '.nme-add-block-btn[data-type="title"]';
            await utils.findElement(page, element);
            await utils.clickElement(page, page.locator(element).first());

            /* preview */
            await previewCheck(page, 2, 6, 6, 5, 1, 0, 4);

        });

    });

});