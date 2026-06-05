import { test, expect } from '@playwright/test';

test('ohm law recalculates from the last two edited decimal values', async ({ page }) => {
  await page.goto('?route=tools#tool-ohm-law');
  await expect(page.locator('#tool-ohm-law')).toBeVisible();

  await page.fill('#ohm-voltage', '12,50');
  await page.fill('#ohm-current', '2,50');
  await expect(page.locator('#ohm-resistance')).toHaveValue('5.00');

  await page.fill('#ohm-resistance', '10,00');
  await expect(page.locator('#ohm-voltage')).toHaveValue('25.00');
});

test('swr computes from forward and reflected decimal power', async ({ page }) => {
  await page.goto('?route=tools#tool-swr');
  await expect(page.locator('#tool-swr')).toBeVisible();

  await page.fill('#swr-forward', '50,00');
  await page.fill('#swr-reflected', '2,00');
  await expect(page.locator('#swr-value')).toHaveText('1.50');

  await page.fill('#swr-reflected', '50,00');
  await expect(page.locator('#swr-value')).toHaveText(/^\u2014$/);
});

test('unit converter accepts decimal comma input', async ({ page }) => {
  await page.goto('?route=tools#tool-unit-conversions');
  await expect(page.locator('#tool-unit-conversions')).toBeVisible();

  await page.selectOption('#unit-conv-group', 'rf');
  await page.selectOption('#unit-conv-from', 'mhz');
  await page.selectOption('#unit-conv-to', 'khz');
  await page.fill('#unit-conv-input', '145,50');

  await expect(page.locator('#unit-conv-output')).toContainText('kHz');
  await expect(page.locator('#unit-conv-output')).not.toHaveText(/^\u2014$/);
});
