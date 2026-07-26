# Teacher Portal Dashboard Foundation

Implementation date: 2026-07-24  
Phase: Teacher Portal T2

## Architecture summary

T2 extends the dedicated T1 `teacher` session guard without changing authentication, identity, or authorization. `TeacherAuthController` obtains the authenticated `CultivationAdmin` exclusively from `Auth::guard('teacher')` and passes it to `TeacherDashboardService`. No teacher ID is accepted from request input.

`TeacherDashboardService` owns all dashboard queries. Blade templates receive presentation-ready, teacher-scoped data and perform no database queries.

## Layout structure

`resources/views/layouts/teacher.blade.php` is a dedicated teacher-only shell containing:

- responsive document and viewport structure;
- CSRF meta tag;
- institute-aware page title;
- skip link and visible focus treatment;
- teacher sidebar and top bar;
- flash and validation messages;
- main content and footer;
- keyboard-accessible mobile sidebar controls;
- reduced-motion behavior;
- teacher account menu and POST logout.

It does not inherit or render the admin layout.

Reusable Blade components:

- `x-teacher.nav-item`;
- `x-teacher.stat-card`;
- `x-teacher.empty-state`.

Teacher-specific sidebar and top-bar partials keep the layout extensible without exposing admin controls.

## Route group

The T1 names and paths remain unchanged:

- public `teacher.guest`: `GET /teacher/login`, `POST /teacher/login`;
- protected `teacher.auth`: `GET /teacher/dashboard`, `POST /teacher/logout`.

The `teacher` URI and route-name prefixes provide one protected group for future modules. T2 adds no placeholder routes.

## Navigation

The sidebar shows:

- Dashboard — active and linked;
- Attendance — disabled, Coming Soon;
- Results — disabled, Coming Soon;
- My Classes — disabled, Coming Soon;
- My Students — disabled, Coming Soon;
- Routine — disabled, Coming Soon;
- Profile — disabled, Coming Soon.

Disabled items are non-link elements with `aria-disabled`; they do not use `#` or point to existing admin routes. Quick Actions follow the same nonfunctional Coming Soon contract.

The top bar shows institute name, page title, teacher display name, a safe verified avatar or initials, dashboard link, profile placeholder, and POST logout. Email, mobile, password fields, database ID, and assignment internals are not displayed.

## Dashboard data sources

### Teacher header

- display name: authenticated `cultivation_admins.adminName`;
- public teacher ID/username: authenticated `cultivation_admins.adminUser`;
- avatar: authenticated `cultivation_admins.avatar`, only when it is a basename and the expected admin-avatar file exists;
- institute name: latest `server_configs.instituteName`, with `Cultivation` fallback;
- academic session: displayed only when all non-null authenticated-teacher assignment rows resolve to exactly one distinct `session_manages.session`;
- date: server-side application date.

Designation and department are omitted because `teacher_management` has no authoritative link to the operational `cultivation_admins` identity.

## Scoped statistics

All numeric statistics use `teacher_class_subjects.teacher_id = authenticated cultivation_admins.id`:

- Assigned Classes: `COUNT(DISTINCT class_id)`;
- Assigned Subjects: `COUNT(DISTINCT subject_id)`;
- Assigned Sections: `COUNT(DISTINCT section_id)`.

Distinct aggregation prevents gender or overlapping assignment rows from inflating counts.

### Omitted numeric statistics

- Assigned Students: Coming Soon because correct population requires combined session/class/section/group/gender and active-student semantics.
- Pending Result Work: Coming Soon because a teacher-facing pending-scope contract has not been approved.
- Pending Attendance: Coming Soon because no reliable pending-attendance workflow/status contract exists.

No global count, fabricated value, or misleading zero is shown.

## Assignment summary

The summary joins the authenticated teacher's `teacher_class_subjects` rows to:

- `session_manages`;
- `class_manages`;
- `section_manages`;
- `subjects`;
- `departments`.

It groups by academic identity fields to collapse duplicate gender-scope rows, orders deterministically, and returns at most eight rows. It is read-only and contains no student data, other-teacher data, or editing link. Missing optional section, subject, or group values render with professional scoped fallbacks.

## Recent activity

`result_lifecycle_events` is a reliable append-only source. The dashboard selects at most five rows where:

- `actor_id` equals the authenticated teacher ID; and
- `actor_role = teacher`.

Only a mapped safe action label and timestamp are rendered. Raw JSON, change sets, reasons, IP addresses, student IDs, scope IDs, and other actors' events are never sent to the view. Unknown actions receive a generic safe label.

## Security controls

- `teacher.auth` protects the dashboard.
- An admin legacy session alone cannot satisfy the teacher guard.
- All queries derive scope from the authenticated guard model.
- Assignment and activity queries include teacher identity predicates.
- Disabled menu state is presentation only; no module routes were enabled.
- Logout remains POST in the `web` middleware group with CSRF.
- No admin menus or admin-only actions are rendered.
- No user-controlled redirect or teacher scope is accepted.
- Avatar paths reject directory components and require an existing expected file.

## Performance considerations

- Aggregate statistics use one database aggregation query.
- Assignment rows are grouped and limited in SQL.
- Activity rows are filtered, ordered, projected, and limited in SQL.
- No student or marks collection is loaded.
- No N+1 relationship traversal occurs.
- Dashboard query count remains at or below eight as assignment volume grows.
- No cross-teacher cache was added; current query cost does not justify cache-consistency risk.

## Files changed

- `app/Http/Controllers/TeacherAuthController.php`
- `app/Services/TeacherDashboardService.php`
- `resources/views/layouts/teacher.blade.php`
- `resources/views/teacher/dashboard.blade.php`
- `resources/views/teacher/partials/sidebar.blade.php`
- `resources/views/teacher/partials/topbar.blade.php`
- `resources/views/components/teacher/nav-item.blade.php`
- `resources/views/components/teacher/stat-card.blade.php`
- `resources/views/components/teacher/empty-state.blade.php`
- `tests/Feature/TeacherPortalDashboardTest.php`
- `docs/teacher-portal-dashboard.md`

## Database changes

None. T2 adds no migration, table, column, record, or production-data mutation.

## Verification

Verification results:

- focused T1/T2 portal suite: 25 tests, 113 assertions, PASS;
- full application suite: 334 tests, 1,444 assertions, PASS;
- Result Engine authorization and lifecycle suites: PASS within the full run;
- `php artisan route:list`: PASS, 420 routes with the four established teacher routes intact;
- `php artisan view:cache`: PASS;
- PHP syntax and diff checks: PASS;
- `npm run build`: not required because no Vite source or input asset changed; T2 styling and minimal behavior are contained in the dedicated Blade layout.

## Remaining limitations

- No authoritative operational-account-to-HR-profile link exists for designation or profile photo.
- Student population and pending-work metrics require separately approved module semantics.
- Navigation modules remain intentionally disabled.
- Recent activity covers Result Engine lifecycle events only; attendance has no equivalent unified audit source.
- The assignment summary is limited to current `teacher_class_subjects` records and does not infer missing assignments from legacy text fields.

## Recommended T3 scope

Define and implement one teacher module through its existing authorization service—preferably read-only My Classes/My Students scope discovery before enabling mutation workflows. Approve exact population, active-status, session, group, section, gender, audit, and empty-state contracts before exposing student or marks data.
