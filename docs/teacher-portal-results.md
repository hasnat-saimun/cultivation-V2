# Teacher Portal Results Workspace

Implementation date: 2026-07-25  
Phases: T3 and T3A

## Result Engine architecture reused

The teacher workspace is an authenticated adapter over the production Result Engine. It does not calculate grades, save marks, confirm scopes, or create audit evidence independently.

Reused components:

- `MarksEntryAuthorizationService` for teacher assignment authorization;
- `ResultMarksPopulationService` for server-side student population;
- `ResultMarksDraftService` for transactional Draft writes;
- `ResultMarksConfirmationService` for readiness and Confirm;
- `ResultMarksScopeService` for lifecycle state, revision and publication locks;
- `ResultLifecycleEventService` for immutable audit events;
- centralized board result calculator invoked by the Draft service.

`TeacherResultWorkspaceService` prepares teacher-scoped selection and read models. `TeacherResultController` resolves the authenticated teacher guard and delegates all mutations.

## Route structure

All routes use the existing `teacher` prefix, `teacher.` name prefix and `teacher.auth` middleware:

| Method | URI | Name | Purpose |
|---|---|---|---|
| GET | `/teacher/results` | `teacher.results.index` | Assigned scopes and eligible exams |
| GET | `/teacher/results/workspace` | `teacher.results.workspace` | Authorized marks workspace |
| POST | `/teacher/results/load` | `teacher.results.load` | Revalidate selection and redirect |
| POST | `/teacher/results/draft` | `teacher.results.draft` | Result Engine Draft Save |
| POST | `/teacher/results/confirm` | `teacher.results.confirm` | Result Engine Confirm |

No teacher Reopen, Publish, Unpublish or Promotion route exists.

## Teacher assignment source

`teacher_class_subjects` is authoritative. Landing rows require valid linked session, class and subject records; stale non-null section or department references are excluded. Rows are grouped by:

- session;
- class;
- section;
- department/group;
- subject.

Gender-scope variants therefore do not duplicate the academic assignment card. Actual gender authorization remains enforced by `MarksEntryAuthorizationService` and population resolution.

## Scope authorization flow

Every landing, load, Draft and Confirm operation derives teacher identity from `Auth::guard('teacher')`.

For a submitted scope:

1. positive session, class and subject identifiers are normalized;
2. optional section and department identifiers are normalized;
3. `MarksEntryAuthorizationService::canEnterMarksFor` proves the assignment;
4. the subject must exist;
5. `TeacherResultExamEligibilityService` proves the exam exists and is class-compatible;
6. `ResultMarksPopulationService` resolves students again for reads or mutations;
7. Draft/Confirm services enforce lifecycle, revision, readiness and publication locks.

Client-supplied teacher identity, totals, grade and student population are never trusted.

## Exam Eligibility Policy

### Authoritative source

`exams` is authoritative. `exams.className` is the class applicability field.

Verified accepted values:

- exact selected `class_manages.id`, stored as its string representation;
- `0`, meaning All Class.

The `0` convention is verified by:

- legacy and modern create/edit exam forms using `<option value="0">All Class</option>`;
- legacy and modern exam listings treating values not greater than zero as All Class;
- restored `cultivation_rhs_rehearsal` data containing three `exams.className = 0` rows and two class-specific rows;
- no other historical all-class exam value found.

The resolver does not accept blank, null, `all`, `All`, or descriptive text as all-class values.

### Session policy

Exams are not session-specific. Session eligibility comes from the authenticated teacher assignment and remains part of the Result Engine marks-scope identity. The same class-level exam can create independent authorized scopes for different sessions.

### Subject policy

Exams are class-level assessments. Subject eligibility comes from the teacher assignment and existing marks authorization.

### Section and department policy

Section, department/group and gender eligibility come exclusively from `teacher_class_subjects`, `MarksEntryAuthorizationService` and `ResultMarksPopulationService`.

### Why routines are non-authoritative

`exam_routines` and `exam_routine_items` are optional scheduling/display data. They are not queried by `TeacherResultExamEligibilityService`, do not restrict subject authorization, and are not required for eligibility. Tests prove:

- an exam without a routine remains eligible;
- an unrelated routine subject does not block an assigned subject;
- a routine cannot grant an incompatible exam;
- a routine cannot replace a teacher assignment.

## Assignment session compatibility

New Admin assignment saves persist `assignmentSessionId` as
`teacher_class_subjects.session_id` and remain strictly isolated to that session.
Production-compatible historical rows created before session-aware assignments may
have a null `session_id`. The existing marks-entry authorization policy already treats
those rows as legacy session wildcards. The Teacher Result landing page now applies the
same policy by expanding a null-session row across existing sessions for display only;
it does not update the row or relax class, section, department, subject, gender, actor
or exam authorization. Non-null stale session references remain unavailable.

In local/testing environments, rejected authorization stages emit structured ID-only
diagnostics under `teacher_result_authorization_rejected`. Production teachers continue
to receive the generic safe error message.

### Security implications

Only exams matching the assigned class or exact all-class value are sent to the browser. The same resolver revalidates workspace load, Draft and Confirm, so a forged hidden/query exam ID fails closed.

### Future recommendation

A future normalized exam academic-scope model may explicitly map exams to sessions, classes and other dimensions. It requires a separately approved migration and backward-compatible production-data policy.

## Student population resolution

The workspace calls `ResultMarksPopulationService::resolve` using authenticated teacher identity and authorized session/class/section/department/subject. It applies:

- student session matching, including verified legacy ID/label compatibility;
- class and section;
- department/group;
- assignment gender scope;
- religious subject assignment;
- fourth-subject assignment.

