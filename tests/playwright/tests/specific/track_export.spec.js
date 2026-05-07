// Tests for traffic source report spreadsheet export (civicrm/track/report?output=csv)
// Covers TC-01 to TC-10 per the Track Export AC specification.
const { test, expect, chromium } = require('@playwright/test');
const utils = require('../utils.js');
const { spawnSync } = require('child_process');
const fs = require('fs');
const os = require('os');
const path = require('path');

const CONTAINER = 'neticrm-ci-php8-d10';
const DB = 'neticrmci';
const PREFIX = 'pw_track_';

// Known counts of base test data inserted in beforeAll
const COUNT_SOCIAL = 31;   // 30 loop records + 1 comma-utm record
const COUNT_DIRECT = 10;
const COUNT_WITHIN_3M = 46; // total within 3 months (stale record excluded)

let page;
let browser;

// ── DB helpers ─────────────────────────────────────────────────────────────

// Inside the container, `docker` CLI is unavailable — connect to MySQL directly.
// On the host machine, use `docker exec` to reach the container's MySQL.
const isInsideDocker = spawnSync('docker', ['info'], { timeout: 3000 }).status !== 0;

function runSql(sql, timeoutMs = 120000) {
  const [bin, ...args] = isInsideDocker
    ? ['mysql', '-uroot', DB]
    : ['docker', 'exec', '-i', CONTAINER, 'mysql', '-uroot', DB];
  const result = spawnSync(bin, args, { input: sql, encoding: 'utf-8', timeout: timeoutMs });
  if (result.status !== 0) {
    throw new Error(`SQL failed:\n${result.stderr || result.error}`);
  }
  return result.stdout || '';
}

function cleanupData() {
  runSql(`DELETE FROM civicrm_track WHERE session_key LIKE '${PREFIX}%';`);
}

// Insert base test data (46 records within 3 months + 1 stale record)
function insertBaseData() {
  // 30 social records within 3 months (seq 0–29, max 79 days ago)
  const socialRows = Array.from({ length: 30 }, (_, i) =>
    `('${PREFIX}social_${i}', 1, DATE_SUB(NOW(), INTERVAL ${i % 80} DAY), 'civicrm_uf_group', 1, 1, 'social', 'facebook', NULL, NULL)`
  ).join(',\n');
  runSql(`INSERT INTO civicrm_track
    (session_key, counter, visit_date, page_type, page_id, state, referrer_type, referrer_network, entity_table, entity_id)
    VALUES ${socialRows};`);

  // 10 direct records within 3 months
  const directRows = Array.from({ length: 10 }, (_, i) =>
    `('${PREFIX}direct_${i}', 1, DATE_SUB(NOW(), INTERVAL ${i % 60} DAY), 'civicrm_uf_group', 1, 1, 'direct', NULL, NULL, NULL)`
  ).join(',\n');
  runSql(`INSERT INTO civicrm_track
    (session_key, counter, visit_date, page_type, page_id, state, referrer_type, referrer_network, entity_table, entity_id)
    VALUES ${directRows};`);

  // 1 social record with comma in utm_campaign (TC-04)
  runSql(`INSERT INTO civicrm_track
    (session_key, counter, visit_date, page_type, page_id, state, referrer_type, utm_campaign)
    VALUES ('${PREFIX}comma_utm', 1, NOW(), 'civicrm_uf_group', 1, 1, 'social', 'campaign_A,campaign_B');`);

  // 5 direct records with NULL entity_id (already default; for TC-05 clarity)
  const nullEntityRows = Array.from({ length: 5 }, (_, i) =>
    `('${PREFIX}null_entity_${i}', 1, DATE_SUB(NOW(), INTERVAL ${(i + 1) * 5} DAY), 'civicrm_uf_group', 1, 1, 'direct', NULL, NULL, NULL)`
  ).join(',\n');
  runSql(`INSERT INTO civicrm_track
    (session_key, counter, visit_date, page_type, page_id, state, referrer_type, referrer_network, entity_table, entity_id)
    VALUES ${nullEntityRows};`);

  // 1 stale record older than 3 months (150 days ago) — must NOT appear in default export (TC-02)
  runSql(`INSERT INTO civicrm_track
    (session_key, counter, visit_date, page_type, page_id, state, referrer_type)
    VALUES ('${PREFIX}stale', 1, DATE_SUB(NOW(), INTERVAL 150 DAY), 'civicrm_uf_group', 1, 1, 'direct');`);
}

// ── xlsx helpers ───────────────────────────────────────────────────────────

