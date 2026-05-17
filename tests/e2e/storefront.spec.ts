import { test, expect } from '@playwright/test';

test.describe('Storefront smoke', () => {
  test('homepage loads with a title and visible body', async ({ page }) => {
    const response = await page.goto('/');
    expect(response?.status(), 'homepage should respond 2xx').toBeLessThan(400);

    // GitHub Codespaces shows a "development port" interstitial on first visit
    // to a public-forwarded port. Click through it if present.
    const continueBtn = page.getByRole('link', { name: /^Continue$/i }).or(
      page.getByRole('button', { name: /^Continue$/i })
    );
    if (await continueBtn.first().isVisible().catch(() => false)) {
      await continueBtn.first().click();
      await page.waitForLoadState('domcontentloaded');
    }

    await expect(page).toHaveTitle(/.+/);
    await expect(page.locator('body')).toBeVisible();
    await page.screenshot({
      path: 'test-results/homepage.png',
      fullPage: true,
    });
  });
});
