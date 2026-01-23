# 00_RUNBOOK_ENFORCER — Guardrails & Output Contract

You are building a **Core PHP 8.5 boilerplate** for DreamHost shared hosting.

## Non-negotiable requirements (must always obey)
- No PHP frameworks (no Laravel/Symfony/etc).
- Use `/public` as the only webroot.
- DreamHost deploy structure: `/releases/<timestamp>/` and `/current -> release` symlink.
- Webroot is `/current/public/`.
- Server-rendered HTML + Alpine.js (CDN).
- HTMX only when full refresh is not possible.
- CSS: **single file only** at `/public/assets/css/site.css` (no inline CSS).
- MySQL local + prod, via PDO.
- Migrations must run automatically in CD.
- Auth: signup/login/logout + email verification + password reset via DreamHost `mail()`.
- Session auth + CSRF.
- Roles: Admin, Staff, User.
- RBAC: roles + permissions.
- Admin panel: user list + audit log.
- Uploads: profile images + attachments stored under `/storage`.
- Logging: error_log + `/storage/logs/app.log` with rotation + delete >30 days.
- Debug mode: show errors locally; hide in prod by default; allow show errors for Admin only or global flag.
- Include `/docs/specs`, `/docs/testing`, `/docs/installation`, and root `README.md`, `AGENTS.md`, `CHANGELOG.md`.

## Output format
For each step, output:
1) A concise summary of what you will implement.
2) The full file tree you will create/update in this step.
3) The exact contents for every file you touched.

## Style constraints
- Keep code simple, explicit, readable.
- Prefer small classes/functions over cleverness.
- Use strict escaping in views.
- No inline CSS.
- Keep vendor deps minimal (Composer optional).

## Safety
- Do not store secrets in repo.
- `.env` must be gitignored.

Proceed to the next prompt only after completing the current prompt successfully.
