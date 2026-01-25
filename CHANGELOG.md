# Changelog

## Unreleased
- Initial skeleton layout, routing, and baseline docs.
- Add config bootstrap, env loading, and secure session defaults.
- Add custom router, middleware stubs, and view renderer.
- Add PDO DB helper, migrations runner, and seed data scripts.
- Add auth flows with CSRF protection, verification, and password reset.
- Add RBAC admin screens for roles, permissions, and user role assignment.
- Add admin users list and audit log with event tracking.
- Add uploads module for profile images and attachments.
- Add application logger, rotation, and error handler.
- Add security headers and baseline input helpers.
- Add minimal test harness and core tests.
- Add GitHub Actions deploy workflow for DreamHost releases.
- Prefix environment variables with `THRIFTSTACK_`.
- Add migration failure context to error output.
- Guard migration commits when DDL ends transactions.
- Fix route naming API to avoid duplicate method names.
- Add CI workflow for PHP linting and tests.
- Add Apache rewrite rules for front controller routing.
