// Pruebas en el navegador contra un servidor real (php artisan serve) con la base sembrada
// por DatabaseSeeder (usuario admin / admin123). Ver tests/e2e/README.md.
const { defineConfig } = require('@playwright/test');

const port = process.env.E2E_PORT || '8010';

module.exports = defineConfig({
    testDir: '.',
    testMatch: '*.spec.js',
    timeout: 30_000,
    retries: process.env.CI ? 1 : 0,
    reporter: process.env.CI ? [['list'], ['html', { open: 'never', outputFolder: 'report' }]] : 'list',
    use: {
        baseURL: process.env.E2E_BASE_URL || `http://127.0.0.1:${port}`,
        screenshot: 'only-on-failure',
        // En local se puede usar el Chromium del sistema: E2E_CHROMIUM=/snap/bin/chromium
        launchOptions: process.env.E2E_CHROMIUM ? { executablePath: process.env.E2E_CHROMIUM } : {},
    },
    webServer: process.env.E2E_BASE_URL ? undefined : {
        command: `php artisan serve --host=127.0.0.1 --port=${port}`,
        cwd: '../..',
        url: `http://127.0.0.1:${port}/login`,
        reuseExistingServer: !process.env.CI,
        timeout: 60_000,
    },
});
