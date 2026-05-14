import { test, expect } from '@playwright/test';

test('tool dbw supports bi-directional conversion', async ({ page }) => {
  await page.goto('?route=tools#tool-dbw');
  await expect(page.locator('#tool-dbw')).toBeVisible();

  await page.fill('#dbw-dbm', '40');
  await expect(page.locator('#dbw-dbw-input')).toHaveValue('10.00');

  await page.fill('#dbw-dbw-input', '3');
  await expect(page.locator('#dbw-result')).toContainText('33.00 dBm');
});
