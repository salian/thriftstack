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
- RBAC: System access flags + workspace roles/workspace permissions.
- Admin panel: user list and audit log.
- File uploads with secure storage.
- Workspaces with Workspace Roles and invites.
- User settings and preferences.
- Billing: hosted checkout flows with Stripe, Razorpay, PayPal, and Lemon Squeezy + AI credit top-ups.

### Planned next
- Notifications (in-app + email) with immediate and batched delivery.
- Analytics page placeholder.
- Subscriptions with trials, plans, and multi-provider webhooks.

## Current status
- Router, middleware, view rendering, and CSS baseline in place.
- Migrations + seeds + tests harness available.
- GitHub Actions deploy for DreamHost releases configured.

## Roles and permissions scope
- System access flags are global (system admin/staff + app-wide settings/management).
- Workspace roles/permissions are scoped per workspace (member/admin access inside a workspace).

## Success metrics
- Clean boot with minimal configuration.
- Repeatable deployment with zero manual DB steps.
- Easy to extend without breaking conventions.
