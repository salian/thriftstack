# Manual Test Plan

## Smoke tests
- Load `/` and verify the home page renders.
- Verify layout loads the stylesheet and Alpine.js.
- Visit an unknown route and confirm the 404 page renders.
- Visit `/login` and `/signup` pages render.
- Create a user, verify email flow works, then log in.
- Request a password reset and complete reset flow.
- Visit `/dashboard` only when authenticated.
- As Admin, visit `/admin/users` and `/admin/audit`.
- Upload profile image and attachment from `/profile`.
- Download attachment via `/uploads/attachment/{id}`.
- Create a workspace, invite a member, accept invite.
- Switch workspace context and verify role guard.

## Upcoming features (planned)
- Update user settings and preference flags.
- Send immediate notification and view history.
- Send batched notification and verify digest.
- Visit analytics placeholder page.
- Verify subscription trial start and webhook handling.
