# Testing Guide

## Philosophy
Keep tests lightweight and focused on critical flows. Add tests whenever adding features.

## Test harness
- Run `php tests/run.php`.
- Tests cover config loading, router matching, CSRF, password hashing, workspace invites, and settings storage.

## Planned coverage
- Notification delivery and history.
- Billing webhook signature validation.
