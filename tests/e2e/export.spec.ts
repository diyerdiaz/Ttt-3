import { readFile } from 'node:fs/promises';
import { expect, test } from '@playwright/test';
import { createLead, uniqueName } from './helpers';

test('exportar CSV descarga leads-*.csv con el lead creado', async ({ page }) => {
    const name = uniqueName('Lead export');
    await createLead(page, name, 'Export SA');

    await page.getByText('Exportar CSV', { exact: true }).click();
    const download = page.waitForEvent('download');
    await page.getByRole('button', { name: 'Descargar CSV' }).click();
    const file = await download;

    expect(file.suggestedFilename()).toMatch(/^leads-.+\.csv$/);
    const path = await file.path();
    const content = await readFile(path, 'utf8');
    expect(content).toContain(name);
    expect(content).toContain('Export SA');
});
