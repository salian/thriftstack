# 04_DB_SCHEMA_MIGRATIONS_SEEDS — PDO, migrations, schema, seeds

Implement DB access, migrations runner, and seed scripts.

## Requirements
- PDO MySQL connection helper.
- Migration system:
  - migrations stored in `/app/Database/Migrations`
  - table `migrations` to track applied migrations
  - CLI runner: `php scripts/migrate.php`
- Seeds:
  - CLI seed runner: `php scripts/seed.php`
- Insert dummy users/system access flags and workspace permissions skeleton data.

## Create/update
- /app/Database/DB.php
- /app/Database/Migrator.php
- /app/Database/Seeder.php
- /app/Database/Migrations/0001_initial.php (users, workspace_permissions, audit_logs, uploads)
- /scripts/migrate.php
- /scripts/seed.php
- /docs/installation/SETUP_GUIDE.md updated with DB steps

## Deliverable
All file contents.
