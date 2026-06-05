// refs #42727: verify profile preview shows usage pages section (embedded contribution/event pages)
const { test, expect, chromium } = require('@playwright/test');
const utils = require('../utils.js');

/** @type {import('@playwright/test').Page} */
let page;
var element;
const wait_secs = 2000;

// Shared test data populated during setup
const vars = {
  profileMain:   { name: 'TC_Main_'   + utils.makeid(5), id: null },
  profileMixed:  { name: 'TC_Mixed_'  + utils.makeid(5), id: null },
  profileNone:   { name: 'TC_None_'   + utils.makeid(5), id: null },
  profileOrphan: { name: 'TC_Orphan_' + utils.makeid(5), id: null },
  profileF:      { name: 'TC_F_'      + utils.makeid(5), id: null }, // linked to 25 pages
  profileG:      { name: 'TC_G_'      + utils.makeid(5), id: null }, // linked to 26 pages
  contribPageMain:  { title: 'TC_CP_Main_'  + utils.makeid(5), id: null },
  eventMain:        { title: 'TC_Ev_Main_'  + utils.makeid(5), id: null },
  contribPageMixed: { title: 'TC_CP_Mixed_' + utils.makeid(5), id: null },
  eventMixed:       { title: 'TC_Ev_Mixed_' + utils.makeid(5), id: null },
};

/**
 * CiviCRM API v3 helper via page.evaluate (requires a loaded CiviCRM page).
 */
async function callCiviApi(entity, action, params) {
  return await page.evaluate(async ({ entity, action, params }) => {
    return new Promise((resolve, reject) => {
      if (typeof cj === 'undefined' || typeof cj().crmAPI !== 'function') {
        reject(new Error('cj().crmAPI is not available on this page'));
        return;
      }
      cj().crmAPI(entity, action, params, {
        success: (data) => resolve(data),
        error: (err) => reject(err),
      });
    });
  }, { entity, action, params });
}

/**
 * Create a minimal profile via UI.
 * groupTypes: array of module strings to check in "Used For", e.g. ['CiviContribute', 'CiviEvent'].
 * Checking a module type triggers createUFJoin so the profile appears in the
 * corresponding page's Include dropdown.
 * Leaving groupTypes empty creates no uf_join records (profile not embeddable).
 * Returns the uf_group ID.
 */
async function createProfile(profileName, groupTypes = []) {
  await page.goto('civicrm/admin/uf/group?reset=1');
  await utils.findElement(page, '#newCiviCRMProfile-top');
  await utils.clickElement(page, page.locator('#newCiviCRMProfile-top'), { exist: 'form#Group' });

  await utils.fillInput(page.locator('input#title'), profileName);

  // is_active must be checked so the profile appears in dropdowns and uf_join is created
  const isActiveCheckbox = page.locator('#is_active');
  if (!await isActiveCheckbox.isChecked()) {
    await isActiveCheckbox.click();
    await expect(isActiveCheckbox).toBeChecked();
  }

  // Explicitly set all "Used For" checkboxes — session state from the previous profile
  // creation may carry over even with reset=1, so we must uncheck unwanted boxes too.
  const allGroupTypeSelectors = {
    'CiviContribute':    'input[name="uf_group_type[CiviContribute]"]',
    'CiviEvent':         'input[name="uf_group_type[CiviEvent]"]',
    'Profile':           'input[name="uf_group_type[Profile]"]',
    'User Registration': 'input[name="uf_group_type_user[User Registration]"]',
    'User Account':      'input[name="uf_group_type_user[User Account]"]',
  };
  for (const [moduleType, selector] of Object.entries(allGroupTypeSelectors)) {
    const checkbox = page.locator(selector);
    if (await checkbox.count() === 0) continue;
    const shouldCheck = groupTypes.includes(moduleType);
    const isChecked = await checkbox.isChecked();
    if (shouldCheck && !isChecked) {
      await checkbox.click();
      await expect(checkbox).toBeChecked();
    } else if (!shouldCheck && isChecked) {
      await checkbox.click();
      await expect(checkbox).not.toBeChecked();
    }
  }

  element = '#_qf_Group_next-bottom';
  await utils.findElement(page, element);
  await utils.clickElement(page, page.locator(element), { exist: 'form#Field' });

  // Add a Contact > Email field — required so getProfileType returns a valid type.
  // Profiles without fields are excluded from Include dropdowns by getProfiles().
  await utils.selectOption(page.locator('select[name="field_name[0]"]'), 'Contact');
  await utils.findElement(page, '#select2-field_name1-container');
  await page.locator('#select2-field_name1-container').click();
  await utils.wait(300);
  await page.keyboard.type('Email');
  await utils.wait(1000);
  await page.keyboard.press('Enter');
  await page.locator('#_qf_Field_next-bottom').click();

  // Get profile ID from the profile list (3rd column)
  await page.goto('civicrm/admin/uf/group?reset=1');
  await utils.findElement(page, '#user-profiles');
  const profileId = await page.evaluate((name) => {
    const rows = document.querySelectorAll('#user-profiles table tr');
    for (const row of rows) {
      const titleCell = row.querySelector('td:first-child span');
      if (titleCell && titleCell.textContent.trim() === name) {
        const idCell = row.querySelector('td:nth-child(3)');
        return idCell ? parseInt(idCell.textContent.trim(), 10) : null;
      }
    }
    return null;
  }, profileName);

  console.log(`Created profile "${profileName}" id=${profileId}`);
  return profileId;
}

