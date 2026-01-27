# Setup Guide

## Local
1. Point your web server document root to `/public`.
2. Ensure PHP 8.5 and required extensions are installed.
3. Create a MySQL database and update `.env` with `THRIFTSTACK_`-prefixed credentials.
4. If you do not have MySQL locally, set `THRIFTSTACK_DB_DRIVER=sqlite` and
   `THRIFTSTACK_DB_PATH=storage/database.sqlite`.
5. Run migrations: `php scripts/migrate.php`.
6. Seed dummy data: `php scripts/seed.php`.
7. Optional: set `THRIFTSTACK_BUILD_ID` to show a build identifier in the footer.
8. Optional: set `THRIFTSTACK_BILLING_OWNER_ROLES` (comma-separated) to control who can access billing.
9. Configure payment gateway credentials in **App Super Admin → Payment Gateways**:
   - Stripe: publishable key, secret key, webhook secret
   - Razorpay: key id, key secret, webhook secret
   - PayPal: client id, client secret, webhook id
   - Lemon Squeezy: API key, store id, webhook secret
   - Dodo Payments: API key, webhook secret, environment
   - Paddle: API key, Paddle.js client token, endpoint secret, environment
10. To add another payment provider, follow `docs/installation/BILLING_PROVIDERS.md`.

## DreamHost
### GitHub Actions deploy
1. Add GitHub Secrets:
   - `DEPLOY_HOST`
   - `DEPLOY_USER`
   - `DEPLOY_SSH_KEY` (private key)
   - `DEPLOY_PATH_PROD` (e.g. `/home/user/example.com`)
   - `DEPLOY_PATH_STAGING` (e.g. `/home/user/staging.example.com`)
   - `ENV_FILE` (full .env contents for the target, using `THRIFTSTACK_`-prefixed keys)
2. Ensure the DreamHost account has the release layout:
   - `${DEPLOY_PATH}/releases/`
   - `${DEPLOY_PATH}/shared/storage`
3. Push to `main` for production or `staging` for staging.
4. Migrations run automatically during deployment.
5. Add a cron job to send notification digests (example daily run):
   - `0 9 * * * /usr/bin/php ${DEPLOY_PATH}/current/scripts/notifications_dispatch.php`
