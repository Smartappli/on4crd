import { test, expect } from '@playwright/test';
import { clickToolTarget } from './tools-navigation';

test('tools hash navigation and second panel calculation', async ({ page }) => {
  await page.goto('?route=tools#tool-freq-wave');

  await expect(page.locator('#tool-freq-wave')).toBeVisible();

  await page.fill('#freq-mhz', '145.5');
  await expect(page.locator('#freq-wavelength')).not.toHaveText(/^\u2014$/);

  // Navigate to another tool to validate on-demand panel activation path.
  await clickToolTarget(page, 'tool-dbuv');
  await expect(page.locator('#tool-dbuv')).toBeVisible();
  await page.fill('#dbuv-dbm', '10');
  await expect(page.locator('#dbuv-result')).not.toHaveText(/^\u2014$/);
});
