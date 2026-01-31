const { test, expect } = require('@playwright/test');

test('home page renders', async ({ page }) => {
  await page.goto('/');

  await expect(page.getByRole('heading', { level: 1 })).toContainText('Welcome to');
});

test('support page is reachable from the footer', async ({ page }) => {
  await page.goto('/');
  await page.getByRole('link', { name: 'Support' }).click();

  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Support');
  await expect(page.getByText('Contact')).toBeVisible();
});
