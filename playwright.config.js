const { defineConfig } = require('@playwright/test');
module.exports = defineConfig({
  testDir: './tests/browser',
  timeout: 45000,
  fullyParallel: false,
  workers: 1,
  reporter: [['list'], ['json', { outputFile: 'tests/artifacts/browser-results.json' }]],
  outputDir: 'tests/artifacts/browser',
  use: {
    baseURL: process.env.BASE_URL || 'http://127.0.0.1:8080',
    browserName: 'chromium',
    channel: process.env.BROWSER_CHANNEL || 'chrome',
    viewport: { width: 1440, height: 1000 },
    screenshot: 'only-on-failure',
    trace: 'retain-on-failure'
  }
});