Only scoped student names, public student IDs, rolls and relevant marks are loaded. Submitted student rows are resolved again by Draft Save; one forged row rejects the complete batch.

## Draft flow

The controller performs bounded shape/range validation, reauthorizes assignment and exam, and calls `ResultMarksDraftService::save`.

The existing service preserves:

- exact component maxima;
- server-computed total, grade and grade point;
- full population authorization;
- transaction and row partitioning;
- Draft-only and publication locks;
- optimistic revisions;
- idempotent unchanged submissions;
- actor attribution and immutable lifecycle audit.

No publish or promotion side effect occurs.

## Confirm flow

The controller reauthorizes scope/exam and calls `ResultMarksConfirmationService::confirm`. The service verifies:

- current revision;
- Draft lifecycle state;
- unpublished parent scope;
- complete authorized population;
- all required components;
- centralized calculation/cache agreement;
- transactional state transition and audit insertion.

Confirmation never publishes or promotes.

## Reopen decision

Reopen is intentionally omitted. `ResultMarksReopenService` calls `ResultMarksScopeService::assertActor($actor, true)`, which explicitly rejects teachers. Reopening requires General or Super administrator action through the existing admin workflow.

## Lifecycle visibility

The workspace displays server-derived:

- Draft;
- Confirmed;
- Published.

Reopened scopes use the engine's actual Draft state and audit event; no fabricated Reopened state is introduced. Unpublished publication rows defer to the underlying Draft/Confirmed marks state. Confirmed and Published scopes do not render editable controls.

Class-wide assignments can save Draft rows partitioned by actual student section using `scope_revisions`. Confirm is shown only for a single concrete Result Engine scope.

## Revision and concurrency handling

The workspace posts the current scope revision, plus partition revisions where applicable. Stale revisions fail with a reload instruction. Existing transactional locks, duplicate-key handling, idempotency and fingerprint comparison remain authoritative.

## Security controls

- guest and admin-only sessions fail teacher middleware;
- teacher ID comes only from the guard;
- assignment and exam are revalidated for reads and writes;
- unknown or class-incompatible exams fail closed;
- routines cannot grant access;
- another teacher cannot read or mutate the scope;
- forged students reject the batch;
- grades and totals are server-generated;
- lifecycle state cannot be bypassed;
- state changes use POST, CSRF and throttling;
- raw lifecycle payloads and exceptions are not rendered;
- Reopen, Publish, Unpublish and Promotion are absent.

## Performance considerations

- assignment rows are filtered/grouped in SQL;
- exams for all assignment classes load in one bounded query;
- student population and relevant marks load by scope;
- marks use a single batch query and keying;
- no institution-wide marks or students are loaded;
- lifecycle activity is actor-filtered and limited to five;
- Draft/Confirm reuse existing batch services and avoid controller per-row queries.

## User interface

- Results navigation is active for all teacher result routes.
- Landing cards show only assigned contexts and eligible exams.
- The responsive workspace shows only supported CQ/Written, MCQ and Practical components.
- Inputs use configured maxima and keyboard-friendly numeric controls.
- Existing totals and grades are read-only.
- Sticky context/actions, lifecycle badge, confirmation warning and unsaved-change warning support efficient entry.
- Internal student database IDs appear only as required hidden batch identity; visible UI uses public student ID.

## Files changed

- `app/Http/Controllers/TeacherResultController.php`
- `app/Services/TeacherResultExamEligibilityService.php`
- `app/Services/TeacherResultWorkspaceService.php`
- `routes/web.php`
- `resources/views/teacher/partials/sidebar.blade.php`
- `resources/views/teacher/results/index.blade.php`
- `resources/views/teacher/results/workspace.blade.php`
- `tests/Feature/TeacherResultWorkspaceTest.php`
- `docs/teacher-portal-results.md`

## Database changes

None. No migration, table, column or production record was changed.

## Verification

- Focused teacher portal suite: `43 passed` (`197 assertions`).
- Full Laravel suite: `352 passed` (`1,528 assertions`).
- Teacher result route inspection: five intended routes only (index, load, workspace, Draft and Confirm).
- PHP syntax checks passed for the controller and both new services.
- Blade compilation and diff whitespace checks passed.
- No frontend asset source changed, so a new asset bundle was not required.

## Limitations

- Exams remain class-level because the current schema has no normalized session/subject scope.
- The current `exams` schema has no active, deleted or archive eligibility field. Existing-row presence plus exact `className` matching (including verified all-class value `0`) is therefore the complete fail-closed exam contract.
- Class-wide multi-section Draft is supported, but Confirm requires one concrete section scope.
- Attendance activity is outside this workspace.
- No teacher Reopen, Publish, Unpublish or Promotion capability exists.

## Recommended T4 scope

Implement a read-only My Students/My Classes portal module using the same assignment and population services. If richer exam scope is required, first approve a normalized exam-scope migration and production backfill policy.

## Label resolution and effective-scope deduplication

Teacher Result landing cards and workspace headers resolve Session, Class,
Section, Department, Subject and Exam through their authoritative tables.
Numeric IDs remain the submitted authorization values in hidden fields. Null
section and department contexts display `All Sections` and `All Departments`.
Required stale display relations fail closed and are logged only in local and
testing environments.

Assignment cards are normalized before rendering and deduplicated by:

`teacher_id + session_id + class_id + section_id + group_id + subject_id + gender_scope`

This collapses duplicate physical assignment rows and query/legacy-expansion
multiplication without merging scopes that differ by section, department,
subject or gender. A legacy null-session row expands at most once for each
available session, while a concrete session assignment remains isolated to its
stored session.

The landing query remains three bounded queries: assignment labels, available
sessions and eligible exams. Workspace label resolution adds one bounded query
for all display labels and does not issue per-label or per-row queries.
