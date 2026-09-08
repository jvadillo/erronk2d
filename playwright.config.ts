import { defineConfig } from '@playwright/test';

const baseURL = process.env.TEST_BROWSER_URL;
if (baseURL !== 'http://browser-app:8083') throw new Error('Usa el servicio browser de compose.test.yml; no se permiten destinos de producción.');

export default defineConfig({
  testDir: './tests/Browser',
  workers: 1,
  fullyParallel: false,
  timeout: 45_000,
  use: {
    baseURL,
    viewport: { width: 1440, height: 1000 },
    screenshot: 'only-on-failure',
    trace: 'off',
  },
});
