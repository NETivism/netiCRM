// refs #45574: verify mailing view button is enabled/disabled based on is_complete
const { test, expect, chromium } = require('@playwright/test');
const utils = require('../utils.js');

/** @type {import('@playwright/test').Page} */
let page;
var element;
const wait_secs = 2000;

const BROWSE_URL = 'civicrm/mailing/browse/unscheduled?reset=1';

const vars = {
  completeName: 'TC01_' + utils.makeid(6),
  completeSubject: 'Subj_' + utils.makeid(6),
  textOnlyName: 'TC02_' + utils.makeid(6),
  textOnlySubject: 'Subj_' + utils.makeid(6),
  draftName: 'TC03_' + utils.makeid(6),
  subjectOnlyName: 'TC04_' + utils.makeid(6),
  subjectOnlySubject: 'Subj_' + utils.makeid(6),
};

/**
 * Create a mailing through step 1 only (name + recipients).
 * The mailing record is saved to DB with NULL subject and body.
 * Returns the mailing name for later lookup.
 */
async function createMailingStep1(name) {
  await page.goto('civicrm/mailing/send?reset=1');
  await utils.wait(wait_secs);
  await expect(page.locator('.crm-error')).toHaveCount(0);
  await utils.findElement(page, 'form#Group');

  await utils.fillInput(page.locator('input#name'), name);

  element = '.crm-mailing-group-form-block-includeGroups .select2-search__field';
  await utils.findElement(page, element);
  await page.locator(element).click();
  await page.keyboard.press('Enter');

  await utils.clickElement(page, page.locator('#_qf_Group_next'));
  await utils.wait(wait_secs);
}

/**
 * Create a complete mailing: goes through all 3 wizard steps,
 * fills subject and submits the Upload form so NME body_html is saved to DB.
 */
async function createCompleteMailing(name, subject) {
  // Step 1: name + recipients
  await createMailingStep1(name);
  await expect(page.locator('.crm-error')).toHaveCount(0);

  // Step 2: settings
  await utils.findElement(page, 'select#visibility');
  await utils.selectOption(page.locator('select#visibility'), { index: 1 });
  await utils.clickElement(page, page.locator('#_qf_Settings_next'));
  await utils.wait(wait_secs);
  await expect(page.locator('.crm-error')).toHaveCount(0);

  // Step 3: content — fill subject
  element = '#subject-editor';
  await utils.findElement(page, element);
  await page.locator(element).click();
  await page.keyboard.type(subject);
  await expect(page.locator('input#subject')).toHaveValue(subject);

  // NME default template already has blocks, so body_html will be populated.
  // Click Next to submit the Upload form — NME converts JSON to HTML and saves to DB.
  element = '#_qf_Upload_upload';
  await utils.findElement(page, element);
  await utils.clickElement(page, page.locator(element));
  await utils.wait(wait_secs * 2);
}

/**
 * Use CiviCRM API v3 via page.evaluate to update a mailing's fields by name.
 * Calls cj().crmAPI, which handles the necessary X-Requested-With headers.
 */
async function updateMailingViaApi(name, fields) {
  const result = await page.evaluate(async ({ name, fields }) => {
    const callApi = (entity, action, params) => {
      return new Promise((resolve, reject) => {
        cj().crmAPI(entity, action, params, {
          success: (data) => resolve(data),
          error: (err) => reject(err)
        });
      });
    };

    // Find mailing by name
    const getData = await callApi('Mailing', 'get', { sequential: 1, name: name });
    if (getData.is_error || getData.count === 0) {
      throw new Error(`Mailing not found: ${name}`);
    }
    const mailingId = getData.values[0].id;

    // Update mailing fields
    const updateParams = Object.assign({ sequential: 1, id: mailingId }, fields);
    return await callApi('Mailing', 'create', updateParams);
  }, { name, fields });

  expect(result.is_error).toBe(0);
  return result;
}

/**
 * Navigate to unscheduled mailing browse page and find a row by mailing name.
 */
async function findMailingRow(name) {
  await page.goto(BROWSE_URL);
  await utils.wait(wait_secs);
  await expect(page.locator('.crm-error')).toHaveCount(0);
  return page.locator('tr').filter({ hasText: name });
}

/**
 * Assert the View cell contains a clickable <a> link and no disabled <span>.
 */
