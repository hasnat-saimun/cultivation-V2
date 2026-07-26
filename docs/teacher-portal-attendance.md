# Teacher Portal Attendance Workspace

## Architecture

Teacher attendance is a class-teacher-only capability. The authoritative assignment is
`cultivation_admins.primary_class_id` plus the mandatory
`cultivation_admins.primary_section_id`. `teacher_class_subjects` is deliberately not
queried or accepted as attendance authorization and remains a Result Workspace concern.

The authoritative record is `attendances`. The existing identity is:

`attendance_date + class_id + section_id + student_id`

`session_id` is stored as context and selects the current student population, but it is
not part of attendance identity. Teacher attendance always writes a concrete section.

## Status and overwrite policy

The only accepted statuses are `Present`, `Absent`, `Late`, and `Excused`. The shared
service validates them server-side. Repeated saves use `updateOrCreate` on the existing
identity, so they update the row; T4 introduces no date lock or immutable lifecycle.

## Authorization and population

The dedicated teacher guard supplies actor identity. Class and section are resolved
from the authenticated account and never from browser input. The selected session must
exist. Students are resolved from `new_admissions` using the exact session, primary
class and primary section. Every submitted ID is revalidated, duplicate rows and
out-of-population students reject the whole transaction. Partial legitimate
submissions remain allowed, matching the pre-existing admin behavior.

## Shared save flow

`AttendanceSaveService` is used by both the legacy admin controller and the teacher
controller. It validates row alignment, duplicates, population membership and status,
then performs all `updateOrCreate` operations in one transaction. Existing optional SMS
behavior remains in the shared path. Teacher saves emit a bounded application log with
actor ID, attendance scope, date and counts; payloads, names, credentials, session IDs
and exception traces are not logged.

## Reports and performance

Daily, print, export and monthly report code and status calculations were not changed.
Population, existing attendance and recent summaries use bounded queries; SMS settings
are loaded once per save, not per student. No institution-wide student list is sent to
the teacher browser.

## Admin compatibility

The existing `/attendance/store` route, request names, redirect, created/updated
message, overwrite identity, teacher attribution and SMS behavior remain. Its inline
write loop now delegates to the same transactional service.

## Files changed

- `app/Http/Controllers/AttendanceController.php`
- `app/Http/Controllers/TeacherAttendanceController.php`
- `app/Services/AttendanceSaveService.php`
- `app/Services/TeacherAttendanceWorkspaceService.php`
- `routes/web.php`
- `resources/views/teacher/partials/sidebar.blade.php`
- `resources/views/teacher/attendance/index.blade.php`
- `resources/views/teacher/attendance/workspace.blade.php`
- `tests/Feature/TeacherAttendanceWorkspaceTest.php`
- `docs/teacher-portal-attendance.md`

## Database changes

None. No migration, schema change or production-data operation is part of T4.

## Tests

- Focused teacher portal suite: `56 passed` (`259 assertions`).
- Full Laravel suite: `365 passed` (`1,590 assertions`).
- Teacher attendance route inspection, PHP syntax, Blade compilation and diff
  whitespace validation passed.
- No Vite-managed asset source changed, so an asset rebuild was not required.

## Limitations

- Attendance has no database audit table; T4 uses safe application logs.
- There is no date lock.
- The nullable-section uniqueness risk remains in legacy/admin sectionless writes.
  Teacher attendance cannot exercise it because a concrete primary section is required.
- Session changes update the contextual `session_id` on the same attendance identity.
