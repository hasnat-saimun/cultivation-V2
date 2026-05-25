# Module Inventory

## Scope

This inventory is based on the implemented Laravel application surfaces in [routes/web.php](c:/xampp/htdocs/cultivation-V2/routes/web.php), the role-specific Blade navigation includes under [resources/views](c:/xampp/htdocs/cultivation-V2/resources/views), controller/model/service classes under [app](c:/xampp/htdocs/cultivation-V2/app), and the standalone legacy module under [job-placement](c:/xampp/htdocs/cultivation-V2/job-placement).

## Filament Resources

- No Filament resources, pages, or widgets were found.
- No `Filament` namespaces or `app/Filament` structure are present.
- This project is implemented as a traditional Laravel MVC application with Blade views.

## Navigation Inventory

Primary navigation is role-based and defined in Blade includes:

- General admin dashboard shell: [resources/views/cultivation/include.blade.php](c:/xampp/htdocs/cultivation-V2/resources/views/cultivation/include.blade.php)
- Full admin menu: [resources/views/cultivation/fullMenu.blade.php](c:/xampp/htdocs/cultivation-V2/resources/views/cultivation/fullMenu.blade.php)
- Teacher menu: [resources/views/cultivation/teacherMenu.blade.php](c:/xampp/htdocs/cultivation-V2/resources/views/cultivation/teacherMenu.blade.php)
- Account panel menu: [resources/views/account/include.blade.php](c:/xampp/htdocs/cultivation-V2/resources/views/account/include.blade.php)
- Academic panel menu: [resources/views/academic/include.blade.php](c:/xampp/htdocs/cultivation-V2/resources/views/academic/include.blade.php)
- Result panel menu: [resources/views/result/include.blade.php](c:/xampp/htdocs/cultivation-V2/resources/views/result/include.blade.php)

Main navigation groups surfaced by those files:

- Cultivation Panel
- Academic Panel
- Results Management
- Accounts Management
- Attendance Management
- Admission
- Teachers Panel
- Staffs
- Governing Body
- User Panel
- Configuration
- SMS Settings
- Designations
- User Guides
- Institute Info
- Academic Info
- Placement Cell
- Notice
- Gallery
- Result Manage
- Class
- Department
- Section
- Session
- Subject
- Exam
- Student Fees
- Cash Calculas

## Permissions Inventory

Primary permission logic is role-based and centered on `CultivationAdmin.userType` plus middleware:

- Role constants: [app/Models/CultivationAdmin.php](c:/xampp/htdocs/cultivation-V2/app/Models/CultivationAdmin.php)
- Role middleware: [app/Http/Middleware/Roles.php](c:/xampp/htdocs/cultivation-V2/app/Http/Middleware/Roles.php)
- Session/auth gate: [app/Http/Middleware/adminGuard.php](c:/xampp/htdocs/cultivation-V2/app/Http/Middleware/adminGuard.php)

Role map:

- `1`: Teacher Admin
- `2`: Cash Admin
- `3`: General Admin
- `>3`: higher privileged admin handled as general-or-higher by middleware logic

Observed access rules:

- `adminGuard`: required for the main authenticated application area.
- `Roles:1`: teacher-scoped features such as marks entry.
- `Roles:2`: cash admin and above for account features.
- `Roles:3`: general admin and above for high-privilege settings such as server configuration and SMS settings.
- Blade menus further hide or reveal items using `isTeacher()`, `isCash()`, and `isGeneral()` checks.

## Module Checklist

### Core Admin And Security

- [x] Dashboard And Authentication
  - Module Name: Dashboard And Authentication
  - Main Features: Admin login/logout, dashboard home, profile update, password change, admin avatar update, user registration entry points.
  - Related Models: `CultivationAdmin`
  - Related Resources: Blade dashboard shell in [resources/views/cultivation/include.blade.php](c:/xampp/htdocs/cultivation-V2/resources/views/cultivation/include.blade.php); no Filament resources.
  - Related Routes: `/`, `/login`, `/logout`, `/dashboard`, `/profile`, `/profile/save`, `/profile/password/save`, `/admin/avatar/update`

- [x] User And Role Administration
  - Module Name: User And Role Administration
  - Main Features: Admin user creation, edit, delete, user list, role assignment, teacher access class/subject setup.
  - Related Models: `CultivationAdmin`, `TeacherAdminAccess`, `TeacherClassSubject`
  - Related Resources: User menu in [resources/views/cultivation/fullMenu.blade.php](c:/xampp/htdocs/cultivation-V2/resources/views/cultivation/fullMenu.blade.php); no Filament resources.
  - Related Routes: `/admin/creation`, `/admin/edit/{id}`, `/admin/delete/{id}`, `/save/admin`, `/admin/list`, `/api/teacher/subjects`