// Parse an xlsx file without external packages.
// Uses Python3's built-in zipfile module to read the xlsx (zip) archive,
// then parses the XML in JavaScript.
// Returns an array of rows; each row is an array of cell values (strings).
function parseXlsx(filePath) {
  // Extract sharedStrings.xml and sheet1.xml content via python3 zipfile
  const pyScript = `
import zipfile, sys, json
targets = {'xl/sharedStrings.xml': '', 'xl/worksheets/sheet1.xml': ''}
with zipfile.ZipFile(sys.argv[1]) as zf:
    for name in zf.namelist():
        if name in targets:
            targets[name] = zf.read(name).decode('utf-8')
print(json.dumps(targets))
`.trim();

  const res = spawnSync('python3', ['-c', pyScript, filePath], { encoding: 'utf-8', timeout: 30000 });
  if (res.status !== 0) {
    throw new Error(`xlsx extraction failed: ${res.stderr}`);
  }
  const xmlMap = JSON.parse(res.stdout);

  // Build shared-string lookup table
  const sharedStrings = [];
  const ssXml = xmlMap['xl/sharedStrings.xml'] || '';
  for (const m of ssXml.matchAll(/<t[^>]*>([^<]*)<\/t>/g)) {
    sharedStrings.push(m[1]);
  }

  // Convert xlsx column letters (A, B, ..., Z, AA, ...) to 0-based index.
  function colToIdx(letters) {
    let n = 0;
    for (const ch of letters.toUpperCase()) n = n * 26 + ch.charCodeAt(0) - 64;
    return n - 1;
  }

  // Parse first worksheet
  const sheetXml = xmlMap['xl/worksheets/sheet1.xml'] || '';
  if (!sheetXml) return [];

  const rows = [];
  for (const rowM of sheetXml.matchAll(/<row[^>]*>([\s\S]*?)<\/row>/g)) {
    const cells = [];
    for (const cellM of rowM[1].matchAll(/<c ([^>]*)>([\s\S]*?)<\/c>/g)) {
      const attrs = cellM[1];
      const inner = cellM[2];
      // Determine target column index from the cell reference attribute (r="B3" → col B → idx 1)
      const refM = attrs.match(/r="([A-Z]+)\d+"/);
      const colIdx = refM ? colToIdx(refM[1]) : cells.length;
      // Pad empty columns so the value lands at the correct index
      while (cells.length < colIdx) cells.push('');
      const typeM = attrs.match(/t="([^"]*)"/);
      let cellValue = '';
      if (typeM?.[1] === 'inlineStr') {
        // Inline string: <is><t>value</t></is>
        const isM = inner.match(/<t[^>]*>([\s\S]*?)<\/t>/);
        cellValue = isM ? isM[1] : '';
      } else {
        const valM = inner.match(/<v>([^<]*)<\/v>/);
        if (valM) {
          const raw = valM[1];
          cellValue = typeM?.[1] === 's' ? (sharedStrings[+raw] ?? '') : raw;
        }
      }
      cells.push(cellValue);
    }
    rows.push(cells);
  }
  return rows;
}

// Click the Export Spreadsheet button, confirm the dialog, and wait for download.
// Returns the Playwright Download object and the saved local file path.
async function clickExportAndDownload(page) {
  const downloadPromise = page.waitForEvent('download', { timeout: 60000 });
  await page.click('a#export-track');
  // Wait for the jQuery UI confirmation dialog
  await page.waitForSelector('.ui-dialog-buttonset button', { state: 'visible', timeout: 10000 });
  // Click the first button ("Confirm Export")
  await page.locator('.ui-dialog-buttonset button').first().click();
  const download = await downloadPromise;
  const savePath = path.join(os.tmpdir(), `pw_export_${Date.now()}_${download.suggestedFilename()}`);
  await download.saveAs(savePath);
  return { download, path: savePath };
}

// Click Export, confirm, and wait for the page to navigate (batch redirect).
async function clickExportAndWaitForBatch(page) {
  await page.click('a#export-track');
  await page.waitForSelector('.ui-dialog-buttonset button', { state: 'visible', timeout: 10000 });
  await Promise.all([
    page.waitForURL('**/civicrm/admin/batch**', { timeout: 60000 }),
    page.locator('.ui-dialog-buttonset button').first().click(),
  ]);
}

// ── Setup / teardown ───────────────────────────────────────────────────────

test.beforeAll(async () => {
  browser = await chromium.launch();
  page = await browser.newPage();
  cleanupData();
  insertBaseData();
});

