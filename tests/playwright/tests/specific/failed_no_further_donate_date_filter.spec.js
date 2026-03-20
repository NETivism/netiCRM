const { test, expect, chromium } = require('@playwright/test');
const utils = require('../utils.js');

/** @type {import('@playwright/test').Page} */
let page;
var element;
const wait_secs = 2000;

const PAGE_URL = 'civicrm/search/FailedNoFurtherDonate?force=1';

// Today's date — created_date of contributions created in TC-setup will equal today.
const now = new Date();
const todayYear  = now.getFullYear();
const todayMonth = now.getMonth() + 1; // 1-indexed
const todayDay   = now.getDate();

// Test contacts populated in TC-setup:
//   A — failed today, no re-donation     → in today's range (TC-01 hit), in all-time (TC-02)
//   C — failed today, success today      → excluded always (re-donated within days window)
var testContactA = { firstName: '', lastName: '' };
var testContactC = { firstName: '', lastName: '' };

async function openSearchPage() {
  await page.goto(PAGE_URL);
  await utils.wait(wait_secs);
  await expect(page.locator('.crm-error')).toHaveCount(0);

  const accordionElement = page.locator(
    '.crm-accordion-wrapper.crm-custom_search_form-accordion.crm-accordion-processed'
  );
  const count = await accordionElement.count();
  if (count > 0) {
    const classNames = await accordionElement.getAttribute('class');
    if (classNames.includes('crm-accordion-closed')) {
      await utils.clickElement(page, page.locator('.crm-accordion-header'));
    }
  }
}

/**
 * Creates a new contact inline within the contribution add form with a Failed status.
 * created_date is auto-set by the server to NOW.
 * Returns { firstName, lastName }.
 */
async function createContactWithFailedContribution(prefix) {
  const firstName = prefix + utils.makeid(5);
  const lastName  = prefix + utils.makeid(5);

  await page.goto('civicrm/contribute/add?reset=1&action=add&context=standalone');
  await utils.wait(wait_secs);

  await utils.selectOption(page.locator('#profiles_1'), '4'); // New Individual
  await utils.fillInput(page.locator('form#Edit #last_name'), lastName);
  await utils.fillInput(page.locator('form#Edit #first_name'), firstName);
  await page.locator('#_qf_Edit_next').click();
  await utils.wait(wait_secs);
  await expect(page.locator('#contact_1')).toHaveValue(`${firstName} ${lastName}`);

  await utils.selectOption(page.locator('#contribution_type_id'), '1'); // General
  await utils.fillInput(page.locator('#total_amount'), '100');
  await utils.selectOption(page.locator('#contribution_status_id'), '4'); // Failed

  await page.locator('#_qf_Contribution_upload-bottom').click();
  await utils.wait(wait_secs);
  await expect(page.locator('.crm-error')).toHaveCount(0);

  // Extract contact ID from the redirect URL (CiviCRM redirects to contribution view with ?cid=XXX)
  const redirectUrl = page.url();
  const cid = new URLSearchParams(redirectUrl.split('?')[1] || '').get('cid');

  return { firstName, lastName, cid };
}

/**
 * Adds a Completed contribution to an existing contact by navigating directly
 * to the contribution form with the contact ID pre-filled (?cid=).
 * created_date is auto-set by the server to NOW.
 */
