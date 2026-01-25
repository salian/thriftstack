# 17_NOTIFICATIONS — In-app + email notifications

Implement notifications with immediate and batched delivery.

## Requirements
- In-app notifications stored in DB with read status.
- Email notifications via DreamHost `mail()`.
- Support immediate and batched notifications (daily digest).
- Extensible design for future providers (SMTP/API).
- Notification history page.

## Create/update
- /app/Notifications/NotificationService.php
- /app/Notifications/NotificationDispatcher.php
- /app/Controllers/NotificationsController.php
- /app/Database/Migrations/0005_notifications.php
- /scripts/notifications_dispatch.php
- /views/notifications/*.php
- /routes/web.php

## Data model
- notifications: id, user_id, channel, subject, body, status, is_read, created_at, sent_at
- notification_batches: id, user_id, scheduled_for, status, created_at

## Delivery rules
- Immediate: send and mark sent.
- Batched: store and dispatch via `php scripts/notifications_dispatch.php`.

## Deliverable
All file contents.
