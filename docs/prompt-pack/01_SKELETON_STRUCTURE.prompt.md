# 01_SKELETON_STRUCTURE — Repo layout + Hello Dashboard

Create the initial repository skeleton with the required folders and baseline docs.

## Create folders
- /app
- /public
- /views
- /routes
- /storage (and subfolders for logs, cache, uploads)
- /shared
- /tests
- /docs/specs
- /docs/testing
- /docs/installation
- /docs/prompt-pack

## Create baseline files
- /public/index.php (front controller, minimal bootstrap, show a simple landing or redirect to /login)
- /public/assets/css/site.css (empty baseline + a few basic rules)
- /views/layouts/app.php (base layout)
- /views/home.php (basic page)
- /routes/web.php (define routes)
- /README.md
- /CHANGELOG.md
- /AGENTS.md
- /docs/specs/PRD.md (placeholder but structured)
- /docs/testing/MANUAL_TEST_PLAN.md
- /docs/testing/TESTING_GUIDE.md
- /docs/installation/SETUP_GUIDE.md
- /docs/prompt-pack/RUNBOOK.md (how to run prompt-pack)

## Requirements
- No auth yet (just a simple page).
- No DB yet.
- Use server-rendered HTML.
- Load Alpine.js via CDN in layout.
- Load CSS only from `/public/assets/css/site.css` (no inline CSS).

## Deliverable
Output all created file contents.
