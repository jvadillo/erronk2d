import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './tests/Browser',
  workers: 1,
  fullyParallel: false,
  timeout: 45_000,
  use: {
    baseURL: process.env.TEST_BROWSER_URL || 'http://127.0.0.1:8082',
    viewport: { width: 1440, height: 1000 },
    screenshot: 'only-on-failure',
    trace: 'off',
  },
});
