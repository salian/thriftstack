# ThriftStack

ThriftStack is an agentic coding boilerplate that enables you to ship SaaS MVPs on shared hosting; fast, cheap, and production-ready.

## Philosphy

Modern AI stacks often assume a VPS, managed databases, and a handful of paid third-party services. Each cost may be small, but over time they compound and quietly shorten your runway. 

ThriftStack is built around a simple thought: when the cost of keeping a project alive is low, when the cost of iteration is low and the speed of iteration is high, you get more shots on goal within the same runway and your odds of success go up.

In the past, "MVP" often meant stitching together services to move fast. Today with agentic AI coding, the cost of writing code has dropped so much that many core features are now cheaper and faster to build at a basic level than to rent and integrate.

ThriftStack is a response to this new reality - a modern, production-minded Core PHP on LAMP boilerplate (with CI/CD, releases-based deployments, and sane defaults) designed to help you ship real AI SaaS MVPs on inexpensive shared hosting like DreamHost.

The stack choices (PHP Core, Alpine.js via CDN, MySQL) are due to the constraints of shared hosting.

## Current Repository

This folder currently contains a multi-step prompt-pack to have an LLM build the ** ThriftStack** php-core-starter boilerplate incrementally.

## How to use
1. Run the prompt files from `/docs/prompt-pack/` **in order**, one at a time, in a fresh chat with an LLM that can create files.
2. After each prompt:
   - Apply the file changes
   - Run basic checks
   - Commit the result with a clear message
   - Update `CHANGELOG.md`

## Prompt sequence
- 00_RUNBOOK_ENFORCER.prompt.md
- 01_SKELETON_STRUCTURE.prompt.md
- 02_CONFIG_ENV_BOOTSTRAP.prompt.md
- 03_ROUTER_MIDDLEWARE_VIEWS.prompt.md
- 04_DB_SCHEMA_MIGRATIONS_SEEDS.prompt.md
- 05_AUTH_SESSIONS_CSRF.prompt.md
- 06_RBAC_PERMISSIONS.prompt.md
- 07_ADMIN_PANEL_AUDIT_LOG.prompt.md
- 08_UPLOADS_STORAGE.prompt.md
- 09_LOGGING_DEBUG_MODE.prompt.md
- 10_SECURITY_HEADERS_HARDENING.prompt.md
- 11_FRONTEND_CSS_BASELINE.prompt.md
- 12_TESTS_HARNESS.prompt.md
- 13_GITHUB_ACTIONS_DEPLOY_RELEASES.prompt.md
- 14_DOCS_FINALIZE.prompt.md