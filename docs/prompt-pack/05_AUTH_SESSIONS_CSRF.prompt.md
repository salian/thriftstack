# 05_AUTH_SESSIONS_CSRF — Signup/Login/Logout + CSRF + Email verification + Password reset

Implement the full authentication system.

## Requirements
- Session cookie auth.
- CSRF tokens for all POST forms.
- Signup:
  - create user with hashed password
  - send verification email
- Login:
  - reject if not verified (configurable)
- Logout.
- Email verification flow:
  - signed token stored in DB
- Password reset:
  - request reset email
  - reset form with token

Email sending must use DreamHost `mail()`.

## Create/update
- /app/Auth/Auth.php
- /app/Auth/Password.php
- /app/Auth/Csrf.php
- /app/Mail/Mailer.php
- /app/Controllers/AuthController.php
- /routes/web.php
- /views/auth/*.php (login, signup, verify, forgot, reset)
- Update layout nav to show user state.

## Deliverable
All file contents.
