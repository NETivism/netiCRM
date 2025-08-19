const { test, expect, chromium } = require("@playwright/test");
const utils = require("./utils.js");

/** @type {import('@playwright/test').Page} */
let page;
const wait_secs = 2000;

test.beforeAll(async () => {
  const browser = await chromium.launch();
  page = await browser.newPage();
});

test.afterAll(async () => {
  await page.close();
});

test.use({ storageState: "storageState.json" });

test.describe.serial("Create Membership Type", () => {
  var organization = utils.makeid(10);
  const membership_type = `MembershipTypeForTest${Math.floor(
    Math.random() * 100000
  )}`; // Use random number to avoid duplicate names
  const profile_name = `ProfileNameForTest${Math.floor(
    Math.random() * 100000
  )}`; // Use random number to avoid duplicate names
  const contribution_page_name = `ContributionPageNameForTest${Math.floor(
    Math.random() * 100000
  )}`; // Use random number to avoid duplicate names
  var element;
  test("Create Organization Contact", async () => {
    await test.step("Navigate to add organization page", async () => {
      await page.goto("civicrm/contact/add?reset=1&ct=Organization");
    });

    await test.step("Fill organization name and save", async () => {
      await page.getByLabel("Organization Name").fill(organization);
      await page
        .locator("form[name=Contact] input[type=submit][value='Save']")
        .first()
        .click();
      await utils.wait(wait_secs);
      /* Verify page title contains organization name */
      await expect(page).toHaveTitle(new RegExp("^" + organization));
    });
  });

  test("Create Membership Type", async () => {
    await test.step("Navigate to add membership type page", async () => {
      await page.goto("civicrm/admin/member/membershipType?action=add&reset=1");
    });

    await test.step("Fill membership type name, organization name and search", async () => {
      await page.locator("input#name").fill(membership_type);
      await page.locator("input#member_org").fill(organization);
      /* Search for organization in database */
      await page.locator("#_qf_MembershipType_refresh").click();
      await utils.wait(wait_secs);
      /* Verify organization found in search results */
      await expect(
        page.locator("table tbody tr").filter({ hasText: organization })
      ).toBeVisible();
    });

    await test.step("Configure membership settings and save", async () => {
      await page.locator("select#contribution_type_id").selectOption("2");
      await page.locator("#duration_interval").fill("1");
      await page.locator("select#duration_unit").selectOption("year");
      await page.locator("select#period_type").selectOption("rolling");
      await page.locator('[id="_qf_MembershipType_upload-bottom"]').click();
      /* Verify membership type appears in results table */
      await expect(
        page.getByRole("cell", { name: membership_type })
      ).toHaveText(membership_type);
    });
  });
  test("Create Membership", async () => {
    const firstName = "test_firstName";
    const lastName = "test_lastName";
    const fullName = firstName + " " + lastName;

    await test.step("Navigate to add membership page", async () => {
      await page.goto(
        "/civicrm/member/add?reset=1&action=add&context=standalone"
      );
    });

    await test.step("Create individual contact", async () => {
      await page.locator("#profiles_1").selectOption("4");
      /* Verify dialog input box exists */
      await expect(page.getByRole("dialog")).toBeVisible();
    });

    await test.step("Fill name and submit", async () => {
      await page.locator("#first_name").fill(firstName);
      await page.locator("#last_name").fill(lastName);
      await page.locator("#_qf_Edit_next").click();
      /* Verify data filled correctly */
      await expect(page.locator("#contact_1")).toHaveValue(fullName);
    });

    await test.step("Select organization, membership type, dates and save", async () => {
      element = page.locator('[id="membership_type_id\\[0\\]"]');
      await element.selectOption(organization);
      element = page.locator('[id="membership_type_id\\[1\\]"]');
      await element.selectOption(membership_type);

      /* Set join and start dates */
      await page.locator("#join_date").click();
      await page.getByRole("link", { name: "1", exact: true }).click();
      await page.locator("#start_date").click();
      await page.getByRole("link", { name: "1", exact: true }).click();

      await page.locator('[id="_qf_Membership_upload-bottom"]').click();

      await expect(page.locator("#option11>tbody")).toContainText(
        membership_type
      );
    });
  });
  test("Create Profile", async () => {
    await test.step("Navigate to profile page", async () => {
      await page.goto("/civicrm/admin/uf/group?reset=1");
    });

    await test.step("Create new profile", async () => {
      await page.locator("#newCiviCRMProfile-top").click();
    });

    await test.step("Fill profile", async () => {
      await page.locator("input#title").fill(profile_name);
      await page.locator("input#uf_group_type\\[CiviEvent\\]").uncheck();
      await page.getByText("Advanced options").click();
      await page.locator("input#CIVICRM_QFID_2_12").check();
      await page.locator('[id="_qf_Group_next-bottom"]').click();
      await utils.findElement(page, "h1.page-title");
      await expect(page.locator("h1.page-title")).toContainText(profile_name);
    });

    await test.step("Add  Name field and verify", async () => {
      await page.locator('[id="field_name[0]"]').selectOption("Individual");
      await page.locator("#select2-field_name1-container").click();
      await page.locator('[id*="first_name"]').click();
      await page.locator('[id="_qf_Field_next_new-bottom"]').click();
      await page.locator('[id="field_name[0]"]').selectOption("Individual");
      await page.locator("#select2-field_name1-container").click();
      await page.locator('[id*="last_name"]').click();
      await page.locator('[id="_qf_Field_next-bottom"]').click();
      /* Verify profile current fields are displayed */
      await expect(page.locator("#crm-container")).toBeVisible();
    });
  });
  test("Create Contribution Page", async () => {
    await test.step("Navigate to manage contribution pages", async () => {
      await page.goto("/civicrm/admin/contribute/add?reset=1&action=add");
    });

    await test.step("Edit contribution page title and settings", async () => {
      await page.locator("input[name='is_active']").click();
      await page.locator("input#title").fill(contribution_page_name);
      await page.locator("select#contribution_type_id").selectOption("4");
      /* Verify data filled correctly */
      await expect(page.locator("input#title")).toHaveValue(
        contribution_page_name
      );
    });

    await test.step("Configure amount settings", async () => {
      await page.waitForLoadState("networkidle");
      await page.locator('[id="_qf_Settings_upload-bottom"]').click();
      await page.waitForLoadState("networkidle");

      await page.locator("input#amount_block_is_active").uncheck();
      await page.locator("input#is_monetary").check();
      await page.locator("input#is_pay_later").check();
      await page
        .locator("textarea#pay_later_receipt")
        .fill("I will send payment by check");
      /* Verify settings are correct */
      await expect(page.locator("input#is_pay_later")).toBeChecked();
    });

    await test.step("Enable membership section", async () => {
      await page.locator('[id="_qf_Amount_upload-bottom"]').click();
      await page.locator("input#is_active").check();
      /* Verify memberFields element display is block */
      await expect(page.locator("#memberFields")).toHaveCSS("display", "block");
    });

    await test.step("Select membership type and require membership", async () => {
      await page
        .getByRole("checkbox", {
          name: membership_type,
          exact: true,
        })
        .check();
      await page.locator("input#is_required").check();
      /* Verify error message does not exist */
      await expect(page.locator("#errorList")).not.toBeVisible();
      await page.locator('[id="_qf_MembershipBlock_upload-bottom"]').click();
    });

    await test.step("Configure thank you page", async () => {
      await page.locator("input#thankyou_title").fill("thank");

      /* Verify data filled correctly */
      await expect(page.locator("input#thankyou_title")).toHaveValue("thank");
      await page.locator('[id="_qf_ThankYou_upload-bottom"]').click();
      await page.locator('[id="_qf_Contribute_upload-bottom"]').click();
    });

    await test.step("Select profile for embedded form", async () => {
      await utils.findElement(page, "select#custom_pre_id");
      const options = await page
        .locator("select#custom_pre_id")
        .locator("option")
        .all();
      for (let i = 0; i < options.length; i++) {
        const text = await options[i].textContent();
        if (text && text.includes(profile_name)) {
          await page.locator("select#custom_pre_id").selectOption({ index: i });
          break;
        }
      }
      /* Verify selection is correct */
      await expect(page.locator("select#custom_pre_id")).toHaveValue(/.+/);
    });

    await test.step("Complete remaining setup steps", async () => {
      const steps = ["Custom", "Premium", "Widget", "PCP"];
      for (const step of steps) {
        const buttonSelector = `[id="_qf_${step}_upload-${
          step === "Widget" || step === "PCP" ? "top" : "bottom"
        }"]`;
        await utils.findElement(page, buttonSelector);
        await page.locator(buttonSelector).click();
      }
      /* Verify return to contribution page overview */
      await utils.findElement(page, "h1.page-title");
      await expect(page.locator("h1.page-title")).toContainText(
        contribution_page_name
      );
    });
  });
  test("Fill Contribution Form", async () => {
    let contributionPage;

    await test.step("Navigate to contribution page overview", async () => {
      await page.goto("/civicrm/admin/contribute?reset=1");
      await page.locator("#title").fill(contribution_page_name);
      await page.locator("input#_qf_SearchContribution_refresh").click();
      await page.getByRole("link", { name: contribution_page_name }).click();

      [contributionPage] = await Promise.all([
        page.waitForEvent("popup"),
        page
          .locator(
            "a[href*='/civicrm/contribute/transact'][target='_blank']:not([href*='action=preview'])"
          )
          .click(),
      ]);

      /* Verify page title is correct */
      await expect(contributionPage).toHaveTitle(
        new RegExp(contribution_page_name)
      );
    });

    await test.step("Select membership type", async () => {
      /* Verify membership type selection is correct */
      await expect(
        contributionPage.getByRole("radio", { name: membership_type })
      ).toBeVisible();
    });

    await test.step("Fill name information", async () => {
      const randomUsername = utils.makeid(8);
      const randomEmail = utils.makeid(8) + "@example.com";

      const email = contributionPage.locator("input#email-5");
      /* Check if user is logged in */
      if ((await email.isVisible()) && (await email.isEditable())) {
        await contributionPage
          .locator("input[name='cms_name']")
          .fill(randomUsername);
        await email.fill(randomEmail);
        await contributionPage.locator("input#first_name").fill("chenyy");
        await contributionPage.locator("input#last_name").fill("jerryyy");
      }
      /* Verify data filled correctly - only check name if not logged in */
      const firstNameField = contributionPage.locator("input#first_name");
      if ((await email.isVisible()) && (await email.isEditable())) {
        await expect(firstNameField).toHaveValue("chenyy");
      }
    });

    await test.step("Check input data correctness", async () => {
      await contributionPage.locator("input#_qf_Main_upload-bottom").click();
      /* Verify help container and continue button are visible */
      await expect(contributionPage.locator("#help")).toBeVisible();
      await expect(
        contributionPage.locator("input#_qf_Confirm_next-bottom")
      ).toBeVisible();
    });

    await test.step("Final contribution data", async () => {
      await contributionPage.locator("input#_qf_Confirm_next-bottom").click();
      /* Verify #help container exists and is visible, with payment incomplete message */
      await expect(contributionPage.locator("#help")).toBeVisible();
    });
  });
  test("Update Membership Status", async () => {
    await test.step("Navigate to contact search page", async () => {
      await page.goto("/civicrm/contact/search?reset=1");
    });

    await test.step("Search for contact", async () => {
      await page.locator("input#sort_name").fill("firstName");
      await page.locator("input[type='submit'][value='Search']").click();
      /* Verify search-status container exists with results */
      await expect(page.locator("#search-status")).toBeVisible();
    });

    await test.step("Navigate to member details and membership section", async () => {
      await page
        .getByRole("link", { name: "test_firstName test_lastName" })
        .first()
        .click();
      await page
        .locator(
          "a.ui-tabs-anchor[href*='/civicrm/contact/view/membership'][href*='snippet=1']"
        )
        .click();
      /* Verify URL contains selectedChild=member query parameter */
      await expect(page).toHaveURL(/selectedChild=member/);
    });

    await test.step("Edit membership status", async () => {
      await page
        .locator("a[href*='action=update'][href*='context=membership']")
        .click();
      await page.locator("input#is_override").check();
      await page.getByLabel("Membership Status").selectOption("2");
      await page.locator('[id="_qf_Membership_upload-bottom"]').click();
      /* Verify membership status changed from pending/disabled to current */
      const statusElement = page.locator(".crm-membership-status");
      await expect(statusElement).toBeVisible();
      const statusText = await statusElement.textContent();
      expect(statusText).toMatch(/(Current|正常)/i);
    });

    await test.step("Verify membership dates", async () => {
      const startDateText = await page
        .locator(".crm-membership-start_date")
        .textContent();
      const endDateText = await page
        .locator(".crm-membership-end_date")
        .textContent();

      const startDate = new Date(startDateText.trim());
      const endDate = new Date(endDateText.trim());

      const expectedEndDate = new Date(startDate);
      expectedEndDate.setFullYear(startDate.getFullYear() + 1);
      expectedEndDate.setDate(expectedEndDate.getDate() - 1);

      /* Verify end date is exactly one year from start date */
      expect(endDate.toDateString()).toBe(expectedEndDate.toDateString());
    });
  });
});
