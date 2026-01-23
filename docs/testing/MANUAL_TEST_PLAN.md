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
- Upload profile image and attachment from `/uploads`.
- Download attachment via `/uploads/attachment/{id}`.
