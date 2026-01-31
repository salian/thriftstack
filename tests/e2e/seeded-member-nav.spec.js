const { test, expect } = require('@playwright/test');

const memberEmail = process.env.E2E_SEED_MEMBER_EMAIL || 'member@workware.in';
const memberPassword = process.env.E2E_SEED_MEMBER_PASSWORD || 'MemberPass123';

async function loginWithMemberUser(page) {
  await page.goto('/');
  await page.getByRole('link', { name: 'Login' }).click();
  await page.getByLabel('Email').fill(memberEmail);
  await page.getByLabel('Password').fill(memberPassword);
  await page.getByRole('button', { name: 'Login' }).click();
  await expect(page).toHaveURL(/\/dashboard/);
}

test('member navigation hides admin and billing links', async ({ page }) => {
  await loginWithMemberUser(page);

  await expect(page.getByRole('link', { name: 'Admin' })).toHaveCount(0);
  await expect(page.getByRole('link', { name: 'Analytics' })).toHaveCount(0);

  await page.locator('details.nav-user-menu summary').click();
  await expect(page.getByRole('link', { name: 'Workspace Admin' })).toHaveCount(0);
  await expect(page.getByRole('link', { name: 'System Admin' })).toHaveCount(0);
  await expect(page.getByRole('link', { name: 'Billing' })).toHaveCount(0);
});

test('member can navigate to teams via sidebar', async ({ page }) => {
  await loginWithMemberUser(page);

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
});