/**
 * Create a minimal contribution page via UI.
 * Navigates directly to the Include tab after creation to link profiles.
 * preProfId / postProfId = 0 means "don't select".
 * Returns the contribution page ID.
 */
async function createContributionPage(title, preProfId, postProfId) {
  await page.goto('civicrm/admin/contribute/add?reset=1&action=add');
  await utils.findElement(page, "form#Settings input[name='title']");
  await utils.fillInput(page.locator("form#Settings input[name='title']"), title);

  // Ensure the page is active so it appears as a link (not disabled span) in preview
  const isActiveCheckbox = page.locator('form#Settings #is_active');
  if (await isActiveCheckbox.count() > 0 && !await isActiveCheckbox.isChecked()) {
    await isActiveCheckbox.click();
    await expect(isActiveCheckbox).toBeChecked();
  }

  element = '#_qf_Settings_upload-bottom';
  await utils.findElement(page, element);
  await page.locator(element).click();
  await expect(page.locator('form#Amount')).not.toHaveCount(0);

  // Search the contribution page list by title to get the ID reliably
  await page.goto('civicrm/admin/contribute?reset=1');
  await utils.findElement(page, 'input[name="title"]');
  await page.locator('input[name="title"]').fill(title);
  await page.locator('input[name="_qf_SearchContribution_refresh"]').click();
  await utils.wait(wait_secs);

  const pageId = await page.evaluate((t) => {
    const rows = Array.from(document.querySelectorAll('tr'));
    const row = rows.find(r => r.innerText.includes(t));
    if (row) {
      const td = row.querySelector('td');
      return td ? parseInt(td.innerText.trim(), 10) : null;
    }
    return null;
  }, title);

  if (!pageId || isNaN(pageId)) throw new Error(`Could not find ID for contribution page "${title}"`);

  // Jump directly to Include Profiles tab to link the profile
  await page.goto(`civicrm/admin/contribute/custom?reset=1&action=update&id=${pageId}`);
  await utils.findElement(page, 'form#Custom');

  if (preProfId) {
    await utils.selectOption(page.locator('#custom_pre_id'), String(preProfId));
  }
  if (postProfId) {
    await utils.selectOption(page.locator('#custom_post_id'), String(postProfId));
  }

  element = '#_qf_Custom_upload-bottom';
  await utils.findElement(page, element);
  await page.locator(element).click();
  await expect(page.locator('.crm-error')).toHaveCount(0);

  console.log(`Created contribution page "${title}" id=${pageId}`);
  return pageId;
}

/**
 * Create a minimal event via UI.
 * Enables Online Registration and links a profile via custom_pre_id.
 * Returns the event ID.
 */
