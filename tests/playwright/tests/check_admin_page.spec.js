const {test, expect, chromium} = require('@playwright/test')
const fs = require('fs');
const utils = require('./utils.js');
const readline = require('readline');

let page;
const wait_secs = 2000;
let vars = {
    'urls': []
};

let admin_arr = [
    //{title: '' , url: ''}
]

let missing_url = []

test.beforeAll(async () => {
    const browser = await chromium.launch();
    page = await browser.newPage();
});

test.afterAll(async () => {
    await page.close();
});


test.describe.serial('start checking', () =>{
    test('Check Pages', async () => {
        // read webpage url data
        const rl = readline.createInterface({
            input: fs.createReadStream('tests/files/output(demo-page).csv'),
            crlfDelay: Infinity
        });
        // create new array for url data
        for await (const line of rl) {
            vars.urls.push(line);
            // await utils.print(`- ${line} in array`);
        }
        let i=0;
        for (const url of vars.urls){
            const create_url = await page.goto('http://local.dev.neticrm.tw:7777' + url)
            const status = await create_url.status()
            await expect.soft(status).toBe(200);
            const pageTitle = await page.locator('div#block-olivero-page-title>div>h1').textContent();
            // extract page title
            if (status==200){
                // get existing url and title
                admin_arr.push({
                    title: pageTitle,
                    url: url
                });
                // console.log info
                console.log(`- ${pageTitle} -> Ok`);   
                // output
                const content = `{title:'${admin_arr[i].title}', url: '${admin_arr[i].url}'},\n`;
                try{
                    fs.appendFileSync('tests/files/admin_page_output_new.txt', content);
                    console.log(`{title:'${admin_arr[i].title}', url: '${admin_arr[i].url}'} -> ok`);
                }catch(err){
                    console.error(err);
                }
                i+=1
            }else{
                missing_url.push({
                    title: pageTitle,
                    url: url
                })
            }
        }   
        // admin_arr.forEach((item)=>{
        //     //{title:'CiviCRM Reports', url:'/civicrm/report/list?reset=1'},
        //     const content = `{title:'${item.title}, url: '${item.url}'},\n`;
        //     try{
        //         fs.appendFileSync('tests/files/admin_page_output.txt', content);
        //         console.log(`{title:'${item.title}, url: '${item.url}'} -> ok`);
        //     }catch(err){
        //         console.error(err);
        //     }
        // });
    });
});