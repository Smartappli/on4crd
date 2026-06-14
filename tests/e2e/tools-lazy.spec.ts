import { test, expect } from '@playwright/test';
import { clickToolTarget } from './tools-navigation';

test('tools lazy flow: click -> fetch panel -> init calc', async ({ page }) => {
  await page.goto('?route=tools');

  await expect(page.locator('#tool-grid')).toBeVisible();
  await expect(page.locator('#tool-power')).toHaveCount(0);

  await clickToolTarget(page, 'tool-power');
  await expect(page.locator('#tool-power')).toBeVisible();

  await page.fill('#power-watts', '10');
  await expect(page.locator('#power-dbm')).not.toHaveText(/^\u2014$/);
});
