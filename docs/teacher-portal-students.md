# Teacher Portal My Classes and My Students

## Architecture and authorization

This is a read-only academic directory. `new_admissions.id` is the internal student
identity and `stdId` is the public display identity. Authorization comes exclusively
from the authenticated teacher's `teacher_class_subjects` rows.

Every query applies the assigned session, class, optional section, optional department
and gender scope. Subject remains part of each displayed class context and controls
which result rows may be shown. Client-submitted student IDs are never accepted without
re-running the same server-side academic query.

## My Classes

The class page lists session, class, section, department and subject contexts with an
authorized student count. Attendance links appear only when a context is also the
teacher's primary class/section; result links lead to the existing Result Workspace.

## My Students

The directory combines only the teacher's authorized contexts, eager-loads academic
labels, orders deterministically and paginates 20 students per page. Search by public
student ID, roll or name is applied inside that authorized query, never to the
institution-wide population.

## Student profile

Profiles reauthorize the route ID and return 404 outside scope. They expose only the
existing read-only photo, public identity, roll, name, gender, academic placement and
status. Finance, guardian contact details, admission editing, certificates and admin
actions are omitted.

## Result summary

Only marks for subjects assigned to the viewing teacher are loaded. Each row is
recalculated through `BoardResultCalculator::calculateSubject`, preserving the
centralized Result Engine normalization, grade and component rules. Publish, Reopen,
Promotion and other lifecycle controls are absent.

## Attendance summary

The already-authorized student profile uses the authoritative `attendances` table and
the existing four status values to provide read-only grouped counts. It performs no
attendance mutation and does not change daily or monthly report semantics.

## Security and performance

- teacher guard authentication is mandatory;
- another teacher, forged ID and unknown ID receive 404;
- assignment session/class/section/department/gender dimensions are enforced;
- unassigned-subject results are not exposed;
- no institution-wide student query is executed;
- student relationships are eager-loaded and list results are paginated;
- summaries use bounded aggregate/batch queries and introduce no per-student queries.

## Files changed

- `app/Http/Controllers/TeacherAcademicController.php`
- `app/Services/TeacherAcademicWorkspaceService.php`
- `routes/web.php`
- `resources/views/teacher/partials/sidebar.blade.php`
- `resources/views/teacher/academic/classes.blade.php`
- `resources/views/teacher/academic/students.blade.php`
- `resources/views/teacher/academic/student.blade.php`
- `tests/Feature/TeacherAcademicWorkspaceTest.php`
- `docs/teacher-portal-students.md`

## Database changes

None. No schema, migration or production-data change was made.

## Tests

- Focused teacher portal suite: `63 passed` (`289 assertions`).
- Full Laravel suite: `372 passed` (`1,620 assertions`).
- Teacher academic route inspection, PHP syntax, Blade compilation and diff
  whitespace validation passed.
- No Vite-managed assets changed, so no asset rebuild was required.

## Limitations

- The directory reflects current `new_admissions` placement; it is not a historical
  student archive.
- Result summary is intentionally subject-scoped and is not an overall transcript/GPA.
- Attendance summary is an all-record status aggregate, not a monthly matrix.
