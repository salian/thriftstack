# 16_USER_SETTINGS — User settings system

Implement per-user settings and profile preferences.

## Requirements
- Settings stored per user (JSON or key/value table).
- UI for updating profile fields and preferences.
- Input validation and escaping.
- Include notification preferences flags for later use.

## Create/update
- /app/Settings/SettingsService.php
- /app/Controllers/SettingsController.php
- /app/Database/Migrations/0004_user_settings.php
- /views/settings/*.php
- /routes/web.php

## Data model
- user_settings: user_id, settings_json, updated_at

## Deliverable
All file contents.