- [x] Configuration And Branding
  - Module Name: Configuration And Branding
  - Main Features: Server configuration, logo, favicon, signatures, avatar assets, institutional branding assets.
  - Related Models: `ServerConfig`
  - Related Resources: Configuration link in [resources/views/cultivation/fullMenu.blade.php](c:/xampp/htdocs/cultivation-V2/resources/views/cultivation/fullMenu.blade.php); no Filament resources.
  - Related Routes: `/configuration`, `/configuration/save`, `/sign/save`, `/sign/del/{id}`, `/logo/save`, `/logo/del/{id}`, `/favicon/save`, `/favicon/del/{id}`, `/avatar/save`, `/avatar/del/{id}`

- [x] SMS Settings
  - Module Name: SMS Settings
  - Main Features: SMS provider configuration, enable toggle, connection status, test sending, rate lookup.
  - Related Models: `SmsSetting`
  - Related Resources: SMS Settings menu item in [resources/views/cultivation/fullMenu.blade.php](c:/xampp/htdocs/cultivation-V2/resources/views/cultivation/fullMenu.blade.php); service layer in [app/Services/SmsService.php](c:/xampp/htdocs/cultivation-V2/app/Services/SmsService.php); no Filament resources.
  - Related Routes: `/sms/settings`, `/sms/settings/save`, `/sms/settings/toggle`, `/sms/settings/status`, `/sms/settings/test`, `/sms/alpha-rate`

- [x] Documentation And Guides
  - Module Name: Documentation And Guides
  - Main Features: Internal user guides by admin role, brochure view and print.
  - Related Models: None
  - Related Resources: User Guides menu in [resources/views/cultivation/fullMenu.blade.php](c:/xampp/htdocs/cultivation-V2/resources/views/cultivation/fullMenu.blade.php) and [resources/views/cultivation/teacherMenu.blade.php](c:/xampp/htdocs/cultivation-V2/resources/views/cultivation/teacherMenu.blade.php); no Filament resources.
  - Related Routes: `/user-guide`, `/user-guide/general-admin`, `/user-guide/teacher-admin`, `/user-guide/cash-admin`, `/brochure`, `/brochure/print`

### Academic Content And Public Website

- [x] Home Slider And Homepage Content
  - Module Name: Home Slider And Homepage Content
  - Main Features: Home slider CRUD, homepage information blocks, chairman and principal media assets.
  - Related Models: `HomeSlider`, `HomeInfo`
  - Related Resources: Academic panel links in [resources/views/academic/include.blade.php](c:/xampp/htdocs/cultivation-V2/resources/views/academic/include.blade.php); no Filament resources.
  - Related Routes: `/home/slider`, `/home/slider/details`, `/home/slider/edit/{id}`, `/home/slider/delete/{id}`, `/home/info`, `/home/details`

- [x] Institute Information
  - Module Name: Institute Information
  - Main Features: About institute content, principal speech, ex-principals, managing committee, contact/support page, public institutional pages.
  - Related Models: `InstituteInfo`, `InstituteDetails`, `PrincipalSpeech`, `ExPrincipal`, `ManagingComittee`
  - Related Resources: Institute Info menu in [resources/views/academic/include.blade.php](c:/xampp/htdocs/cultivation-V2/resources/views/academic/include.blade.php); no Filament resources.
  - Related Routes: `/institute/info`, `/institute/details`, `/institute/principal/speech`, `/institute/principal/exList`, `/institute/committee`, `/about-us`, `/principal-speech`, `/exPrincipal`, `/our-comittee`, `/contact-us`

- [x] Gallery Management
  - Module Name: Gallery Management
  - Main Features: Photo gallery CRUD, video gallery CRUD, public gallery pages.
  - Related Models: `PhotoGallery`, `VideoGallery`
  - Related Resources: Gallery menu in [resources/views/academic/include.blade.php](c:/xampp/htdocs/cultivation-V2/resources/views/academic/include.blade.php); no Filament resources.
  - Related Routes: `/institute/photo`, `/photo/save`, `/photo/edit/{id}`, `/photo/delete/{id}`, `/institute/video`, `/video/save`, `/video/edit/{id}`, `/video/delete/{id}`, `/image/gallary`, `/video/gallary`

