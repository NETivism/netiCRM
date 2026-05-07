// TC-01: Risk warning dialog for non-3D TapPay payment processors on external contribution pages
// Verifies AC-1 (dialog shown with correct list), AC-2 (dialog suppressed in safe scenarios),
// and AC-3 (Confirm saves / Cancel stays on page).
//
// Uses an existing external contribution page rather than creating one, because the
// CiviCRM multi-step wizard cannot reliably return a new page ID via the UI flow.
const { test, expect, chromium } = require('@playwright/test');
const utils = require('../utils.js');

/** @type {import('@playwright/test').Page} */
let page;
let browser;

const state = {
  suffix: null,
  ppAId: null,    // TapPay, no 3D
  ppBId: null,    // TapPay, no 3D
  ppCId: null,    // TapPay, with 3D
  ppSpgId: null,  // SPGATEWAY (non-TapPay)
  pageId: null,   // external contribution page ID
  ppAName: null,
  ppBName: null,
  ppCName: null,
  ppSpgName: null,
};

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

async function getProcessorIdByName(page, name) {
  await page.goto('/civicrm/admin/paymentProcessor?reset=1');
  await page.waitForLoadState('networkidle');
  const ppId = await page.evaluate((targetName) => {
    const cells = document.querySelectorAll('td.crm-payment_processor-name');
    for (const cell of cells) {
      if (cell.textContent.trim() === targetName) {
        const row = cell.closest('tr');
        if (row && row.id && row.id.startsWith('row_')) {
          return row.id.replace('row_', '');
        }
      }
    }
    return null;
  }, name);
  return ppId;
}

async function createTapPayProcessor(page, name, enable3D) {
  await page.goto('/civicrm/admin/paymentProcessor?action=add&reset=1&pp=TapPay');
  await page.waitForLoadState('networkidle');

  await page.locator('input[name="name"]').fill(name);
  await page.locator('input[name="user_name"]').fill(`merchant_${name}`);
  await page.locator('input[name="password"]').fill(`key_${name}`);

  const sigField = page.locator('input[name="signature"]');
  if (await sigField.count() > 0) {
    await sigField.fill(`appid_${name}`);
  }
  const subjectField = page.locator('input[name="subject"]');
  if (await subjectField.count() > 0) {
    await subjectField.fill(`appkey_${name}`);
  }

  if (enable3D) {
    // The 3D-Secure checkbox is injected by TapPay JS over the url_site text field.
    // Checking it sets the hidden url_site input to '1'.
    const checkbox3D = page.locator('.crm-paymentProcessor-form-block-url_site input[type="checkbox"]');
    await checkbox3D.waitFor({ state: 'attached', timeout: 5000 });
    await checkbox3D.check();
  }

  await page.locator('#_qf_PaymentProcessor_next-top').click();
  await page.waitForLoadState('networkidle');

  return await getProcessorIdByName(page, name);
}

async function createSpgatewayProcessor(page, name) {
  await page.goto('/civicrm/admin/paymentProcessor?action=add&reset=1&pp=SPGATEWAY');
  await page.waitForLoadState('networkidle');

  await page.locator('input[name="name"]').fill(name);
  await page.locator('input[name="user_name"]').fill(`merchant_${name}`);
  await page.locator('input[name="password"]').fill(`hashkey_${name}`);
  const sigField = page.locator('input[name="signature"]');
  if (await sigField.count() > 0) {
    await sigField.fill(`hashiv_${name}`);
  }

  await page.locator('#_qf_PaymentProcessor_next-top').click();
  await page.waitForLoadState('networkidle');

  return await getProcessorIdByName(page, name);
}

async function deleteProcessor(page, id) {
  if (!id) return;
  await page.goto(`/civicrm/admin/paymentProcessor?action=delete&reset=1&id=${id}`);
  await page.waitForLoadState('networkidle');
  const submitBtn = page.locator('#_qf_PaymentProcessor_next-bottom, #_qf_PaymentProcessor_next-top');
  if (await submitBtn.count() > 0) {
    await submitBtn.first().click();
    await page.waitForLoadState('networkidle');
  }
}

/**
 * Find the first external (is_internal unchecked) contribution page ID.
 * Iterates the listing and inspects each page's Settings form.
 */
async function findExternalPageId(page) {
  await page.goto('/civicrm/admin/contribute?reset=1');
  await page.waitForLoadState('networkidle');

  const rowIds = await page.evaluate(() => {
    return [...document.querySelectorAll('tr[id^="row_"]')].map(r => r.id.replace('row_', ''));
  });
  console.log('[findExternalPageId] Candidate page IDs:', rowIds);

  for (const id of rowIds) {
    await page.goto(`/civicrm/admin/contribute/add?reset=1&action=update&id=${id}`);
    await page.waitForLoadState('networkidle');
    if (await page.locator('form#Settings').count() === 0) continue;
    const isInternalChecked = await page.locator('#is_internal').isChecked().catch(() => false);
    if (!isInternalChecked) {
      console.log('[findExternalPageId] Using external page ID:', id);
      return id;
    }
  }
  const fallback = rowIds[0] || '1';
  console.log('[findExternalPageId] Fallback to page ID:', fallback);
  return fallback;
}

