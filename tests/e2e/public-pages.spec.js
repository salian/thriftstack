const { test, expect } = require('@playwright/test');

test('root redirects to login for signed-out users', async ({ page }) => {
  await page.goto('/');

  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Login');
});

test('support page is reachable from the footer when signed out', async ({ page }) => {
  await page.goto('/');
  await page.getByRole('link', { name: 'Support' }).click();

  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Support');
  await expect(page.getByText('Contact')).toBeVisible();
});