- [x] Notice Management
  - Module Name: Notice Management
  - Main Features: Notice create, edit, preview, list, delete.
  - Related Models: `Notice`
  - Related Resources: Notice menu in [resources/views/academic/include.blade.php](c:/xampp/htdocs/cultivation-V2/resources/views/academic/include.blade.php); no Filament resources.
  - Related Routes: `/notice/list`, `/notice/new`, `/notice/save`, `/notice/edit/{id}`, `/notice/update`, `/notice/delete/{id}`, `/notice/preview/{id}`

- [x] Placement Cell Content
  - Module Name: Placement Cell Content
  - Main Features: Job placement content management, needy student panel management, public placement pages.
  - Related Models: `PlacementCell`, `Placement`, `NeedyStudent`, `needyStudentPanel`
  - Related Resources: Placement Cell menu in [resources/views/academic/include.blade.php](c:/xampp/htdocs/cultivation-V2/resources/views/academic/include.blade.php); no Filament resources.
  - Related Routes: `/placement/jobPlacement`, `/placement/placementCell/save`, `/placement/needyStudentPanel`, `/placement/needyStudentPanel/save`, `/job/placement-cell`, `/job/needy-student`, `/placements`, `/placements/recalculate`

### Academic Operations

- [x] Academic Structure Setup
  - Module Name: Academic Structure Setup
  - Main Features: CRUD for classes, departments, sections, sessions, subjects, exams, and grade lists.
  - Related Models: `Classes`, `classManage`, `Department`, `sectionManage`, `session`, `sessionManage`, `Subject`, `Exam`, `GradeList`
  - Related Resources: Result panel setup menus in [resources/views/result/include.blade.php](c:/xampp/htdocs/cultivation-V2/resources/views/result/include.blade.php); no Filament resources.
  - Related Routes: `/class/create`, `/class/list`, `/department/create`, `/department/list`, `/section/create`, `/Section/list`, `/session/create`, `/session/list`, `/subject/create`, `/subject/list`, `/exam/create`, `/exam/list`, `/grade/create`, `/grade/list`

- [x] Syllabus And Planning
  - Module Name: Syllabus And Planning
  - Main Features: Syllabus management, semester plan management, public syllabus and schedule pages.
  - Related Models: `Syllabus`, `SemisterPlan`
  - Related Resources: Academic Info menu in [resources/views/academic/include.blade.php](c:/xampp/htdocs/cultivation-V2/resources/views/academic/include.blade.php); no Filament resources.
  - Related Routes: `/academic/syllabus`, `/academic/syllabus/save`, `/academic/semisterPlan`, `/academic/semisterPlan/save`, `/syllabus`, `/semister/plan`

- [x] Routine Management
  - Module Name: Routine Management
  - Main Features: Class routine management, exam routine management, result-specific routine generation, teacher-wise routine output, public class and exam schedules.
  - Related Models: `ClassRoutine`, `ClassRoutineItem`, `ExamRoutine`, `ExamRoutineItem`, `TeacherClassSubject`
  - Related Resources: Academic Info and Result Exam menus in [resources/views/academic/include.blade.php](c:/xampp/htdocs/cultivation-V2/resources/views/academic/include.blade.php) and [resources/views/result/include.blade.php](c:/xampp/htdocs/cultivation-V2/resources/views/result/include.blade.php); no Filament resources.
  - Related Routes: `/academic/classRoutine`, `/academic/examRoutine`, `/result/exam-routine/manage`, `/result/class-routine/manage`, `/result/class-routine/view/{id}`, `/class/schedule`, `/exam/schedule`

- [x] Internal Results
  - Module Name: Internal Results
  - Main Features: Internal result content management and public internal result viewing.
  - Related Models: `InternalResult`
  - Related Resources: Academic Info menu in [resources/views/academic/include.blade.php](c:/xampp/htdocs/cultivation-V2/resources/views/academic/include.blade.php); no Filament resources.
  - Related Routes: `/academic/internalResult`, `/academic/internalResult/save`, `/internal/result`

### Student, Teacher, And Staff Management

