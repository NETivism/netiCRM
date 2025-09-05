const { test, expect, chromium } = require('@playwright/test');
const utils = require('./utils.js');
const fs = require('fs');
const path = require('path');

/** @type {import('@playwright/test').Page} */
let page;

test.beforeAll(async () => {
  const browser = await chromium.launch();
  page = await browser.newPage();
});

test.afterAll(async () => {
  await page.close();
});

test.use({ storageState: 'storageState.json' });

test.describe.serial('Donation Tax Deduction Electronic Process', () => {
  const testData = {
    firstName: `Test${utils.makeid(4)}`,
    lastName: `User${utils.makeid(4)}`,
    legalIdentifier: `M${Math.floor(Math.random() * 100000000)}111`,
    donationAmount: '2000',
  };
  const reportTitle = `臺灣財政部所得扣除額_${utils.makeid(4)}`;
  //open legal_identifier field in admin
  test('before all - Enable legal_identifier field', async () => {
    await page.goto('/admin/modules');
    await page
      .locator(
        '#edit-modules-neticrm-civicrm-legalid-enable,#edit-modules-civicrm-legalid-enable'
      )
      .check();
    await page.locator('#edit-submit').click();
    await page.goto('civicrm/contact/add?reset=1&ct=Individual');
    // Verify the legal_identifier field is available
    await expect(page.locator('#legal_identifier')).toBeVisible();
  });

  test('Complete Donation Tax Deduction Process', async () => {
    await test.step('Create individual contact with tax ID', async () => {
      // Navigate to individual contact creation form
      await page.goto('civicrm/contact/add?reset=1&ct=Individual');

      // Fill in contact details
      await page.locator('#last_name').fill(testData.lastName);
      await page.locator('#first_name').fill(testData.firstName);
      await page.locator('#legal_identifier').fill(testData.legalIdentifier);

      // Save the contact
      await page.locator('#_qf_Contact_upload_view').first().click();
      await utils.findElement(page, '#crm-container');

      // Verify contact was created successfully
      await expect(page).toHaveTitle(
        new RegExp(`${testData.firstName} ${testData.lastName}`)
      );
    });

    await test.step('Record contribution for tax deduction', async () => {
      // Navigate to Contributions tab
      await page.locator('#ui-id-2').click();
      await utils.findElement(page, '[accesskey="N"]');

      // Click "Record Contribution" button
      await page.locator('[accesskey="N"]').click();

      // Fill contribution details
      await page.locator('#contribution_type_id').selectOption('1'); // Select "Donation (Deductible)"
      await page.locator('#total_amount').fill(testData.donationAmount);

      // Submit the contribution
      await page.locator('[id="_qf_Contribution_upload-bottom"]').click();
      await utils.findElement(page, '#crm-container');

      // Verify contribution table is displayed
      await expect(page.locator('table.selector')).toBeVisible();

      // Validate contribution creation date
      const today = new Date();
      const contributionRow = page.locator('table.selector tbody tr').first();
      await expect(contributionRow).toBeVisible();

      const dateText = await contributionRow
        .locator('.crm-contribution-created_date')
        .textContent();

      const currentYear = today.getFullYear().toString();
      expect(dateText).toContain(currentYear); // Ensure contribution was created this year
    });
  });

  test('Create Tax Deduction Report', async () => {
    // Generate unique report title

    await test.step('Navigate to report templates and preview report', async () => {
      // Go to report template list
      await page.goto('civicrm/admin/report/template/list?reset=1');

      // Check if Taiwan tax report template exists
      const taiwanTaxLink = page.locator(
        'a[href*="/civicrm/report/contribute/taiwantax"]'
      );
      const linkCount = await taiwanTaxLink.count();

      // Skip test if template not found
      if (linkCount === 0) {
        console.log('Taiwan tax report template not found, skipping test');
        test.skip();
        return;
      }

      // Click Taiwan tax report template
      await taiwanTaxLink.click();
      await utils.findElement(page, 'h1');

      // Click preview report button
      await page.locator('#_qf_TaiwanTax_submit').click();
      await utils.findElement(page, '#TaiwanTax');

      // Verify expected element appears
      await expect(page.locator('#option11_wrapper')).toBeVisible();
    });

    await test.step('Create and configure report', async () => {
      // Expand all accordion sections
      const accordionHeaders = page.locator('.crm-accordion-header');
      const count = await accordionHeaders.count();
      for (let i = 0; i < count; i++) {
        await accordionHeaders.nth(i).click();
      }

      // Fill report title and submit
      await page.locator('#title').fill(reportTitle);
      await page.locator('#_qf_TaiwanTax_submit_save').click();

      // Verify report creation
      await expect(page).toHaveTitle(/臺灣財政部所得扣除額/);
      await expect(page.locator('.messages.status')).toBeVisible();
    });
  });

  test('Download Tax Deduction Report CSV', async () => {
    let downloadPath;
    let download;

    await test.step('Navigate to existing report', async () => {
      // Navigate to report list page
      await page.goto('civicrm/report/list?reset=1');
      
      // Wait for the report title to appear
      await expect(page.getByText(reportTitle)).toBeVisible();

      // Find and click Taiwan tax deduction report link by partial text
      await page
        .locator(
          `a[href*="/civicrm/report/instance/"]:has-text("${reportTitle}")`
        )
        .click();
      await utils.findElement(page, '.crm-tasks');
    });

    await test.step('Download xlsx file', async () => {
      // Setup download handler and click CSV export button
      const downloadPromise = page.waitForEvent('download');
      await page.locator('#_qf_TaiwanTax_submit_csv').click();
      download = await downloadPromise;

      // Verify download file is xlsx format
      await expect(download.suggestedFilename()).toContain('.xlsx');
      console.log(`Downloaded file: ${download.suggestedFilename()}`);

      // Save downloaded file to temporary location
      downloadPath = path.join(__dirname, 'temp', download.suggestedFilename());
      await download.saveAs(downloadPath);
    });

    await test.step('Verify downloaded file properties', async () => {
      // Verify file exists and has content
      expect(fs.existsSync(downloadPath)).toBe(true);

      // Check file size is greater than 0
      const stats = fs.statSync(downloadPath);
      expect(stats.size).toBeGreaterThan(0);
      console.log(`Downloaded file size: ${stats.size} bytes`);

      // Verify file extension is xlsx
      expect(downloadPath).toMatch(/\.xlsx$/);

      // Check file was created recently (within last 5 minutes)
      const now = new Date();
      const fileCreationTime = stats.mtime;
      const timeDifference = now - fileCreationTime;
      expect(timeDifference).toBeLessThan(5 * 60 * 1000); // 5 minutes in milliseconds
    });

    await test.step('Clean up temporary files', async () => {
      // Clean up temporary file
      if (fs.existsSync(downloadPath)) {
        fs.unlinkSync(downloadPath);
        console.log('Temporary file cleaned up');
      }
    });
  });
});
