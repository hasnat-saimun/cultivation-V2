# Teacher Portal Profile and Settings

## Authoritative source

`cultivation_admins` is the authoritative Teacher Portal account and profile source.
The teacher guard uses its `CultivationAdmin` model through the `teachers` provider.
`TeacherManagement` contains separate employee records but has no verified account
identity relationship, so T6 neither reads nor mutates it.

## Editable fields and login behavior

Teachers may update only `adminName`, `adminMail`, `adminMobile`, and an optional
validated `avatar`. The controller never accepts an account ID. Email is normalized to
lowercase and mobile separators are removed. Both fields are application-level unique
excluding the current account.

`adminUser`, `adminMail`, and `adminMobile` are supported login identifiers. Updating
email/mobile keeps the current authenticated session valid; future logins use the new
identifier.

## Protected fields

Account ID, username, `userType`, roles, status, primary class/section, assignments,
permissions, academic mappings, timestamps and password are not accepted by profile
updates. `CultivationAdmin` has no broad fillable list and the service assigns only its
explicit whitelist.

## Password flow and hashing

Password changes require the current password, a confirmed new password and a minimum
length of eight characters. Current credentials use `Hash::check`; the new
`loginPassword` value uses Laravel `Hash::make`. Only the teacher-guard actor is
updated, and the session ID is regenerated afterward. There is no invented
remember-token workflow.

## Profile photo

The existing authoritative convention is `public/upload/image/admin`, already consumed
by the Teacher Dashboard. T6 accepts actual JPEG, PNG or WebP images, maximum 2 MB and
80–3000 pixels per dimension. It generates `teacher-{accountId}-{uuid}` filenames.
Only an earlier T6-managed file with that exact prefix may be removed after a successful
database update. Legacy, default and shared files are never deleted; a failed upload or
save preserves the prior database reference.

## Authorization and logging

All five routes use `teacher.auth` and resolve the account from the teacher guard.
Profile changes log actor ID, action and changed field names. Password rejection/change
logs contain no password, hash, contact values or request payload.

## Routes

- `GET /teacher/profile`
- `GET /teacher/profile/edit`
- `PUT /teacher/profile`
- `GET /teacher/settings/password`
- `PUT /teacher/settings/password`

## Files changed

- `app/Http/Controllers/TeacherProfileController.php`
- `app/Http/Requests/TeacherProfileUpdateRequest.php`
- `app/Http/Requests/TeacherPasswordUpdateRequest.php`
- `app/Services/TeacherProfileService.php`
- `routes/web.php`
- `resources/views/teacher/partials/sidebar.blade.php`
- `resources/views/teacher/partials/topbar.blade.php`
- `resources/views/teacher/profile/show.blade.php`
- `resources/views/teacher/profile/edit.blade.php`
- `resources/views/teacher/profile/password.blade.php`
- `tests/Feature/TeacherProfileSettingsTest.php`
- `docs/teacher-portal-profile-settings.md`

## Database changes

None. No migration, schema or production-data operation was performed.

## Tests

- Focused T1–T6 Teacher Portal suite: `70 passed` (`337 assertions`).
- Full Laravel suite: `379 passed` (`1,668 assertions`).
- Profile/settings route inspection, PHP syntax, Blade compilation and diff
  whitespace validation passed.
- No Vite-managed asset source changed, so no asset rebuild was required.

## Remaining limitations and deferrals

- No authoritative address, designation, employment status or last-login field exists
  on the portal account, so T6 does not expose or edit those values.
- Uniqueness is enforced on this update path; the historical schema has no unique
  database indexes for email/mobile.
- Legacy avatar files are preserved rather than automatically cleaned up.
- T7 and T8, including routines, notifications, messaging and other new modules, are
  explicitly deferred and were not implemented.
