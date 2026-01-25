# Testing Guide

## Philosophy
Keep tests lightweight and focused on critical flows. Add tests whenever adding features.

## Test harness
- Run `php tests/run.php`.
- Tests cover config loading, router matching, CSRF, and password hashing.

## Planned coverage
- Workspace membership + role guards.
- Settings persistence.
- Notification delivery and history.
- Billing webhook signature validation.
