const { test, expect, chromium } = require('@playwright/test');
const utils = require('./utils.js');
const fs = require('fs');
const readline = require('readline');
/** @type {import('@playwright/test').Page} */
let page;
var locator, element;
const wait_secs = 2000;
let vars = {
    'urls': []
};
test.beforeAll(async () => {
    const browser = await chromium.launch();
    page = await browser.newPage();
});
test.afterAll(async () => {
    await page.close();
});
test.describe.serial('Version Check', () => {
    test.use({ storageState: 'storageState.json' });
    test('Check Pages', async () => {
        // read checking urls in site file
        const rl = readline.createInterface({
            input: fs.createReadStream('tests/files/sites.csv'),
            crlfDelay: Infinity
        });
        for await (const line of rl) {
            //look for any extra commas at the beginning (^) or end ($) of the toy (line), and get rid of them.
            vars.urls.push(line.replace(/^,+|,+$/g, ''));
        }
        var numStation = 1;
        // create new empty file
        fs.open('./tests/files/check_site_result.txt', 'w', function (err, file) {
            if (err) throw err;
            console.log('New output is created!');
          });
        for (const url of vars.urls) {
            // append current site to file
            fs.appendFile('./tests/files/check_site_result.txt', '\n' + numStation + ' ' + url + ' START\n', function(err){
                if (err) console.log(err);
                else console.log('\n' + numStation + ' ' + url + ' START');
            })
            // check user path
            try{
                const userResponse = await page.goto('https://' + url + '/user');
                const userApiStatus = await userResponse.status();
                var result;
                if ( userApiStatus ==200){
                    await expect(userApiStatus).toBe(200);
                    // check error message
                    var error = await page.locator('.crm-error').count();
                    if (error == 0){
                        await expect(page.locator('.crm-error')).toHaveCount(0);
                        // check user login element
                        var loginElement = await page.getByText('密碼').first().isVisible();
                        if (!loginElement){
                            result = numStation + ' ' + url + '/user PASS\n';
                        }else{
                            result = numStation + ' ' + url + '/user FAIL Reason:There is no user login element\n';
                        }
                    }else{
                        // have error message
                        result = numStation + ' ' + url + '/user FAIL Reason:An error message appears\n';
                    }
                }else{
                    result = url + '/user FAIL Reason:The status is ' + userApiStatus +'\n';
                }
            }catch (error){
                result =   numStation + ' ' + url + '/user FAIL'+ '\n' + 'error message: ' + error.message +'\n';
            }
            // append user path status result to file
            fs.appendFile('./tests/files/check_site_result.txt', result, function(err){
                if (err) console.log(err);
                else console.log(numStation + ' Append opertaion complete for user path.');
            })
            // check subscribe path status
            try{
                const mailingResponse = await page.goto('https://' + url + '/civicrm/mailing/subscribe?reset=1');
                const mailApiStatus = await mailingResponse.status();
                if ( mailApiStatus ==200){
                    await expect(mailApiStatus).toBe(200);
                    // check error message
                    var error = await page.locator('.crm-error').count();
                    if (error == 0){
                        await expect(page.locator('.crm-error')).toHaveCount(0);
                        // check user login element
                        var loginElement = await page.locator('#Subscribe').isVisible();
                        if (loginElement){
                            result =   numStation + ' ' + url + '/subscribe PASS\n';
                        }else{
                            result = numStation + ' ' + url + '/subscribe FAIL Reason:There is no subscribe element\n';
                        }
                    }else{
                        // have error message
                        result = numStation + ' ' + url + '/subscribe FAIL Reason:An error message appears\n';
                    }
                }else{
                    result = url + '/subscribe FAIL Reason:The status is ' + userApiStatus +'\n';
                }
            }catch (error){
                result =   numStation + ' ' + url + '/subscribe FAIL'+ '\n' + 'error message: ' + error.message + '\n';
            }
            // append subscribe path status result to file
            fs.appendFile('./tests/files/check_site_result.txt', result, function(err){
                if (err) console.log(err);
                else console.log(numStation + ' Append opertaion complete for subscribe path.');
            })
            numStation++;
        }
    });
});