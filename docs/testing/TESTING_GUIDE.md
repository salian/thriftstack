# Testing Guide

## Philosophy
Keep tests lightweight and focused on critical flows. Add tests whenever adding features.

## Test harness
- Run `php tests/run.php`.
- Tests cover config loading, router matching, CSRF, password hashing, workspace invites, and settings storage.

## Playwright (local E2E)
- Install deps: `npm install` and `npx playwright install`.
- Run: `npm run test:e2e` (starts a local PHP server on `127.0.0.1:8000`).
- Override base URL with `PLAYWRIGHT_BASE_URL=http://127.0.0.1:8000` when needed.
- Details: `docs/testing/PLAYWRIGHT.md`.
- Add or update Playwright specs for each user-facing feature you ship.
- Seeded auth specs expect `php scripts/migrate.php` and `php scripts/seed.php` to run first.
- Seeded member specs use the member credentials from `app/Database/Seeder.php`.

## Planned coverage
- Notification delivery and history.
- Billing webhook signature validation.
