# Result Engine Production Data Reconciliation

Database inspected: `cultivation_rhs_rehearsal`  
Inspection mode: read-only  
Date: 2026-07-24 (Asia/Dhaka)

## Executive conclusion

The rehearsal database contains 5,997 marks rows. Of these, 5,902 reference an existing `new_admissions.id`; 95 rows reference eight missing internal admission IDs.

Historical scope and promotion evidence survives for some missing IDs, but no inspected table contains enough evidence to recover the exact student master identity for all eight. No ID may be remapped safely from roll, class, or marks similarity alone.

Classification:

```text
HOLD — HISTORICAL RECORD IDENTITY COULD NOT BE RESOLVED
```

No data, schema, preflight rule, or migration state was changed during this investigation.

## Read-only scope inspected

Schema metadata and identity-bearing columns were inspected across:

- `marksheets.studentId`, academic scope columns, actors, marks, and timestamps;
- `new_admissions.id`, `stdId`, name, session, class, section, department, roll, status, and timestamps;
- `result_archives.student_id`, old academic identity, exam, snapshot JSON, and timestamps;
- `promotion_audit_logs.student_id`, old/new session/class/section/roll, correlation identity, actor, and timestamps;
- `exam_placements.studentId` and result scope;
- `attendances.student_id`, class, section, session, attendance date, and timestamps;
- `testimonials.admission_id`;
- `transfer_certificates.admission_id`;
- `student_management` and its legacy admission/profile fields;
- `internal_results`, `result_publishes`, exams, subjects, sessions, classes, and sections.

Generic primary-key matches in unrelated tables were not treated as student identity evidence.

## Complete Laravel project inventory

The application inventory contains:

- 59 Eloquent models under `app/Models`;
- 118 migration files under `database/migrations`;
- 9 seeders under `database/seeders`;
- 68 physical tables in the restored rehearsal database.

Eloquent models and their resolved tables:

| Area | Models / tables |
|---|---|
| Student/admission | `newAdmission` → `new_admissions`; `StudentManagement` → `student_management`; `Attendance` → `attendances`; `Testimonial` → `testimonials`; `TransferCertificate` → `transfer_certificates`; `NeedyStudent`, `needyStudentPanel` |
| Results | `Marksheet` → `marksheets`; `MarksScopeState` → `marks_scope_states`; `ResultPublish` → `result_publishes`; `ResultLifecycleEvent` → `result_lifecycle_events`; `InternalResult` → `internal_results`; `GradeList` → `grade_lists` |
| History/promotion/placement | `ResultArchive` → `result_archives`; `PromotionAuditLog` → `promotion_audit_logs`; `Placement` → `exam_placements`; `PlacementCell` → `placement_cells` |
| Academic masters | `Exam`, `Subject`, `classManage`, `Classes`, `sectionManage`, `sessionManage`, `sessionData`, `session`, `Department`, `ReligiousSubjectDefault` |
| Staff/access | `CultivationAdmin`, `TeacherManagement`, `StaffManagement`, `TeacherAdminAccess`, `TeacherClassSubject`, `Designation`, `User`, `SchoolUser` |
| Other application models | `cashManage`, `feesManager`, `tuitionFee`, `ClassWiseFeeSetup`, `ClassRoutine`, `ClassRoutineItem`, `ExamRoutine`, `ExamRoutineItem`, `Syllabus`, `SemisterPlan`, `InstituteInfo`, `InstituteDetails`, `ServerConfig`, `SmsSetting`, `HomeInfo`, `HomeSlider`, `Notice`, `PhotoGallery`, `VideoGallery`, `Visitor`, `registerSchool`, `ExPrincipal`, `ManagingComittee`, `PrincipalSpeech` |

The restored database's complete physical-table inventory is:

