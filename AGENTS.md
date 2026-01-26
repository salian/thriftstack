# AGENTS

## Role
Maintain a reusable Core PHP 8.5 starter for DreamHost shared hosting with predictable, LLM-friendly structure.

## Guardrails
- No PHP frameworks.
- `/public` is the only webroot; keep app logic outside it.
- Use a single stylesheet at `/public/assets/css/site.css` only.
- Alpine.js via CDN; use HTMX only when full refresh is not possible.
- Keep code simple and explicit; prefer small functions/classes.
- Escape output in views; avoid inline CSS.
- Do not commit secrets or `.env` files.

## Platform requirements
- DreamHost release layout: `/releases/<timestamp>/` with `/current` symlink and webroot `/current/public/`.
- MySQL via PDO; migrations must run automatically in CD.
- Auth: signup/login/logout, email verification, password reset via DreamHost `mail()`.
- Session auth + CSRF; roles Super Admin/Staff/User; workspace roles Workspace Owner/Workspace Admin/Workspace Member; RBAC with permissions.
- Admin panel skeleton (users + audit log) and uploads stored under `/storage`.
- Log to PHP error log and `/storage/logs/app.log` with rotation and >30-day cleanup.
- Debug: show locally, hide in prod by default; allow admin-only or global override.

## Change hygiene
- Add or update tests for every feature.
- Append user-facing changes to `CHANGELOG.md`.
- Keep docs in `/docs` aligned with code changes.

## Prompt-pack workflow
- Follow prompt packs in order; finish the current prompt before advancing.
- When a runbook specifies output contracts, comply for that step.

## How to add a module
1. Add routes in `routes/web.php` with middleware as needed.
2. Add controller under `app/Controllers` and keep actions small.
3. Add view(s) under `views/` and escape output with `e()`.
4. Add DB tables/migrations in `app/Database/Migrations` if needed.
5. Add tests in `tests/` and update `CHANGELOG.md`.

## Never do these
- Do not introduce a framework or add heavy dependencies.
- Do not put secrets or `.env` files in the repo.
- Do not serve files from `/storage` directly via `/public`.
- Do not bypass CSRF or auth checks on POST routes.
- Do not change the release layout (`/releases`, `/current`).
- Do not delete existing code or comments which are unrelated to the current task or change.