async function addSuccessContributionToContact(cid) {
  await page.goto(`civicrm/contribute/add?reset=1&action=add&context=standalone&cid=${cid}`);
  await utils.wait(wait_secs);

  await utils.selectOption(page.locator('#contribution_type_id'), '1'); // General
  await utils.fillInput(page.locator('#total_amount'), '100');
  await utils.selectOption(page.locator('#contribution_status_id'), '1'); // Completed

  await page.locator('#_qf_Contribution_upload-bottom').click();
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

test.describe.serial('FailedNoFurtherDonate Date Filter', () => {

  test.use({ storageState: 'storageState.json' });

  // ── UI presence ──

  test('TC-UI: Date filter fields are present in the search form', async () => {
    await openSearchPage();

    await utils.findElement(page, '#days');

    element = '#failed_date_from';
    await utils.findElement(page, element);
    console.log('Found failed_date_from field.');

    element = '#failed_date_to';
    await utils.findElement(page, element);
    console.log('Found failed_date_to field.');
  });

  // ── Validation tests (no data dependency) ──

  test('TC-03a / AC-5: Invalid date format triggers validation error', async () => {
    await openSearchPage();

    await utils.findElement(page, '#failed_date_from');
    await page.evaluate(() => {
      document.getElementById('failed_date_from').removeAttribute('readonly');
      document.getElementById('failed_date_from').value = 'invalid-date-xyz';
    });

    await page.locator('#_qf_Custom_refresh-top').click();
    await utils.wait(wait_secs);

    const pageText = await page.locator('body').innerText();
    const hasErrorText = pageText.includes('valid date') || pageText.includes('errors in the form');
    expect(hasErrorText).toBeTruthy();
    console.log('TC-03a passed: invalid date format triggers validation error.');
  });

  test('TC-03b / AC-6: Start date later than end date triggers validation error', async () => {
    await openSearchPage();

    await utils.findElement(page, '#failed_date_from');
    await utils.selectDate(page, page.locator('#failed_date_from'), 2025, 2, 28);

    await utils.findElement(page, '#failed_date_to');
    await utils.selectDate(page, page.locator('#failed_date_to'), 2025, 2, 1);

    await page.locator('#_qf_Custom_refresh-top').click();
    await utils.wait(wait_secs);

    const pageText = await page.locator('body').innerText();
    const hasErrorText = pageText.includes('must be earlier') || pageText.includes('errors in the form');
    expect(hasErrorText).toBeTruthy();
    console.log('TC-03b passed: date order validation error shown.');
  });

  test('TC-04 / AC-2: Same start and end date (boundary) is accepted', async () => {
    await openSearchPage();

    await utils.selectOption(page.locator('#days'), '7');
    await utils.selectDate(page, page.locator('#failed_date_from'), 2025, 2, 15);
    await utils.selectDate(page, page.locator('#failed_date_to'), 2025, 2, 15);

    await utils.clickElement(page, page.locator('#_qf_Custom_refresh-top'));
    await utils.wait(wait_secs);

    await expect(page.locator('.crm-error')).toHaveCount(0);
    console.log('TC-04 passed: same start/end date search completes without errors.');
  });

  // ── Data-driven tests ──

  test('TC-setup: Create test contacts A and C', async () => {
    await test.step('Contact A — failed today, no re-donation', async () => {
      const a = await createContactWithFailedContribution('TFA');
      testContactA.firstName = a.firstName;
      testContactA.lastName  = a.lastName;
      console.log(`Contact A: ${a.lastName}, ${a.firstName} (failed, created_date = today)`);
    });

    await test.step('Contact C — failed today, then re-donated today (within 7-day window)', async () => {
      const c = await createContactWithFailedContribution('TFC');
      testContactC.firstName = c.firstName;
      testContactC.lastName  = c.lastName;
      console.log(`Contact C: ${c.lastName}, ${c.firstName} (failed, created_date = today, cid=${c.cid})`);

      await addSuccessContributionToContact(c.cid);
      console.log(`Contact C success contribution added (created_date = today, within 7-day window)`);
    });
  });

  /**
   * TC-01 / AC-2 + AC-4
   *
   * The date filter acts on created_date (auto-set on record creation = today).
   *
   * Sub-check 1 (hit): today's date range → Contact A appears; Contact C excluded (re-donated within 7 days).
   * Sub-check 2 (miss): date range 2 years ago → Contact A absent (proves old-data exclusion).
   */
  test('TC-01 / AC-2 + AC-4: Date range filter correctly includes and excludes records', async () => {
    await test.step('Hit range: today → A in results, C excluded by days window', async () => {
      await openSearchPage();

      await utils.selectOption(page.locator('#days'), '7');
      await utils.selectDate(page, page.locator('#failed_date_from'), todayYear, todayMonth, todayDay);
      await utils.selectDate(page, page.locator('#failed_date_to'),   todayYear, todayMonth, todayDay);
      console.log(`Set date range: ${todayYear}-${todayMonth}-${todayDay} to ${todayYear}-${todayMonth}-${todayDay}`);

      await utils.clickElement(page, page.locator('#_qf_Custom_refresh-top'));
      await utils.wait(wait_secs);
      await expect(page.locator('.crm-error')).toHaveCount(0);

      const resultsTable = page.locator('table.selector');
      await expect(resultsTable).toBeVisible();

      // Contact A (failed today, no re-donation) → must appear
      await expect(resultsTable).toContainText(testContactA.lastName);
      console.log(`✓ Contact A (${testContactA.lastName}) found — inside date range, no re-donation.`);

      // Contact C (re-donated today, within 7-day window) → must NOT appear
      const pageText = await page.locator('body').innerText();
      expect(pageText).not.toContain(testContactC.lastName);
      console.log(`✓ Contact C (${testContactC.lastName}) absent — re-donated within 7 days.`);
    });

    await test.step('Miss range: 2 years ago → A absent (old-data exclusion)', async () => {
      await openSearchPage();

      const pastYear = todayYear - 2;
      await utils.selectOption(page.locator('#days'), '7');
      await utils.selectDate(page, page.locator('#failed_date_from'), pastYear, 1,  1);
      await utils.selectDate(page, page.locator('#failed_date_to'),   pastYear, 12, 31);
      console.log(`Set date range: ${pastYear}-01-01 to ${pastYear}-12-31`);

      await utils.clickElement(page, page.locator('#_qf_Custom_refresh-top'));
      await utils.wait(wait_secs);
      await expect(page.locator('.crm-error')).toHaveCount(0);

      // Contact A (created today) must NOT appear in this past range
      const pageText = await page.locator('body').innerText();
      expect(pageText).not.toContain(testContactA.lastName);
      console.log(`✓ Contact A (${testContactA.lastName}) absent — created today, outside past range.`);
    });
  });

  /**
   * TC-02 / AC-3
   *
   * No date filter = backward-compatible all-time search.
   * Contact A (failed today, no re-donation) must appear.
   * Contact C (re-donated within 7 days) must NOT appear.
   */
  test('TC-02 / AC-3: No date filter (all-time) — backward compat preserved', async () => {
    await openSearchPage();

    await utils.selectOption(page.locator('#days'), '7');
    // Leave date fields empty

    await utils.clickElement(page, page.locator('#_qf_Custom_refresh-top'));
    await utils.wait(wait_secs);
    await expect(page.locator('.crm-error')).toHaveCount(0);

    const resultsTable = page.locator('table.selector');
    await expect(resultsTable).toBeVisible();

    // Contact A (failed today, no re-donation) → must appear in all-time results
    await expect(resultsTable).toContainText(testContactA.lastName);
    console.log(`✓ Contact A (${testContactA.lastName}) found in all-time results.`);

    // Contact C (re-donated within 7 days) → must NOT appear even without date filter
    const pageText = await page.locator('body').innerText();
    expect(pageText).not.toContain(testContactC.lastName);
    console.log(`✓ Contact C (${testContactC.lastName}) absent — re-donated within 7 days.`);
  });

});