/**
 * Ensure the Amount form is in a valid, save-ready state:
 * - is_monetary checked (to show payment processor checkboxes)
 * - amount_block_is_active + is_allow_other_amount checked (satisfies amount block rule)
 * - is_pay_later checked with text + receipt (removes hard requirement for a payment processor)
 */
async function ensureFormValid(page) {
  const isMonetary = page.locator('#is_monetary');
  if (!await isMonetary.isChecked()) {
    await isMonetary.click();
    await expect(page.locator('#is_monetary_child_table')).toBeVisible();
  }

  const amountBlock = page.locator('#amount_block_is_active');
  if (!await amountBlock.isChecked()) {
    await amountBlock.click();
  }
  const allowOther = page.locator('#is_allow_other_amount');
  if (!await allowOther.isChecked()) {
    await allowOther.click();
  }

  const payLater = page.locator('#is_pay_later');
  if (!await payLater.isChecked()) {
    await payLater.click();
    await expect(page.locator('#payLaterFields')).toBeVisible();
  }
  const payLaterText = page.locator('#pay_later_text');
  if (!await payLaterText.inputValue()) {
    await payLaterText.fill('I will send payment by check');
  }
  const receipt = page.locator('#pay_later_receipt');
  if (!await receipt.inputValue()) {
    await receipt.fill('Please mail your check within 3 business days.');
  }
}

// ---------------------------------------------------------------------------
// Test suite
// ---------------------------------------------------------------------------

test.beforeAll(async () => {
  browser = await chromium.launch();
  page = await browser.newPage();
});

test.afterAll(async () => {
  if (page && !page.isClosed()) {
    for (const ppId of [state.ppAId, state.ppBId, state.ppCId, state.ppSpgId]) {
      await deleteProcessor(page, ppId).catch(() => {});
    }
    await page.close();
  }
  await browser.close();
});

