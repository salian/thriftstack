# Playwright Setup (Local)

## Requirements
- Node.js 18+ (or newer) with npm.
- PHP available on your PATH.

## Install
```bash
npm install
npx playwright install
```

## Run tests
```bash
npm run test:e2e
```

Playwright will start the app using the built-in PHP server at `http://127.0.0.1:8000`.

## Seeded auth flows
The seeded E2E suite expects a seeded database user.

```bash
php scripts/migrate.php
php scripts/seed.php
```

Default credentials come from `app/Database/Seeder.php`:
- Email: `ops@workware.in`
- Password: `Ma3GqqHVkb`

Member credentials for role-based navigation checks:
- Email: `member@workware.in`
- Password: `MemberPass123`

Override with environment variables if you want a different seeded account:

```bash
E2E_SEED_EMAIL=you@example.com E2E_SEED_PASSWORD=yourpassword npm run test:e2e
```

Member overrides:

```bash
E2E_SEED_MEMBER_EMAIL=member@example.com E2E_SEED_MEMBER_PASSWORD=memberpass npm run test:e2e
```

## Useful options
```bash
npm run test:e2e:headed
npm run test:e2e:ui
npm run test:e2e:debug
```

## Using a different base URL
If you already have the app running (or want a different port), set:

```bash
PLAYWRIGHT_BASE_URL=http://127.0.0.1:8001 npm run test:e2e
```

## Notes
- Current specs live in `tests/e2e/` and cover public pages, auth pages, and seeded auth flows.
- Test artifacts are written to `playwright-report/` and `test-results/`.

## Adding new E2E coverage
- Add or update Playwright specs for each user-facing feature you ship.
- Prefer click-based navigation to verify link visibility and role gating.
- Favor click-based navigation checks when data setup is heavy.
- Keep specs small and focused on critical flows.
