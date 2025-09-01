const { test, expect, chromium } = require('@playwright/test');
const utils = require('./utils.js');
const fs = require('fs');
const path = require('path');
const XLSX = require('xlsx');

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

    await test.step('Verify xlsx file content and structure', async () => {
      // Read Excel file using XLSX library
      const workbook = XLSX.readFile(downloadPath);
      const sheetName = workbook.SheetNames[0];
      const worksheet = workbook.Sheets[sheetName];
      const jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1 });

      // Display sample data for debugging
      console.log('Excel file content (first 5 rows):');
      jsonData.slice(0, 5).forEach((row, index) => {
        console.log(`Row ${index + 1}:`, row);
      });

      // Check headers contain required fields based on the downloaded content
      const headers = jsonData[0];
      const headersString = headers.join(' ');
      expect(headersString).toMatch(/捐贈年度|金額|身分證/); // Match key fields from actual file

      // Verify test data appears in Excel file
      const allDataString = jsonData.map((row) => row.join(' ')).join(' ');

      // Check if our test data exists in the downloaded file
      expect(allDataString).toContain(testData.legalIdentifier); // Should find our test contact
      expect(allDataString).toContain(testData.donationAmount); // Should find our test donation
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
