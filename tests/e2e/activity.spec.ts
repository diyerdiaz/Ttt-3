import { expect, test } from '@playwright/test';
import { createLead, uniqueName } from './helpers';

test('registrar una actividad la muestra en el timeline con hora local', async ({ page }) => {
    const name = uniqueName('Lead actividad');
    const description = `Llamada de seguimiento ${name}`;
    await createLead(page, name);

    await page.getByRole('link', { name }).click();
    await expect(page.getByRole('heading', { name, level: 1 })).toBeVisible();

    await page.getByLabel('Tipo').selectOption({ label: 'Llamada' });
    await page.getByLabel(/^Fecha y hora/).fill('2026-03-05T14:30');
    await page.getByLabel(/^Descripción/).fill(description);
    await page.getByRole('button', { name: 'Registrar actividad' }).click();

    const entry = page.getByRole('listitem').filter({ hasText: description });
    await expect(entry).toBeVisible();
    await expect(entry.locator('time')).toHaveText('05/03/2026 14:30');

    await page.reload();
    await expect(page.getByRole('listitem').filter({ hasText: description }).locator('time')).toHaveText('05/03/2026 14:30');
});
