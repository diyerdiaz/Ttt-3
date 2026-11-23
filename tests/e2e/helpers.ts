import { expect, type Locator, type Page } from '@playwright/test';

let counter = 0;

export function uniqueName(prefix: string): string {
    counter += 1;
    return `${prefix} ${Date.now().toString(36)}-${counter}`;
}

export async function createLead(page: Page, name: string, company = ''): Promise<void> {
    await page.goto('/leads/create');
    await page.getByLabel(/^Nombre/).fill(name);
    if (company) {
        await page.getByLabel('Empresa').fill(company);
    }
    await page.getByRole('button', { name: 'Guardar' }).click();
    await expect(page.getByRole('status').filter({ hasText: 'Lead guardado.' })).toBeVisible();
}

export function column(page: Page, status: string): Locator {
    return page.locator(`ul[data-status="${status}"]`);
}

export function card(page: Page, name: string): Locator {
    return page.locator('ul[data-status] > li[data-id]').filter({ hasText: name });
}

export async function cardNames(list: Locator): Promise<string[]> {
    return list.locator('> li p.font-medium').allTextContents();
}

const isLivewireUpdate = (response: { url(): string; request(): { method(): string } }) =>
    /\/livewire[^/]*\/update/.test(response.url()) && response.request().method() === 'POST';

/**
 * Drags with raw mouse events (SortableJS ignores Locator.dragTo) and resolves once Livewire answers.
 * `position` chooses the drop point inside the target element.
 */
export async function dragTo(
    page: Page,
    source: Locator,
    target: Locator,
    position: 'top' | 'center' | 'bottom' = 'center',
): Promise<void> {
    await source.scrollIntoViewIfNeeded();
    const from = await source.boundingBox();
    const to = await target.boundingBox();
    if (!from || !to) {
        throw new Error('Drag source or target is not visible.');
    }
    const startX = from.x + from.width / 2;
    const startY = from.y + from.height / 2;
    const endX = to.x + to.width / 2;
    const endY = position === 'top' ? to.y + 4 : position === 'bottom' ? to.y + to.height - 4 : to.y + to.height / 2;

    const answered = page.waitForResponse(isLivewireUpdate);
    await page.mouse.move(startX, startY);
    await page.mouse.down();
    await page.mouse.move(startX + 8, startY + 8, { steps: 5 });
    await page.mouse.move(endX, endY, { steps: 25 });
    await page.mouse.move(endX, endY + 1, { steps: 3 });
    await page.mouse.up();
    await answered;
}
