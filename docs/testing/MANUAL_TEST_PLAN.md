# Manual Test Plan

## Smoke tests
- Load `/` and verify the home page renders.
- Verify layout loads the stylesheet and Alpine.js.
- Visit an unknown route and confirm the 404 page renders.
- Visit `/login` and `/signup` pages render.
- Create a user, verify email flow works, then log in.
- Request a password reset and complete reset flow.
- Visit `/dashboard` only when authenticated.
- As Workspace Admin, visit `/workspace-admin/users` and `/workspace-admin/audit`.
- As Super Admin, visit `/super-admin/roles`, `/super-admin/permissions`, and `/super-admin/user-roles`.
- Upload profile image and attachment from `/profile`.
- Download attachment via `/uploads/attachment/{id}`.
- Create a workspace, invite a member, accept invite.
- Switch workspace context and verify role guard.
- Update profile name from `/profile` and notification preferences from `/settings`.
- Send an in-app notification and view `/notifications`.
- Queue a batched notification and run `php scripts/notifications_dispatch.php`.

## Upcoming features (planned)
- Update user settings and preference flags.
- Visit analytics placeholder page.
- Verify subscription trial start and webhook handling.
