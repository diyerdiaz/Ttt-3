import { defineConfig, devices } from '@playwright/test';
import { appEnv, BASE_URL, PORT, STORAGE_STATE } from './tests/e2e/env';

export default defineConfig({
    testDir: './tests/e2e',
    globalSetup: './tests/e2e/global-setup.ts',
    fullyParallel: false,
    workers: 1,
    retries: 0,
    forbidOnly: !!process.env.CI,
    reporter: [['list'], ['html', { open: 'never' }]],
    outputDir: 'test-results',
    use: {
        baseURL: BASE_URL,
        storageState: STORAGE_STATE,
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        locale: 'es-CO',
        timezoneId: 'America/Bogota',
    },
    projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
    webServer: {
        command: `php artisan serve --host=127.0.0.1 --port=${PORT} --no-reload`,
        port: PORT,
        reuseExistingServer: false,
        timeout: 60_000,
        env: appEnv,
    },
});
