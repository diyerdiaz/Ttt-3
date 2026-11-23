import { expect, test } from '@playwright/test';
import { ADMIN_EMAIL, ADMIN_PASSWORD } from './env';

test.describe('autenticación', () => {
    test.use({ storageState: { cookies: [], origins: [] } });

    test('login correcto lleva al pipeline', async ({ page }) => {
        await page.goto('/login');
        await page.getByLabel('Correo electrónico').fill(ADMIN_EMAIL);
        await page.getByLabel('Contraseña').fill(ADMIN_PASSWORD);
        await page.getByRole('button', { name: 'Entrar' }).click();

        await expect(page).toHaveURL(/\/pipeline$/);
        await expect(page.getByRole('heading', { name: 'Pipeline' })).toBeVisible();
    });

    test('login incorrecto muestra un error genérico', async ({ page }) => {
        await page.goto('/login');
        await page.getByLabel('Correo electrónico').fill(ADMIN_EMAIL);
        await page.getByLabel('Contraseña').fill('wrong-password-123');
        await page.getByRole('button', { name: 'Entrar' }).click();

        await expect(page).toHaveURL(/\/login$/);
        const error = page.getByRole('alert');
        await expect(error).toBeVisible();
        await expect(error).toHaveText('Credenciales inválidas');
    });

    test('sin sesión, /leads redirige al login', async ({ page }) => {
        await page.goto('/leads');
        await expect(page).toHaveURL(/\/login$/);
        await expect(page.getByRole('heading', { name: 'Iniciar sesión' })).toBeVisible();
    });
});
