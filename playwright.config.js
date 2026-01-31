const { defineConfig } = require('@playwright/test');

const baseURL = process.env.PLAYWRIGHT_BASE_URL || 'http://127.0.0.1:8000';

module.exports = defineConfig({
  testDir: 'tests/e2e',
  reporter: 'html',
  timeout: 30_000,
  expect: {
    timeout: 5_000
  },
  use: {
    baseURL,
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure'
  },
  webServer: {
    command: 'php -S 127.0.0.1:8000 -t public',
    url: baseURL,
    reuseExistingServer: true,
    timeout: 120_000
  }
});