async function createEvent(title, preProfId) {
  await page.goto('civicrm/event/add?reset=1&action=add');
  await utils.findElement(page, 'form#EventInfo');

  await utils.selectOption(page.locator('#event_type_id'), { index: 1 });
  await utils.fillInput(page.locator('#title'), title);

  element = '#_qf_EventInfo_upload-bottom';
  await utils.findElement(page, element);
  await page.locator(element).click();
  await expect(page.locator('form#Location')).not.toHaveCount(0);

  // Event ID is in the Location form URL
  const locationUrl = new URL(page.url());
  const eventId = parseInt(locationUrl.searchParams.get('id'), 10);

  // Jump directly to Online Registration tab
  await page.goto(`civicrm/event/manage/registration?reset=1&action=update&id=${eventId}`);
  await utils.findElement(page, 'form#Registration');

  // Enable online registration if not already checked
  const regCheckbox = page.locator('#is_online_registration');
  const isChecked = await regCheckbox.isChecked();
  if (!isChecked) {
    await regCheckbox.click();
    await expect(regCheckbox).toBeChecked();
  }

  // Wait for profile dropdown to become available
  await utils.findElement(page, '#custom_pre_id');

  if (preProfId) {
    await utils.selectOption(page.locator('#custom_pre_id'), String(preProfId));
  }

  element = '#_qf_Registration_upload-bottom';
  await utils.findElement(page, element);
  await page.locator(element).click();
  await expect(page.locator('.crm-error')).toHaveCount(0);

  console.log(`Created event "${title}" id=${eventId}`);
  return eventId;
}

/**
 * Profile preview URL.
 */
function previewUrl(profileId) {
  return `civicrm/admin/uf/group?action=preview&id=${profileId}&field=0&context=group`;
}


test.beforeAll(async () => {
  const browser = await chromium.launch();
  page = await browser.newPage();
});

test.afterAll(async () => {
  await page.close();
});