- [x] Student Admission And Records
  - Module Name: Student Admission And Records
  - Main Features: Student admission, profile view/edit, delete, bulk upload, template download, photo upload, bulk updates, PDF export.
  - Related Models: `StudentManagement`, `newAdmission`
  - Related Resources: Admission menu in [resources/views/cultivation/fullMenu.blade.php](c:/xampp/htdocs/cultivation-V2/resources/views/cultivation/fullMenu.blade.php); no Filament resources.
  - Related Routes: `/student/admit`, `/student/admit/confirm`, `/view/student/{stdId}`, `/student/edit/{stdId}`, `/student/list`, `/bulk-upload-students`, `/download-student-template`, `/student/photo/bulk`, `/student/bulk-update`, `/student/export/pdf`

- [x] Student Promotion And IDs
  - Module Name: Student Promotion And IDs
  - Main Features: Promotion workflows, revert promotion, student ID card generation, bulk ID cards.
  - Related Models: `StudentManagement`, `ResultArchive`
  - Related Resources: Admission menu in [resources/views/cultivation/fullMenu.blade.php](c:/xampp/htdocs/cultivation-V2/resources/views/cultivation/fullMenu.blade.php); no Filament resources.
  - Related Routes: `/student/promotion`, `/student/promotion/getData`, `/student/promotion/confirm`, `/promotion/revert/{stdId}`, `/student/idCard/{stdId}`, `/student/idcards/bulk`

- [x] Teacher Management
  - Module Name: Teacher Management
  - Main Features: Teacher admission, profile management, bulk upload, template/sample download, bulk photo upload, bulk update, PDF export, teacher list API.
  - Related Models: `TeacherManagement`, `TeacherClassSubject`, `CultivationAdmin`
  - Related Resources: Teachers Panel menu in [resources/views/cultivation/fullMenu.blade.php](c:/xampp/htdocs/cultivation-V2/resources/views/cultivation/fullMenu.blade.php); no Filament resources.
  - Related Routes: `/teacher/admit`, `/teacher/list`, `/teacher/edit/{profileId}`, `/teacher/bulk-upload`, `/teacher/bulk-photo-upload`, `/teacher/bulk-update`, `/teacher/export/pdf`, `/api/teachers/list`

- [x] Staff Management
  - Module Name: Staff Management
  - Main Features: Staff admission, profile management, bulk upload, template/sample download, bulk photo upload, bulk update, PDF export, staff list API.
  - Related Models: `StaffManagement`
  - Related Resources: Staffs menu in [resources/views/cultivation/fullMenu.blade.php](c:/xampp/htdocs/cultivation-V2/resources/views/cultivation/fullMenu.blade.php); no Filament resources.
  - Related Routes: `/staff/admit`, `/staff/list`, `/staff/edit/{profileId}`, `/staff/bulk-upload`, `/staff/bulk-photo-upload`, `/staff/bulk-update`, `/staff/export/pdf`, `/api/staff/list`

- [x] Designation Management
  - Module Name: Designation Management
  - Main Features: Designation CRUD, sort reorder, active toggle.
  - Related Models: `Designation`
  - Related Resources: Designations link in [resources/views/cultivation/fullMenu.blade.php](c:/xampp/htdocs/cultivation-V2/resources/views/cultivation/fullMenu.blade.php); no Filament resources.
  - Related Routes: `/designations`, `/designations/create`, `/designations/store`, `/designations/{id}/edit`, `/designations/{id}/update`, `/designations/{id}/delete`, `/designations/reorder`, `/designations/{id}/toggle`

### Results, Attendance, And Certificates

- [x] Marks Entry And Result Processing
  - Module Name: Marks Entry And Result Processing
  - Main Features: Marks entry, marksheet creation, subjectwise result, at-a-glance result, result summary, final result publish, transcript generation.
  - Related Models: `Marksheet`, `ResultPublish`, `InternalResult`, `TeacherClassSubject`
  - Related Resources: Result Manage menu in [resources/views/result/include.blade.php](c:/xampp/htdocs/cultivation-V2/resources/views/result/include.blade.php); teacher result links in [resources/views/cultivation/teacherMenu.blade.php](c:/xampp/htdocs/cultivation-V2/resources/views/cultivation/teacherMenu.blade.php); no Filament resources.
  - Related Routes: `/marks/add`, `/marks/add/getData`, `/marks/add/confirm`, `/marksheet/create`, `/marksheet/all`, `/marksheet/at-a-glance`, `/marksheet/result-summary`, `/marksheet/generate`, `/result/final-publish`, `/transcripts/bulk`, `/transcripts/bulk/pdf`, `/individual/result`

