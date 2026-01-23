# 13_GITHUB_ACTIONS_DEPLOY_RELEASES — GitHub Actions rsync deploy + migrations

Implement GitHub Actions workflow for DreamHost.

## Requirements
- Deploy via SSH + rsync.
- Create release folder:
  - `/releases/<timestamp>/`
- Rsync code into release.
- Symlink shared folders:
  - `/shared/storage` or similar
  - `.env` injected at deploy time
- Run migrations automatically:
  - `php scripts/migrate.php`
- Support staging + production:
  - branch-based or manual approval (environment protection)
- Ensure `current` symlink points to new release.
- Keep last N releases (e.g. 5), delete older ones.

## Create/update
- /.github/workflows/deploy.yml
- /scripts/deploy_helpers.sh (optional)
- /docs/installation/SETUP_GUIDE.md updated

## Deliverable
All file contents.
