const { test, expect } = require('@playwright/test');

const seedEmail = process.env.E2E_SEED_EMAIL || 'ops@workware.in';
const seedPassword = process.env.E2E_SEED_PASSWORD || 'Ma3GqqHVkb';

async function loginWithSeedUser(page) {
  await page.goto('/');
  await page.getByRole('link', { name: 'Login' }).click();
  await page.getByLabel('Email').fill(seedEmail);
  await page.getByLabel('Password').fill(seedPassword);
  await page.getByRole('button', { name: 'Login' }).click();
  await expect(page).toHaveURL(/\/dashboard/);
}

async function openUserMenu(page) {
  await page.locator('details.nav-user-menu summary').click();
}

test('seeded user can access dashboard', async ({ page }) => {
  await loginWithSeedUser(page);

  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Dashboard');
  await expect(page.getByText(seedEmail)).toBeVisible();
});

test('seeded user can access teams', async ({ page }) => {
  await loginWithSeedUser(page);
  await page.waitForLoadState('networkidle');
  const workspaceSelect = page.getByRole('combobox', { name: 'Switch workspace' });
  await expect(workspaceSelect).toBeVisible();
  await Promise.all([
    page.waitForURL(/\/teams/),
    workspaceSelect.evaluate((select) => {
      select.value = '__create__';
      select.dispatchEvent(new Event('change', { bubbles: true }));
    }),
  ]);

  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Teams');
  await expect(page.getByRole('button', { name: 'Team Members' })).toBeVisible();
});

test('seeded user can access profile', async ({ page }) => {
  await loginWithSeedUser(page);
  await openUserMenu(page);
  await page.getByRole('link', { name: 'Profile' }).click();

  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Profile');
  await expect(page.getByRole('button', { name: 'Save profile' })).toBeVisible();
});

test('seeded user can access billing', async ({ page }) => {
  await loginWithSeedUser(page);
  await openUserMenu(page);
  await page.getByRole('link', { name: 'Billing' }).click();

  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Billing');
  await expect(page.getByText('Current subscription')).toBeVisible();
});

test('seeded user can access notifications', async ({ page }) => {
  await loginWithSeedUser(page);
  await page.getByRole('link', { name: 'Notifications' }).click();

  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Notifications');
  await expect(page.getByText('Review your recent notifications.')).toBeVisible();
});

test('seeded admin navigation shows admin links', async ({ page }) => {
  await loginWithSeedUser(page);

  await expect(page.getByRole('link', { name: 'Admin' })).toBeVisible();
  await expect(page.getByRole('link', { name: 'Analytics' })).toBeVisible();

  await openUserMenu(page);
  await expect(page.getByRole('link', { name: 'Workspace Admin' })).toBeVisible();
  await expect(page.getByRole('link', { name: 'System Admin' })).toBeVisible();
});

test('seeded user can logout', async ({ page }) => {
  await loginWithSeedUser(page);

  await openUserMenu(page);
  await page.getByRole('button', { name: 'Logout' }).click();

  await expect(page).toHaveURL(/\/login/);
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Login');
});