`academic_sessions`, `attendances`, `cash_manages`, `classes`, `class_manages`, `class_routines`, `class_routine_items`, `class_wise_fee_setups`, `cultivation_admins`, `departments`, `designations`, `exams`, `exam_placements`, `exam_routines`, `exam_routine_items`, `ex_principals`, `failed_jobs`, `fees_managers`, `grade_lists`, `home_infos`, `home_sliders`, `institute_details`, `institute_infos`, `internal_results`, `jobs`, `managing_comittees`, `marksheets`, `migrations`, `needy_students`, `needy_student_panels`, `new_admissions`, `notices`, `password_reset_tokens`, `personal_access_tokens`, `photo_galleries`, `placement_cells`, `principal_speeches`, `promotion_audit_logs`, `register_schools`, `religious_subject_defaults`, `result_archives`, `result_publishes`, `school_users`, `section_manages`, `semister_plans`, `server_configs`, `sessions`, `sessions_years`, `session_data`, `session_manages`, `settings`, `sms_settings`, `staff_management`, `student_management`, `subjects`, `syllabi`, `teacher_admin_accesses`, `teacher_classes`, `teacher_class_subjects`, `teacher_management`, `teacher_sections`, `teacher_subjects`, `testimonials`, `transfer_certificates`, `tuition_fees`, `users`, `video_galleries`, and `visitors`.

All 118 migrations were inventoried. Migrations directly material to this investigation are:

- `2023_11_11_004923_create_marksheets_table.php`;
- `2024_12_17_121833_create_new_admissions_table.php`;
- `2025_11_11_000001_create_attendances_table.php`;
- `2025_11_13_000001_create_testimonials_table.php`;
- `2025_11_15_000000_create_transfer_certificates_table.php`;
- `2025_12_22_000000_create_placements_table.php`;
- `2025_12_22_000001_rename_placements_to_exam_placements.php`;
- `2025_12_30_000000_create_result_archives_table.php`;
- `2025_12_31_000001_add_exam_id_to_result_archives_table.php`;
- `2026_02_08_000001_create_result_publishes_table.php`;
- `2026_03_04_000003_add_audit_fields_to_marksheets.php`;
- `2026_07_15_210000_create_promotion_audit_logs_table.php`;
- `2026_07_23_000002_add_centralized_promotion_identity.php`;
- the Phase C2 integrity migrations `2026_07_24_000004` through `000017`.

Seeder review:

- `AcademicLookupSeeder`, `ExamSeeder`, and `SubjectSeeder` can create/update academic lookup masters.
- No seeder creates `new_admissions` students or marks rows.
- No seeder deletes students, exams, subjects, marks, archives, promotion logs, or placements.
- Therefore the isolated marks row cannot be labelled demo data merely because seeders exist.

## Code-path evidence

### Marks identity is the internal admission key

`app/Models/newAdmission.php` defines:

```text
hasMany(Marksheet::class, 'studentId', 'id')
```

`ResultMarksDraftService`, `ResultMarksConfirmationService`, result builders, promotion preview, and placement recalculation consistently compare `marksheets.studentId` with `new_admissions.id`. `new_admissions.stdId` is a separate business/display identifier. A presentation field in `MarksheetController` may display `stdId`, but it does not change the stored marks identity contract.

### Student deletion code

`app/Http/Controllers/AdmissionController.php:948` (`delStudent`) loads `newAdmission::find($id)` and directly calls `delete()`. `app/Http/Controllers/AdmissionController.php:959` (`studentBulkDelete`) directly calls:

```text
newAdmission::whereIn('id', $ids)->delete()
```

Routes:

- `GET /student/del/{stdId}` → `delStudent`;
- `POST /student/bulk-delete` → `studentBulkDelete`.

Despite the route parameter name `stdId`, `delStudent()` passes it to `find()`, so the value is treated as the internal `new_admissions.id`.

Neither deletion path:

- archives the student profile;
- checks for dependent marks, attendance, result archive, promotion, or placement rows;
- removes or preserves a name snapshot;
- writes a student-deletion audit event.

The `marksheets` migration has no foreign key to `new_admissions`, so the hard delete succeeds while marks remain. This is a direct code-supported mechanism capable of producing the observed 95 orphan rows. Because the delete paths retain no audit record, the database cannot prove which operator invoked them or whether each deletion was intentional.

### Promotion and archive code

Legacy and centralized promotion paths insert `ResultArchive` and `PromotionAuditLog` records, then update the existing `new_admissions` row's session/class/section/roll. They do not create a replacement admission master and do not normally delete the student.

`ResultArchive` stores academic result JSON plus old class/session/section/roll, but not a durable student name or `stdId` snapshot. `PromotionAuditLog` likewise stores old/new scope and roll without a name. Consequently these tables prove historical lineage for five missing IDs but cannot reconstruct their exact master profiles after deletion.

