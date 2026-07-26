# Teacher Portal Authentication

Implementation date: 2026-07-24  
Phase: Teacher Portal T1

## Inspected authentication architecture

The existing admin portal does not use Laravel's default `web` guard. `FrontController` verifies the hashed `cultivation_admins.loginPassword`, stores `cultivation_admins.id` in the `cultivationAdmin` session key, and admin routes use `adminGuard`.

`config/auth.php` previously defined only the default `web` guard backed by `App\Models\User`. Existing Result Engine and teacher assignment code does not use that model for teachers.

Two teacher-related identity concepts exist:

- `teacher_management` stores HR/profile fields such as `teacherId`, name, email, and mobile. It has no password, authentication status, or link to Result Engine assignments.
- `cultivation_admins` stores the operational account, hashed `loginPassword`, username, email, mobile, and `userType`. A `userType` of `1` is the established teacher role.

All assignment relations (`teacher_classes`, `teacher_sections`, `teacher_subjects`, and `teacher_class_subjects`), marks audit identity, attendance identity, and Result Engine teacher authorization reference `cultivation_admins.id`. This makes `cultivation_admins` the authoritative authentication and authorization identity.

## Selected teacher identity source

Teacher authentication uses existing `cultivation_admins` rows with `userType = 1`.

Supported identifiers are the verified existing columns:

- teacher username/ID: `adminUser`;
- email: `adminMail`;
- mobile: `adminMobile`.

The backend searches all three fields and accepts the identifier only when exactly one teacher account matches. Zero or multiple matches return the same generic error. Ambiguous identifiers are logged using a one-way identifier hash, never the submitted credential.

`loginPassword` remains the password source and is checked through Laravel's configured password hasher/provider. No plaintext or reversible comparison is used and no legacy password is rewritten.

## Guard and provider

- guard: `teacher`
- driver: `session`
- provider: `teachers`
- model: `App\Models\CultivationAdmin`
- password attribute: `loginPassword`

`CultivationAdmin` now implements Laravel's authenticatable contract while retaining its existing table, primary key, relationships, and role helpers.

The teacher guard uses its own session key and does not replace the admin `cultivationAdmin` session identity. Remember-me is intentionally unavailable because `cultivation_admins` has no verified remember-token column.

## Routes

| Method | URI | Name | Access |
|---|---|---|---|
| GET | `/teacher/login` | `teacher.login` | teacher guest |
| POST | `/teacher/login` | `teacher.login.submit` | teacher guest, rate limited |
| GET | `/teacher/dashboard` | `teacher.dashboard` | authenticated teacher |
| POST | `/teacher/logout` | `teacher.logout` | authenticated teacher |

The routes remain in Laravel's `web` middleware group, which provides cookie encryption, sessions, CSRF verification, and validation-error sharing.

## Middleware and eligibility

`teacher.guest` redirects an already authenticated teacher to the teacher dashboard.

`teacher.auth` requires:

- a valid account resolved by the `teacher` guard;
- an existing `CultivationAdmin` model;
- current `userType = 1`.

If the account is deleted, the session guard can no longer resolve it and access redirects to login. If its role changes, middleware logs the safe account ID, logs out the teacher guard, regenerates the session, and redirects to login.

No active, archived, suspended, institute, branch, or soft-delete fields exist on `cultivation_admins`; therefore no unverified status convention was invented. A future status control requires an explicitly approved schema and lifecycle policy.

## Session separation and logout

Successful login regenerates the session ID. Teacher logout:

1. logs out only the `teacher` guard;
2. invalidates the old session;
3. regenerates the CSRF token;
4. restores an independently authenticated legacy admin session ID when one existed.

The legacy admin logout was narrowed from flushing the entire session to removing only `cultivationAdmin`, preventing it from destroying a concurrent teacher guard session.

Teacher-only sessions cannot satisfy `adminGuard`; admin-only sessions cannot satisfy `teacher.auth`.

## Result Engine integration

`CultivationAdminResolver` continues to prefer the established `cultivationAdmin` admin-session identity. When that key is absent, it safely resolves an authenticated `teacher` guard account only when the model is a teacher.

This is an identity adapter, not an authorization bypass. Existing assignment and Result Engine authorization remains authoritative:

- class, subject, section, group, session, and gender assignments remain scoped by `cultivation_admins.id`;
- authentication alone grants no academic scope;
- one teacher does not inherit another teacher's assignment;
- no grading, lifecycle, confirmation, publication, promotion, or placement rule changed.

T1 exposes no marks or student modules from the new dashboard.

## Security controls

- generic credential failure message;
- input length validation;
- exact single-account match requirement;
- Laravel hashed-password validation;
- session regeneration after login;
- session invalidation and CSRF regeneration on logout;
- five attempts per minute per identifier hash and IP;
- `POST` login/logout with CSRF protection;
- no client-selected guard or teacher ID;
- no external intended redirect;
- safe operational logs for success, failure, ambiguity, ineligibility, logout, and middleware denial;
- passwords, hashes, tokens, session IDs, and raw identifiers are not logged.

## Files changed

- `config/auth.php`
- `app/Models/CultivationAdmin.php`
- `app/Http/Controllers/TeacherAuthController.php`
- `app/Http/Controllers/FrontController.php`
- `app/Http/Middleware/EnsureTeacherAuthenticated.php`
- `app/Http/Middleware/RedirectIfTeacherAuthenticated.php`
- `app/Providers/AppServiceProvider.php`
- `app/Services/CultivationAdminResolver.php`
- `bootstrap/app.php`
- `routes/web.php`
- `resources/views/teacher/auth/login.blade.php`
- `resources/views/teacher/dashboard.blade.php`
- `tests/Feature/TeacherPortalAuthenticationTest.php`
- `docs/teacher-portal-authentication.md`

## Database changes

None. No migration, table, column, record, identity duplication, or production-data change was introduced.

## Verification

The dedicated feature suite covers login rendering, all verified identifiers, wrong and unknown credentials, role eligibility, ambiguity, session regeneration, dashboard access, guest redirects, authenticated-login redirect, logout isolation, admin/teacher separation, throttling, CSRF middleware, Result Engine identity resolution, and assignment isolation.

Verification results:

- focused teacher portal suite: 15 tests, 62 assertions, PASS;
- full application suite: 324 tests, 1,393 assertions, PASS;
- `php artisan route:list`: PASS, 420 routes, including all four teacher portal routes;
- `php artisan view:cache`: PASS;
- PHP syntax checks: PASS;
- frontend build: not required because no bundled frontend asset was changed.

## Remaining limitations

- No separate active/suspended/archived state exists for operational teacher accounts.
- No remember-me support exists because there is no remember-token field.
- No teacher password reset or registration flow was added.
- `teacher_management` profiles are not linked to operational accounts, so the dashboard intentionally displays only verified `cultivation_admins` information.
- The T1 dashboard is a secure foundation only; it does not expose Result Engine or other academic modules.

## Recommended next phase

Define a Teacher Portal T2 authorization map and add modules one at a time through existing Result Engine and assignment services. Before adding profile enrichment or account status, approve an authoritative linkage/status design rather than matching profile records heuristically.
