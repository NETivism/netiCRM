const { test, expect, chromium } = require('@playwright/test');
const utils = require('./utils.js');

/** @type {import('@playwright/test').Page} */

const site_name = 'netiCRM';

// List all admin page links
const url_admin_ary = [
    {title:'Administer CiviCRM', url:'/civicrm/admin?reset=1'},
    {title:'Configuration Checklist', url:'/civicrm/admin/configtask?reset=1'},
    {title:'Synchronize Users to Contacts', url:'/civicrm/admin/synchUser?reset=1'},
    {title:'Manage Tags (Categories)', url:'/civicrm/admin/tag?reset=1'},
    {title:'Relationship Types', url:'/civicrm/admin/reltype?reset=1'},
    {title:'CiviCRM Profile', url:'/civicrm/admin/uf/group?reset=1'},
    {title:'Custom Data', url:'/civicrm/admin/custom/group?reset=1'},
    {title:'Coupon', url:'/civicrm/admin/coupon?reset=1'},
    {title:'Event Type Options', url:'/civicrm/admin/options/event_type?group=event_type&reset=1'},
    {title:'Participant Status', url:'/civicrm/admin/participant_status?reset=1'},
    {title:'Participant Role Options', url:'/civicrm/admin/options/participant_role?group=participant_role&reset=1'},
    {title:'Headers, Footers, and Automated Messages', url:'/civicrm/admin/component?reset=1'},
    {title:'Message Templates', url:'/civicrm/admin/messageTemplates?reset=1'},
    {title:'Membership Types', url:'/civicrm/admin/member/membershipType?reset=1'},
    {title:'Membership Status Rules', url:'/civicrm/admin/member/membershipStatus?reset=1'},
    {title:'Create Reports from Templates', url:'/civicrm/admin/report/template/list?reset=1'},
    {title:'Registered Templates', url:'/civicrm/admin/report/options/report_template?reset=1'},
    //new check
    {title:'FROM Email Addresses', url: '/civicrm/admin/from_email?reset=1'},
    {title:'Addressee Options', url: '/civicrm/admin/options/addressee?group=addressee&reset=1'},
    {title:'Gender Options', url: '/civicrm/admin/options/gender?group=gender&reset=1'},
    {title:'Instant Messenger Service Options', url: '/civicrm/admin/options/instant_messenger_service?group=instant_messenger_service&reset=1'},
    {title:'Languages Options', url: '/civicrm/admin/options/language?group=languages&reset=1'},
    {title:'Contact Types', url: '/civicrm/admin/options/subtype?reset=1'},
    {title:'Participant Listing Options', url: '/civicrm/admin/options/participant_listing?group=participant_listing&reset=1'},
    {title:'Postal Greeting Options', url: '/civicrm/admin/options/postal_greeting?group=postal_greeting&reset=1'},
    {title:'Word Replacements', url: '/civicrm/admin/options/wordreplacements?reset=1'},
    {title:'Activity Type Options', url: '/civicrm/admin/options/activity_type?group=activity_type&reset=1'},
    {title:'Email Greeting Options', url: '/civicrm/admin/options/email_greeting?group=email_greeting&reset=1'},
    {title:'Website Type Options', url: '/civicrm/admin/options/website_type?group=website_type&reset=1'},
    {title:'Grant Type Options', url: '/civicrm/admin/options/grant_type?group=grant_type&reset=1'},
    {title:'Custom Search Options', url: '/civicrm/admin/options/custom_search?group=custom_search&reset=1'},
    {title:'Accept Creditcard Options', url: '/civicrm/admin/options/accept_creditcard?group=accept_creditcard&reset=1'},
    {title:'Safe File Extension Options', url: '/civicrm/admin/options/safe_file_extension?group=safe_file_extension&reset=1'},
    {title:'Event Badge Options', url: '/civicrm/admin/options/event_badge?group=event_badge&reset=1'},
    {title:'Mobile Provider Options', url: '/civicrm/admin/options/mobile_provider?group=mobile_provider&reset=1'},
    {title:'Individual Prefix Options', url: '/civicrm/admin/options/individual_prefix?group=individual_prefix&reset=1'},
    {title:'Payment Instrument Options', url: '/civicrm/admin/options/payment_instrument?group=payment_instrument&reset=1'},
    {title:'Individual Suffix Options', url: '/civicrm/admin/options/individual_suffix?group=individual_suffix&reset=1'},
    {title:'Preferred Communication Method Options', url: '/civicrm/admin/options/preferred_communication_method?group=preferred_communication_method&reset=1'},
    {title:'Phone Type Options', url: '/civicrm/admin/options/phone_type?group=phone_type&reset=1'},
    {title:'Location Types (Home, Work...)', url: '/civicrm/admin/locationType?reset=1'},
    {title:'Personal Campaign Pages', url: '/civicrm/admin/pcp?reset=1'},
    {title:'AI Copywriter - Settings', url: '/civicrm/admin/setting/aicompletion?reset=1'},
    {title:'Settings - Update Directory Path and URL', url: '/civicrm/admin/setting/updateConfigBackend?reset=1'},
    {title:'CiviCRM Extensions', url: '/civicrm/admin/extensions?reset=1'},
    {title:'Batch Job List', url: '/civicrm/admin/batch?reset=1'},
    {title:'FROM Email Addresses', url: '/civicrm/admin/from_email_address?reset=1'},
    {title:'Reports Listing', url: '/civicrm/admin/report/list?reset=1'},
    {title:'Manage Events', url: '/civicrm/admin/event?reset=1'},
    {title:'Dedupe Exceptions', url: '/civicrm/dedupe/exception?reset=1'},
    {title:'Settings - Contribution Receipt', url: '/civicrm/admin/receipt?reset=1'},
    {title:'Material Design Iconic Font', url: '/civicrm/admin/zmdi-docs?reset=1'},
    {title:'Settings - SMS Provider', url: '/civicrm/admin/sms/provider?reset=1'},
    {title:'Settings - Mailer Settings', url: '/civicrm/admin/mail?reset=1'},
    {title:'Access Control', url: '/civicrm/admin/access?reset=1'},
    {title:'Event Templates', url: '/civicrm/admin/eventTemplate?reset=1'},
    {title:'Find and Merge Duplicate Contacts', url: '/civicrm/contact/deduperules?reset=1'},
    {title:'Contribution Types', url: '/civicrm/admin/contribute/contributionType?reset=1'},
    {title:'Manage Premiums', url: '/civicrm/admin/contribute/managePremiums?reset=1'},
    {title:'Domain Information', url: '/civicrm/admin/domain?action=update&reset=1'},
    {title:'Import/Export Mappings', url: '/civicrm/admin/mapping?reset=1'},
    {title:'Settings - Receipt Tax included', url: '/civicrm/admin/taxreceipt?reset=1'},
    {title:'Manage Contribution Pages', url: '/civicrm/admin/contribute?reset=1'},
    {title:'Audit Records', url: '/civicrm/admin/audit-records?reset=1'},
    {title:'Mail Accounts', url: '/civicrm/admin/mailSettings?reset=1'},
    {title:'Price Sets', url: '/civicrm/admin/price?reset=1'},
    {title:'Traffic Source', url: '/civicrm/track/report?reset=1'},
    {title:'Global Settings', url: '/civicrm/admin/setting?reset=1'},
    {title:'Settings - Recurring Contribution', url: '/civicrm/admin/recurring?reset=1'},
];

