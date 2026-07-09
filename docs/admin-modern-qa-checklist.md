# Admin-Modern Keyboard QA Checklist

Date: 2026-06-01

## Run Commands

- php artisan view:cache
- npm run build
- php artisan route:list --path=admin-modern

## Scope

All current admin-modern GET routes/pages from route list (31 total).

Note on dynamic edit routes:

- Edit routes were verified using existing record id 1.
- Tested URLs: /admin-modern/academic/\*/1/edit and /admin-modern/users/1/edit.

## Check Matrix Criteria

- Skip link first focus
- Enter moves to #main-content
- Sidebar keyboard reachable
- Page controls keyboard reachable
- Focus visible
- Mobile/table overflow okay
- No JS errors
- No duplicate flash
- Route links resolve

## Summary

- Total routes tested: 31
- Passed all checks: 29
- Failed: 2

## Failing Findings

### 1) adminModernAcademicSubjectsEdit

- URL: /admin-modern/academic/subjects/1/edit
- HTTP status: 500
- Failed checks:
    - skip link first focus
    - Enter moves to #main-content
    - sidebar keyboard reachable
    - page controls keyboard reachable
    - focus visible
    - no JS errors
    - route links resolve

### 2) adminModernStudentsIndex

- URL: /admin-modern/students
- HTTP status: 200
- Failed checks:
    - mobile/table overflow okay

## Full Route Matrix

| Route Name                           | URL                                       | Status | Result | Failed Checks                                                                                                                                                       |
| ------------------------------------ | ----------------------------------------- | -----: | ------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| adminModernAcademicClassesIndex      | /admin-modern/academic/classes            |    200 | PASS   | -                                                                                                                                                                   |
| adminModernAcademicClassesCreate     | /admin-modern/academic/classes/create     |    200 | PASS   | -                                                                                                                                                                   |
| adminModernAcademicClassesEdit       | /admin-modern/academic/classes/1/edit     |    200 | PASS   | -                                                                                                                                                                   |
| adminModernAcademicDepartmentsIndex  | /admin-modern/academic/departments        |    200 | PASS   | -                                                                                                                                                                   |
| adminModernAcademicDepartmentsCreate | /admin-modern/academic/departments/create |    200 | PASS   | -                                                                                                                                                                   |
| adminModernAcademicDepartmentsEdit   | /admin-modern/academic/departments/1/edit |    200 | PASS   | -                                                                                                                                                                   |
| adminModernAcademicExamsIndex        | /admin-modern/academic/exams              |    200 | PASS   | -                                                                                                                                                                   |
| adminModernAcademicExamsCreate       | /admin-modern/academic/exams/create       |    200 | PASS   | -                                                                                                                                                                   |
| adminModernAcademicExamsEdit         | /admin-modern/academic/exams/1/edit       |    200 | PASS   | -                                                                                                                                                                   |
| adminModernAcademicGradesIndex       | /admin-modern/academic/grades             |    200 | PASS   | -                                                                                                                                                                   |
| adminModernAcademicGradesCreate      | /admin-modern/academic/grades/create      |    200 | PASS   | -                                                                                                                                                                   |
| adminModernAcademicGradesEdit        | /admin-modern/academic/grades/1/edit      |    200 | PASS   | -                                                                                                                                                                   |
| adminModernAcademicSectionsIndex     | /admin-modern/academic/sections           |    200 | PASS   | -                                                                                                                                                                   |
| adminModernAcademicSectionsCreate    | /admin-modern/academic/sections/create    |    200 | PASS   | -                                                                                                                                                                   |
| adminModernAcademicSectionsEdit      | /admin-modern/academic/sections/1/edit    |    200 | PASS   | -                                                                                                                                                                   |
| adminModernAcademicSessionsIndex     | /admin-modern/academic/sessions           |    200 | PASS   | -                                                                                                                                                                   |
| adminModernAcademicSessionsCreate    | /admin-modern/academic/sessions/create    |    200 | PASS   | -                                                                                                                                                                   |
| adminModernAcademicSessionsEdit      | /admin-modern/academic/sessions/1/edit    |    200 | PASS   | -                                                                                                                                                                   |
| adminModernAcademicSubjectsIndex     | /admin-modern/academic/subjects           |    200 | PASS   | -                                                                                                                                                                   |
| adminModernAcademicSubjectsCreate    | /admin-modern/academic/subjects/create    |    200 | PASS   | -                                                                                                                                                                   |
| adminModernAcademicSubjectsEdit      | /admin-modern/academic/subjects/1/edit    |    500 | FAIL   | skip link first focus; Enter moves to #main-content; sidebar keyboard reachable; page controls keyboard reachable; focus visible; no JS errors; route links resolve |
| adminModernAttendanceIndex           | /admin-modern/attendance                  |    200 | PASS   | -                                                                                                                                                                   |
| adminModernAttendanceMonthly         | /admin-modern/attendance/monthly          |    200 | PASS   | -                                                                                                                                                                   |
| adminModernAttendanceReport          | /admin-modern/attendance/report           |    200 | PASS   | -                                                                                                                                                                   |
| adminModernDashboard                 | /admin-modern/dashboard                   |    200 | PASS   | -                                                                                                                                                                   |
| adminModernStaffIndex                | /admin-modern/staff                       |    200 | PASS   | -                                                                                                                                                                   |
| adminModernStudentsIndex             | /admin-modern/students                    |    200 | FAIL   | mobile/table overflow okay                                                                                                                                          |
| adminModernTeachersIndex             | /admin-modern/teachers                    |    200 | PASS   | -                                                                                                                                                                   |
| adminModernUsersIndex                | /admin-modern/users                       |    200 | PASS   | -                                                                                                                                                                   |
| adminModernUsersCreate               | /admin-modern/users/create                |    200 | PASS   | -                                                                                                                                                                   |
| adminModernUsersEdit                 | /admin-modern/users/1/edit                |    200 | PASS   | -                                                                                                                                                                   |

## Signoff Notes

- This pass created documentation only.
- No application code, controller, database, route, or business logic changes were made.
