import { test, expect } from '@playwright/test';

test('tools lazy flow: click -> fetch panel -> init calc', async ({ page }) => {
  await page.goto('?route=tools');

  await expect(page.locator('#tool-grid')).toBeVisible();
  await expect(page.locator('#tool-power')).toHaveCount(0);

  await page.locator('[data-tool-target="tool-power"]').click();
  await expect(page.locator('#tool-power')).toBeVisible();

  await page.fill('#power-watts', '10');
  await expect(page.locator('#power-dbm')).not.toHaveText('—');
});
