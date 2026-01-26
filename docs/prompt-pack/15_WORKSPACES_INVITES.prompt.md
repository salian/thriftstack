# 15_WORKSPACES_INVITES — Workspaces, memberships, invites

Add workspaces (organizations) with per-workspace roles and invite flow.

## Requirements
- Workspaces are named containers; users can belong to multiple workspaces.
- Per-workspace roles: Workspace Owner, Workspace Admin, Workspace Member.
- Invite flow:
- Workspace Admin/Workspace Owner can invite by email.
  - Token stored hashed in DB with expiry.
  - Accept invite creates membership.
- Workspace context stored in session and switchable.
- Middleware to require workspace membership and role.
- Audit events for workspace created/invite/accept/role change.

## Create/update
- /app/Workspaces/WorkspaceService.php
- /app/Controllers/WorkspaceController.php
- /app/Controllers/WorkspaceInviteController.php
- /app/Http/Middleware/RequireWorkspace.php
- /app/Database/Migrations/0003_workspaces.php
- /views/workspaces/*.php
- /routes/web.php

## Data model
- workspaces: id, name, created_by, created_at
- workspace_memberships: user_id, workspace_id, role, created_at
- workspace_invites: id, workspace_id, email, role, token_hash, expires_at, created_at, accepted_at

## Deliverable
All file contents.
