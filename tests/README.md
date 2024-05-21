
Before testing, you need to install the following packages:

- phpunit
- casperjs (not maintained)
- playwright (Please refer to the Playwright section)

# Unit Test


```
cd civicrm
mysql -udbuser -p -e "CREATE DATABASE civicrm_tests_dev CHARACTER SET utf8 COLLATE utf8_unicode_ci";
mysql -udbuser -p civicrm_tests_dev < sql/civicrm.mysql
mysql -udbuser -p civicrm_tests_dev < sql/civicrm_generated.mysql
export CIVICRM_TEST_DSN=mysqli://dbuser:password@localhost/civicrm_tests_dev

cd tests/phpunit
phpunit --colors api_v3_ContactTest
phpunit --colors api_v3_AllTests
```


# CasperJS

Make sure you have a running website:

```
export RUNPORT=80

cd tests/casperjs
casperjs test googletest.js
casperjs test pagespages.js

```


**Note: This is no longer maintained.**

# Playwright

To use our Playwright testing code to test your website, follow these steps:

* Switch to our Playwright code directory:
    
    ```
    cd netiCRM/tests/playwright
    ```

* Install the Playwright module:
    
    ```
    npm init playwright@latest
    ```
    * For more details about installation, see the [official documentation](https://playwright.dev/docs/intro).

* Install the dotenv module:

    ```
    cd netiCRM/tests/playwright
    npm install dotenv --save
    ```
    
* Set up the `setup.env` file:
    * Rename the file `setup.example.env` to `setup.env` and modify the content according to your requirements:
        ```
        # .env file
        isLocal=true
        localUrl=http://<your_domain>:<port>/
        remoteUrl=http://<your_domain>:<port>/
        adminUser=admin
        adminPwd=<password> # e.g., admin_password
        ```

* Check the configuration file `playwright.config.js`:
    * The above `setup.env` file is required here, and there is a `global-setup.js` file to handle logins, so you don't need to manually input your account and password every time (You can check the `global-setup.js` file).
    * You can modify the following setup according to your requirements:

        ```javascript
        const config = {
          globalSetup: require.resolve('./global-setup'),
          testDir: './tests',
          timeout: 120 * 1000,
          expect: {
            timeout: 30 * 1000
          },
          /* Run tests in files in parallel */
          fullyParallel: true,
          /* Fail the build on CI if you accidentally left test.only in the source code. */
          forbidOnly: !!process.env.CI,
          /* Retry on CI only */
          retries: process.env.CI ? 2 : 0,
          /* Opt out of parallel tests on CI. */
          workers: process.env.CI ? 1 : undefined,

          /* Reporter to use. See https://playwright.dev/docs/test-reporters */
          reporter: [
            ['list'],
            ['html', { open: 'never' }],
            // ['./test-reporter.js']
          ],
          use: {
            headless: true,
            actionTimeout: 30 * 1000,
            storageState: 'storageState.json',
            baseURL: process.env.localUrl,
            trace: 'retain-on-failure',
          },

          /* Configure projects for major browsers */
          projects: [
            {
              name: 'chromium',
              use: {
                ...devices['Desktop Chrome'],
              }
            }
          ],
          outputDir: 'test-results/'
        };
        ```

* Run Playwright commands:

    ```
    cd netiCRM/tests/playwright
    // run a file
    npx playwright test <file_name> --project=chromium
    // Check the report
    npx playwright show-report
    ```

    * [More information](https://playwright.dev/docs/running-tests)

