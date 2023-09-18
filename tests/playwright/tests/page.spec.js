const { test, expect, chromium } = require('@playwright/test');
const utils = require('./utils.js');

/** @type {import('@playwright/test').Page} */

const site_name = 'netiCRM';

const url_ary = [
    {title:'Administer CiviCRM', url:'/civicrm/admin?reset=1'},
    {title:'CiviCRM Home', url:'/civicrm/dashboard?reset=1'},
    {title:'Configuration Checklist', url:'/civicrm/admin/configtask?reset=1'},
    {title:'Synchronize Users to Contacts', url:'/civicrm/admin/synchUser?reset=1'},
    {title:'Find Contacts', url:'/civicrm/contact/search?reset=1'},
    {title:'New Individual', url:'/civicrm/contact/add?reset=1&ct=Individual'},
    {title:'New Organization', url:'/civicrm/contact/add?reset=1&ct=Organization'},
    {title:'New Household', url:'/civicrm/contact/add?reset=1&ct=Household'},
    {title:'Activities', url:'/civicrm/activity/add?atype=3&action=add&reset=1&context=standalone'},
    {title:'Import Contacts', url:'/civicrm/import/contact?reset=1'},
    {title:'Manage Groups', url:'/civicrm/group?reset=1'},
    {title:'Manage Tags (Categories)', url:'/civicrm/admin/tag?reset=1'},
    {title:'New Activity', url:'/civicrm/activity?reset=1&action=add&context=standalone'},
    {title:'Find Activities', url:'/civicrm/activity/search?reset=1'},
    {title:'Import Activities', url:'/civicrm/import/activity?reset=1'},
    {title:'Find and Merge Duplicate Contacts', url:'/civicrm/contact/deduperules?reset=1'},
    {title:'Relationship Types', url:'/civicrm/admin/reltype?reset=1'},
    {title:'CiviCRM Profile', url:'/civicrm/admin/uf/group?reset=1'},
    {title:'Custom Data', url:'/civicrm/admin/custom/group?reset=1'},
    {title:'CiviContribute Dashboard', url:'/civicrm/contribute?reset=1'},
    {title:'Payment Instrument Options', url:'/civicrm/admin/options/payment_instrument?group=payment_instrument&reset=1'},
    {title:'New Contribution', url:'/civicrm/contribute/add?reset=1&action=add&context=standalone'},
    {title:'Find Contributions', url:'/civicrm/contribute/search?reset=1'},
    {title:'Import Contributions', url:'/civicrm/contribute/import?reset=1'},
    {title:'CiviPledge', url:'/civicrm/pledge?reset=1'},
    {title:'New Pledge', url:'/civicrm/pledge/add?reset=1&action=add&context=standalone'},
    {title:'Find Pledges', url:'/civicrm/pledge/search?reset=1'},
    {title:'Title and Settings', url:'/civicrm/admin/contribute/add?reset=1&action=add'},
    {title:'Manage Contribution Pages', url:'/civicrm/admin/contribute?reset=1'},
    {title:'Personal Campaign Pages', url:'/civicrm/admin/pcp?reset=1'},
    {title:'Manage Premiums', url:'/civicrm/admin/contribute/managePremiums?reset=1'},
    {title:'New Price Set', url:'/civicrm/admin/price?reset=1&action=add'},
    {title:'Price Sets', url:'/civicrm/admin/price?reset=1'},
    {title:'Contribution Types', url:'/civicrm/admin/contribute/contributionType?reset=1'},
    {title:'CiviEvent Dashboard', url:'/civicrm/event?reset=1'},
    {title:'Event Type Options', url:'/civicrm/admin/options/event_type?group=event_type&reset=1'},
    {title:'Participant Status', url:'/civicrm/admin/participant_status?reset=1'},
    {title:'Participant Role Options', url:'/civicrm/admin/options/participant_role?group=participant_role&reset=1'},
    {title:'Register New Participant', url:'/civicrm/participant/add?reset=1&action=add&context=standalone'},
    {title:'Find Participants', url:'/civicrm/event/search?reset=1'},
    {title:'Import Participants', url:'/civicrm/event/import?reset=1'},
    {title:'New Event', url:'/civicrm/event/add?reset=1&action=add'},
    {title:'CiviEvent Dashboard', url:'/civicrm/event/manage?reset=1'},
    {title:'Event Templates', url:'/civicrm/admin/eventTemplate?reset=1'},
    {title:'New Price Set', url:'/civicrm/admin/price?reset=1&action=add'},
    {title:'Price Sets', url:'/civicrm/admin/price?reset=1'},
    {title:'Find Mailings', url:'/civicrm/mailing/browse?reset=1&scheduled=true'},
    {title:'New Mailing', url:'/civicrm/mailing/send?reset=1'},
    {title:'Draft and Unscheduled Mailings', url:'/civicrm/mailing/browse/unscheduled?reset=1&scheduled=false'},
    {title:'Scheduled and Sent Mailings', url:'/civicrm/mailing/browse/scheduled?reset=1&scheduled=true'},
    {title:'Archived Mailings', url:'/civicrm/mailing/browse/archived?reset=1'},
    {title:'Headers, Footers, and Automated Messages', url:'/civicrm/admin/component?reset=1'},
    {title:'Message Templates', url:'/civicrm/admin/messageTemplates?reset=1'},
    {title:'FROM Email Addresses', url:'/civicrm/admin/from_email_address?&reset=1&action=browse'},
    {title:'Email Greeting Options', url:'/civicrm/admin/options/email_greeting?group=email_greeting&reset=1'},
    {title:'CiviMember', url:'/civicrm/member?reset=1'},
    {title:'New Member', url:'/civicrm/member/add?reset=1&action=add&context=standalone'},
    {title:'Find Members', url:'/civicrm/member/search?reset=1'},
    {title:'Import Memberships', url:'/civicrm/member/import?reset=1'},
    {title:'Membership Types', url:'/civicrm/admin/member/membershipType?reset=1'},
    {title:'Membership Status Rules', url:'/civicrm/admin/member/membershipStatus?reset=1'},
    {title:'CiviCRM Reports', url:'/civicrm/report/list?reset=1'},
    {title:'Create Reports from Templates', url:'/civicrm/admin/report/template/list?reset=1'},
    {title:'Registered Templates', url:'/civicrm/admin/report/options/report_template?reset=1'},
    {title:'Search Builder', url:'/civicrm/contact/search/builder?reset=1'},
    {title:'全文搜尋', url:'/civicrm/contact/search/custom?csid=15&reset=1'},
    {title:'Advanced Search', url:'/civicrm/contact/search/advanced?reset=1'}
]


