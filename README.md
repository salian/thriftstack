# ThriftStack

Core PHP 8.5 starter focused on logged-in SaaS dashboards, DreamHost shared hosting, and predictable LLM-friendly structure.

## Highlights
- Front controller at `/public/index.php` with a custom router.
- Auth: signup/login/logout, email verification, password reset, CSRF.
- RBAC admin: roles, permissions, user role assignments.
- Admin panel: users list and audit log.
- Uploads for profile images and attachments in `/storage/uploads`.
- Single stylesheet at `/public/assets/css/site.css` with enterprise baseline.
- GitHub Actions deploy with releases and migrations.

## Local development
1. Point your web server docroot to `/public`.
2. Copy `.env.example` to `.env` and fill `THRIFTSTACK_`-prefixed values.
3. If you do not have MySQL locally, set `THRIFTSTACK_DB_DRIVER=sqlite` and
   `THRIFTSTACK_DB_PATH=storage/database.sqlite`.
4. Run migrations: `php scripts/migrate.php`.
5. Seed dummy data: `php scripts/seed.php`.

## Tests
Run the minimal test suite: `php tests/run.php`.

## DreamHost deploy
See `docs/installation/SETUP_GUIDE.md` for GitHub Actions setup.
