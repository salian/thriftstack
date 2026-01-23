# 06_RBAC_PERMISSIONS — Roles, permissions, guards

Implement RBAC.

## Requirements
- Default roles: Admin, Staff, User.
- Permissions table and role_permissions pivot.
- user_roles pivot.
- Middleware helpers:
  - requireRole('Admin')
  - requirePermission('users.view') etc.
- Add Admin-only pages to manage:
  - roles
  - permissions
  - user role assignments

## Create/update
- /app/Auth/Rbac.php
- /app/Http/Middleware/RequireRole.php
- /app/Http/Middleware/RequirePermission.php
- /app/Controllers/Admin/RolesController.php
- /app/Controllers/Admin/PermissionsController.php
- /app/Controllers/Admin/UserRolesController.php
- /views/admin/rbac/*.php
- /routes/web.php

## Deliverable
All file contents.