const url_dashboard_ary = [
    {title:'CiviCRM Home', url:'/civicrm/dashboard?reset=1'},
];

// List all contact page links
const url_contact_ary = [
    {title:'Find Contacts', url:'/civicrm/contact/search?reset=1'},
    {title:'New Individual', url:'/civicrm/contact/add?reset=1&ct=Individual'},
    {title:'New Organization', url:'/civicrm/contact/add?reset=1&ct=Organization'},
    {title:'New Household', url:'/civicrm/contact/add?reset=1&ct=Household'},
    {title:'Find and Merge Duplicate Contacts', url:'/civicrm/contact/deduperules?reset=1'},
    {title:'Search Builder', url:'/civicrm/contact/search/builder?reset=1'},
    {title:'全文搜尋', url:'/civicrm/contact/search/custom?csid=15&reset=1'},
    {title:'Advanced Search', url:'/civicrm/contact/search/advanced?reset=1'},
    {title:'Find Contacts', url:'/civicrm/contact/search/basic'},
    {title:'Simple Search', url:'/civicrm/contact/search/simple'},
    {title:'Print Annual Receipt', url:'/civicrm/contact/task/annualreceipt'},
    {title:'Find and Merge Duplicate Contacts', url:'/civicrm/contact/dedupefind'},
];

// List all contact-group page links
const url_group_ary = [
    {title:'Manage Groups', url:'/civicrm/group?reset=1'},
    {title:'New Group', url:'/civicrm/group/add?reset=1'},
    {title:'包括/不包括群組聯絡人/標籤', url:'/civicrm/contact/search/custom?reset=1&csid=4'},
];