- [x] Result Archive
  - Module Name: Result Archive
  - Main Features: Historical result archive list and transcript access.
  - Related Models: `ResultArchive`
  - Related Resources: Result Archive links in [resources/views/cultivation/fullMenu.blade.php](c:/xampp/htdocs/cultivation-V2/resources/views/cultivation/fullMenu.blade.php); no Filament resources.
  - Related Routes: `/result-archive`, `/result-archive/transcript/{id}`

- [x] Attendance Management
  - Module Name: Attendance Management
  - Main Features: Attendance marking, daily reports, monthly sheet, CSV export, printing.
  - Related Models: `Attendance`
  - Related Resources: Attendance Management in [resources/views/cultivation/fullMenu.blade.php](c:/xampp/htdocs/cultivation-V2/resources/views/cultivation/fullMenu.blade.php) and [resources/views/cultivation/teacherMenu.blade.php](c:/xampp/htdocs/cultivation-V2/resources/views/cultivation/teacherMenu.blade.php); no Filament resources.
  - Related Routes: `/attendance`, `/attendance/fetch`, `/attendance/store`, `/attendance/report`, `/attendance/export`, `/attendance/print`, `/attendance/monthly`, `/attendance/monthly/export`, `/attendance/monthly/print`

- [x] Exam Documents
  - Module Name: Exam Documents
  - Main Features: Admit card generation, seat plan generation, attendance sheet generation.
  - Related Models: `Exam`, `ExamRoutine`, `ClassRoutine`
  - Related Resources: Result Exam menu in [resources/views/result/include.blade.php](c:/xampp/htdocs/cultivation-V2/resources/views/result/include.blade.php); no Filament resources.
  - Related Routes: `/admit/card/creation`, `/admit/card/getData`, `/admit-card/creation`, `/admit-card/getData`, `/attend/sheet/creation`, `/attend/sheet/getData`

- [x] Certificates
  - Module Name: Certificates
  - Main Features: Testimonial create/view/print/edit and transfer certificate create/view/print/edit.
  - Related Models: `Testimonial`, `TransferCertificate`
  - Related Resources: Certificate-related routes are linked from student/result workflows; no dedicated Filament resources.
  - Related Routes: `/testimonials/create/{admission}`, `/testimonials/store`, `/testimonials/{id}`, `/testimonials/{id}/print`, `/tc/create/{admission}`, `/tc/store`, `/tc/{id}`, `/tc/{id}/print`

### Finance And Accounts

- [x] Tuition Fees
  - Module Name: Tuition Fees
  - Main Features: Fee collection, invoice view, dues collection, dues dashboard, classwise fee setup, report generation, bulk delete.
  - Related Models: `tuitionFee`, `ClassWiseFeeSetup`, `feesManager`, `StudentManagement`
  - Related Resources: Student Fees menu in [resources/views/account/include.blade.php](c:/xampp/htdocs/cultivation-V2/resources/views/account/include.blade.php) and [resources/views/cultivation/teacherMenu.blade.php](c:/xampp/htdocs/cultivation-V2/resources/views/cultivation/teacherMenu.blade.php); no Filament resources.
  - Related Routes: `/add-tuition-fee`, `/save-tuition-fee`, `/tuition-fee-list`, `/edit-tuition-fee/{id}`, `/collect-tuition-due/{id}`, `/student/fees/dues-dashboard`, `/student/fees/classwise-setup`, `/student/fees/generate`, `/tuition-repot-generate/{id}`

- [x] Cash Ledger And Voucher Reporting
  - Module Name: Cash Ledger And Voucher Reporting
  - Main Features: Debit and credit entry, single voucher view, date-wise report generation, receipt generation.
  - Related Models: `cashManage`
  - Related Resources: Cash Calculas menu in [resources/views/account/include.blade.php](c:/xampp/htdocs/cultivation-V2/resources/views/account/include.blade.php); no Filament resources.
  - Related Routes: `/cash-calculas-from`, `/save-cash-calculas`, `/get-report`, `/single-report/{id}`, `/edit-cash-calculas/{id}`, `/calculas-repot-generate/{id}`, `/calculas-date-repot-generate`, `/calculas-date-repot-recipit`

- [x] Fee Definition Management
  - Module Name: Fee Definition Management
  - Main Features: Add, edit, update, and delete fee definitions used by student fee collection.
  - Related Models: `feesManager`
  - Related Resources: Add New Fees link in [resources/views/account/include.blade.php](c:/xampp/htdocs/cultivation-V2/resources/views/account/include.blade.php); no Filament resources.
  - Related Routes: `/add-fees`, `/edit-fees-data/{id}`, `/update-fees`, `/save-fees`, `/delete-fees-data/{id}`

