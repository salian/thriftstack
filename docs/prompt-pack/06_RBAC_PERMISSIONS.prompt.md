# 06_RBAC_PERMISSIONS — Roles, permissions, guards

Implement RBAC.

## Requirements
- System access flags: System Admin and System Staff.
- No app permissions table; global access is based on system access flags.
- Workspace permissions table and workspace_role_permissions pivot.
- No app role pivots; user access is stored on the `users` table.
- Middleware helpers:
  - RequireSystemAdmin
  - RequireSystemAccess for staff/admin access
- Add System Admin-only pages to manage:
  - system access flags
  - workspace permissions
  - system access flags for users

## Create/update
- /app/Auth/Rbac.php
- /app/Http/Middleware/RequireSystemAdmin.php
- /app/Http/Middleware/RequireSystemAccess.php
- /app/Controllers/Admin/UserRolesController.php
- /views/admin/site_settings/index.php
- /routes/web.php

## Deliverable
All file contents.
