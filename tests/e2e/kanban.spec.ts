import { expect, test } from '@playwright/test';
import { card, cardNames, column, createLead, dragTo, uniqueName } from './helpers';

test('arrastrar una tarjeta a otra columna persiste tras recargar', async ({ page }) => {
    const name = uniqueName('Drag columna');
    await createLead(page, name);
    await page.goto('/pipeline');

    await expect(column(page, 'new').locator('li', { hasText: name })).toBeVisible();
    await dragTo(page, card(page, name), column(page, 'qualified'));

    await expect(column(page, 'qualified').locator('li', { hasText: name })).toBeVisible();
    await expect(page.getByRole('alert')).toHaveCount(0);

    await page.reload();
    await expect(column(page, 'qualified').locator('li', { hasText: name })).toBeVisible();
    await expect(column(page, 'new').locator('li', { hasText: name })).toHaveCount(0);
});

test('reordenar dentro de la misma columna persiste tras recargar', async ({ page }) => {
    const [a, b, c] = [uniqueName('Orden A'), uniqueName('Orden B'), uniqueName('Orden C')];
    for (const name of [a, b, c]) {
        await createLead(page, name);
    }
    await page.goto('/pipeline');

    const list = column(page, 'new');
    const mine = async () => (await cardNames(list)).filter((n) => [a, b, c].includes(n));
    const before = await mine();
    expect([...before].sort()).toEqual([a, b, c].sort());

    const last = before[2];
    const first = before[0];
    await dragTo(page, card(page, last), card(page, first), 'top');

    const expected = [last, before[0], before[1]];
    await expect.poll(mine).toEqual(expected);

    await page.reload();
    await expect.poll(mine).toEqual(expected);
});