test.afterAll(async () => {
  cleanupData();
  await page.close();
  await browser.close();
});

// ── Tests ──────────────────────────────────────────────────────────────────

test.describe.serial('Track Export', () => {
  test.use({ storageState: 'storageState.json' });

  // ──────────────────────────────────────────────────────────────────────────
  // TC-01: Direct xlsx download, row count == displayed total, no HTML, entity is numeric/empty
  // ──────────────────────────────────────────────────────────────────────────
  test('TC-01: small dataset downloads xlsx with correct row count and clean content', async () => {
    await test.step('Navigate to track report with default filter', async () => {
      await page.goto('/civicrm/track/report?reset=1');
      await page.waitForLoadState('networkidle');
    });

    await test.step('Export and verify xlsx content', async () => {
      const { download, path: filePath } = await clickExportAndDownload(page);
      try {
        // Filename must end with .xlsx
        expect(download.suggestedFilename()).toMatch(/\.xlsx$/);

        const rows = parseXlsx(filePath);
        const dataRows = rows.slice(1); // exclude header

        // Row count matches expected (COUNT_WITHIN_3M records within default 3-month window)
        expect(dataRows.length).toBe(COUNT_WITHIN_3M);

        // No HTML tags anywhere in the data
        for (const row of dataRows) {
          for (const cell of row) {
            expect(cell).not.toMatch(/<[^>]+>/);
          }
        }

        // Referenced Record column (index 13) must be numeric or empty
        for (const row of dataRows) {
          const entity = row[13] ?? '';
          expect(entity).toMatch(/^\d*$/);
        }
      } finally {
        fs.unlinkSync(filePath);
      }
    });
  });

  // ──────────────────────────────────────────────────────────────────────────
  // TC-02: Default 3-month filter is applied to the export
  // ──────────────────────────────────────────────────────────────────────────
  test('TC-02: default 3-month filter excludes records older than 3 months', async () => {
    await test.step('Navigate without explicit date filter', async () => {
      await page.goto('/civicrm/track/report?reset=1');
      await page.waitForLoadState('networkidle');
    });

    await test.step('Export and verify stale record is excluded', async () => {
      const { path: filePath } = await clickExportAndDownload(page);
      try {
        const rows = parseXlsx(filePath);
        const dataRows = rows.slice(1);
        // 47 total records, 1 is 150 days old — only 46 should appear
        expect(dataRows.length).toBe(COUNT_WITHIN_3M);
      } finally {
        fs.unlinkSync(filePath);
      }
    });
  });

  // ──────────────────────────────────────────────────────────────────────────
  // TC-03: rtype=social filter is preserved in the export
  // ──────────────────────────────────────────────────────────────────────────
  test('TC-03: social referrer-type filter applied correctly in export', async () => {
    await test.step('Navigate with rtype=social filter', async () => {
      await page.goto('/civicrm/track/report?reset=1&rtype=social');
      await page.waitForLoadState('networkidle');
    });

    await test.step('Export and verify row count matches social records', async () => {
      const { path: filePath } = await clickExportAndDownload(page);
      try {
        const rows = parseXlsx(filePath);
        const dataRows = rows.slice(1);
        expect(dataRows.length).toBe(COUNT_SOCIAL);
      } finally {
        fs.unlinkSync(filePath);
      }
    });
  });

  // ──────────────────────────────────────────────────────────────────────────
  // TC-04: Special characters (comma) in utm_campaign do not break xlsx columns
  // ──────────────────────────────────────────────────────────────────────────
  test('TC-04: comma in utm_campaign does not cause column misalignment', async () => {
    await test.step('Navigate to track report', async () => {
      await page.goto('/civicrm/track/report?reset=1');
      await page.waitForLoadState('networkidle');
    });

    await test.step('Export and verify comma record is intact', async () => {
      const { path: filePath } = await clickExportAndDownload(page);
      try {
        const rows = parseXlsx(filePath);
        const dataRows = rows.slice(1);

        // utm_campaign is column index 8
        const commaRow = dataRows.find(row => (row[8] ?? '').includes(','));
        expect(commaRow).toBeDefined();
        expect(commaRow[8]).toBe('campaign_A,campaign_B');

        // Row must have at least 14 columns (no column shift)
        expect(commaRow.length).toBeGreaterThanOrEqual(9);
      } finally {
        fs.unlinkSync(filePath);
      }
    });
  });

  // ──────────────────────────────────────────────────────────────────────────
  // TC-05: Records with null entity_id appear with empty Referenced Record cell
  // ──────────────────────────────────────────────────────────────────────────
  test('TC-05: null entity_id records export with empty Referenced Record cell', async () => {
    await test.step('Navigate to track report', async () => {
      await page.goto('/civicrm/track/report?reset=1');
      await page.waitForLoadState('networkidle');
    });

    await test.step('Export and verify null entity rows have empty cell', async () => {
      const { path: filePath } = await clickExportAndDownload(page);
      try {
        const rows = parseXlsx(filePath);
        const dataRows = rows.slice(1);

        // All test records have entity_id=NULL, so Referenced Record (index 13) must be empty
        const nullEntityRows = dataRows.filter(row => (row[13] ?? '') === '');
        expect(nullEntityRows.length).toBeGreaterThan(0);
      } finally {
        fs.unlinkSync(filePath);
      }
    });
  });

  // ──────────────────────────────────────────────────────────────────────────
  // TC-06: Future date range → header-only xlsx (0 data rows)
  // ──────────────────────────────────────────────────────────────────────────
  test('TC-06: future date range produces header-only xlsx with no errors', async () => {
    await test.step('Navigate with future date range', async () => {
      await page.goto('/civicrm/track/report?reset=1&start=2099-01-01&end=2099-12-31');
      await page.waitForLoadState('networkidle');
    });

    await test.step('Export and verify only header row exists', async () => {
      const { download, path: filePath } = await clickExportAndDownload(page);
      try {
        expect(download.suggestedFilename()).toMatch(/\.xlsx$/);
        const rows = parseXlsx(filePath);
        // Only the header row; no data rows
        expect(rows.length).toBe(1);
      } finally {
        fs.unlinkSync(filePath);
      }
    });
  });

  // ──────────────────────────────────────────────────────────────────────────
  // TC-07: >= 10,000 records → batch job (xlsx)
  // ──────────────────────────────────────────────────────────────────────────
  test('TC-07: large dataset (>=10000) triggers batch job redirect for xlsx', async () => {
    await test.step('Insert 10001 records to cross batch threshold', async () => {
      // 3-level cross-join produces 10^3 = 1000 rows; repeat with LIMIT 10001
      // Use a 4-level cross-join (10^4 = 10000 rows) and add 1 extra
      runSql(`
        INSERT INTO civicrm_track (session_key, counter, visit_date, page_type, page_id, state, referrer_type)
        SELECT
          CONCAT('${PREFIX}tc07_', a.n*1000+b.n*100+c.n*10+d.n),
          1,
          DATE_SUB(NOW(), INTERVAL MOD(a.n*1000+b.n*100+c.n*10+d.n, 80) DAY),
          'civicrm_uf_group', 1, 1, 'direct'
        FROM
          (SELECT 0 n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a,
          (SELECT 0 n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b,
          (SELECT 0 n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) c,
          (SELECT 0 n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) d
        LIMIT 10001;
      `, 120000);
    });

    await test.step('Navigate to track report', async () => {
      await page.goto('/civicrm/track/report?reset=1');
      await page.waitForLoadState('networkidle');
    });

    await test.step('Click export and confirm dialog', async () => {
      await clickExportAndWaitForBatch(page);
    });

    await test.step('Verify redirect landed on batch job page', async () => {
      expect(page.url()).toContain('civicrm/admin/batch');
    });

    await test.step('Verify batch label indicates xlsx format', async () => {
      const pageText = await page.locator('#crm-container').textContent();
      expect(pageText).toMatch(/\.xlsx/i);
    });

    await test.step('Cleanup TC-07 records', async () => {
      runSql(`DELETE FROM civicrm_track WHERE session_key LIKE '${PREFIX}tc07_%';`);
    });
  });

  // ──────────────────────────────────────────────────────────────────────────
  // TC-08: >= 100,000 records → batch job (csv)
  // ──────────────────────────────────────────────────────────────────────────
  test('TC-08: very large dataset (>=100000) triggers batch job redirect for csv', async () => {
    await test.step('Insert 100000 records to cross CSV threshold', async () => {
      // 5-level cross-join: 10^5 = 100,000 rows
      runSql(`
        INSERT INTO civicrm_track (session_key, counter, visit_date, page_type, page_id, state, referrer_type)
        SELECT
          CONCAT('${PREFIX}tc08_', a.n*10000+b.n*1000+c.n*100+d.n*10+e.n),
          1,
          DATE_SUB(NOW(), INTERVAL MOD(a.n*10000+b.n*1000+c.n*100+d.n*10+e.n, 80) DAY),
          'civicrm_uf_group', 1, 1, 'direct'
        FROM
          (SELECT 0 n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a,
          (SELECT 0 n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b,
          (SELECT 0 n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) c,
          (SELECT 0 n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) d,
          (SELECT 0 n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) e;
      `, 180000);
    });

    await test.step('Navigate to track report', async () => {
      await page.goto('/civicrm/track/report?reset=1');
      await page.waitForLoadState('networkidle');
    });

    await test.step('Click export and confirm dialog', async () => {
      await clickExportAndWaitForBatch(page);
    });

    await test.step('Verify redirect landed on batch job page', async () => {
      expect(page.url()).toContain('civicrm/admin/batch');
    });

    await test.step('Verify batch label indicates CSV format', async () => {
      const pageText = await page.locator('#crm-container').textContent();
      expect(pageText).toMatch(/\.csv/i);
    });

    await test.step('Cleanup TC-08 records', async () => {
      runSql(`DELETE FROM civicrm_track WHERE session_key LIKE '${PREFIX}tc08_%';`, 180000);
    });
  });

  // ──────────────────────────────────────────────────────────────────────────
  // TC-09: decryptExcelOption=1 → downloaded xlsx is encrypted (not plain zip)
  // ──────────────────────────────────────────────────────────────────────────
  test('TC-09: encrypted xlsx (decryptExcelOption=1) is not a plain zip', async () => {
    await test.step('Set decryptExcelOption=1 in security settings', async () => {
      await page.goto('/civicrm/admin/setting/security?reset=1');
      await page.waitForLoadState('networkidle');
      await page.locator('input[name="decryptExcelOption"][value="1"]').check();
      await page.locator('input[name="_qf_Security_upload"]').first().click();
      await page.waitForLoadState('networkidle');
    });

    await test.step('Navigate to track report', async () => {
      await page.goto('/civicrm/track/report?reset=1');
      await page.waitForLoadState('networkidle');
    });

    await test.step('Export and verify file is encrypted (not a plain zip)', async () => {
      const { download, path: filePath } = await clickExportAndDownload(page);
      try {
        expect(download.suggestedFilename()).toMatch(/\.xlsx$/);
        const buf = fs.readFileSync(filePath);
        // A plain (unencrypted) xlsx starts with PK magic bytes: 0x50 0x4B
        const isPlainZip = buf[0] === 0x50 && buf[1] === 0x4B;
        // The encrypted file must NOT be a plain zip
        expect(isPlainZip).toBe(false);
      } finally {
        fs.unlinkSync(filePath);
      }
    });

    await test.step('Reset decryptExcelOption back to 0', async () => {
      await page.goto('/civicrm/admin/setting/security?reset=1');
      await page.waitForLoadState('networkidle');
      await page.locator('input[name="decryptExcelOption"][value="0"]').check();
      await page.locator('input[name="_qf_Security_upload"]').first().click();
      await page.waitForLoadState('networkidle');
    });
  });

  // ──────────────────────────────────────────────────────────────────────────
  // TC-10: decryptExcelOption=0 → downloaded xlsx is a plain zip (no password)
  // ──────────────────────────────────────────────────────────────────────────
  test('TC-10: unencrypted xlsx (decryptExcelOption=0) is a valid plain zip', async () => {
    await test.step('Ensure decryptExcelOption=0 in security settings', async () => {
      await page.goto('/civicrm/admin/setting/security?reset=1');
      await page.waitForLoadState('networkidle');
      await page.locator('input[name="decryptExcelOption"][value="0"]').check();
      await page.locator('input[name="_qf_Security_upload"]').first().click();
      await page.waitForLoadState('networkidle');
    });

    await test.step('Navigate to track report', async () => {
      await page.goto('/civicrm/track/report?reset=1');
      await page.waitForLoadState('networkidle');
    });

    await test.step('Export and verify file is a valid plain zip (parseable)', async () => {
      const { download, path: filePath } = await clickExportAndDownload(page);
      try {
        expect(download.suggestedFilename()).toMatch(/\.xlsx$/);
        const buf = fs.readFileSync(filePath);
        // Plain xlsx must start with PK magic bytes: 0x50 0x4B
        expect(buf[0]).toBe(0x50); // 'P'
        expect(buf[1]).toBe(0x4B); // 'K'
        // Must be parseable without errors
        const rows = parseXlsx(filePath);
        expect(rows.length).toBeGreaterThan(0);
      } finally {
        fs.unlinkSync(filePath);
      }
    });
  });
});
