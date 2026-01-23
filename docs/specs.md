# ✅ Combined Prompt — Core PHP Boilerplate (DreamHost + LLM-Friendly)

You are an expert PHP 8.5 engineer and DevOps architect. Generate a production-ready **Core PHP boilerplate repo** that is optimized for:

- building **logged-in SaaS dashboard apps**
- **multi-project reuse** (this boilerplate will be copied into multiple separate repos)
- **DreamHost shared hosting**
- **SSH + rsync deployment via GitHub Actions**
- **LLM-driven development workflows** (prompt packs + strict runbooks)

The project must be clean, structured, secure, and easy for an LLM to extend without breaking conventions.

---

## 1) Project Scope
- Type: **App dashboard (logged-in)**
- Multi-project reuse: **Yes**
- Must be designed as a reusable starter template repo.

---

## 2) Hosting & Deployment (DreamHost)
Deployment target: DreamHost shared hosting.

### Deployment method
- **SSH + rsync via GitHub Actions**
- Must support **releases-based deployments**:
  - `/releases/<timestamp>/`
  - `/current -> /releases/<timestamp>/` (symlink)
- Web root must be:
  - `/current/public/`

### Environments
- **Staging and production are on the same DreamHost server account**
- They live in **different directories** (different subdomains)
- Boilerplate must support both environments cleanly via config/env.

---

## 3) PHP Version + Extensions
Target PHP version: **PHP 8.5**

Assume these extensions exist:
- `pdo_mysql`
- `openssl`
- `mbstring`
- `curl`
- `fileinfo`

---

## 4) Database
Database: **MySQL for local and production**

Must include:
- SQL + script to initialize database schema
- Script to insert **dummy seed data for testing**

### Migrations
- Must include a **PHP migrations system**
- Migrations must be automatically executed as part of **CD deployment**
- Migrations must be safe and idempotent (avoid partial failures).

---

## 5) Authentication + Roles + RBAC
Auth system is required and must include:

### Core auth
- Signup
- Login
- Logout
- Email verification
- Password reset (email-based)
- Session cookie auth
- CSRF protection everywhere

### Roles
Default roles:
- `Admin`
- `Staff`
- `User`

### RBAC
- Implement **roles + permissions**
- Must support:
  - assigning roles to users
  - permissions grouped by feature/module
  - middleware/guards in routing layer
- Admin can manage roles/permissions.

---

## 6) Routing + Project Structure
Routing:
- Must use `index.php` **front controller**
- Must use a **custom router** (no framework)

Must maintain this repo structure:

```
/docs/prompt-pack/RUNBOOK.md
/docs/specs/PRD.md
/docs/testing/MANUAL_TEST_PLAN.md
/docs/testing/TESTING_GUIDE.md
/docs/installation/SETUP_GUIDE.md

/app
/public
/views
/routes
/storage
/shared
/tests

CHANGELOG.md
README.md
AGENTS.md
```

Notes:
- `/public` is the only webroot.
- All app logic must stay outside `/public`.

---

## 7) Frontend Style
Frontend approach:
- **Server-rendered pages**
- Use **Alpine.js** for interactivity
- Use **HTMX only where full refresh is not possible** (example: plugin contexts)

Styling rules:
- Must use a single stylesheet:
  - `/public/assets/css/site.css`
- **No inline CSS**
- Theme baseline: **Modern neutral enterprise SaaS**
  - blues + greys
  - clean typography
  - good spacing
  - consistent buttons/forms/cards/tables
- Generate `site.css` with a solid baseline system.

---

## 8) Environment Config + Secrets
Configuration rules:
- Provide `config.php` which:
  - reads from `.env` if present
  - falls back to environment variables
- `.env` must be populated at deploy-time by GitHub Actions via GitHub Secrets
- `.env` must not be committed.

---

## 9) Logging + Debugging
Logging requirements:
- Log to both:
  - PHP error log
  - `/storage/logs/app.log`

Log rotation:
- Must implement log rotation + auto-delete beyond **30 days**

Debug mode:
- In local dev: show detailed errors
- In production: hide errors by default
- Must support config to show errors in production:
  - globally OR
  - only for Admin users

---

## 10) Email Provider
Email sending:
- Use **DreamHost `mail()`**
- Must support sending:
  - verification email
  - password reset email

---

## 11) Batteries Included (Must exist in boilerplate)
Must include:

### Admin panel skeleton
- User list page
- Audit log page
- Basic admin navigation shell

### File uploads module
- Profile image upload
- Attachments upload (generic)
- Must store in `/storage` with safe paths
- Must prevent direct listing / unsafe execution

### Security
- CSRF protection
- Security headers defaults (CSP-lite or safe baseline)
- Must include safe session cookie flags
- Input validation and escaping guidelines

---

## 12) LLM Workflow Requirements
Must include a root doc:

### `00_RUNBOOK.md`
This is a strict LLM runbook that tells an LLM:

- how to add a new page/module safely
- what not to break
- how to run migrations
- to create unit tests after every feature
- to append to `CHANGELOG.md` after every feature

Also include:
- `/docs/prompt-pack/RUNBOOK.md` (LLM prompt-pack oriented)
- `AGENTS.md` describing agent behavior expectations and boundaries.

---

## 13) Naming & Tone
Repo name:
- Generic working name: `php-core-starter`
- Must be designed so it can be renamed easily.

Tone:
- Modern enterprise SaaS
- Neutral blues and greys

---

## Output Requirements
Generate the complete repo with:
- all files and folders
- working code (not placeholders)
- sensible defaults
- secure-by-default practices
- clear documentation

Also include:
- a working GitHub Actions workflow for rsync deploy + migrations
- setup docs for local dev and DreamHost deploy
- seed data scripts
- a minimal test harness in `/tests`

Do NOT use Laravel/Symfony or any external framework.

You may use:
- Composer (only if needed, keep minimal)
- Alpine.js from CDN
- HTMX from CDN (only for special cases)

---
