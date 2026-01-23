# Setup Guide

## Local
1. Point your web server document root to `/public`.
2. Ensure PHP 8.5 and required extensions are installed.
3. Create a MySQL database and update `.env` with credentials.
4. Run migrations: `php scripts/migrate.php`.
5. Seed dummy data: `php scripts/seed.php`.

## DreamHost
### GitHub Actions deploy
1. Add GitHub Secrets:
   - `DEPLOY_HOST`
   - `DEPLOY_USER`
   - `DEPLOY_SSH_KEY` (private key)
   - `DEPLOY_PATH_PROD` (e.g. `/home/user/example.com`)
   - `DEPLOY_PATH_STAGING` (e.g. `/home/user/staging.example.com`)
   - `ENV_FILE` (full .env contents for the target)
2. Ensure the DreamHost account has the release layout:
   - `${DEPLOY_PATH}/releases/`
   - `${DEPLOY_PATH}/shared/storage`
3. Push to `main` for production or `staging` for staging.
4. Migrations run automatically during deployment.
