// @ts-check
const { devices } = require('@playwright/test');

// Read from default ".env" file.
require('dotenv').config();
const path = require('path')
require('dotenv').config({ path: path.resolve(__dirname, 'setup.env') });

/**
 * @see https://playwright.dev/docs/test-configuration
 * @type {import('@playwright/test').PlaywrightTestConfig}
 */
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
    ['html', { open: 'never'}],
    // ['./test-reporter.js']
  ],
  use: {
    headless: true,
    actionTimeout: 30 * 1000,
    baseURL: process.env.localUrl ,
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

module.exports = config;
