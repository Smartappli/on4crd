import { test, expect } from '@playwright/test';

test('fallback on panel fetch 404 and browser history hashchange', async ({ page }) => {
  await page.goto('?route=tools#tool-grid');

  await page.route('**/index.php?route=tools&ajax=tool_panel&id=tool-power', async (route) => {
    await route.fulfill({ status: 404, contentType: 'text/plain', body: 'Unknown tool panel' });
  });

  await page.locator('[data-tool-target="tool-power"]').click();
  await expect(page.locator('#tool-grid')).toBeVisible();

  await page.unroute('**/index.php?route=tools&ajax=tool_panel&id=tool-power');
  await page.locator('[data-tool-target="tool-freq-wave"]').click();
  await expect(page.locator('#tool-freq-wave')).toBeVisible();

  await page.goBack();
  await expect(page.locator('#tool-grid')).toBeVisible();
});
