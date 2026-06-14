import { test, expect } from '@playwright/test';
import { clickToolTarget } from './tools-navigation';

test('fallback on panel fetch 404 and browser history hashchange', async ({ page }) => {
  await page.goto('?route=tools#tool-grid');

  await page.route('**/index.php?route=tools&ajax=tool_panel&id=tool-power', async (route) => {
    await route.fulfill({ status: 404, contentType: 'text/plain', body: 'Unknown tool panel' });
  });

  await clickToolTarget(page, 'tool-power');
  await expect(page.locator('#tool-grid')).toBeVisible();

  await page.unroute('**/index.php?route=tools&ajax=tool_panel&id=tool-power');
  await clickToolTarget(page, 'tool-freq-wave');
  await expect(page.locator('#tool-freq-wave')).toBeVisible();

  await page.goBack();
  await expect(page.locator('#tool-grid')).toBeVisible();
});

test('stale failed ajax response does not override latest successful tool selection', async ({ page }) => {
  await page.goto('?route=tools#tool-grid');

  await page.route('**/index.php?route=tools&ajax=tool_panel&id=tool-power', async (route) => {
    await new Promise((resolve) => setTimeout(resolve, 300));
    await route.fulfill({ status: 404, contentType: 'text/plain', body: 'Unknown tool panel' });
  });

  await page.route('**/index.php?route=tools&ajax=tool_panel&id=tool-freq-wave', async (route) => {
    await route.continue();
  });

  await clickToolTarget(page, 'tool-power');
  await clickToolTarget(page, 'tool-freq-wave');

  await expect(page.locator('#tool-freq-wave')).toBeVisible();
  await expect(page.locator('#grid-tool-error')).toHaveClass(/is-hidden/);

  await page.unroute('**/index.php?route=tools&ajax=tool_panel&id=tool-power');
  await page.unroute('**/index.php?route=tools&ajax=tool_panel&id=tool-freq-wave');
});