test.describe.serial('TapPay Risk Warning Dialog', () => {
  test.use({ storageState: 'storageState.json' });

  // -------------------------------------------------------------------------
  // Setup
  // -------------------------------------------------------------------------
  test('Setup: find external contribution page and create test payment processors', async () => {
    state.suffix = Date.now();
    state.ppAName = `TapPay 商店 A ${state.suffix}`;
    state.ppBName = `TapPay 商店 B ${state.suffix}`;
    state.ppCName = `TapPay 商店 C ${state.suffix}`;
    state.ppSpgName = `SPGATEWAY Test ${state.suffix}`;

    await test.step('Find existing external contribution page', async () => {
      state.pageId = await findExternalPageId(page);
      expect(state.pageId).toBeTruthy();
      console.log('Using contribution page ID:', state.pageId);
    });

    await test.step('Create TapPay Store A (no 3D secure)', async () => {
      state.ppAId = await createTapPayProcessor(page, state.ppAName, false);
      expect(state.ppAId).toBeTruthy();
      console.log('TapPay A ID:', state.ppAId);
    });

    await test.step('Create TapPay Store B (no 3D secure)', async () => {
      state.ppBId = await createTapPayProcessor(page, state.ppBName, false);
      expect(state.ppBId).toBeTruthy();
      console.log('TapPay B ID:', state.ppBId);
    });

    await test.step('Create TapPay Store C (3D secure enabled)', async () => {
      state.ppCId = await createTapPayProcessor(page, state.ppCName, true);
      expect(state.ppCId).toBeTruthy();
      console.log('TapPay C ID:', state.ppCId);
    });

    await test.step('Create SPGATEWAY processor (non-TapPay, optional)', async () => {
      state.ppSpgId = await createSpgatewayProcessor(page, state.ppSpgName);
      console.log('SPGATEWAY ID:', state.ppSpgId);
    });
  });

  // -------------------------------------------------------------------------
  // TC-01: Warning dialog appears, lists only the non-3D TapPay processors (AC-1)
  // -------------------------------------------------------------------------
  test('TC-01: Warning dialog appears listing only non-3D TapPay processors', async () => {

    await test.step('Navigate to Amount settings for the external page', async () => {
      await page.goto(`/civicrm/admin/contribute/amount?reset=1&action=update&id=${state.pageId}`);
      await page.waitForLoadState('networkidle');
      await utils.findElement(page, 'form#Amount');
    });

    await test.step('Set form to valid save-ready state', async () => {
      await ensureFormValid(page);
    });

    await test.step('Select TapPay A, B, C and SPGATEWAY checkboxes', async () => {
      for (const ppId of [state.ppAId, state.ppBId, state.ppCId, state.ppSpgId].filter(Boolean)) {
        const cb = page.locator(`input[name="payment_processor[${ppId}]"]`);
        if (await cb.count() > 0) {
          await cb.check();
          await expect(cb).toBeChecked();
        }
      }
    });

    await test.step('Click Save — risk warning dialog must appear', async () => {
      await page.locator('[id^="_qf_Amount_upload"]').first().click();
      await expect(page.locator('#dialog-tappay-risk')).toBeVisible({ timeout: 5000 });
    });

    await test.step('Dialog title contains the warning symbol ⚠', async () => {
      const titleText = await page.locator('.ui-dialog-titlebar .ui-dialog-title').textContent();
      expect(titleText).toContain('⚠');
    });

    await test.step('Dialog body lists TapPay A and B but not C (3D) or SPGATEWAY', async () => {
      const list = page.locator('#tappay-risk-processor-list li');
      await expect(list.first()).toBeVisible();

      let listText = '';
      const count = await list.count();
      for (let i = 0; i < count; i++) {
        listText += await list.nth(i).textContent();
      }

      expect(listText).toContain(state.ppAName);
      expect(listText).toContain(state.ppBName);
      expect(listText).not.toContain(state.ppCName);
      if (state.ppSpgId) {
        expect(listText).not.toContain(state.ppSpgName);
      }
    });

    // AC-3: Confirm → save completes
    await test.step('AC-3: Confirm button completes the save without errors', async () => {
      const confirmBtn = page.locator('.ui-dialog-buttonset button').first();
      await confirmBtn.click();
      await page.waitForLoadState('networkidle');
      await expect(page.locator('#dialog-tappay-risk')).not.toBeVisible();
      await expect(page.locator('.crm-error')).toHaveCount(0);
    });
  });

  // -------------------------------------------------------------------------
  // TC-01 Cancel path: Cancel keeps user on Amount page, selections intact (AC-3)
  // -------------------------------------------------------------------------
  test('TC-01: Cancel keeps user on Amount page with processor selections intact', async () => {

    await test.step('Navigate back to Amount settings', async () => {
      await page.goto(`/civicrm/admin/contribute/amount?reset=1&action=update&id=${state.pageId}`);
      await page.waitForLoadState('networkidle');
      await utils.findElement(page, 'form#Amount');
    });

    await test.step('Set form to valid state and check non-3D TapPay A and B', async () => {
      await ensureFormValid(page);

      for (const ppId of [state.ppAId, state.ppBId].filter(Boolean)) {
        const cb = page.locator(`input[name="payment_processor[${ppId}]"]`);
        if (await cb.count() > 0) {
          await cb.check();
          await expect(cb).toBeChecked();
        }
      }
    });

    await test.step('Click Save — dialog appears', async () => {
      await page.locator('[id^="_qf_Amount_upload"]').first().click();
      await expect(page.locator('#dialog-tappay-risk')).toBeVisible({ timeout: 5000 });
    });

    await test.step('AC-3: Click Cancel — dialog closes, page stays on Amount', async () => {
      const cancelBtn = page.locator('.ui-dialog-buttonset button').last();
      await cancelBtn.click();

      await expect(page.locator('#dialog-tappay-risk')).not.toBeVisible();
      await expect(page.locator('form#Amount')).toBeVisible();
      expect(page.url()).toContain('amount');
    });

    await test.step('Processor checkboxes A and B retain checked state after Cancel', async () => {
      for (const ppId of [state.ppAId, state.ppBId].filter(Boolean)) {
        const cb = page.locator(`input[name="payment_processor[${ppId}]"]`);
        if (await cb.count() > 0) {
          await expect(cb).toBeChecked();
        }
      }
    });
  });

  // -------------------------------------------------------------------------
  // AC-2: No dialog when only the 3D-enabled TapPay processor is selected
  // -------------------------------------------------------------------------
  test('AC-2: No warning dialog when only the 3D-enabled TapPay processor is selected', async () => {

    await page.goto(`/civicrm/admin/contribute/amount?reset=1&action=update&id=${state.pageId}`);
    await page.waitForLoadState('networkidle');
    await utils.findElement(page, 'form#Amount');

    await ensureFormValid(page);

    // Uncheck A and B; check only C (3D)
    for (const ppId of [state.ppAId, state.ppBId].filter(Boolean)) {
      const cb = page.locator(`input[name="payment_processor[${ppId}]"]`);
      if (await cb.count() > 0) await cb.uncheck();
    }
    if (state.ppCId) {
      const cbC = page.locator(`input[name="payment_processor[${state.ppCId}]"]`);
      if (await cbC.count() > 0) await cbC.check();
    }

    await page.locator('[id^="_qf_Amount_upload"]').first().click();
    await page.waitForLoadState('networkidle');

    const dialogVisible = await page.locator('#dialog-tappay-risk').isVisible().catch(() => false);
    expect(dialogVisible).toBe(false);
  });
});