### Placement code

Placement logic uses `exam_placements.studentId` as the internal admission ID and recalculation may delete/rebuild placement rows inside an explicitly selected placement scope. It does not delete admission masters or marks. The rehearsal table contains zero placement rows, so it supplies no recovery evidence.

### Exam and subject deletion code

`app/Http/Controllers/ExamController.php:105` (`delExam`) and `app/Http/Controllers/SubjectController.php:111` (`delSubject`) directly call Eloquent `delete()` on the selected master. Routes expose:

- `GET /exam/del/{itemId}`;
- `GET /subject/del/{itemId}`.

Neither action checks dependent marks or archives, creates a deletion audit record, nor preserves a master snapshot. `marksheets.examId` and `marksheets.subjectId` were created without foreign keys. These code paths can therefore leave marks rows pointing to deleted exam/subject masters, exactly matching the structural pattern of `marksheets.id = 1`.

This establishes historical master deletion as a supported and plausible cause. It does not prove which request deleted exam 1 or subject 1.

Relevant table counts:

| Table | Rows |
|---|---:|
| `marksheets` | 5,997 |
| `new_admissions` | 545 |
| `attendances` | 242 |
| `result_archives` | 339 |
| `promotion_audit_logs` | 183 |
| `exam_placements` | 0 |
| `result_publishes` | 0 |
| `internal_results` | 0 |
| `student_management` | 0 |
| `testimonials` | 1 |
| `transfer_certificates` | 1 |

## Finding 1 — orphan marks identities

### Exact count

| Measure | Count |
|---|---:|
| Total marks rows | 5,997 |
| Marks with existing admission master | 5,902 |
| Orphan marks rows | 95 |
| Distinct missing internal IDs | 8 |

Every orphan row still references an existing exam, subject, class, and section. The broken reference is specifically the admission/student master.

### Orphan scopes

| Missing ID | Rows | Historical scope(s) | Subjects | Evidence period | Classification |
|---:|---:|---|---:|---|---|
| 5 | 15 | Session 2025 / Class Six / Section A / Annual Examination: 14; Session 2025 / Class Seven / Section A / Half-yearly: 1 | 14 distinct | 2025-12-22 to 2026-07-22 | PRESERVED HISTORICAL ORPHAN |
| 6 | 15 | Same two scopes as ID 5 | 14 distinct | 2025-12-22 to 2026-07-22 | PRESERVED HISTORICAL ORPHAN |
| 48 | 14 | Session 2025 / Class Six / Section A / Annual Examination | 14 | 2025-12-22 | PRESERVED HISTORICAL ORPHAN |
| 64 | 14 | Session 2025 / Class Six / Section A / Annual Examination | 14 | 2025-12-22 | PRESERVED HISTORICAL ORPHAN |
| 257 | 14 | Session 2025 / Class Seven / Section A / Annual Examination | 14 | 2025-12-22 | PRESERVED HISTORICAL ORPHAN |
| 332 | 15 | Session 2025 / Class Nine / Section A / Annual Examination | 15 | 2025-12-22 to 2025-12-24 | UNRESOLVED MANUAL DECISION |
| 344 | 4 | Session 2025 / Class Seven / Section A / Annual Examination | 4 | 2025-12-22 | UNRESOLVED MANUAL DECISION |
| 347 | 4 | Session 2025 / Class Seven / Section A / Annual Examination | 4 | 2025-12-22 | UNRESOLVED MANUAL DECISION |

The 14–15 row groups are consistent with historical subject rosters. The four-row groups are partial histories. Completeness does not prove the student's name or justify a new admission mapping.

### Historical recovery sources

| Missing ID | Attendance | Result archive | Promotion audit | Placement | Certificate/testimonial | Recoverable evidence |
|---:|---:|---:|---:|---:|---:|---|
| 5 | 4 | 1 | 1 | 0 | 0 | Old Class Six roll 05; promoted to Class Seven roll 16 |
| 6 | 4 | 1 | 1 | 0 | 0 | Old Class Six roll 07; promoted to Class Seven roll 32 |
| 48 | 0 | 2 | 1 | 0 | 0 | Historical Class Six roll 34; later section movement recorded without roll |
| 64 | 0 | 2 | 1 | 0 | 0 | Historical Class Six roll 39; later section movement recorded without roll |
| 257 | 0 | 1 | 1 | 0 | 0 | Historical Class Seven roll 05 |
| 332 | 0 | 0 | 0 | 0 | 0 | Marks scope only |
| 344 | 0 | 0 | 0 | 0 | 0 | Partial marks scope only |
| 347 | 0 | 0 | 0 | 0 | 0 | Partial marks scope only |

