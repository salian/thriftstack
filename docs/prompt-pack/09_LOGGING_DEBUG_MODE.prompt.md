# 09_LOGGING_DEBUG_MODE — app.log + rotation + debug rules

Implement logging and debug behavior.

## Requirements
- `/storage/logs/app.log`
- Simple logger class with levels.
- Log rotation:
  - delete logs older than 30 days
  - rotate when size threshold exceeded (e.g. 5MB)
- Error handling:
  - show detailed errors in local
  - hide in production
  - config flags:
    - show errors globally in prod
    - OR only for Super Admin users

## Create/update
- /app/Logging/Logger.php
- /app/Logging/LogRotation.php
- /app/Errors/ErrorHandler.php
- /app/Bootstrap.php

## Deliverable
All file contents.
