import { defineConfig } from '@playwright/test';

export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: false,
    retries: 0,
    reporter: 'list',
    use: {
        baseURL: process.env.PLAYWRIGHT_BASE_URL ?? 'http://127.0.0.1:8765',
        browserName: 'chromium',
        headless: true,
        trace: 'retain-on-failure',
    },
    webServer: {
        command: 'DB_CONNECTION="${DB_CONNECTION:-sqlite}" DB_DATABASE="${DB_DATABASE:-database/database.sqlite}" php artisan serve --host=127.0.0.1 --port=8765',
        url: 'http://127.0.0.1:8765/tenant/login',
        reuseExistingServer: !process.env.CI,
        timeout: 120_000,
    },
});
