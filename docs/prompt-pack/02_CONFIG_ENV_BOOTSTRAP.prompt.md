# 02_CONFIG_ENV_BOOTSTRAP — config.php + .env loader + app bootstrap

Implement configuration and environment loading.

## Requirements
- Create `config.php` at project root.
- Support `.env` file in root (not committed).
- `config.php` must read from `.env` if present, fallback to getenv().
- Create `/app/Bootstrap.php` (or similar) to:
  - load config
  - set timezone
  - set error handling defaults
  - start session securely

## Create/update
- /.gitignore (must include `.env`, `/storage/*` except keep placeholder files)
- /config.php
- /app/Bootstrap.php
- /public/index.php updated to use bootstrap
- Add placeholder `.env.example` (safe values only)

## Must include config keys
- APP_ENV (local/staging/production)
- APP_DEBUG (true/false)
- APP_URL
- DB_HOST, DB_NAME, DB_USER, DB_PASS
- MAIL_FROM_NAME, MAIL_FROM_EMAIL
- SECURITY_SHOW_ERRORS_IN_PROD (true/false)
- SECURITY_SHOW_ERRORS_IN_PROD_FOR_ADMIN_ONLY (true/false)

## Deliverable
Output all updated file contents.
