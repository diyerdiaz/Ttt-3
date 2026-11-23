import { execFileSync } from 'node:child_process';
import { chromium, type FullConfig } from '@playwright/test';
import { ADMIN_EMAIL, ADMIN_PASSWORD, appEnv, BASE_URL, E2E_DATABASE, STORAGE_STATE } from './env';

function artisan(args: string[], extraEnv: Record<string, string> = {}): void {
    execFileSync('php', ['artisan', ...args], {
        stdio: 'inherit',
        env: { ...process.env, ...appEnv, ...extraEnv },
    });
}

export default async function globalSetup(_config: FullConfig): Promise<void> {
    if (appEnv.DB_DATABASE !== E2E_DATABASE) {
        throw new Error('E2E must only run against the crm_e2e database.');
    }

    artisan(['migrate:fresh', '--force']);
    artisan(['db:seed', '--class=AdminUserSeeder', '--force'], {
        ADMIN_EMAIL,
        ADMIN_PASSWORD,
    });

    const browser = await chromium.launch();
    const context = await browser.newContext({ baseURL: BASE_URL, storageState: undefined });
    const page = await context.newPage();
    await page.goto('/login');
    await page.getByLabel('Correo electrónico').fill(ADMIN_EMAIL);
    await page.getByLabel('Contraseña').fill(ADMIN_PASSWORD);
    await page.getByRole('button', { name: 'Entrar' }).click();
    await page.waitForURL('**/pipeline');
    await context.storageState({ path: STORAGE_STATE });
    await browser.close();
}