let browser;
test.beforeAll(async () => {
    browser = await chromium.launch();
});
  
test.afterAll(async () => {
});


test.describe.serial('Page output correct test', () => {
    test.setTimeout(300000);
    var i = 0;
    var visited = [];
    for(let obj of url_ary){
        let url = obj.url;
        let full_title = obj.title + ' | ' + site_name;
        i += 1;

        (function(url, full_title) {
          test(`Check page output ${i} - ${obj.title}`, async () => {

              await test.step(`"${full_title}" should match the page title and have no errors`, async() => {
                  const page = await browser.newPage();
                  await page.goto(url);
                  await page.waitForURL(url);
                  await expect(page, `page title is not match "${full_title}"`).toHaveTitle(full_title);
                  await expect(page.locator('.error-ci'), 'No error occurred in the page').toHaveCount(0);

                  if (typeof process.env.pageTestLink !== 'undfined' && process.env.pageTestLink) {
                      const links = page.locator('.crm-container >> a:visible');
                      const linkCount = await links.count();
                      await utils.print(`Found ${linkCount} links.`);
                      for (let i = 0; i < linkCount; i++) {
                          let href = await links.nth(i).getAttribute('href');
                          utils.print(` Trying click link: ${href}`);
                          if ( visited.includes(href) ) {
                              utils.print(" ... skip visited link");
                              continue;
                          }
                          if ( href && href.length > 0 && href.match(/^\/|^http:\/\/1/) && !href.match(/admin\/weight.*idName=/)) {
                              let newPage = await browser.newPage();
                              try {
                                const responsePromise = newPage.waitForResponse(href, { timeout: 5000 });
                                await newPage.goto(href, { timeout: 3000, waitUntil: 'commit' });
                                const response = await responsePromise;
                                if (response.ok()) {
                                    utils.print(" ... successful clicked on link");
                                }
                                else {
                                    throw new Error(" ... error on loading page");
                                }
                                await newPage.close()
                                visited.push(href);
                              }
                              catch(e) {
                                utils.print(e.toString());
                                continue;
                              }
                          }
                          else {
                              utils.print(" ... skip invalid link");
                          }
                      }
                  }
                  await page.close();
              });

          })
        })(url, full_title);
    }

});