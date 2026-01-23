# 10_SECURITY_HEADERS_HARDENING — Security defaults

Implement security headers and session hardening.

## Requirements
- Security headers:
  - X-Frame-Options / frame-ancestors equivalent
  - X-Content-Type-Options
  - Referrer-Policy
  - Permissions-Policy
  - CSP baseline compatible with Alpine CDN
- Session cookie flags:
  - HttpOnly, Secure (when HTTPS), SameSite=Lax
- Input validation + output escaping helpers

## Create/update
- /app/Security/Headers.php
- /app/Bootstrap.php
- /views/layouts/app.php

## Deliverable
All file contents.
