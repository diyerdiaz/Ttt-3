import { expect, test } from '@playwright/test';
import { card, column, createLead, uniqueName } from './helpers';

test('crear un lead lo muestra en la lista y en la columna Nuevo, y se puede editar', async ({ page }) => {
    const name = uniqueName('Lead crear');
    const renamed = `${name} editado`;

    await page.goto('/pipeline');
    await page.getByRole('link', { name: 'Nuevo lead' }).click();
    await expect(page.getByRole('heading', { name: 'Nuevo lead' })).toBeVisible();
    await page.getByLabel(/^Nombre/).fill(name);
    await page.getByLabel('Correo', { exact: true }).fill('lead@example.test');
    await page.getByLabel('Empresa').fill('Acme E2E');
    await page.getByRole('button', { name: 'Guardar' }).click();

    await expect(page).toHaveURL(/\/leads$/);
    await expect(page.getByRole('status').filter({ hasText: 'Lead guardado.' })).toBeVisible();
    await expect(page.getByRole('link', { name })).toBeVisible();

    await page.goto('/pipeline');
    await expect(column(page, 'new').locator('li', { hasText: name })).toBeVisible();

    await card(page, name).getByRole('link', { name: 'Editar' }).click();
    await expect(page.getByRole('heading', { name: 'Editar lead' })).toBeVisible();
    await expect(page.getByLabel(/^Nombre/)).toHaveValue(name);
    await page.getByLabel(/^Nombre/).fill(renamed);
    await page.getByLabel('Estado').selectOption({ label: 'Contactado' });
    await page.getByRole('button', { name: 'Guardar' }).click();

    await expect(page.getByRole('status').filter({ hasText: 'Lead guardado.' })).toBeVisible();
    await expect(page.getByRole('link', { name: renamed })).toBeVisible();

    await page.goto('/pipeline');
    await expect(column(page, 'contacted').locator('li', { hasText: renamed })).toBeVisible();
    await expect(column(page, 'new').locator('li', { hasText: renamed })).toHaveCount(0);
});

test('un lead sin nombre no se guarda', async ({ page }) => {
    await createLead(page, uniqueName('Lead previo'));
    await page.goto('/leads/create');
    await page.getByRole('button', { name: 'Guardar' }).click();
    await expect(page).toHaveURL(/\/leads\/create$/);
    await expect(page.getByRole('alert')).toBeVisible();
});
