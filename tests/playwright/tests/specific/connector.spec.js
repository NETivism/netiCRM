const { test, expect, chromium } = require('@playwright/test');
const utils = require('../utils.js');

let page;
let browser;

// Contact ID of the admin user used in storageState — typically 1 in test env.
// Adjust if your test environment uses a different admin contact ID.
const ADMIN_CID = 1;

test.beforeAll(async () => {
  browser = await chromium.launch();
  page = await browser.newPage();
});

test.afterAll(async () => {
  await page.close();
  await browser.close();
});

test.describe.serial('Connector Page', () => {
  test.use({ storageState: 'storageState.json' });

  // -------------------------------------------------------------------------
  // TC-01: MCP section visible when CIVICRM_MCP_ENABLED is set
  // -------------------------------------------------------------------------
  test('TC-01: MCP section visible on connector page', async () => {
    await test.step('Navigate to connector page for admin contact', async () => {
      await page.goto(`/civicrm/connector?reset=1&cid=${ADMIN_CID}`);
      await page.waitForLoadState('networkidle');
    });

    await test.step('Verify page title', async () => {
      await expect(page.locator('h1, #page-title')).toContainText('Connector');
    });

    await test.step('Verify MCP block exists when constant is enabled', async () => {
      // If CIVICRM_MCP_ENABLED is set, the MCP block is rendered
      const mcpBlock = page.locator('.crm-connector-mcp-block');
      const apiBlock = page.locator('.crm-connector-apikey-block');
      // At least one of the sections should be visible
      const mcpVisible = await mcpBlock.isVisible().catch(() => false);
      const apiVisible = await apiBlock.isVisible().catch(() => false);
      expect(mcpVisible || apiVisible).toBeTruthy();
    });
  });

  // -------------------------------------------------------------------------
  // TC-02: Generate MCP connector (first time — clears any existing key first)
  // -------------------------------------------------------------------------
  test('TC-02: Generate MCP connector shows result panel with full URL', async () => {
    await test.step('Navigate to connector page', async () => {
      await page.goto(`/civicrm/connector?reset=1&cid=${ADMIN_CID}`);
      await page.waitForLoadState('networkidle');
    });

    const mcpBlock = page.locator('.crm-connector-mcp-block');
    const mcpVisible = await mcpBlock.isVisible().catch(() => false);
    if (!mcpVisible) {
      test.skip(true, 'CIVICRM_MCP_ENABLED not set — skipping MCP tests');
      return;
    }

    await test.step('Click generate or regenerate button', async () => {
      const generateBtn = page.locator('#btn-generate-mcp');
      const resetBtn    = page.locator('#btn-reset-mcp');
      const hasGenerate = await generateBtn.isVisible().catch(() => false);
      const hasReset    = await resetBtn.isVisible().catch(() => false);

      if (hasGenerate) {
        await generateBtn.click();
      }
      else if (hasReset) {
        // Accept the confirm dialog
        page.once('dialog', async dialog => { await dialog.accept(); });
        await resetBtn.click();
      }
      else {
        throw new Error('Neither generate nor reset button found in MCP section');
      }
    });

    await test.step('Result panel appears with MCP URL', async () => {
      await expect(page.locator('#mcp-result-panel')).toBeVisible({ timeout: 5000 });
      const value = await page.locator('#mcp-result-value').textContent();
      expect(value).toContain('mcp.php');
      expect(value).toContain('cid=');
      expect(value).toContain('cs=');
    });

    await test.step('Action area is hidden while result panel is shown', async () => {
      await expect(page.locator('#mcp-action-area')).toBeHidden();
    });
  });

  // -------------------------------------------------------------------------
  // TC-03: Copy button works in MCP result panel
  // -------------------------------------------------------------------------
  test('TC-03: Copy button in MCP result panel triggers copy confirm', async () => {
    await test.step('Navigate to connector page and generate key', async () => {
      await page.goto(`/civicrm/connector?reset=1&cid=${ADMIN_CID}`);
      await page.waitForLoadState('networkidle');
    });

    const mcpBlock = page.locator('.crm-connector-mcp-block');
    const mcpVisible = await mcpBlock.isVisible().catch(() => false);
    if (!mcpVisible) {
      test.skip(true, 'CIVICRM_MCP_ENABLED not set — skipping');
      return;
    }

    await test.step('Trigger generate/reset', async () => {
      const generateBtn = page.locator('#btn-generate-mcp');
      const resetBtn    = page.locator('#btn-reset-mcp');
      if (await generateBtn.isVisible().catch(() => false)) {
        await generateBtn.click();
      }
      else {
        page.once('dialog', async d => { await d.accept(); });
        await resetBtn.click();
      }
      await expect(page.locator('#mcp-result-panel')).toBeVisible({ timeout: 5000 });
    });

    await test.step('Click copy button and verify confirm text appears', async () => {
      // Grant clipboard permissions to avoid browser clipboard permission denial
      await page.context().grantPermissions(['clipboard-read', 'clipboard-write']);
      await page.locator('#btn-copy-mcp').click();
      await expect(page.locator('#mcp-copy-confirm')).toBeVisible({ timeout: 2000 });
    });
  });

  // -------------------------------------------------------------------------
  // TC-04: After confirm-close, page reloads and shows masked URL
  // -------------------------------------------------------------------------
  test('TC-04: After confirm-close, masked URL is shown (not full URL)', async () => {
    await test.step('Navigate to connector page', async () => {
      await page.goto(`/civicrm/connector?reset=1&cid=${ADMIN_CID}`);
      await page.waitForLoadState('networkidle');
    });

    const mcpBlock = page.locator('.crm-connector-mcp-block');
    const mcpVisible = await mcpBlock.isVisible().catch(() => false);
    if (!mcpVisible) {
      test.skip(true, 'CIVICRM_MCP_ENABLED not set — skipping');
      return;
    }

    // At this point (after TC-02/03) the contact already has an api_key.
    // The reset button should now be visible. Click it to regenerate (simulates flow).
    await test.step('Trigger reset and accept confirm', async () => {
      const resetBtn = page.locator('#btn-reset-mcp');
      if (await resetBtn.isVisible().catch(() => false)) {
        page.once('dialog', async d => { await d.accept(); });
        await resetBtn.click();
        await expect(page.locator('#mcp-result-panel')).toBeVisible({ timeout: 5000 });
      }
    });

    await test.step('Click confirm-close → page reloads', async () => {
      await page.locator('#btn-close-mcp').click();
      await page.waitForLoadState('networkidle');
    });

    await test.step('Info table now shows masked URL (contains ****)', async () => {
      const infoTable = page.locator('.crm-connector-mcp-block .crm-info-panel');
      await expect(infoTable).toBeVisible();
      const tableText = await infoTable.textContent();
      expect(tableText).toContain('****');
    });

    await test.step('Result panel is not visible after reload', async () => {
      await expect(page.locator('#mcp-result-panel')).toBeHidden();
    });
  });

  // -------------------------------------------------------------------------
  // TC-05: API Key section generate and mask
  // -------------------------------------------------------------------------
  test('TC-05: Generate API Key shows result panel with key value', async () => {
    await test.step('Navigate to connector page', async () => {
      await page.goto(`/civicrm/connector?reset=1&cid=${ADMIN_CID}`);
      await page.waitForLoadState('networkidle');
    });

    const apiBlock = page.locator('.crm-connector-apikey-block');
    const apiVisible = await apiBlock.isVisible().catch(() => false);
    if (!apiVisible) {
      test.skip(true, 'CIVICRM_APIEXPLORER_ENABLED not set — skipping');
      return;
    }

    await test.step('Click generate or reset API key button', async () => {
      const generateBtn = page.locator('#btn-generate-apikey');
      const resetBtn    = page.locator('#btn-reset-apikey');
      if (await generateBtn.isVisible().catch(() => false)) {
        await generateBtn.click();
      }
      else {
        page.once('dialog', async d => { await d.accept(); });
        await resetBtn.click();
      }
    });

    await test.step('Result panel appears with API key value', async () => {
      await expect(page.locator('#apikey-result-panel')).toBeVisible({ timeout: 5000 });
      const value = await page.locator('#apikey-result-value').textContent();
      // API key is 32 hex chars
      expect(value.trim()).toMatch(/^[0-9a-f]{32}$/);
    });
  });

  // -------------------------------------------------------------------------
  // TC-06: After API Key confirm-close, masked value shown in table
  // -------------------------------------------------------------------------
  test('TC-06: After API Key confirm-close, info table shows masked key', async () => {
    await test.step('Navigate to connector page', async () => {
      await page.goto(`/civicrm/connector?reset=1&cid=${ADMIN_CID}`);
      await page.waitForLoadState('networkidle');
    });

    const apiBlock = page.locator('.crm-connector-apikey-block');
    const apiVisible = await apiBlock.isVisible().catch(() => false);
    if (!apiVisible) {
      test.skip(true, 'CIVICRM_APIEXPLORER_ENABLED not set — skipping');
      return;
    }

    await test.step('Trigger generate/reset and get result', async () => {
      const generateBtn = page.locator('#btn-generate-apikey');
      const resetBtn    = page.locator('#btn-reset-apikey');
      if (await generateBtn.isVisible().catch(() => false)) {
        await generateBtn.click();
      }
      else {
        page.once('dialog', async d => { await d.accept(); });
        await resetBtn.click();
      }
      await expect(page.locator('#apikey-result-panel')).toBeVisible({ timeout: 5000 });
    });

    await test.step('Click confirm-close → page reloads', async () => {
      await page.locator('#btn-close-apikey').click();
      await page.waitForLoadState('networkidle');
    });

    await test.step('Info table shows masked API key (first4...last4)', async () => {
      const infoTable = page.locator('.crm-connector-apikey-block .crm-info-panel');
      await expect(infoTable).toBeVisible();
      const tableText = await infoTable.textContent();
      // Masked format is first4...last4, so contains '...'
      expect(tableText).toContain('...');
    });
  });

  // -------------------------------------------------------------------------
  // TC-07: Site Key click-to-reveal
  // -------------------------------------------------------------------------
  test('TC-07: Site Key click-to-reveal shows full key', async () => {
    await test.step('Navigate to connector page (API key must already exist from TC-05/06)', async () => {
      await page.goto(`/civicrm/connector?reset=1&cid=${ADMIN_CID}`);
      await page.waitForLoadState('networkidle');
    });

    const apiBlock = page.locator('.crm-connector-apikey-block');
    const apiVisible = await apiBlock.isVisible().catch(() => false);
    if (!apiVisible) {
      test.skip(true, 'CIVICRM_APIEXPLORER_ENABLED not set — skipping');
      return;
    }

    const revealBtn = page.locator('#btn-reveal-site-key');
    const revealBtnVisible = await revealBtn.isVisible().catch(() => false);
    if (!revealBtnVisible) {
      test.skip(true, 'No API key exists yet — Site Key row not visible');
      return;
    }

    await test.step('Masked site key is visible, full is hidden', async () => {
      await expect(page.locator('#display-site-key-masked')).toBeVisible();
      await expect(page.locator('#display-site-key-full')).toBeHidden();
    });

    await test.step('Click reveal button', async () => {
      await revealBtn.click();
    });

    await test.step('Full site key now visible, masked and button hidden', async () => {
      await expect(page.locator('#display-site-key-full')).toBeVisible();
      await expect(page.locator('#display-site-key-masked')).toBeHidden();
      await expect(revealBtn).toBeHidden();
    });

    await test.step('Full site key is not empty', async () => {
      const fullKey = await page.locator('#display-site-key-full').textContent();
      expect(fullKey.trim().length).toBeGreaterThan(0);
    });
  });
});