// List all activity page links
const url_activity_ary = [
    {title:'Activities', url:'/civicrm/activity/add?atype=3&action=add&reset=1&context=standalone'},
    {title:'New Activity', url:'/civicrm/activity?reset=1&action=add&context=standalone'},
    {title:'Find Activities', url:'/civicrm/activity/search?reset=1'},
    {title:'View Activity', url:'/civicrm/activity/view?reset=1'},
];

// List all import page links
const url_import_ary = [
    {title:'Import Contacts', url:'/civicrm/import/contact?reset=1'},
    {title:'Import Activities', url:'/civicrm/import/activity?reset=1'},
    {title:'Import Contributions', url:'/civicrm/contribute/import?reset=1'},
    {title:'Import Participants', url:'/civicrm/event/import?reset=1'},
    {title:'Import Memberships', url:'/civicrm/member/import?reset=1'},
];

// List all contribute page links
const url_contribute_ary = [
    // add contribution
    {title:'CiviContribute Dashboard', url:'/civicrm/contribute?reset=1'},
    {title:'New Contribution', url:'/civicrm/contribute/add?reset=1&action=add&context=standalone'},
    {title:'Title and Settings', url:'/civicrm/admin/contribute/add?reset=1&action=add'},
    // search contribution
    {title:'Find Contributions', url:'/civicrm/contribute/search?reset=1'},
    {title:'Contribution Booster', url:'/civicrm/contribute/booster?reset=1'},
    {title:'Custom Search - Recurring Contribution', url:'/civicrm/contact/search/custom?reset=1&csid=17'},
    {title:'捐款整合', url:'/civicrm/contact/search/custom?reset=1&csid=2'},
    {title:'匯出募款專頁的價格表內容', url:'/civicrm/contact/search/custom?reset=1&csid=16'},
    // manage contribution page
    {title:'Manage Contribution Pages', url:'/civicrm/admin/contribute?reset=1'},
    {title:'Personal Campaign Pages', url:'/civicrm/admin/pcp?reset=1'},
    {title:'Manage Premiums', url:'/civicrm/admin/contribute/managePremiums?reset=1'},
    {title:'Manage Premiums', url:'/civicrm/admin/contribute/managePremiums?action=add&reset=1'},
    {title:'New Price Set', url:'/civicrm/admin/price?reset=1&action=add'},
    {title:'Price Sets', url:'/civicrm/admin/price?reset=1'},
    {title:'Import ACH', url:'/civicrm/contribute/taiwanach/import'},
    // contribution page - setting
    {title:'Settings - Contribution Receipt', url:'/civicrm/admin/receipt'},
    {title:'Contribution Types', url:'/civicrm/admin/contribute/contributionType?reset=1'},
    {title:'Contribution Types', url:'/civicrm/admin/contribute/contributionType?action=add&reset=1'},
    {title:'Payment Instrument Options', url:'/civicrm/admin/options/payment_instrument?group=payment_instrument&reset=1'},
    {title:'Payment Instrument Options', url:'/civicrm/admin/options/payment_instrument?group=payment_instrument&action=add&reset=1'},
];

// List all event page links (Include participant)
const url_event_ary = [
    // event
    {title:'CiviEvent Dashboard', url:'/civicrm/event?reset=1'},
    {title:'New Event', url:'/civicrm/event/add?reset=1&action=add'},
    {title:'CiviEvent Dashboard', url:'/civicrm/event/manage?reset=1'},
    {title:'Event Templates', url:'/civicrm/admin/eventTemplate?reset=1'},
    // participant
    {title:'Register New Participant', url:'/civicrm/participant/add?reset=1&action=add&context=standalone'},
    {title:'給活動參與者的價格表詳情' ,url:'/civicrm/contact/search/custom?reset=1&csid=9'},
    {title:'Find Participants', url:'/civicrm/event/search?reset=1'},
];

// List all mailing page links
const url_mailing_ary = [
    {title:'Find Mailings', url:'/civicrm/mailing/browse?reset=1&scheduled=true'},
    {title:'New Mailing', url:'/civicrm/mailing/send?reset=1'},
    {title:'Draft and Unscheduled Mailings', url:'/civicrm/mailing/browse/unscheduled?reset=1&scheduled=false'},
    {title:'Scheduled and Sent Mailings', url:'/civicrm/mailing/browse/scheduled?reset=1&scheduled=true'},
    {title:'Archived Mailings', url:'/civicrm/mailing/browse/archived?reset=1'},
    // mailing setting
    {title:'FROM Email Addresses', url:'/civicrm/admin/from_email_address?&reset=1&action=browse'},
    {title:'Email Greeting Options', url:'/civicrm/admin/options/email_greeting?group=email_greeting&reset=1'},
];