test.describe.serial('UF Group Preview Usage Pages', () => {

  test.use({ storageState: 'storageState.json' });

  // ── Setup ───────────────────────────────────────────────────────────────

  test('TC-setup: create all test data', async () => {

    await test.step('Create 6 profiles', async () => {
      // profileMain/profileMixed need CiviContribute+CiviEvent uf_join to appear in both dropdowns
      vars.profileMain.id   = await createProfile(vars.profileMain.name,   ['CiviContribute', 'CiviEvent']);
      vars.profileMixed.id  = await createProfile(vars.profileMixed.name,  ['CiviContribute', 'CiviEvent']);
      // profileNone: no group types → no uf_join → won't appear in any Include dropdown
      vars.profileNone.id   = await createProfile(vars.profileNone.name,   []);
      // profileOrphan: will be linked to a contribution page that gets deleted
      vars.profileOrphan.id = await createProfile(vars.profileOrphan.name, ['CiviContribute']);
      // profileF/G: linked to contribution pages for pagination test
      vars.profileF.id      = await createProfile(vars.profileF.name,      ['CiviContribute']);
      vars.profileG.id      = await createProfile(vars.profileG.name,      ['CiviContribute']);
    });

    await test.step('Create contribution page and event for profileMain (TC-1/2/5)', async () => {
      vars.contribPageMain.id = await createContributionPage(
        vars.contribPageMain.title, vars.profileMain.id, 0
      );
      vars.eventMain.id = await createEvent(vars.eventMain.title, vars.profileMain.id);
    });

    await test.step('Create contribution page and event for profileMixed (TC-4)', async () => {
      vars.contribPageMixed.id = await createContributionPage(
        vars.contribPageMixed.title, vars.profileMixed.id, 0
      );
      vars.eventMixed.id = await createEvent(vars.eventMixed.title, vars.profileMixed.id);
    });

    await test.step('Disable eventMixed via API (TC-4)', async () => {
      // Navigate to a CiviCRM page so cj().crmAPI is available
      await page.goto('civicrm/dashboard?reset=1');
      await utils.wait(wait_secs);
      const result = await callCiviApi('Event', 'create', {
        id: vars.eventMixed.id,
        is_active: 0,
        sequential: 1,
      });
      expect(result.is_error).toBe(0);
      console.log(`Disabled eventMixed id=${vars.eventMixed.id}`);
    });

    await test.step('Create orphan contribution page, link profileOrphan, then delete it (TC-3B)', async () => {
      const orphanTitle = 'TC_Orphan_CP_' + utils.makeid(5);
      const orphanPageId = await createContributionPage(orphanTitle, vars.profileOrphan.id, 0);

      // Delete the contribution page via API, leaving uf_join record orphaned
      await page.goto('civicrm/dashboard?reset=1');
      await utils.wait(wait_secs);
      const deleteResult = await callCiviApi('ContributionPage', 'delete', {
        id: orphanPageId,
        sequential: 1,
      });
      expect(deleteResult.is_error).toBe(0);
      console.log(`Deleted orphan contribution page id=${orphanPageId}`);
    });

    await test.step('Create 26 contribution pages for TC-6 (one by one via UI)', async () => {
      for (let i = 1; i <= 26; i++) {
        const tc6Title = 'TC6_' + utils.makeid(6);
        // Pages 1-25 linked to profileF (pre) and profileG (post)
        // Page 26 linked to profileG only
        const preProfId = i <= 25 ? vars.profileF.id : 0;
        const postProfId = vars.profileG.id;
        await createContributionPage(tc6Title, preProfId, postProfId);
        console.log(`TC-6 page ${i}/26 created`);
      }
    });

  });

  // ── TC-01: contribution page appears in usage section ──────────────────

  test('TC-01: contribution page linked profile shows usage section and title', async () => {

    await test.step('Navigate to profileMain preview', async () => {
      await page.goto(previewUrl(vars.profileMain.id));
      await utils.wait(wait_secs);
      await expect(page.locator('.crm-error')).toHaveCount(0);
    });

    await test.step('Subtitle banner is visible', async () => {
      const subtitle = page.locator('.messages.status span.font-size11pt');
      await expect(subtitle).toBeVisible();
      const subtitleText = await subtitle.innerText();
      expect(subtitleText).toContain('The actual style and layout should be viewed on the page where this profile is embedded');
    });

    await test.step('Usage section block is rendered', async () => {
      await expect(page.locator('.crm-uf-usagepages-block')).toBeVisible();
    });

    await test.step('Table contains contribution page title with correct ID format', async () => {
      const cells = page.locator('.crm-uf-usagepages-block tbody td:first-child');
      const cellTexts = await cells.allInnerTexts();
      const matched = cellTexts.some(text =>
        text.includes(vars.contribPageMain.title) &&
        text.includes(`(ID: ${vars.contribPageMain.id})`)
      );
      expect(matched, `Expected to find "${vars.contribPageMain.title}(ID: ${vars.contribPageMain.id})" in cells: ${cellTexts.join(' | ')}`).toBe(true);
    });

  });

  // ── TC-02: event page appears in usage section ─────────────────────────

  test('TC-02: event page linked profile shows in usage section with configure link', async () => {

    await test.step('Navigate to profileMain preview', async () => {
      await page.goto(previewUrl(vars.profileMain.id));
      await utils.wait(wait_secs);
      await expect(page.locator('.crm-error')).toHaveCount(0);
    });

    await test.step('Table contains event title with correct ID format', async () => {
      const cells = page.locator('.crm-uf-usagepages-block tbody td:first-child');
      const cellTexts = await cells.allInnerTexts();
      const matched = cellTexts.some(text =>
        text.includes(vars.eventMain.title) &&
        text.includes(`(ID: ${vars.eventMain.id})`)
      );
      expect(matched, `Expected to find "${vars.eventMain.title}(ID: ${vars.eventMain.id})" in cells: ${cellTexts.join(' | ')}`).toBe(true);
    });

    await test.step('Event row has "Configure this page\'s form" link pointing to event registration', async () => {
      // Find the row that contains the event title
      const rows = page.locator('.crm-uf-usagepages-block tbody tr');
      const rowCount = await rows.count();
      let eventRow = null;
      for (let i = 0; i < rowCount; i++) {
        const rowText = await rows.nth(i).locator('td:first-child').innerText();
        if (rowText.includes(vars.eventMain.title)) {
          eventRow = rows.nth(i);
          break;
        }
      }
      expect(eventRow, 'Event row not found in usage table').not.toBeNull();

      const configLink = eventRow.locator('td:nth-child(2) a');
      await expect(configLink).toBeVisible();
      const linkText = await configLink.innerText();
      expect(linkText).toContain("Configure this page's form");
      const href = await configLink.getAttribute('href');
      expect(href).toContain('civicrm/event/manage/registration');
      expect(href).toContain(`id=${vars.eventMain.id}`);
    });

  });

  // ── TC-03: no usage section when profile has no linked pages ───────────

  test('TC-03A: profile with no links shows no usage section', async () => {

    await test.step('Navigate to profileNone preview', async () => {
      await page.goto(previewUrl(vars.profileNone.id));
      await utils.wait(wait_secs);
      await expect(page.locator('.crm-error')).toHaveCount(0);
    });

    await test.step('No subtitle in banner', async () => {
      await expect(page.locator('.messages.status span.font-size11pt')).toHaveCount(0);
    });

    await test.step('Usage section block is not rendered', async () => {
      await expect(page.locator('.crm-uf-usagepages-block')).toHaveCount(0);
    });

  });

  test('TC-03B: profile linked to deleted page shows no usage section', async () => {

    await test.step('Navigate to profileOrphan preview', async () => {
      await page.goto(previewUrl(vars.profileOrphan.id));
      await utils.wait(wait_secs);
      await expect(page.locator('.crm-error')).toHaveCount(0);
    });

    await test.step('No subtitle in banner (orphaned uf_join filtered out)', async () => {
      await expect(page.locator('.messages.status span.font-size11pt')).toHaveCount(0);
    });

    await test.step('Usage section block is not rendered', async () => {
      await expect(page.locator('.crm-uf-usagepages-block')).toHaveCount(0);
    });

  });

  // ── TC-04: active vs inactive page styling ─────────────────────────────

  test('TC-04: active page has link, disabled page has red text without link', async () => {

    await test.step('Navigate to profileMixed preview', async () => {
      await page.goto(previewUrl(vars.profileMixed.id));
      await utils.wait(wait_secs);
      await expect(page.locator('.crm-error')).toHaveCount(0);
      await expect(page.locator('.crm-uf-usagepages-block')).toBeVisible();
    });

    await test.step('Active contribution page title is a clickable link', async () => {
      // Find the row with contribPageMixed title
      const rows = page.locator('.crm-uf-usagepages-block tbody tr');
      const rowCount = await rows.count();
      let activeRow = null;
      for (let i = 0; i < rowCount; i++) {
        const rowText = await rows.nth(i).locator('td:first-child').innerText();
        if (rowText.includes(vars.contribPageMixed.title)) {
          activeRow = rows.nth(i);
          break;
        }
      }
      expect(activeRow, 'Active contribution page row not found').not.toBeNull();

      // Title cell must contain an <a> link (not a disabled span)
      const titleLink = activeRow.locator('td:first-child a');
      await expect(titleLink).toBeVisible();
      await expect(activeRow.locator('td:first-child span.disabled')).toHaveCount(0);
    });

    await test.step('Inactive event title is red text without link', async () => {
      // Find the row with eventMixed title
      const rows = page.locator('.crm-uf-usagepages-block tbody tr');
      const rowCount = await rows.count();
      let inactiveRow = null;
      for (let i = 0; i < rowCount; i++) {
        const rowText = await rows.nth(i).locator('td:first-child').innerText();
        if (rowText.includes(vars.eventMixed.title)) {
          inactiveRow = rows.nth(i);
          break;
        }
      }
      expect(inactiveRow, 'Inactive event row not found').not.toBeNull();

      // Title cell must contain a <span class="disabled"> with red color
      const disabledSpan = inactiveRow.locator('td:first-child span.disabled');
      await expect(disabledSpan).toBeVisible();
      const styleAttr = await disabledSpan.getAttribute('style');
      expect(styleAttr).toContain('color: red');

      // Must NOT have a clickable link
      await expect(inactiveRow.locator('td:first-child a')).toHaveCount(0);
    });

    await test.step('Both rows have "Configure this page\'s form" link', async () => {
      const configLinks = page.locator('.crm-uf-usagepages-block tbody td:nth-child(2) a');
      const count = await configLinks.count();
      expect(count).toBeGreaterThanOrEqual(2);
      for (let i = 0; i < count; i++) {
        const text = await configLinks.nth(i).innerText();
        expect(text).toContain("Configure this page's form");
      }
    });

  });

  // ── TC-05: title format and configure link text ────────────────────────

  test('TC-05: title format is "{title}(ID: {id})" and settings link text is correct', async () => {

    await test.step('Navigate to profileMain preview', async () => {
      await page.goto(previewUrl(vars.profileMain.id));
      await utils.wait(wait_secs);
      await expect(page.locator('.crm-error')).toHaveCount(0);
      await expect(page.locator('.crm-uf-usagepages-block')).toBeVisible();
    });

    await test.step('First column text matches "{title}(ID: {N})" pattern', async () => {
      const cells = page.locator('.crm-uf-usagepages-block tbody td:first-child');
      const cellCount = await cells.count();
      expect(cellCount).toBeGreaterThan(0);

      // Every cell must match the ID format pattern
      const idPattern = /\(ID: \d+\)/;
      for (let i = 0; i < cellCount; i++) {
        const text = await cells.nth(i).innerText();
        expect(text, `Cell ${i} does not match ID format: "${text}"`).toMatch(idPattern);
      }
    });

    await test.step('Contribution page row title includes exact ID', async () => {
      const cells = page.locator('.crm-uf-usagepages-block tbody td:first-child');
      const cellTexts = await cells.allInnerTexts();
      const matched = cellTexts.some(text =>
        text.includes(vars.contribPageMain.title) &&
        text.includes(`(ID: ${vars.contribPageMain.id})`)
      );
      expect(matched, `Title cell must contain "${vars.contribPageMain.title}(ID: ${vars.contribPageMain.id})"`).toBe(true);
    });

    await test.step('Second column links all say "Configure this page\'s form"', async () => {
      const configLinks = page.locator('.crm-uf-usagepages-block tbody td:nth-child(2) a');
      const count = await configLinks.count();
      expect(count).toBeGreaterThan(0);
      for (let i = 0; i < count; i++) {
        const text = await configLinks.nth(i).innerText();
        expect(text).toContain("Configure this page's form");
      }
    });

  });

  // ── TC-06: pagination ──────────────────────────────────────────────────

  test('TC-06A: 25 linked pages - table shows 25 rows, no pager rendered', async () => {

    await test.step('Navigate to profileF preview', async () => {
      await page.goto(previewUrl(vars.profileF.id));
      await utils.wait(wait_secs);
      await expect(page.locator('.crm-error')).toHaveCount(0);
      await expect(page.locator('.crm-uf-usagepages-block')).toBeVisible();
    });

    await test.step('Table has exactly 25 rows', async () => {
      const rows = page.locator('.crm-uf-usagepages-block tbody tr');
      await expect(rows).toHaveCount(25);
    });

    await test.step('No pager is rendered (numPages = 1)', async () => {
      await expect(page.locator('.crm-uf-usagepages-block .crm-pager')).toHaveCount(0);
    });

  });

  test('TC-06B: 26 linked pages - table shows 25 rows (page 1), pager is rendered', async () => {

    await test.step('Navigate to profileG preview', async () => {
      await page.goto(previewUrl(vars.profileG.id));
      await utils.wait(wait_secs);
      await expect(page.locator('.crm-error')).toHaveCount(0);
      await expect(page.locator('.crm-uf-usagepages-block')).toBeVisible();
    });

    await test.step('First page shows 25 rows (rowCount per page limit)', async () => {
      const rows = page.locator('.crm-uf-usagepages-block tbody tr');
      await expect(rows).toHaveCount(25);
    });

    await test.step('Pager is rendered at both top and bottom', async () => {
      const pagers = page.locator('.crm-uf-usagepages-block .crm-pager');
      // Two pager divs: one from location="top", one from location="bottom"
      await expect(pagers).toHaveCount(2);
      await expect(pagers.first()).toBeVisible();
      await expect(pagers.last()).toBeVisible();
    });

  });

});
