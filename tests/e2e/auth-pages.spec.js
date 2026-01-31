const { test, expect } = require('@playwright/test');

test('login page is reachable from home', async ({ page }) => {
  await page.goto('/');
  await page.getByRole('link', { name: 'Login' }).click();

  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Login');
  await expect(page.getByRole('button', { name: 'Login' })).toBeVisible();
  await expect(page.getByRole('link', { name: 'Create an account' })).toBeVisible();
  await expect(page.getByRole('link', { name: 'Forgot password?' })).toBeVisible();
});

test('signup page is reachable from home', async ({ page }) => {
  await page.goto('/');
  await page.getByRole('link', { name: 'Sign up' }).click();

  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Create account');
  await expect(page.getByRole('button', { name: 'Create account' })).toBeVisible();
  await expect(page.getByRole('link', { name: 'Already have an account?' })).toBeVisible();
});

test('forgot password page is reachable from login', async ({ page }) => {
  await page.goto('/');
  await page.getByRole('link', { name: 'Login' }).click();
  await page.getByRole('link', { name: 'Forgot password?' }).click();

  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Reset password');
  await expect(page.getByRole('button', { name: 'Send reset link' })).toBeVisible();
});

test('signup page is reachable from login', async ({ page }) => {
  await page.goto('/');
  await page.getByRole('link', { name: 'Login' }).click();
  await page.getByRole('link', { name: 'Create an account' }).click();

  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Create account');
  await expect(page.getByRole('button', { name: 'Create account' })).toBeVisible();
});
