# 07_ADMIN_PANEL_AUDIT_LOG — Admin UI + audit logging

Implement admin shell and audit log.

## Requirements
- Admin layout/nav section.
- Users list page (Admin+Staff maybe).
- Audit log table page (Admin only).
- Audit events:
  - login success/fail
  - signup
  - email verification
  - password reset requested/completed
  - role changes
  - uploads

## Create/update
- /app/Audit/Audit.php
- /app/Controllers/Admin/UsersController.php
- /app/Controllers/Admin/AuditLogController.php
- /views/admin/users/index.php
- /views/admin/audit/index.php
- /routes/web.php

## Deliverable
All file contents.
