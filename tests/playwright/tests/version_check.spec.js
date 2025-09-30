const { test, expect, chromium } = require('@playwright/test');
const utils = require('./utils.js');
const fs = require('fs').promises;
const readline = require('readline');

/** @type {import('@playwright/test').Page} */
let page;
let errorSites = [];

// Configuration for paths to check
const PATHS_TO_CHECK = {
    user: {
        path: '/user',
        selector: '#edit-submit',
        name: 'user',
    },
    subscribe: {
        path: '/civicrm/mailing/subscribe?reset=1',
        selector: '#Subscribe',
        name: 'subscribe',
    },
};

const HTTP_SUCCESS = 200;
const RESULTS_FILE = './tests/files/check_site_result.txt';

// Simplify error message function
function simplifyErrorMessage(errorMessage) {
    const errorMappings = {
        'Cannot navigate to invalid URL': 'Invalid URL format',
        ERR_NAME_NOT_RESOLVED: 'Domain name resolution failed (DNS error)',
        ERR_CONNECTION_REFUSED: 'Connection refused',
        ERR_CONNECTION_TIMED_OUT: 'Connection timed out',
        404: 'Page not found (404)',
        500: 'Internal server error (500)',
        503: 'Service temporarily unavailable (503)',
        'There is no user login element': 'Missing login element',
        'There is no subscribe element': 'Missing subscribe element',
        'An error message appears': 'Error message displayed on page',
    };

    for (const [key, value] of Object.entries(errorMappings)) {
        if (errorMessage.includes(key)) {
            return value;
        }
    }

    return errorMessage.length > 70
        ? errorMessage.substring(0, 70) + '...'
        : errorMessage;
}
test.beforeAll(async () => {
    const browser = await chromium.launch();
    page = await browser.newPage();
});
// Helper function to read URLs from CSV with site grouping
async function readUrlsFromCsv() {
    const urlsWithSites = [];
    const rl = readline.createInterface({
        input: require('fs').createReadStream('tests/files/sites.csv'),
        crlfDelay: Infinity,
    });

    let currentSite = '';

    for await (const line of rl) {
        const cleanedLine = line.replace(/^,+|,+$/g, '').trim();

        // Check if this is a site header (starts with ###)
        if (cleanedLine.startsWith('###')) {
            currentSite = cleanedLine.replace('###', '').trim();
        } else if (cleanedLine !== '') {
            // This is a URL, associate it with the current site
            urlsWithSites.push({
                url: cleanedLine,
                site: currentSite,
            });
        }
    }
    return urlsWithSites;
}

// Helper function to check a specific path
async function checkPath(url, pathConfig, numStation) {
    const fullUrl = `https://${url}${pathConfig.path}`;
    const result = { success: false, message: '', error: null };

    try {
        const response = await page.goto(fullUrl);
        const status = response.status();

        if (status === HTTP_SUCCESS) {
            await expect(status).toBe(HTTP_SUCCESS);

            // Check for error messages
            const errorCount = await page.locator('.crm-error').count();
            if (errorCount === 0) {
                await expect(page.locator('.crm-error')).toHaveCount(0);

                // Check for required element
                const elementVisible = await page
                    .locator(pathConfig.selector)
                    .isVisible();
                if (elementVisible) {
                    result.success = true;
                    result.message = `${numStation} ${url}/${pathConfig.name} PASS\n`;
                } else {
                    result.message = `${numStation} ${url}/${pathConfig.name} FAIL Reason:There is no ${pathConfig.name} login element\n`;
                    result.error = {
                        path: `/${pathConfig.name}`,
                        message: simplifyErrorMessage(
                            `There is no ${pathConfig.name} login element`
                        ),
                    };
                }
            } else {
                result.message = `${numStation} ${url}/${pathConfig.name} FAIL Reason:An error message appears\n`;
                result.error = {
                    path: `/${pathConfig.name}`,
                    message: simplifyErrorMessage('An error message appears'),
                };
            }
        } else {
            result.message = `${url}/${pathConfig.name} FAIL Reason:The status is ${status}\n`;
            result.error = {
                path: `/${pathConfig.name}`,
                message: simplifyErrorMessage(`The status is ${status}`),
            };
        }
    } catch (error) {
        result.message = `${numStation} ${url}/${pathConfig.name} FAIL\nerror message: ${error.message}\n`;
        result.error = {
            path: `/${pathConfig.name}`,
            message: simplifyErrorMessage(error.message),
        };
    }

    return result;
}

// Helper function to write result to file
async function writeResultToFile(content) {
    await fs.appendFile(RESULTS_FILE, content);
}

test.afterAll(async () => {
    // Output expected format
    console.log('\n========== Test Results Summary ==========');

    if (errorSites.length > 0) {
        console.log('Sites with errors\n');

        // Group errors by site
        const errorsBySite = {};
        errorSites.forEach((siteData) => {
            const siteName = siteData.site || 'Unknown Site';
            if (!errorsBySite[siteName]) {
                errorsBySite[siteName] = [];
            }
            errorsBySite[siteName].push(siteData);
        });

        // Display errors grouped by site
        Object.entries(errorsBySite).forEach(([siteName, sites]) => {
            console.log(`目前執行的主機 ${siteName}`);
            sites.forEach((siteData) => {
                console.log(`* ${siteData.url}`);
                siteData.errors.forEach((error) => {
                    console.log(
                        `${error.path} : Error message: ${error.message}`
                    );
                });
                console.log('');
            });
        });
    } else {
        console.log('All sites passed testing!');
    }
    console.log('==========================================\n');

    await page.close();
});
test.describe.serial('Version Check', () => {
    test.use({ storageState: 'storageState.json' });
    test('Check Pages', async () => {
        // Read URLs from CSV file with site information
        const urlsWithSites = await readUrlsFromCsv();

        // Create new empty results file
        await fs.writeFile(RESULTS_FILE, '');
        console.log('New output file created!');

        let numStation = 1;

        for (const urlData of urlsWithSites) {
            const currentSiteErrors = [];
            console.log(`\n${numStation} ${urlData.url} START`);

            // Write site start to file
            await writeResultToFile(`\n${numStation} ${urlData.url} START\n`);

            // Check each configured path
            for (const pathConfig of Object.values(PATHS_TO_CHECK)) {
                const result = await checkPath(
                    urlData.url,
                    pathConfig,
                    numStation
                );

                // Write result to file
                await writeResultToFile(result.message);
                console.log(
                    `${numStation} Append operation complete for ${pathConfig.name} path.`
                );

                // Collect errors
                if (result.error) {
                    currentSiteErrors.push(result.error);
                }
            }

            // Add to error sites list if there are errors
            if (currentSiteErrors.length > 0) {
                errorSites.push({
                    url: urlData.url,
                    site: urlData.site,
                    errors: currentSiteErrors,
                });
            }

            numStation++;
        }
    });
});
