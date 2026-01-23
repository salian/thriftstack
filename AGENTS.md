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
- Session auth + CSRF; roles Admin/Staff/User; RBAC with permissions.
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
