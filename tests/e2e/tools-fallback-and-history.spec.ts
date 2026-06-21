import { test, expect, type Page } from '@playwright/test';
import { clickToolTarget } from './tools-navigation';

const waitForToolPanelResponse = (page: Page, toolId: string, expectedStatus: number) => page.waitForResponse((response) => {
  const url = new URL(response.url());
  return url.pathname.endsWith('/index.php')
    && url.searchParams.get('route') === 'tools'
    && url.searchParams.get('ajax') === 'tool_panel'
    && url.searchParams.get('id') === toolId
    && response.status() === expectedStatus;
});

test('fallback on panel fetch 404 and browser history hashchange', async ({ page }) => {
  await page.goto('?route=tools#tool-grid');

  await page.route('**/index.php?route=tools&ajax=tool_panel&id=tool-power', async (route) => {
    await route.fulfill({ status: 404, contentType: 'text/plain', body: 'Unknown tool panel' });
  });

  const failedPanelResponse = waitForToolPanelResponse(page, 'tool-power', 404);
  await clickToolTarget(page, 'tool-power');
  await failedPanelResponse;
  await expect(page.locator('#tool-grid')).toBeVisible();

  await page.unroute('**/index.php?route=tools&ajax=tool_panel&id=tool-power');
  const loadedPanelResponse = waitForToolPanelResponse(page, 'tool-freq-wave', 200);
  await clickToolTarget(page, 'tool-freq-wave');
  await loadedPanelResponse;
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

  const staleFailedPanelResponse = waitForToolPanelResponse(page, 'tool-power', 404);
  await clickToolTarget(page, 'tool-power');
  await clickToolTarget(page, 'tool-freq-wave');

  await expect(page.locator('#tool-freq-wave')).toBeVisible();
  await staleFailedPanelResponse;
  await expect(page.locator('#tool-freq-wave')).toBeVisible();
  await expect(page.locator('#grid-tool-error')).toHaveClass(/is-hidden/);

  await page.unroute('**/index.php?route=tools&ajax=tool_panel&id=tool-power');
  await page.unroute('**/index.php?route=tools&ajax=tool_panel&id=tool-freq-wave');
});
