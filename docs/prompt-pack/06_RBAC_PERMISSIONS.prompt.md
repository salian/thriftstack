# 06_RBAC_PERMISSIONS — Roles, permissions, guards

Implement RBAC.

## Requirements
- Default roles: App Super Admin, App Staff, App User.
- App permissions table and app_role_permissions pivot.
- Workspace permissions table and workspace_role_permissions pivot.
- user_app_roles pivot.
- Middleware helpers:
  - requireRole('App Super Admin')
  - requirePermission('users.view') etc.
- Add App Super Admin-only pages to manage:
  - app_roles
  - app_permissions
  - workspace_permissions
  - user role assignments

## Create/update
- /app/Auth/Rbac.php
- /app/Http/Middleware/RequireAppRole.php
- /app/Http/Middleware/RequirePermission.php
- /app/Controllers/Admin/RolesController.php
- /app/Controllers/Admin/PermissionsController.php
- /app/Controllers/Admin/UserRolesController.php
- /views/admin/site_settings/index.php
- /routes/web.php

## Deliverable
All file contents.