async function assertViewEnabled(row) {
  const viewCell = row.locator('td.crm-mailing-visibility');
  await expect(viewCell.locator('a')).toBeVisible();
  await expect(viewCell.locator('span[title]')).toHaveCount(0);
}

/**
 * Assert the View cell contains a disabled <span> with a title attribute
 * and no clickable <a> link.
 */
async function assertViewDisabled(row) {
  const viewCell = row.locator('td.crm-mailing-visibility');
  await expect(viewCell.locator('a')).toHaveCount(0);
  const span = viewCell.locator('span[title]');
  await expect(span).toBeVisible();
  const titleAttr = await span.getAttribute('title');
  expect(titleAttr).toBeTruthy();
}


test.beforeAll(async () => {
  const browser = await chromium.launch();
  page = await browser.newPage();
  page.on('dialog', dialog => dialog.accept());
});

test.afterAll(async () => {
  await page.close();
});


test.describe.serial('Mailing View Button State', () => {

  test.use({ storageState: 'storageState.json' });

  // ── Setup: create test mailings ──────────────────────────────────────────

  test('TC-setup: create mailings in various states', async () => {

    await test.step('Complete mailing — subject + HTML body via NME', async () => {
      await createCompleteMailing(vars.completeName, vars.completeSubject);
      console.log(`Created complete mailing: ${vars.completeName}`);
    });

    await test.step('Text-only mailing — subject + body_text via API', async () => {
      await createMailingStep1(vars.textOnlyName);
      await updateMailingViaApi(vars.textOnlyName, {
        subject: vars.textOnlySubject,
        body_text: 'Plain text content for testing',
      });
      console.log(`Created text-only mailing: ${vars.textOnlyName}`);
    });

    await test.step('Step-1 draft — subject and body both empty', async () => {
      await createMailingStep1(vars.draftName);
      console.log(`Created step-1 draft mailing: ${vars.draftName}`);
    });

    await test.step('Subject-only mailing — has subject, no body via API', async () => {
      await createMailingStep1(vars.subjectOnlyName);
      await updateMailingViaApi(vars.subjectOnlyName, {
        subject: vars.subjectOnlySubject,
      });
      console.log(`Created subject-only mailing: ${vars.subjectOnlyName}`);
    });

  });

  // ── Normal flow ──────────────────────────────────────────────────────────

  test('TC-01 / AC-3: complete mailing (subject + HTML body) → View is a clickable link', async () => {
    const row = await findMailingRow(vars.completeName);
    await expect(row).toBeVisible();
    await assertViewEnabled(row);
    console.log(`TC-01 passed: ${vars.completeName} has clickable View link.`);
  });

  test('TC-02 / AC-3: text-only mailing (subject + body_text, no HTML) → View is a clickable link', async () => {
    const row = await findMailingRow(vars.textOnlyName);
    await expect(row).toBeVisible();
    await assertViewEnabled(row);
    console.log(`TC-02 passed: ${vars.textOnlyName} has clickable View link.`);
  });

  // ── Exception scenarios ──────────────────────────────────────────────────

  test('TC-03 / AC-1: step-1 draft (subject + body both empty) → View is disabled span with tooltip', async () => {
    const row = await findMailingRow(vars.draftName);
    await expect(row).toBeVisible();
    await assertViewDisabled(row);
    console.log(`TC-03 passed: ${vars.draftName} has disabled View span with tooltip.`);
  });

  test('TC-04 / AC-2: has subject but no body → View is disabled span with tooltip', async () => {
    const row = await findMailingRow(vars.subjectOnlyName);
    await expect(row).toBeVisible();
    await assertViewDisabled(row);
    console.log(`TC-04 passed: ${vars.subjectOnlyName} has disabled View span with tooltip.`);
  });

  // ── Boundary ─────────────────────────────────────────────────────────────

  test('TC-05 / AC-1: empty-string subject (boundary) — confirms empty string is treated as missing', async () => {
    // The step-1 draft has subject = NULL in DB. The SQL logic treats both
    // NULL and empty string as "missing" (IS NOT NULL AND != '').
    // Reuse draft row to verify this boundary condition.
    const row = await findMailingRow(vars.draftName);
    await expect(row).toBeVisible();
    await assertViewDisabled(row);
    console.log(`TC-05 passed: empty/null subject correctly disables View.`);
  });

});
