# 08_UPLOADS_STORAGE — Upload module (profile images + attachments)

Implement uploads.

## Requirements
- Store uploads under `/storage/uploads/...`
- Never store under `/public`
- Provide secure download route for attachments (auth required)
- Validate file types and sizes
- Randomized filenames
- DB table `uploads` with metadata

## Create/update
- /app/Uploads/Uploader.php
- /app/Controllers/UploadController.php
- /views/profile/*.php (profile + uploads)
- /routes/web.php

## Deliverable
All file contents.
