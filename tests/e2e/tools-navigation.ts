import { expect, type Page } from '@playwright/test';

export const clickToolTarget = async (page: Page, toolId: string) => {
  const link = page.locator(`[data-tool-target="${toolId}"]`).first();

  await link.evaluate((element) => {
    const details = element.closest('details');
    if (details) {
      details.open = true;
    }
  });

  await expect(link).toBeVisible();
  await link.click();
};