### External And Legacy Modules

- [x] School Registration Requests
  - Module Name: School Registration Requests
  - Main Features: Register request submission, list, view, edit, logo upload, delete.
  - Related Models: `registerSchool`
  - Related Resources: Routed admin workflow; no Filament resources.
  - Related Routes: `/register/request`, `/register/list`, `/register/save`, `/register/view/{regId}`, `/register/edit/{regId}`, `/register/update`, `/register/del/{regId}`

- [x] School User Requests
  - Module Name: School User Requests
  - Main Features: User request form, listing, save flow.
  - Related Models: `SchoolUser`
  - Related Resources: Routed admin workflow; no Filament resources.
  - Related Routes: `/user/request`, `/user/list`, `/user/save`

- [x] Legacy Job Placement Standalone App
  - Module Name: Legacy Job Placement Standalone App
  - Main Features: Standalone procedural PHP admin/profile pages, notices, sliders, export, website settings, placement records.
  - Related Models: Not implemented as Laravel Eloquent models in this module.
  - Related Resources: Standalone PHP files under [job-placement](c:/xampp/htdocs/cultivation-V2/job-placement), not Filament resources.
  - Related Routes: Direct file entry points such as `/job-placement/index.php`, `/job-placement/admin_register.php`, `/job-placement/create-notice.php`, `/job-placement/manage-slider.php`

## Controller Inventory

Implemented controllers found in [app/Http/Controllers](c:/xampp/htdocs/cultivation-V2/app/Http/Controllers):

- `AcademicController`
- `admissionController`
- `AttendanceController`
- `BackofficeController`
- `cashCalculasController`
- `ClassController`
- `Controller`
- `CultivationController`
- `DepartmentController`
- `DesignationController`
- `DocsController`
- `ExamController`
- `FrontController`
- `GalleryController`
- `GradeListController`
- `individualController`
- `InstituteController`
- `MarksheetController`
- `NoticeController`
- `PlacementCellController`
- `PlacementController`
- `registerController`
- `ResultArchiveController`
- `schoolUserController`
- `SessionController`
- `SmsSettingsController`
- `StaffController`
- `StudentController`
- `SubjectController`
- `TeacherController`
- `TestimonialController`
- `TransferCertificateController`
- `tuitionController`

## Model Inventory

Implemented models found in [app/Models](c:/xampp/htdocs/cultivation-V2/app/Models):

- `Attendance`
- `cashManage`
- `Classes`
- `classManage`
- `ClassRoutine`
- `ClassRoutineItem`
- `ClassWiseFeeSetup`
- `CultivationAdmin`
- `Department`
- `Designation`
- `Exam`
- `ExamRoutine`
- `ExamRoutineItem`
- `ExPrincipal`
- `feesManager`
- `GradeList`
- `HomeInfo`
- `HomeSlider`
- `InstituteDetails`
- `InstituteInfo`
- `InternalResult`
- `ManagingComittee`
- `Marksheet`
- `NeedyStudent`
- `needyStudentPanel`
- `newAdmission`
- `Notice`
- `PhotoGallery`
- `Placement`
- `PlacementCell`
- `PrincipalSpeech`
- `registerSchool`
- `ReligiousSubjectDefault`
- `ResultArchive`
- `ResultPublish`
- `SchoolUser`
- `sectionManage`
- `SemisterPlan`
- `ServerConfig`
- `session`
- `sessionData`
- `sessionManage`
- `SmsSetting`
- `StaffManagement`
- `StudentManagement`
- `Subject`
- `Syllabus`
- `TeacherAdminAccess`
- `TeacherClassSubject`
- `TeacherManagement`
- `Testimonial`
- `TransferCertificate`
- `tuitionFee`
- `User`
- `VideoGallery`
- `Visitor`

## Service Inventory

Implemented services found in [app/Services](c:/xampp/htdocs/cultivation-V2/app/Services):

- `SmsService`

Additional gateway layer:

- `AlphaSmsGateway` in [app/Gateways/AlphaSmsGateway.php](c:/xampp/htdocs/cultivation-V2/app/Gateways/AlphaSmsGateway.php)

## Summary

- Filament resources: none found
- Permission model: `userType` plus middleware gates
- Main implementation style: route-driven Laravel MVC with Blade menus
- Legacy parallel module: standalone `job-placement` PHP app