# Product Requirements Document

## Overview
ThriftStack is a reusable Core PHP starter for logged-in SaaS dashboards on DreamHost shared hosting.

## Goals
- Provide a secure, minimal baseline for SaaS dashboards.
- Enable repeatable deployments via GitHub Actions + rsync.
- Offer clear extension points for LLM-guided development.

## Non-goals
- No PHP frameworks.
- No client-side SPA.

## Users
- SaaS teams shipping small-to-mid dashboards.
- Operators deploying to DreamHost shared hosting.

## Core features (planned)
### Implemented
- Auth: signup/login/logout, email verification, password reset.
- RBAC: Super Admin/Staff/User roles and permissions.
- Admin panel: user list and audit log.
- File uploads with secure storage.
- Workspaces with per-workspace roles and invites.
- User settings and preferences.

### Planned next
- Notifications (in-app + email) with immediate and batched delivery.
- Analytics page placeholder.
- Subscriptions with trials, plans, and multi-provider webhooks.

## Current status
- Router, middleware, view rendering, and CSS baseline in place.
- Migrations + seeds + tests harness available.
- GitHub Actions deploy for DreamHost releases configured.

## Success metrics
- Clean boot with minimal configuration.
- Repeatable deployment with zero manual DB steps.
- Easy to extend without breaking conventions.