All seven matching archive rows contain valid result JSON, but their payloads contain subjects and aggregate results—not student names, master IDs, or external admission numbers.

`new_admissions.stdId`, testimonial, transfer-certificate, placement, legacy student-management, archive payload, and promotion evidence produced no exact master identity for these IDs.

One current admission occupies Session 2025 / Class Seven / Section A / roll 05, matching ID 257's historical tuple. It has a different internal ID. This is not sufficient evidence of identity because rolls may be reused or reassigned and the historical archive contains no name. It must not be auto-linked.

### Academic risks

- Remapping to the wrong current admission would attach marks and historical promotion evidence to another student.
- Deleting the rows would erase potentially genuine academic evidence.
- Creating fabricated admission masters would invent names, identifiers, and enrollment history.
- Leaving the records unresolved prevents strict referential certification and blocks the integrity preflight/migration sequence.

### Recommended remediation

No remediation was executed. The recommended approval workflow is:

1. Obtain the production backup immediately preceding the deletion/promotion operations, if available.
2. Compare the missing internal IDs directly in that backup's `new_admissions` table.
3. Cross-check official admission register, class roster, result sheet, and promotion register using session/class/section/roll.
4. Require two independent identity attributes, preferably original internal ID plus `stdId` or full name and guardian/admission evidence.
5. For IDs 332, 344, and 347, require a manual academic-owner decision because no secondary database history exists.
6. Record every approved decision in a signed reconciliation manifest before any mutation phase.
7. Preserve unresolved rows as historical orphans if identity cannot be proven; do not guess or reuse a current ID.

## Finding 2 — isolated `marksheets.id = 1`

Limited evidence:

| Field | Value |
|---|---|
| Marks row | 1 |
| Student | Internal ID 1; current admission master exists |
| Scope | Session 1 / Class 2 / Section 1 |
| Exam | ID 1 — missing |
| Subject | ID 1 — missing |
| Components | CQ 17, MCQ 20, Practical 0 |
| Stored total/result | 37 / F / 0 |
| Actor attribution | All actor fields NULL |
| Created/updated | 2025-09-03 07:51:56 |

Additional observations:

- It is the only marks row using `examId = 1`.
- It is the only marks row using `subjectId = 1`.
- It is the only marks row created at that exact timestamp.
- The referenced student, session, class, and section still exist.
- No surviving exam or subject master proves the academic meaning of IDs 1/1.

### Classification

```text
UNRESOLVED MANUAL DECISION
```

The isolation, missing masters, null actors, and early timestamp are compatible with a test/demo row, but they do not prove it. It therefore cannot be classified as a confirmed test/demo record without external evidence.

### Academic risks and recommendation

- Treating the row as genuine without its exam/subject masters could contaminate scope initialization.
- Deleting it as “test data” without evidence could destroy a genuine historical attempt.
- Reassigning exam or subject IDs would fabricate academic meaning.

Locate an older database backup, exam setup register, subject setup export, or contemporaneous marks-entry record. An authorized academic owner must decide whether this was demonstrably test/demo data or whether its missing masters must be recovered. Until then, preserve it unchanged.

## Classification summary

| Category | Findings |
|---|---|
| RECOVERABLE HISTORICAL MASTER RECORD | None proven from the inspected database |
| PRESERVED HISTORICAL ORPHAN | IDs 5, 6, 48, 64, 257 — historical lineage exists, exact master identity does not |
| CONFIRMED TEST / DEMO RECORD | None proven |
| UNRESOLVED MANUAL DECISION | IDs 332, 344, 347; isolated marks row ID 1; any proposed mapping for the other five |

## Approval boundary

This report is diagnostic only. A separate explicitly approved remediation phase is required before any insert, update, delete, remap, migration, or preflight-rule change.