// List all member page links
const url_member_ary = [
    {title:'CiviMember', url:'/civicrm/member?reset=1'},
    {title:'New Member', url:'/civicrm/member/add?reset=1&action=add&context=standalone'},
    {title:'Find Members', url:'/civicrm/member/search?reset=1'},
];

//List all report page list
const url_report_ary = [
    {title:'CiviCRM Reports', url:'/civicrm/report/list?reset=1', type:'all'},
    {title:'Traffic Source', url:'/civicrm/track/report'},
    {title:'Create Reports from Templates', url:'/civicrm/report/template/list'},
    {title:'Registered Templates', url:'/civicrm/report/options/report_template'},
    {title:'Report Template', url:'/civicrm/admin/report/register'},
    {title:'Reports Listing', url:'/civicrm/admin/report/list'},
    {title:'Report Summary', url:'/civicrm/report/summary'},
    {title:'Constituent Report (Summary)', url:'/civicrm/report/instance/1?reset=1'},
    {title:'Constituent Report (Detail)', url:'/civicrm/report/instance/2?reset=1'},
    {title:'Donor Report (Summary)', url:'/civicrm/report/instance/3?reset=1'},
    {title:'Donor Report (Detail)', url:'/civicrm/report/instance/4?reset=1'},
    {title:'Donation Summary Report (Repeat)', url:'/civicrm/report/instance/5?reset=1'},
    {title:'SYBUNT Report', url:'/civicrm/report/instance/6?reset=1'},
    {title:'LYBUNT Report', url:'/civicrm/report/instance/7?reset=1'},
    {title:'Soft Credit Report', url:'/civicrm/report/instance/8?reset=1'},
    {title:'Membership Report (Summary)', url:'/civicrm/report/instance/9?reset=1'},
    {title:'Membership Report (Detail)', url:'/civicrm/report/instance/10?reset=1'},
    {title:'Membership Report (Lapsed)', url:'/civicrm/report/instance/11?reset=1'},
    {title:'Event Participant Report (List)', url:'/civicrm/report/instance/12?reset=1'},
    {title:'Event Income Report (Summary)', url:'/civicrm/report/instance/13?reset=1'},
    {title:'Event Income Report (Detail)', url:'/civicrm/report/instance/14?reset=1'},
    {title:'Attendee List', url:'/civicrm/report/instance/15?reset=1'},
    {title:'Activity Report ', url:'/civicrm/report/instance/16?reset=1'},
    {title:'Relationship Report', url:'/civicrm/report/instance/17?reset=1'},
    {title:'Donation Summary Report (Organization)', url:'/civicrm/report/instance/18?reset=1'},
    {title:'Donation Summary Report (Household)', url:'/civicrm/report/instance/19?reset=1'},
    {title:'Top Donors Report', url:'/civicrm/report/instance/20?reset=1'},
    {title:'Pledge Summary Report', url:'/civicrm/report/instance/21?reset=1'},
    {title:'Pledged But not Paid Report', url:'/civicrm/report/instance/22?reset=1'},
    {title:'Bookkeeping Transactions Report', url:'/civicrm/report/instance/23?reset=1'},
    {title:'Grant Report', url:'/civicrm/report/instance/24?reset=1'},
    {title:'Mail Bounce Report', url:'/civicrm/report/instance/25?reset=1'},
    {title:'Mail Summary Report', url:'/civicrm/report/instance/26?reset=1'},
    {title:'Mail Opened Report', url:'/civicrm/report/instance/27?reset=1'},
    {title:'Mail Clickthrough Report', url:'/civicrm/report/instance/28?reset=1'}
];


const all_pages_ary = [
    {name: 'admin', array: url_admin_ary},
    {name: 'dashboard', array: url_dashboard_ary},
    {name: 'contact', array: url_contact_ary},
    {name: 'activity', array: url_activity_ary},
    {name: 'import', array: url_import_ary},
    {name: 'group', array: url_group_ary},
    {name: 'contribute', array: url_contribute_ary},
    {name: 'event', array: url_event_ary},
    {name: 'mailing', array: url_mailing_ary},
    {name: 'member', array: url_member_ary},
    {name: 'report', array: url_report_ary}
];

let browser;
test.beforeAll(async () => {
    browser = await chromium.launch();
});

test.afterAll(async () => {
});

test.describe.serial('Page output correct test', () => {
    test.setTimeout(300000);
    all_pages_ary.forEach(arrayName => {
        let j = 0;
        for(let obj of arrayName.array){
            let url = obj.url;
            let full_title = obj.title + ' | ' + site_name;
            j += 1;
            (function(url, full_title) {
              test(`Check ${arrayName.name} page output ${j} - ${obj.title}`, async () => {
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
    })
});