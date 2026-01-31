# Prompt-Pack Runbook

## Usage
- Execute prompt packs in numeric order.
- Complete each prompt fully before moving to the next.
- Keep outputs small and explicit.
- Update `CHANGELOG.md` for every user-facing feature.

## Guardrails
- No frameworks.
- `/public` only webroot.
- Keep styles in `/public/assets/css/site.css`.
- Prefer small, explicit functions and classes.

## How to add a module
1. Add route definitions in `routes/web.php`.
2. Add a controller in `app/Controllers` and keep logic minimal.
3. Add views under `views/` and escape with `e()`.
4. Add migrations in `app/Database/Migrations` if data changes.
5. Add tests under `tests/` (including Playwright E2E for user-facing flows) and update `CHANGELOG.md`.

## Never do these
- Don’t introduce frameworks or heavy dependencies.
- Don’t commit `.env` or secrets.
- Don’t serve `/storage` directly through `/public`.
- Don’t skip CSRF or auth checks on POST routes.
- Don’t change the release layout contract.
