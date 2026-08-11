<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BackofficeController;
use App\Http\Controllers\AcademicController;
use App\Http\Controllers\CultivationController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\FrontController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\GradeListController;
use App\Http\Controllers\InstituteController;
use App\Http\Controllers\MarksheetController;
use App\Http\Controllers\NoticeController;
use App\Http\Controllers\PlacementCellController;
use App\Http\Controllers\PublicAssetController;
use App\Http\Controllers\individualController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\AdmissionController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\cashCalculasController;
use App\Http\Controllers\tuitionController;
use App\Http\Controllers\registerController;
use App\Http\Controllers\schoolUserController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AcademicAttendanceController;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\CurriculumSubjectMappingController;
use App\Http\Controllers\TeacherAuthController;
use App\Http\Controllers\TeacherResultController;
use App\Http\Controllers\TeacherAttendanceController;
use App\Http\Controllers\TeacherAcademicController;
use App\Http\Controllers\TeacherProfileController;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\cultivationAdmin;
use App\Http\Middleware\EncryptCookies;
use App\Http\Middleware\PreventRequestsDuringMaintenance;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\TrimStrings;
use App\Http\Middleware\TrustHosts;
use App\Http\Middleware\TrustProxies;
use App\Http\Middleware\ValidateSignature;
use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Middleware\ModeratorAdmin;
use App\Http\Middleware\SuperAdmin;

// ...existing code...
use App\Http\Middleware\BasicAdmin;
use App\Http\Middleware\DealerAdmin;
use App\Http\Middleware\adminGuard;





/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Testimonial routes
Route::get('testimonials/create/{admission}', [App\Http\Controllers\TestimonialController::class, 'create'])->name('testimonials.create');
Route::post('testimonials/store', [App\Http\Controllers\TestimonialController::class, 'store'])->name('testimonials.store');
Route::get('testimonials/{id}', [App\Http\Controllers\TestimonialController::class, 'show'])->name('testimonials.show');
Route::get('testimonials/{id}/print', [App\Http\Controllers\TestimonialController::class, 'print'])->name('testimonials.print');
Route::get('testimonials/{id}/edit', [App\Http\Controllers\TestimonialController::class, 'edit'])->name('testimonials.edit');
Route::post('testimonials/update', [App\Http\Controllers\TestimonialController::class, 'update'])->name('testimonials.update');
// Transfer Certificate (TC) routes
Route::get('tc/create/{admission}', [App\Http\Controllers\TransferCertificateController::class, 'create'])->name('tc.create');
Route::post('tc/store', [App\Http\Controllers\TransferCertificateController::class, 'store'])->name('tc.store');
Route::get('tc/{id}', [App\Http\Controllers\TransferCertificateController::class, 'show'])->name('tc.show');
Route::get('tc/{id}/print', [App\Http\Controllers\TransferCertificateController::class, 'print'])->name('tc.print');
Route::get('tc/{id}/edit', [App\Http\Controllers\TransferCertificateController::class, 'edit'])->name('tc.edit');
Route::post('tc/update', [App\Http\Controllers\TransferCertificateController::class, 'update'])->name('tc.update');
// ...existing code...


Route::get('/',[
    FrontController::class,
    'adminLogin'
])->name('admin.login.entry');

// Static asset compatibility routes for Apache environments where static
// file resolution falls through to Laravel. Controller actions remain safe
// when routes are cached; path validation is centralized in the service.
Route::get('/favicon.ico', [PublicAssetController::class, 'favicon']);
Route::get('/back-office/{path}', [PublicAssetController::class, 'backOffice'])->where('path', '.*');
Route::get('/loginPart/{path}', [PublicAssetController::class, 'loginPart'])->where('path', '.*');
Route::get('/public/{path}', [PublicAssetController::class, 'publicAsset'])->where('path', '.*');

// Public brochure pages (no login required)
Route::get('/brochure', [DocsController::class, 'brochure'])->name('brochure');
Route::get('/brochure/print', [DocsController::class, 'brochurePrint'])->name('brochure.print');

Route::post('/login/confirm',[
    FrontController::class ,
    'cultivationLogin'
])->name('cultivationLogin');

Route::get('/login',[
    FrontController::class,
    'adminLogin'
])->name('adminLogin');

Route::prefix('teacher')->name('teacher.')->group(function () {
    Route::middleware('teacher.guest')->group(function () {
        Route::get('/login', [TeacherAuthController::class, 'create'])->name('login');
        Route::post('/login', [TeacherAuthController::class, 'store'])->name('login.submit');
    });

    Route::middleware('teacher.auth')->group(function () {
        Route::get('/dashboard', [TeacherAuthController::class, 'dashboard'])->name('dashboard');
        Route::get('/attendance', [TeacherAttendanceController::class, 'index'])->name('attendance.index');
        Route::get('/attendance/workspace', [TeacherAttendanceController::class, 'workspace'])->name('attendance.workspace');
        Route::post('/attendance/load', [TeacherAttendanceController::class, 'load'])->name('attendance.load');
        Route::post('/attendance/save', [TeacherAttendanceController::class, 'save'])
            ->middleware('throttle:30,1')->name('attendance.save');
        Route::get('/classes', [TeacherAcademicController::class, 'classes'])->name('classes.index');
        Route::get('/students', [TeacherAcademicController::class, 'students'])->name('students.index');
        Route::get('/students/{student}', [TeacherAcademicController::class, 'student'])
            ->whereNumber('student')->name('students.show');
        Route::get('/profile', [TeacherProfileController::class, 'show'])->name('profile.show');
        Route::get('/profile/edit', [TeacherProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [TeacherProfileController::class, 'update'])->name('profile.update');
        Route::get('/settings/password', [TeacherProfileController::class, 'passwordEdit'])->name('password.edit');
        Route::put('/settings/password', [TeacherProfileController::class, 'passwordUpdate'])
            ->middleware('throttle:6,1')->name('password.update');
        Route::get('/results', [TeacherResultController::class, 'index'])->name('results.index');
        Route::get('/results/workspace', [TeacherResultController::class, 'workspace'])->name('results.workspace');
        Route::post('/results/load', [TeacherResultController::class, 'load'])->name('results.load');
        Route::post('/results/draft', [TeacherResultController::class, 'draft'])
            ->middleware('throttle:30,1')->name('results.draft');
        Route::post('/results/confirm', [TeacherResultController::class, 'confirm'])
            ->middleware('throttle:12,1')->name('results.confirm');
        Route::get('/results/subject-marksheet/print', [TeacherResultController::class, 'subjectMarksheetPrint'])
            ->name('results.subject-marksheet.print');
        Route::get('/results/subject-marksheet/pdf', [TeacherResultController::class, 'subjectMarksheetPdf'])
            ->name('results.subject-marksheet.pdf');
        Route::post('/logout', [TeacherAuthController::class, 'destroy'])->name('logout');
    });
});

Route::get('/logout',[
    FrontController::class,
    'adminLogout'
])->name('adminLogout');

Route::post('/register',[
    FrontController::class ,
    'adminRegister'
])->name('adminRegister');

Route::post('/bulk-upload-students', [StudentController::class, 'bulkUploadStudents'])->name('bulkUploadStudents');
Route::get('/download-student-template', [StudentController::class, 'downloadStudentTemplate'])->name('downloadStudentTemplate');


Route::middleware(['adminGuard'])->group (function(){
        // Result Archive
        Route::get('/result-archive', [\App\Http\Controllers\ResultArchiveController::class, 'index'])->name('resultArchive');
        Route::get('/result-archive/transcript/{id}', [\App\Http\Controllers\ResultArchiveController::class, 'transcript'])
            ->name('resultArchive.transcript');
    // Documentation route (internal user guide)
    Route::get('/user-guide', [DocsController::class, 'userGuide'])->name('userGuide');
    Route::get('/user-guide/general-admin', [DocsController::class, 'userGuideGeneralAdmin'])->name('userGuide.generalAdmin');
    Route::get('/user-guide/teacher-admin', [DocsController::class, 'userGuideTeacherAdmin'])->name('userGuide.teacherAdmin');
    Route::get('/user-guide/cash-admin', [DocsController::class, 'userGuideCashAdmin'])->name('userGuide.cashAdmin');
    // Attendance (teacher & general access)
    Route::get('/attendance', [AttendanceController::class,'index'])->name('attendanceIndex');
    Route::post('/attendance/fetch', [AttendanceController::class,'fetch'])->name('attendanceFetch');
    Route::post('/attendance/store', [AttendanceController::class,'store'])->name('attendanceStore');
    Route::get('/attendance/report', [AttendanceController::class,'report'])->name('attendanceReport');
    Route::get('/attendance/export', [AttendanceController::class,'exportCsv'])->name('attendanceExport');
    Route::get('/attendance/print', [AttendanceController::class,'print'])->name('attendancePrint');
    Route::get('/attendance/monthly', [AttendanceController::class,'monthly'])->name('attendanceMonthly');
    Route::get('/attendance/monthly/export', [AttendanceController::class,'monthlyExport'])->name('attendanceMonthlyExport');
    Route::get('/attendance/monthly/print', [AttendanceController::class,'monthlyPrint'])->name('attendanceMonthlyPrint');
    
    //Cultivation Part
    
    Route::get('/dashboard',[
        CultivationController::class,
        'cultivationIndex'
    ])->name('cultivationIndex');

    Route::get('/admin-modern/dashboard', function (CultivationController $controller) {
        $legacyResponse = $controller->cultivationIndex();

        if ($legacyResponse instanceof \Illuminate\View\View) {
            return view('admin-modern.dashboard', $legacyResponse->getData());
        }

        return $legacyResponse;
    })->name('adminModernDashboard');

    Route::get('/admin-modern/attendance', function (AttendanceController $controller) {
        $legacyResponse = $controller->index();

        if ($legacyResponse instanceof \Illuminate\View\View) {
            return view('admin-modern.attendance.index', $legacyResponse->getData());
        }

        return $legacyResponse;
    })->name('adminModernAttendanceIndex');

    Route::post('/admin-modern/attendance/fetch', function (AttendanceController $controller) {
        $legacyResponse = $controller->fetch(request());

        if ($legacyResponse instanceof \Illuminate\View\View) {
            return view('admin-modern.attendance.mark', $legacyResponse->getData());
        }

        return $legacyResponse;
    })->name('adminModernAttendanceFetch');

    Route::get('/admin-modern/attendance/report', function (AttendanceController $controller) {
        $legacyResponse = $controller->report(request());

        if ($legacyResponse instanceof \Illuminate\View\View) {
            return view('admin-modern.attendance.report', $legacyResponse->getData());
        }

        return $legacyResponse;
    })->name('adminModernAttendanceReport');

    Route::get('/admin-modern/attendance/monthly', function (AttendanceController $controller) {
        $legacyResponse = $controller->monthly(request());

        if ($legacyResponse instanceof \Illuminate\View\View) {
            return view('admin-modern.attendance.monthly', $legacyResponse->getData());
        }

        return $legacyResponse;
    })->name('adminModernAttendanceMonthly');

    Route::get('/admin-modern/users', function (CultivationController $controller) {
        $legacyResponse = $controller->userRegList();

        if ($legacyResponse instanceof \Illuminate\View\View) {
            return view('admin-modern.users.index', $legacyResponse->getData());
        }

        return $legacyResponse;
    })->middleware(\App\Http\Middleware\Roles::class.':3')->name('adminModernUsersIndex');

    Route::get('/admin-modern/users/create', function (CultivationController $controller) {
        $legacyResponse = $controller->userType();

        if ($legacyResponse instanceof \Illuminate\View\View) {
            return view('admin-modern.users.create', $legacyResponse->getData());
        }

        return $legacyResponse;
    })->middleware(\App\Http\Middleware\Roles::class.':3')->name('adminModernUsersCreate');

    Route::get('/admin-modern/users/{id}/edit', function (CultivationController $controller, $id) {
        $legacyResponse = $controller->editUser($id);

        if ($legacyResponse instanceof \Illuminate\View\View) {
            return view('admin-modern.users.edit', $legacyResponse->getData());
        }

        return $legacyResponse;
    })->middleware(\App\Http\Middleware\Roles::class.':3')->name('adminModernUsersEdit');

    Route::get('/admin-modern/students', function (AdmissionController $controller) {
        $legacyResponse = $controller->studentList(request());

        if ($legacyResponse instanceof \Illuminate\View\View) {
            return view('admin-modern.students.index', $legacyResponse->getData());
        }

        return $legacyResponse;
    })->name('adminModernStudentsIndex');

    Route::get('/admin-modern/staff', function (StaffController $controller) {
        $legacyResponse = $controller->staffList();

        if ($legacyResponse instanceof \Illuminate\View\View) {
            return view('admin-modern.staff.index', $legacyResponse->getData());
        }

        return $legacyResponse;
    })->name('adminModernStaffIndex');

    Route::get('/admin-modern/teachers', function (TeacherController $controller) {
        $legacyResponse = $controller->teacherList();

        if ($legacyResponse instanceof \Illuminate\View\View) {
            $viewData = $legacyResponse->getData();
            $search = trim((string) request()->query('search', ''));

            if ($search !== '' && isset($viewData['profileData'])) {
                $needle = mb_strtolower($search);
                $profileData = collect($viewData['profileData'])->filter(function ($teacher) use ($needle) {
                    $teacherId = mb_strtolower((string) ($teacher->teacherId ?? ''));
                    $firstName = mb_strtolower((string) ($teacher->firstName ?? ''));
                    $lastName = mb_strtolower((string) ($teacher->lastName ?? ''));
                    $mobile = mb_strtolower((string) ($teacher->mobile ?? ''));
                    $email = mb_strtolower((string) ($teacher->email ?? ''));

                    return str_contains($teacherId, $needle)
                        || str_contains($firstName, $needle)
                        || str_contains($lastName, $needle)
                        || str_contains($mobile, $needle)
                        || str_contains($email, $needle);
                })->values();

                $viewData['profileData'] = $profileData;
            }

            return view('admin-modern.teachers.index', $viewData);
        }

        return $legacyResponse;
    })->name('adminModernTeachersIndex');

    Route::get('/admin-modern/academic/classes', function (individualController $controller) {
        $legacyResponse = $controller->allClasses();

        if ($legacyResponse instanceof \Illuminate\View\View) {
            return view('admin-modern.academic.classes.index', $legacyResponse->getData());
        }

        return $legacyResponse;
    })->name('adminModernAcademicClassesIndex');

    Route::get('/admin-modern/academic/classes/create', function (individualController $controller) {
        $legacyResponse = $controller->createClass();

        if ($legacyResponse instanceof \Illuminate\View\View) {
            return view('admin-modern.academic.classes.create', $legacyResponse->getData());
        }

        return $legacyResponse;
    })->name('adminModernAcademicClassesCreate');

    Route::get('/admin-modern/academic/classes/{itemId}/edit', function (individualController $controller, $itemId) {
        $legacyResponse = $controller->editClass($itemId);

        if ($legacyResponse instanceof \Illuminate\View\View) {
            return view('admin-modern.academic.classes.edit', $legacyResponse->getData());
        }

        return $legacyResponse;
    })->name('adminModernAcademicClassesEdit');

    Route::get('/admin-modern/academic/departments', function (individualController $controller) {
        $legacyResponse = $controller->allDepartment();

        if ($legacyResponse instanceof \Illuminate\View\View) {
            return view('admin-modern.academic.departments.index', $legacyResponse->getData());
        }

        return $legacyResponse;
    })->name('adminModernAcademicDepartmentsIndex');

    Route::get('/admin-modern/academic/departments/create', function (individualController $controller) {
        $legacyResponse = $controller->createDepartment();

        if ($legacyResponse instanceof \Illuminate\View\View) {
            return view('admin-modern.academic.departments.create', $legacyResponse->getData());
        }

        return $legacyResponse;
    })->name('adminModernAcademicDepartmentsCreate');

    Route::get('/admin-modern/academic/departments/{itemId}/edit', function (individualController $controller, $itemId) {
        $legacyResponse = $controller->editDepartment($itemId);

        if ($legacyResponse instanceof \Illuminate\View\View) {
            return view('admin-modern.academic.departments.edit', $legacyResponse->getData());
        }

        return $legacyResponse;
    })->name('adminModernAcademicDepartmentsEdit');

    Route::get('/admin-modern/academic/sections', function (individualController $controller) {
        $legacyResponse = $controller->allSection();

        if ($legacyResponse instanceof \Illuminate\View\View) {
            return view('admin-modern.academic.sections.index', $legacyResponse->getData());
        }

        return $legacyResponse;
    })->name('adminModernAcademicSectionsIndex');

    Route::get('/admin-modern/academic/sections/create', function (individualController $controller) {
        $legacyResponse = $controller->createSection();

        if ($legacyResponse instanceof \Illuminate\View\View) {
            return view('admin-modern.academic.sections.create', $legacyResponse->getData());
        }

        return $legacyResponse;
    })->name('adminModernAcademicSectionsCreate');

    Route::get('/admin-modern/academic/sections/{itemId}/edit', function (individualController $controller, $itemId) {
        $legacyResponse = $controller->editSection($itemId);

        if ($legacyResponse instanceof \Illuminate\View\View) {
            return view('admin-modern.academic.sections.edit', $legacyResponse->getData());
        }

        return $legacyResponse;
    })->name('adminModernAcademicSectionsEdit');

    Route::get('/admin-modern/academic/sessions', function (individualController $controller) {
        $legacyResponse = $controller->allSession();

        if ($legacyResponse instanceof \Illuminate\View\View) {
            return view('admin-modern.academic.sessions.index', $legacyResponse->getData());
        }

        return $legacyResponse;
    })->name('adminModernAcademicSessionsIndex');

    Route::get('/admin-modern/academic/sessions/create', function (individualController $controller) {
        $legacyResponse = $controller->createSession();

        if ($legacyResponse instanceof \Illuminate\View\View) {
            return view('admin-modern.academic.sessions.create', $legacyResponse->getData());
        }

        return $legacyResponse;
    })->name('adminModernAcademicSessionsCreate');

    Route::get('/admin-modern/academic/sessions/{itemId}/edit', function (individualController $controller, $itemId) {
        $legacyResponse = $controller->editSession($itemId);

        if ($legacyResponse instanceof \Illuminate\View\View) {
            return view('admin-modern.academic.sessions.edit', $legacyResponse->getData());
        }

        return $legacyResponse;
    })->name('adminModernAcademicSessionsEdit');

    Route::get('/admin-modern/academic/subjects', function (SubjectController $controller) {
        $legacyResponse = $controller->allSubject();

        if ($legacyResponse instanceof \Illuminate\View\View) {
            return view('admin-modern.academic.subjects.index', $legacyResponse->getData());
        }

        return $legacyResponse;
    })->name('adminModernAcademicSubjectsIndex');

    Route::get('/admin-modern/academic/subjects/create', function (SubjectController $controller) {
        $legacyResponse = $controller->createSubject();

        if ($legacyResponse instanceof \Illuminate\View\View) {
            return view('admin-modern.academic.subjects.create', $legacyResponse->getData());
        }

        return $legacyResponse;
    })->name('adminModernAcademicSubjectsCreate');

    Route::get('/admin-modern/academic/subjects/{itemId}/edit', function (SubjectController $controller, $itemId) {
        if (! \App\Models\Subject::find($itemId)) {
            return view('admin-modern.academic.subjects.edit', [
                'item' => null,
                'classList' => collect([]),
                'defaultClassIds' => [],
            ]);
        }

        $legacyResponse = $controller->editSubject($itemId);

        if ($legacyResponse instanceof \Illuminate\View\View) {
            return view('admin-modern.academic.subjects.edit', $legacyResponse->getData());
        }

        return $legacyResponse;
    })->name('adminModernAcademicSubjectsEdit');

    Route::get('/admin-modern/academic/grades', function (GradeListController $controller) {
        $legacyResponse = $controller->allGrade();

        if ($legacyResponse instanceof \Illuminate\View\View) {
            return view('admin-modern.academic.grades.index', $legacyResponse->getData());
        }

        return $legacyResponse;
    })->name('adminModernAcademicGradesIndex');

    Route::get('/admin-modern/academic/grades/create', function (GradeListController $controller) {
        $legacyResponse = $controller->createGrade();

        if ($legacyResponse instanceof \Illuminate\View\View) {
            return view('admin-modern.academic.grades.create', $legacyResponse->getData());
        }

        return $legacyResponse;
    })->name('adminModernAcademicGradesCreate');

    Route::get('/admin-modern/academic/grades/{itemId}/edit', function (GradeListController $controller, $itemId) {
        $legacyResponse = $controller->editGrade($itemId);

        if ($legacyResponse instanceof \Illuminate\View\View) {
            return view('admin-modern.academic.grades.edit', $legacyResponse->getData());
        }

        return $legacyResponse;
    })->name('adminModernAcademicGradesEdit');

    Route::get('/admin-modern/academic/exams', function (ExamController $controller) {
        $legacyResponse = $controller->allExam();

        if ($legacyResponse instanceof \Illuminate\View\View) {
            return view('admin-modern.academic.exams.index', $legacyResponse->getData());
        }

        return $legacyResponse;
    })->name('adminModernAcademicExamsIndex');

    Route::get('/admin-modern/academic/exams/create', function (ExamController $controller) {
        $legacyResponse = $controller->createExam();

        if ($legacyResponse instanceof \Illuminate\View\View) {
            return view('admin-modern.academic.exams.create', $legacyResponse->getData());
        }

        return $legacyResponse;
    })->name('adminModernAcademicExamsCreate');

    Route::get('/admin-modern/academic/exams/{itemId}/edit', function (ExamController $controller, $itemId) {
        $legacyResponse = $controller->editExam($itemId);

        if ($legacyResponse instanceof \Illuminate\View\View) {
            return view('admin-modern.academic.exams.edit', $legacyResponse->getData());
        }

        return $legacyResponse;
    })->name('adminModernAcademicExamsEdit');

    Route::get('/profile',[
        CultivationController::class,
        'adminProfile'
    ])->name('adminProfile');

    Route::post('/profile/save',[
        CultivationController::class ,
        'saveAdminProfile'
    ])->name('saveAdminProfile');

    Route::post('/profile/password/save',[
        CultivationController::class ,
        'changeAdminPassword'
    ])->name('changeAdminPassword');

    // Admin profile photo
    Route::post('/admin/avatar/update', [
        CultivationController::class,
        'updateAdminPhoto'
    ])->name('updateAdminPhoto');
    Route::delete('/admin/del/avatar/{id}', [
        CultivationController::class,
        'delAdminPhoto'
    ])->name('delAdminPhoto');

    Route::get('/notice/list',[
        NoticeController::class ,
        'noticeList'
    ])->name('noticeList');

    Route::get('/notice/new',[
        NoticeController::class ,
        'newNotice'
    ])->name('newNotice');

    Route::post('/notice/save',[
        NoticeController::class ,
        'saveNotice'
    ])->name('saveNotice');

    Route::get('/notice/edit/{id}',[
        NoticeController::class ,
        'editNotice'
    ])->name('editNotice');

    Route::post('/notice/update',[
        NoticeController::class ,
        'updateNotice'
    ])->name('updateNotice');

    Route::delete('/notice/delete/{id}',[
        NoticeController::class ,
        'delNotice'
    ])->name('delNotice');

    // Promotion revert (restore previous class/section/roll from archive)
    Route::post('/promotion/revert/{stdId}', [AdmissionController::class, 'revertPromotion'])->name('promotion.revert');
    Route::post('/promotion/revert-centralized/{promotionCycleId}', [AdmissionController::class, 'revertCentralizedPromotion'])
        ->name('promotion.revert.centralized');

    Route::get('/notice/preview/{id}',[
        NoticeController::class ,
        'prevNotice'
    ])->name('prevNotice');

    //notice ends here

    //Image str

    Route::get('/institute/photo/',[
        GalleryController::class,
        'newPhoto'
    ])->name('newPhoto');

    Route::post('/photo/save',[
        GalleryController::class ,
        'savePhoto'
    ])->name('savePhoto');

    Route::get('/photo/edit/{id}',[
        GalleryController::class ,
        'editPhoto'
    ])->name('editPhoto');

    Route::delete('/photo/content/delete/{id}',[
        GalleryController::class ,
        'delPhotoContent'
    ])->name('delPhotoContent');

    Route::delete('/photo/delete/{id}',[
        GalleryController::class ,
        'delPhoto'
    ])->name('delPhoto');

    //image end

    //video str

    Route::get('/institute/video/',[
        GalleryController::class,
        'newVideo'
    ])->name('newVideo');

    Route::post('/video/save',[
        GalleryController::class ,
        'saveVideo'
    ])->name('saveVideo');

    Route::get('/video/edit/{id}',[
        GalleryController::class ,
        'editVideo'
    ])->name('editVideo');

    Route::delete('/video/content/delete/{id}',[
        GalleryController::class ,
        'delVideoContent'
    ])->name('delVideoContent');

    Route::delete('/video/delete/{id}',[
        GalleryController::class ,
        'delVideo'
    ])->name('delVideo');

    //video end

     Route::get('/home/slider/',[
        InstituteController::class,
        'sliderInfo'
    ])->name('sliderInfo'); 

     Route::post('/home/slider/details',[
        InstituteController::class,
        'sliderDetail'
    ])->name('sliderDetail'); 

     Route::get('/home/slider/edit/{id}',[
        InstituteController::class ,
        'editSlider'
    ])->name('editSlider');


    Route::delete('/home/slider/image/delete/{id}',[
        InstituteController::class ,
        'delSliderImg'
    ])->name('delSliderImg');

    Route::delete('/home/slider/delete/{id}',[
        InstituteController::class ,
        'delSlider'
    ])->name('delSlider');

    Route::get('/home/info/',[
        InstituteController::class,
        'homeInfo'
    ])->name('homeInfo'); 

    Route::post('/home/details/',[
        InstituteController::class ,
        'homeDetails'
    ])->name('homeDetails');



    Route::delete('/home/info/eduMinImg/del/{id}',[
        InstituteController::class ,
        'delEduMinImg'
    ])->name('home.delEduMinImg');

    Route::delete('/home/info/boardChairmanImg/del/{id}',[
        InstituteController::class ,
        'delBoardChairmanImg'
    ])->name('home.delBoardChairmanImg');

    Route::delete('/home/info/principalImg/del/{id}',[
        InstituteController::class ,
        'delPrincipalImg'
    ])->name('delPrincipalImg');


    Route::get('/institute/info/',[
        InstituteController::class,
        'insInfo'
    ])->name('insInfo');

    // SMS settings (super admin)
    Route::get('/sms/settings', [\App\Http\Controllers\SmsSettingsController::class, 'edit'])
        ->middleware(\App\Http\Middleware\Roles::class.':3')
        ->name('sms.settings');
    Route::post('/sms/settings/save', [\App\Http\Controllers\SmsSettingsController::class, 'save'])
        ->middleware(\App\Http\Middleware\Roles::class.':3')
        ->name('sms.settings.save');
    Route::post('/sms/settings/toggle', [\App\Http\Controllers\SmsSettingsController::class, 'toggleEnabled'])
        ->middleware(\App\Http\Middleware\Roles::class.':3')
        ->name('sms.settings.toggle');
    Route::get('/sms/settings/status', [\App\Http\Controllers\SmsSettingsController::class, 'status'])
        ->middleware(\App\Http\Middleware\Roles::class.':3')
        ->name('sms.settings.status');
    Route::post('/sms/settings/test', [\App\Http\Controllers\SmsSettingsController::class, 'test'])
        ->middleware(\App\Http\Middleware\Roles::class.':3')
        ->name('sms.settings.test');
    Route::get('/sms/alpha-rate', [\App\Http\Controllers\SmsSettingsController::class, 'alphaRate'])
        ->middleware(\App\Http\Middleware\Roles::class.':3')
        ->name('sms.alphaRate');

    Route::delete('/institute/info/img/del/{id}',[
        InstituteController::class ,
        'delHeroImg'
    ])->name('delHeroImg');

    Route::post('/institute/details/',[
        InstituteController::class ,
        'insDetails'
    ])->name('insDetails');

    Route::get('/institute/principal/speech',[
        InstituteController::class ,
        'principalSpeech'
    ])->name('principalSpeech');

    Route::post('/institute/principal/speech/save',[
        InstituteController::class ,
        'savePrincipalSpeech'
    ])->name('savePrincipalSpeech');

    Route::get('/institute/principal/exList',[
        InstituteController::class,
        'exPrincipal'
    ])->name('exPrincipal');

    Route::get('/institute/view/exPrincipal/{id}',[
        InstituteController::class,
        'viewExPrincipal'
    ])->name('viewExPrincipal');

    Route::post('/institute/principal/exList/save',[
        InstituteController::class ,
        'saveExPrincipal'
    ])->name('saveExPrincipal');

    Route::get('/institute/principal/exList/edit/{id}',[
        InstituteController::class ,
        'editExPrincipal'
    ])->name('editExPrincipal');

    Route::delete('/academic/exPlc/content/delete/{id}',[
        InstituteController::class ,
        'delexPlcCon'
    ])->name('delexPlcCon');

    Route::delete('/institute/principal/exList/del/{id}',[
        InstituteController::class ,
        'delExPrincipal'
    ])->name('delExPrincipal');

    Route::get('/institute/committee/',[
        InstituteController::class ,
        'managingCommittee'
    ])->name('managingCommittee');

    Route::post('/institute/committee/save',[
        InstituteController::class ,
        'saveManagingCommittee'
    ])->name('saveManagingCommittee');

    Route::get('/institute/committee/view/{id}',[
        InstituteController::class ,
        'viewManagingCommittee'
    ])->name('viewManagingCommittee');

    Route::get('/institute/committee/edit/{id}',[
        InstituteController::class ,
        'editManagingCommittee'
    ])->name('editManagingCommittee');

    Route::delete('/institute/committee/dlt/image/{id}',[
        InstituteController::class ,
        'delImgContent'
    ])->name('delImgContent');

    Route::delete('/institute/committee/del/{id}',[
        InstituteController::class ,
        'delManagingCommittee'
    ])->name('delManagingCommittee');

    // Governing body bulk photo upload
    Route::get('/governing-body/bulk-photo-upload',[
        InstituteController::class ,
        'bulkPhotoUploadForm'
    ])->name('governingBodyBulkPhotoUpload');

    Route::post('/governing-body/bulk-photo-upload',[
        InstituteController::class ,
        'bulkPhotoUploadStore'
    ])->name('governingBodyBulkPhotoUploadStore');

    // institute info ends here

    Route::get('/academic/syllabus/',[
        AcademicController::class ,
        'syllabusManage'
    ])->name('syllabusManage');

    Route::post('/academic/syllabus/save',[
        AcademicController::class ,
        'saveSyllabus'
    ])->name('saveSyllabus');

    Route::get('/academic/syllabus/edit/{id}',[
        AcademicController::class ,
        'editSyllabus'
    ])->name('editSyllabus');

    Route::delete('/academic/syllabus/content/delete/{id}',[
        AcademicController::class ,
        'delSyllabusContent'
    ])->name('delSyllabusContent');

    Route::delete('/academic/syllabus/del/{id}',[
        AcademicController::class ,
        'delSyllabus'
    ])->name('delSyllabus');

    Route::get('/academic/classRoutine/',[
        ExamController::class ,
        'classRoutineManage'
    ])->name('classRoutineManage');

    Route::post('/academic/classRoutine/save',[
        ExamController::class ,
        'saveClassRoutine'
    ])->name('saveClassRoutine');

    Route::get('/academic/classRoutine/edit/{id}',[
        ExamController::class ,
        'editClassRoutine'
    ])->name('editClassRoutine');

    Route::delete('/academic/classRoutine/del/{id}',[
        ExamController::class ,
        'delClassRoutine'
    ])->name('delClassRoutine');

    Route::delete('/academic/classRoutine/content/delete/{id}',[
        ExamController::class ,
        'delClassRoutineContent'
    ])->name('delClassRoutineContent');

    Route::get('/academic/examRoutine/',[
        ExamController::class ,
        'examRoutineManage'
    ])->name('examRoutineManage');

    Route::post('/academic/examRoutine/save',[
        ExamController::class ,
        'saveExamRoutine'
    ])->name('saveExamRoutine');

    Route::get('/academic/examRoutine/edit/{id}',[
        ExamController::class ,
        'editExamRoutine'
    ])->name('editExamRoutine');

    Route::delete('/academic/examRoutine/del/{id}',[
        ExamController::class ,
        'delExamRoutine'
    ])->name('delExamRoutine');

    Route::delete('/academic/examRoutine/content/delete/{id}',[
        ExamController::class ,
        'delExamRoutineContent'
    ])->name('delExamRoutineContent');

    Route::get('/academic/semisterPlan/',[
        AcademicController::class,
        'semisterPlanManage'
    ])->name('semisterPlanManage');

    Route::post('/academic/semisterPlan/save',[
        AcademicController::class ,
        'saveSemisterPlan'
    ])->name('saveSemisterPlan');

    Route::get('/academic/semisterPlan/edit/{id}',[
        AcademicController::class ,
        'editSemisterPlan'
    ])->name('editSemisterPlan');

    Route::delete('/academic/semisterPlan/del/{id}',[
        AcademicController::class ,
        'delSemisterPlan'
    ])->name('delSemisterPlan');

    Route::delete('/academic/semisterPlan/content/delete/{id}',[
        AcademicController::class ,
        'delSemisterPlanContent'
    ])->name('delSemisterPlanContent');

    // Internal Results management
    Route::get('/academic/internalResult/',[
        AcademicController::class ,
        'internalResultManage'
    ])->name('internalResultManage');

    Route::post('/academic/internalResult/save',[
        AcademicController::class ,
        'saveInternalResult'
    ])->name('saveInternalResult');

    Route::get('/academic/internalResult/edit/{id}',[
        AcademicController::class ,
        'editInternalResult'
    ])->name('editInternalResult');

    Route::delete('/academic/internalResult/del/{id}',[
        AcademicController::class ,
        'delInternalResult'
    ])->name('delInternalResult');

    Route::delete('/academic/internalResult/content/delete/{id}',[
        AcademicController::class ,
        'delInternalResultContent'
    ])->name('delInternalResultContent');

    Route::get('/placement/jobPlacement/',[
        PlacementCellController::class ,
        'placementCell'
    ])->name('placementCell');

    Route::post('/placement/placementCell/save',[
        PlacementCellController::class ,
        'savePlacementCell'
    ])->name('savePlacementCell');

    Route::get('/placement/placementCell/edit/{id}',[
        PlacementCellController::class ,
        'editPlc'
    ])->name('editPlc');


    Route::delete('/academic/placementCell/content/delete/{id}',[
        PlacementCellController::class ,
        'delPlcCon'
    ])->name('delPlcCon');

    Route::delete('/placement/placementCell/delete/{id}',[
        PlacementCellController::class ,
        'delPlc'
    ])->name('delPlc');

    Route::get('/placement/needyStudentPanel/',[
        PlacementCellController::class ,
        'needyStudentPanel'
    ])->name('needyStudentPanel');

    Route::post('/placement/needyStudentPanel/save',[
        PlacementCellController::class ,
        'saveNeedyStdPanel'
    ])->name('saveNeedyStdPanel');

    Route::get('/placement/needyStudentPanel/edit/{id}',[
        PlacementCellController::class ,
        'editNeedyStdPanel'
    ])->name('editNeedyStdPanel');


    Route::delete('/academic/needyStudentPanel/photo/delete/{id}',[
        PlacementCellController::class ,
        'delNeedyStdPanelCon'
    ])->name('delNeedyStdPanelCon');
    Route::delete('/academic/needyStudentPanel/documents/delete/{id}',[
        PlacementCellController::class ,
        'delNeedyStdPaneldoc'
    ])->name('delNeedyStdPaneldoc');

    Route::delete('/placement/needyStudentPanel/delete/{id}',[
        PlacementCellController::class ,
        'delNeedyStdPanel'
    ])->name('delNeedyStdPanel');
    //academic info ends here

    // Designation Management
    Route::get('/designations', [
        DesignationController::class,
        'index'
    ])->name('designationsIndex');

    Route::get('/designations/create', [
        DesignationController::class,
        'create'
    ])->name('designationsCreate');

    Route::post('/designations/store', [
        DesignationController::class,
        'store'
    ])->name('designationsStore');

    Route::get('/designations/{id}/edit', [
        DesignationController::class,
        'edit'
    ])->name('designationsEdit');

    Route::post('/designations/{id}/update', [
        DesignationController::class,
        'update'
    ])->name('designationsUpdate');

    Route::delete('/designations/{id}/delete', [
        DesignationController::class,
        'delete'
    ])->name('designationsDelete');

    Route::post('/designations/reorder', [
        DesignationController::class,
        'reorder'
    ])->name('designationsReorder');

    Route::patch('/designations/{id}/toggle', [
        DesignationController::class,
        'toggleActive'
    ])->name('designationsToggle');

    //
    Route::get('/configuration',[
        CultivationController::class ,
        'serverConfig'
    ])->middleware(\App\Http\Middleware\Roles::class.':3')->name('serverConfig');
    
    Route::post('/configuration/save',[
        CultivationController::class ,
        'saveConfig'
    ])->middleware(\App\Http\Middleware\Roles::class.':3')->name('saveConfig');
    Route::delete('/sign/del/{id}',[
        CultivationController::class ,
        'delSign'
    ])->middleware(\App\Http\Middleware\Roles::class.':3')->name('delSign');
    Route::post('/sign/save',[
        CultivationController::class,
        'saveSign'
    ])->middleware(\App\Http\Middleware\Roles::class.':3')->name('saveSign');
    Route::delete('/logo/del/{id}',[
        CultivationController::class ,
        'delLogo'
    ])->middleware(\App\Http\Middleware\Roles::class.':3')->name('delLogo');
    Route::post('/logo/save',[
        CultivationController::class ,
        'saveLogo'
    ])->middleware(\App\Http\Middleware\Roles::class.':3')->name('saveLogo');

    Route::post('/boardChairmanImg/save',[
        CultivationController::class ,
        'saveBoardChairmanImg'
    ])->middleware(\App\Http\Middleware\Roles::class.':3')->name('saveBoardChairmanImg');

    Route::post('/eduMinImg/save',[
        CultivationController::class ,
        'saveEduMinImg'
    ])->middleware(\App\Http\Middleware\Roles::class.':3')->name('saveEduMinImg');


    Route::delete('/favicon/del/{id}',[
        CultivationController::class ,
        'delFavicon'
    ])->middleware(\App\Http\Middleware\Roles::class.':3')->name('delFavicon');
    Route::post('/favicon/save',[
        CultivationController::class ,
        'saveFavicon'
    ])->middleware(\App\Http\Middleware\Roles::class.':3')->name('saveFavicon');
    
    Route::delete('/boardChairmanImg/del/{id}',[
        CultivationController::class ,
        'delBoardChairmanImg'
    ])->middleware(\App\Http\Middleware\Roles::class.':3')->name('delBoardChairmanImg');
    
    Route::delete('/eduMinImg/del/{id}',[
        CultivationController::class ,
        'delEduMinImg'
    ])->middleware(\App\Http\Middleware\Roles::class.':3')->name('delEduMinImg');

    Route::delete('/avatar/del/{id}',[
        CultivationController::class ,
        'delAvatar'
    ])->middleware(\App\Http\Middleware\Roles::class.':3')->name('delAvatar');
    Route::post('/avatar/save',[
        CultivationController::class ,
        'saveAvatar'
    ])->middleware(\App\Http\Middleware\Roles::class.':3')->name('saveAvatar');

    // Account Part (Cash Admin + General)
    Route::middleware(\App\Http\Middleware\Roles::class.':2')->group(function(){
        Route::get('/account',[
            BackofficeController::class ,
            'accountPart'
        ])->name('accountPart');

        // Fees
        Route::get('/add-fees',[
            individualController::class, //add Fees
            'feesForm'
        ])->name('feesForm');

        Route::get('/edit-fees-data/{id}',[
            individualController::class, //edit Fees
            'editFees'
        ])->name('editFees');

        Route::post('/update-fees',[
            individualController::class, //update Fees
            'updateFees'
        ])->name('updateFees');

        Route::post('/save-fees',[
            individualController::class, //add Fees
            'saveFees'
        ])->name('saveFees');

        Route::delete('/delete-fees-data/{id}',[
            individualController::class,      // delete Fees
            'deleteFees'
        ])->name('deleteFees');

        // cashCalculas
        Route::get('/cash-calculas-from',[
            cashCalculasController::class,    //cashCalculas main page
            'cashCalculasView'
        ])->name('cashCalculasView');

        Route::get('/get-report',[
            cashCalculasController::class,    //reportList page
            'reportListView'
        ])->name('reportListView');

        Route::get('/single-report/{id}',[
            cashCalculasController::class,    // report single page
            'singleView'
        ])->name('singleView');

        Route::post('/save-cash-calculas',[
            cashCalculasController::class,    //saveCashCalculas brackhand
            'saveCashCalculas'
        ])->name('saveCashCalculas');

        Route::get('/edit-cash-calculas/{id}',[
            cashCalculasController::class,     // edit calculas 
            'editCashCalculas'
        ])->name('editCashCalculas');

        Route::post('/update-cash-calculas',[
            cashCalculasController::class,   //update calculas
            'updateCashCalculas'
        ])->name('updateCashCalculas');

        Route::delete('/delete-calculas-data/{id}',[
            cashCalculasController::class,      // delete calculas
            'dltCalculasData'
        ])->name('dltCalculasData');

        Route::get('/calculas-repot-generate/{id}',[
            cashCalculasController::class,   // calculas Report
            'cashReport'
        ])->name('cashReport');

        Route::get('/calculas-date-repot-generate',[
            cashCalculasController::class,   // calculas Report
            'cashDateReport'
        ])->name('cashDateReport');

        Route::post('/calculas-date-repot-recipit',[
            cashCalculasController::class, //  free
            'getCashReport'
        ])->name('getCashReport');
    });

        //Tuition str
    Route::get('/getStudentForTutionFee/{stdId}',[
        tuitionController::class,
        'getStudentForTutionFee'
    ])->name('getStudentForTutionFee');

    Route::get('/getStudentsForTutionFeeFilter',[
        tuitionController::class,
        'getStudentsForTutionFeeFilter'
    ])->name('getStudentsForTutionFeeFilter');

    Route::get('/add-tuition-fee',[
        tuitionController::class,   //add tuition free
        'tuitionFee'
    ])->name('tuitionFee');

    Route::post('/save-tuition-fee',[
        tuitionController::class,
        'saveTuitionfee'
    ])->name('saveTuitionfee');

    Route::get('/tuition-fee-list',[
        tuitionController::class,   // tuition free list
        'tuitionFeeList'
    ])->name('tuitionFeeList');
    Route::post('/tuition-fee/bulk-delete',[
        tuitionController::class,
        'bulkDeleteTuitionFees'
    ])->name('bulkDeleteTuitionFees');

    Route::get('/tuition-fee-view/{id}',[
        tuitionController::class,   // tuition free view
        'tuitionFeeView'
    ])->name('tuitionFeeView');

    Route::get('/edit-tuition-fee/{id}',[
        tuitionController::class, //edit tuition free
        'editTuitionFee'
    ])->name('editTuitionFee');

    Route::post('/update-tuition-fee',[
        tuitionController::class, //update tuition free
        'updateTuitionFee'
    ])->name('updateTuitionFee');

    Route::get('/collect-tuition-due/{id}',[
        tuitionController::class,
        'collectDueForm'
    ])->name('collectDueForm');

    Route::post('/collect-tuition-due',[
        tuitionController::class,
        'collectDueSubmit'
    ])->name('collectDueSubmit');

    Route::delete('/delete-tuition-fee/{id}',[
        tuitionController::class,      // delete tuition free
        'dltTuitionFee'
    ])->name('dltTuitionFee');

    Route::get('/tuition-repot-generate/{id}',[
        tuitionController::class,   // tuition free tuitionReport
        'tuitionReport'
    ])->name('tuitionReport');

    Route::get('/student/fees/generate',[
        tuitionController::class, //edit Fees
        'feesReport'
    ])->name('feesReport');

    Route::get('/student/fees/dues-dashboard',[
        tuitionController::class,
        'duesDashboard'
    ])->name('duesDashboard');

    Route::get('/student/fees/classwise-setup',[
        tuitionController::class,
        'classWiseFeeSetup'
    ])->name('classWiseFeeSetup');

    Route::post('/student/fees/classwise-setup/save',[
        tuitionController::class,
        'saveClassWiseFeeSetup'
    ])->name('saveClassWiseFeeSetup');

    Route::get('/student/fees/classwise-setup/data',[
        tuitionController::class,
        'getClassWiseFeeSetupData'
    ])->name('getClassWiseFeeSetupData');

    Route::delete('/student/fees/classwise-setup/delete/{id}',[
        tuitionController::class,
        'deleteClassWiseFeeSetup'
    ])->name('deleteClassWiseFeeSetup');

    Route::post('/student/fees/generate/report',[
        tuitionController::class, //update tuition free
        'getFeesReport'
    ])->name('getFeesReport');
    //Tuition end

    //Account part end

    //Academic Part
    Route::get('/academic',[
        BackofficeController::class ,
        'index'
    ])->name('academicPart');
    //Student route declaration
    Route::get('/student/admit',[
        AdmissionController::class ,
        'admitStudent'
    ])->name('admitStudent');
    Route::post('/student/admit/confirm',[
        AdmissionController::class ,
        'confirmAdmit'
    ])->name('confirmAdmit');
    Route::get('/view/student/{stdId}',[
        AdmissionController::class,
        'viewAdmission'
    ])->name('viewAdmission');
    Route::get('/student/edit/{stdId}',[
        AdmissionController::class ,
        'editStudent'
    ])->name('editStudent');

    Route::post('/student/edit/confirm',[
        AdmissionController::class ,
        'updateAdmit'
    ])->name('updateAdmit');


    Route::post('/student/photo/update',[
        AdmissionController::class ,
        'stdPhotoUpdate'
    ])->name('stdPhotoUpdate');

    Route::get('/student/photo/bulk', [
        AdmissionController::class,
        'bulkPhotoForm'
    ])->name('studentPhotoBulk');

    Route::post('/student/photo/bulk', [
        AdmissionController::class,
        'bulkPhotoUpload'
    ])->name('studentPhotoBulkUpload');


    Route::delete('/student/del/avatar/{stdId}',[
        AdmissionController::class ,
        'delStudentPhoto'
    ])->name('delStudentPhoto');

    Route::delete('/student/del/{stdId}',[
        AdmissionController::class ,
        'delStudent'
    ])->name('delStudent');

    Route::post('/student/bulk-delete',[
        AdmissionController::class ,
        'studentBulkDelete'
    ])->name('studentBulkDelete');


    Route::get('/student/list',[
        AdmissionController::class,
        'studentList'
    ])->name('studentList');
    Route::get('/student/export/pdf',[
        AdmissionController::class,
        'exportStudentPDF'
    ])->name('student.export.pdf');
    Route::get('/student/export/excel',[
        AdmissionController::class,
        'exportStudentExcel'
    ])->name('student.export.excel');

    // Bulk Student ID Cards (professional format)
    Route::get('/student/idcards/bulk', [
        AdmissionController::class,
        'bulkIdCards'
    ])->name('student.idcards.bulk');

    Route::get('/student/idCard/{stdId}',[
        AdmissionController::class ,
        'stdIdCard'
    ])->name('stdIdCard');
    Route::get('/student/idCard/{stdId}/pdf', [
        AdmissionController::class,
        'stdIdCardPdf'
    ])->name('stdIdCard.pdf');

    Route::get('/student/promotion',[
        AdmissionController::class ,
        'studentPromotion'
    ])->name('studentPromotion');


    Route::post('/student/promotion/getData',[
        AdmissionController::class ,
        'getPromotionData'
    ])->name('getPromotionData');

    Route::post('/student/promotion/confirm',[
        AdmissionController::class ,
        'confirmPromotData'
    ])->name('confirmPromotData');


    //Teacher route declaration

    Route::get('/teacher/admit',[
        TeacherController::class ,
        'addTeacher'
    ])->name('addTeacher');
    Route::post('/teacher/admit/confirm',[
        TeacherController::class ,
        'confirmTeacher'
    ])->name('confirmTeacher');
    Route::get('/view/teacher/{profileId}',[
        TeacherController::class,
        'viewTeacher'
    ])->name('viewTeacher');
    Route::get('/teacher/edit/{profileId}',[
        TeacherController::class ,
        'editTeacher'
    ])->name('editTeacher');
    Route::post('/teacher/edit/confirm',[
        TeacherController::class ,
        'updateTeacher'
    ])->name('updateTeacher');
    Route::delete('/teacher/del/{profileId}',[
        TeacherController::class ,
        'delTeacher'
    ])->name('delTeacher');
    Route::delete('/teacher/del/avatar/{profileId}',[
        TeacherController::class ,
        'delTeacherPhoto'
    ])->name('delTeacherPhoto');


    Route::post('/teacher/avatar/update',[
        TeacherController::class,
        'updateTeacherPhoto'
    ])->name('updateTeacherPhoto');

    Route::get('/teacher/list',[
        TeacherController::class ,
        'teacherList'
    ])->name('teacherList');

    Route::get('/teacher/bulk-update',[
        TeacherController::class,
        'bulkUpdateForm'
    ])->name('teacherBulkUpdate');

    Route::post('/teacher/bulk-update',[
        TeacherController::class,
        'bulkUpdateStore'
    ])->name('teacherBulkUpdateStore');

    Route::get('/student/bulk-update',[
        AdmissionController::class,
        'bulkStudentUpdateForm'
    ])->name('studentBulkUpdate');

    Route::post('/student/bulk-update',[
        AdmissionController::class,
        'bulkStudentUpdateStore'
    ])->middleware(\App\Http\Middleware\BulkStudentUpdateDiagnostics::class)
      ->name('studentBulkUpdateStore');

    Route::get('/teacher/export/pdf',[
        TeacherController::class,
        'exportPDF'
    ])->name('teacher.export.pdf');

    Route::get('/teacher/bulk-upload',[
        TeacherController::class,
        'bulkUploadForm'
    ])->name('teacherBulkUpload');

    Route::post('/teacher/bulk-upload',[
        TeacherController::class,
        'bulkUploadStore'
    ])->name('teacherBulkUploadStore');

    Route::get('/teacher/bulk-sample',[
        TeacherController::class,
        'downloadSample'
    ])->name('teacherBulkSample');

    Route::get('/teacher/template',[
        TeacherController::class,
        'downloadTemplate'
    ])->name('teacherTemplate');

    Route::get('/teacher/bulk-photo-upload',[
        TeacherController::class,
        'bulkPhotoUploadForm'
    ])->name('teacherBulkPhotoUpload');

    Route::post('/teacher/bulk-photo-upload',[
        TeacherController::class,
        'bulkPhotoUploadStore'
    ])->name('teacherBulkPhotoUploadStore');

    Route::post('/teacher/bulk-delete',[
        TeacherController::class,
        'teacherBulkDelete'
    ])->name('teacherBulkDelete');

    //Teacher route declaration

    Route::get('/staff/admit',[
        StaffController::class ,
        'addStaff'
    ])->name('addStaff');
    Route::post('/staff/admit/confirm',[
        StaffController::class ,
        'confirmStaff'
    ])->name('confirmStaff');
    Route::get('/view/staff/{profileId}',[
        StaffController::class,
        'viewStaff'
    ])->name('viewStaff');
    Route::get('/staff/edit/{profileId}',[
        StaffController::class ,
        'editStaff'
    ])->name('editStaff');
    Route::post('/staff/edit/confirm',[
        StaffController::class ,
        'updateStaff'
    ])->name('updateStaff');
    Route::delete('/staff/del/{profileId}',[
        StaffController::class ,
        'delStaff'
    ])->name('delStaff');
    Route::delete('/staff/del/avatar/{profileId}',[
        StaffController::class ,
        'delStaffPhoto'
    ])->name('delStaffPhoto');

    Route::post('/staff/avatar/update',[
        StaffController::class,
        'updateStaffPhoto'
    ])->name('updateStaffPhoto');

    Route::get('/staff/bulk-upload',[
        StaffController::class,
        'bulkUploadForm'
    ])->name('staffBulkUpload');

    Route::post('/staff/bulk-upload',[
        StaffController::class,
        'bulkUploadStore'
    ])->name('staffBulkUploadStore');

    Route::get('/staff/bulk-sample',[
        StaffController::class,
        'downloadSample'
    ])->name('staffBulkSample');

    Route::get('/staff/template',[
        StaffController::class,
        'downloadTemplate'
    ])->name('staffBulkTemplate');

    Route::get('/staff/bulk-photo-upload',[
        StaffController::class,
        'bulkPhotoUploadForm'
    ])->name('staffBulkPhotoUpload');

    Route::post('/staff/bulk-photo-upload',[
        StaffController::class,
        'bulkPhotoUploadStore'
    ])->name('staffBulkPhotoUploadStore');

    Route::post('/staff/bulk-delete',[
        StaffController::class,
        'staffBulkDelete'
    ])->name('staffBulkDelete');

    Route::get('/staff/list',[
        StaffController::class ,
        'staffList'
    ])->name('staffList');

    Route::get('/staff/bulk-update',[
        StaffController::class,
        'bulkUpdateForm'
    ])->name('staffBulkUpdate');

    Route::post('/staff/bulk-update',[
        StaffController::class,
        'bulkUpdateStore'
    ])->name('staffBulkUpdateStore');

    Route::get('/staff/export/pdf',[
        StaffController::class,
        'exportPDF'
    ])->name('staff.export.pdf');


    //Classes route declaration

    Route::get('/class/create',[
        individualController::class ,
        'createClass'
    ])->name('createClass');
    Route::post('/class/create/confirm',[
        individualController::class ,
        'confirmClass'
    ])->name('confirmClass');
    Route::get('/class/edit/{itemId}',[
        individualController::class ,
        'editClass'
    ])->name('editClass');
    Route::post('/class/edit/confirm',[
        individualController::class ,
        'updateClass'
    ])->name('updateClass');
    Route::delete('/class/del/{itemId}',[
        individualController::class ,
        'delClass'
    ])->name('delClass');

    Route::get('/class/list',[
        individualController::class ,
        'allClasses'
    ])->name('allClasses');


    //Department route declaration

    Route::get('/department/create',[
        individualController::class ,
        'createDepartment'
    ])->name('createDepartment');
    Route::post('/department/create/confirm',[
        individualController::class ,
        'confirmDepartment'
    ])->name('confirmDepartment');
    Route::get('/department/edit/{itemId}',[
        individualController::class ,
        'editDepartment'
    ])->name('editDepartment');
    Route::post('/department/edit/confirm',[
        individualController::class ,
        'updateDepartment'
    ])->name('updateDepartment');
    Route::delete('/department/del/{itemId}',[
        individualController::class ,
        'delDepartment'
    ])->name('delDepartment');

    Route::get('/department/list',[
        individualController::class ,
        'allDepartment'
    ])->name('allDepartment');

    //Section route declaration

    Route::get('/section/create',[
        individualController::class ,
        'createSection'
    ])->name('createSection');
    Route::post('/Section/create/confirm',[
        individualController::class ,
        'confirmSection'
    ])->name('confirmSection');
    Route::get('/Section/edit/{itemId}',[
        individualController::class ,
        'editSection'
    ])->name('editSection');
    Route::post('/Section/edit/confirm',[
        individualController::class ,
        'updateSection'
    ])->name('updateSection');
    Route::delete('/Section/del/{itemId}',[
        individualController::class ,
        'delSection'
    ])->name('delSection');

    Route::get('/Section/list',[
        individualController::class ,
        'allSection'
    ])->name('allSection');

    //Session route declaration

    Route::get('/session/create',[
        individualController::class ,
        'createSession'
    ])->name('createSession');
    Route::post('/session/create/confirm',[
        individualController::class ,
        'confirmSession'
    ])->name('confirmSession');
    Route::get('/session/edit/{itemId}',[
        individualController::class ,
        'editSession'
    ])->name('editSession');
    Route::post('/session/edit/confirm',[
        individualController::class ,
        'updateSession'
    ])->name('updateSession');
    Route::delete('/session/del/{itemId}',[
        individualController::class ,
        'delSession'
    ])->name('delSession');

    Route::get('/session/list',[
        individualController::class ,
        'allSession'
    ])->name('allSession');

    //Result Part
    Route::get('/result',[
        BackofficeController::class,
        'resultPart'
    ])->name('resultPart');
    
    //Subject route declaration

    Route::get('/subject/create',[
        SubjectController::class ,
        'createSubject'
    ])->name('createSubject');
    Route::post('/subject/create/confirm',[
        SubjectController::class ,
        'confirmSubject'
    ])->name('confirmSubject');
    Route::get('/subject/edit/{itemId}',[
        SubjectController::class ,
        'editSubject'
    ])->name('editSubject');
    Route::post('/subject/edit/confirm',[
        SubjectController::class ,
        'updateSubject'
    ])->name('updateSubject');
    Route::delete('/subject/del/{itemId}',[
        SubjectController::class ,
        'delSubject'
    ])->name('delSubject');

    Route::get('/subject/list',[
        SubjectController::class,
        'allSubject'
    ])->name('allSubject');

    Route::middleware(\App\Http\Middleware\Roles::class.':3')->group(function () {
        Route::get('/subject/{itemId}/scope-split', [SubjectController::class, 'splitScopeForm'])->name('subject.scope.split');
        Route::post('/subject/{itemId}/scope-split/preview', [SubjectController::class, 'previewScopeSplit'])->name('subject.scope.split.preview');
        Route::post('/subject/{itemId}/scope-split/apply', [SubjectController::class, 'applyScopeSplit'])->name('subject.scope.split.apply');
    });


    //Exam route declaration

    Route::get('/exam/create',[
        ExamController::class ,
        'createExam'
    ])->name('createExam');
    Route::post('/exam/create/confirm',[
        ExamController::class ,
        'confirmExam'
    ])->name('confirmExam');
    Route::get('/exam/edit/{itemId}',[
        ExamController::class ,
        'editExam'
    ])->name('editExam');
    Route::post('/exam/edit/confirm',[
        ExamController::class ,
        'updateExam'
    ])->name('updateExam');
    Route::delete('/exam/del/{itemId}',[
        ExamController::class ,
        'delExam'
    ])->name('delExam');

    Route::get('/exam/list',[
        ExamController::class ,
        'allExam'
    ])->name('allExam');


    //Marks route declaration (Teacher + General)
    Route::middleware(\App\Http\Middleware\Roles::class.':1')->group(function(){
        Route::get('/marks/add',[
            MarksheetController::class ,
            'addMarks'
        ])->name('addMarks');
        Route::post('/marks/add/classes',[
            MarksheetController::class ,
            'marksEntryClasses'
        ])->name('api.marks.classes');
        Route::post('/marks/add/subjects',[
            MarksheetController::class ,
            'marksEntrySubjects'
        ])->name('api.marks.subjects');
        Route::post('/marks/add/sections',[
            MarksheetController::class ,
            'marksEntrySections'
        ])->name('api.marks.sections');
        Route::post('/marks/add/groups',[
            MarksheetController::class ,
            'marksEntryGroups'
        ])->name('api.marks.groups');
        Route::post('/marks/add/getData',[
            MarksheetController::class ,
            'getMarks'
        ])->name('getMarks');
        Route::post('/marks/add/confirm',[
            MarksheetController::class ,
            'confirmMarks'
        ])->middleware('throttle:result-draft')->name('confirmMarks');
        Route::post('/marks/add/draft',[
            MarksheetController::class,
            'saveDraftMarks'
        ])->middleware('throttle:result-draft')->name('marks.draft.save');
        Route::post('/marks/add/confirm-subject',[
            MarksheetController::class,
            'confirmSubjectMarks'
        ])->middleware('throttle:result-transition')->name('marks.subject.confirm');
        Route::post('/marks/add/reopen-subject',[
            MarksheetController::class,
            'reopenSubjectMarks'
        ])->middleware('throttle:result-transition')->name('marks.subject.reopen');
    });

    Route::get('/marksheet/create',[
        MarksheetController::class ,
        'createMarksheet'
    ])->name('createMarksheet');

    Route::get('/marksheet/all',[
        MarksheetController::class ,
        'allMarksheet'
    ])->name('allMarksheet');

    Route::get('/marksheet/at-a-glance',[
        MarksheetController::class ,
        'allMarksheet'
    ])->name('atGlanceResult');

    Route::get('/marksheet/result-summary',[
        MarksheetController::class,
        'resultSummary'
    ])->name('result.summary');

    Route::middleware(\App\Http\Middleware\Roles::class.':3')->group(function () {
        Route::get('/result/academic-attendance', [AcademicAttendanceController::class, 'index'])
            ->name('academic-attendance.index');
        Route::post('/result/academic-attendance/bulk', [AcademicAttendanceController::class, 'storeBulk'])
            ->name('academic-attendance.bulk.store');
        Route::post('/result/academic-attendance/single', [AcademicAttendanceController::class, 'storeSingle'])
            ->name('academic-attendance.single.store');
    });

    Route::post('/marksheet/generate',[
        MarksheetController::class ,
        'generateMarksheet'
    ])->name('generateMarksheet');

    Route::middleware(\App\Http\Middleware\Roles::class.':3')->group(function(){
        Route::get('/result/final-publish',[
            MarksheetController::class,
            'finalPublishIndex'
        ])->name('result.final.publish');
        Route::post('/result/final-publish',[
            MarksheetController::class,
            'finalPublishStore'
        ])->middleware('throttle:result-publication')->name('result.final.publish.store');
        Route::post('/result/final-publish/publish',[
            MarksheetController::class,
            'publishResult'
        ])->middleware('throttle:result-publication')->name('result.publish');
        Route::post('/result/final-publish/unpublish',[
            MarksheetController::class,
            'unpublishResult'
        ])->middleware('throttle:result-publication')->name('result.unpublish');
    });


    //Admit Card route declaration

    Route::get('/admit/card/creation',[
        ExamController::class ,
        'admitCard'
    ])->name('admitCard');
    Route::post('/admit/card/getData',[
        ExamController::class ,
        'getAdmitCard'
    ])->name('getAdmitCard');

    Route::get('/result/exam-routine/manage',[
        ExamController::class,
        'resultExamRoutineManage'
    ])->name('resultExamRoutineManage');
    Route::post('/result/exam-routine/save',[
        ExamController::class,
        'saveResultExamRoutine'
    ])->name('saveResultExamRoutine');
    Route::get('/result/exam-routine/edit/{id}',[
        ExamController::class,
        'editResultExamRoutine'
    ])->name('editResultExamRoutine');
    Route::delete('/result/exam-routine/del/{id}',[
        ExamController::class,
        'delResultExamRoutine'
    ])->name('delResultExamRoutine');

    Route::get('/result/class-routine/manage',[
        ExamController::class,
        'resultClassRoutineManage'
    ])->name('resultClassRoutineManage');
    Route::post('/result/class-routine/save',[
        ExamController::class,
        'saveResultClassRoutine'
    ])->name('saveResultClassRoutine');
    Route::post('/result/class-routine/teacher-assignment/save',[
        ExamController::class,
        'saveResultClassRoutineTeacherAssignments'
    ])->name('saveResultClassRoutineTeacherAssignments');
    Route::get('/result/class-routine/edit/{id}',[
        ExamController::class,
        'editResultClassRoutine'
    ])->name('editResultClassRoutine');
    Route::delete('/result/class-routine/del/{id}',[
        ExamController::class,
        'delResultClassRoutine'
    ])->name('delResultClassRoutine');
    Route::get('/result/class-routine/view/{id}',[
        ExamController::class,
        'viewResultClassRoutine'
    ])->name('viewResultClassRoutine');
    Route::get('/result/class-routine/print/{id}',[
        ExamController::class,
        'printResultClassRoutine'
    ])->name('printResultClassRoutine');
    Route::get('/result/class-routine/pdf/{id}',[
        ExamController::class,
        'downloadResultClassRoutinePdf'
    ])->name('downloadResultClassRoutinePdf');
    Route::get('/result/class-routine/teacher-wise/view/{id}',[
        ExamController::class,
        'viewResultClassRoutineTeacherWise'
    ])->name('viewResultClassRoutineTeacherWise');
    Route::get('/result/class-routine/teacher-wise/print/{id}',[
        ExamController::class,
        'printResultClassRoutineTeacherWise'
    ])->name('printResultClassRoutineTeacherWise');
    Route::get('/result/class-routine/teacher-wise/pdf/{id}',[
        ExamController::class,
        'downloadResultClassRoutineTeacherWisePdf'
    ])->name('downloadResultClassRoutineTeacherWisePdf');

    Route::get('/result/curriculum-mapping/manage', [
        CurriculumSubjectMappingController::class,
        'index',
    ])->name('resultCurriculumMappingManage');
    Route::post('/result/curriculum-mapping/save', [
        CurriculumSubjectMappingController::class,
        'save',
    ])->name('saveResultCurriculumMapping');
    Route::post('/result/curriculum-mapping/copy-preview', [
        CurriculumSubjectMappingController::class,
        'copyPreview',
    ])->name('previewResultCurriculumMappingCopy');
    Route::post('/result/curriculum-mapping/copy', [
        CurriculumSubjectMappingController::class,
        'copy',
    ])->name('copyResultCurriculumMapping');
    Route::post('/result/curriculum-mapping/classes', [
        CurriculumSubjectMappingController::class,
        'lookupClasses',
    ])->name('api.resultCurriculumMapping.classes');
    Route::post('/result/curriculum-mapping/sections', [
        CurriculumSubjectMappingController::class,
        'lookupSections',
    ])->name('api.resultCurriculumMapping.sections');
    Route::post('/result/curriculum-mapping/departments', [
        CurriculumSubjectMappingController::class,
        'lookupDepartments',
    ])->name('api.resultCurriculumMapping.departments');

    Route::get('/admit-card/creation',[
        ExamController::class,
        'admitCardRoutine'
    ])->name('admitCardRoutine');
    Route::post('/admit-card/getData',[
        ExamController::class,
        'getAdmitCardRoutine'
    ])->name('getAdmitCardRoutine');

    //Attend Sheet route declaration

    Route::get('/attend/sheet/creation',[
        ExamController::class ,
        'attendSheet'
    ])->name('attendSheet');
    Route::post('/attend/sheet/getData',[
        ExamController::class ,
        'getAttendSheet'
    ])->name('getAttendSheet');

    //grade route declaration

    Route::get('/grade/create',[
        GradeListController::class ,
        'createGrade'
    ])->name('createGrade');
    Route::post('/grade/create/confirm',[
        GradeListController::class ,
        'confirmGrade'
    ])->name('confirmGrade');
    Route::get('/grade/edit/{itemId}',[
        GradeListController::class ,
        'editGrade'
    ])->name('editGrade');
    Route::post('/grade/edit/confirm',[
        GradeListController::class ,
        'updateGrade'
    ])->name('updateGrade');
    Route::delete('/grade/del/{itemId}',[
        GradeListController::class ,
        'delGrade'
    ])->name('delGrade');

    Route::get('/grade/list',[
        GradeListController::class ,
        'allGrade'
    ])->name('allGrade');

    // school Requst str
    Route::get('/register/request',[
        registerController::class ,
        'registerForm'
    ])->name('registerForm');

    Route::get('/register/list',[
        registerController::class ,
        'registerList'
    ])->name('registerList');

    Route::post('/register/save',[
        registerController::class ,
        'saveRegForm'
    ])->name('saveRegForm');


    Route::delete('/register/logo/delete/{regId}',[
        registerController::class ,
        'registerLogoDel'
    ])->name('registerLogoDel');

    Route::post('/register/logo/update',[
        registerController::class ,
        'registerLogoUpdate'
    ])->name('registerLogoUpdate');

    Route::get('/register/view/{regId}',[
        registerController::class ,
        'registerView'
    ])->name('registerView');

    Route::get('/register/edit/{regId}',[
        registerController::class ,
        'registerEdit'
    ])->name('registerEdit');

    Route::post('/register/update',[
        registerController::class,
        'registerUpdate'
    ])->name('registerUpdate');

    Route::delete('/register/del/{regId}',[
        registerController::class ,
        'registerDel'
    ])->name('registerDel');
    // school Requst end

    //school uesr panal
     Route::get('/user/request',[
        schoolUserController::class,
        'userForm'
    ])->name('userForm');

    Route::get('/user/list',[
        schoolUserController::class,
        'userList'
    ])->name('userList');

    Route::post('/user/save',[
       schoolUserController::class,
        'saveUserForm'
    ])->name('saveUserForm');

});


    // web font str 

    //academic str
    Route::get('/syllabus',[
        AcademicController::class ,
        'newSyllabus'
    ])->name('newSyllabus');

    Route::get('/class/schedule',[
        AcademicController::class ,
        'newClassSchedule'
    ])->name('newClassSchedule');

    Route::get('/exam/schedule',[
        AcademicController::class,
        'newExamSchedule'
    ])->name('newExamSchedule');

    Route::get('/semister/plan',[
        AcademicController::class,
        'newSemister'
    ])->name('newSemister');
    //academic end

    //MarksheetController str
    Route::get('/internal/result',[
        MarksheetController::class,
        'internalResult'
    ])->name('internalResult');

    Route::get('/individual/result',[
        MarksheetController::class,
        'individualResult'
    ])->name('individualResult');
    
    // Transcript generation by class/section student list
    Route::get('/transcripts/bulk', [
        MarksheetController::class,
        'transcriptList'
    ])->name('transcripts.bulk');
    Route::post('/transcripts/bulk/pdf', [
        MarksheetController::class,
        'bulkTranscriptPdf'
    ])->name('transcripts.bulk.pdf');
    // Single transcript view (existing generator wired up)
    Route::get('/marksheet/generate', [
        MarksheetController::class,
        'generateMarksheet'
    ])->middleware('adminGuard')->name('marksheetGenerate');
    //MarksheetController end

    //Placements (GPA-based ranking)
    Route::get('/placements', [\App\Http\Controllers\PlacementController::class, 'index'])
        ->name('placements.index');
    Route::post('/placements/recalculate', [\App\Http\Controllers\PlacementController::class, 'recalculate'])
        ->name('placements.recalculate');

    //PlacementCellController str
    Route::get('/job/placement-cell',[
        PlacementCellController::class,
        'placementCellView'
    ])->name('placementCellView');

    Route::get('/job/needy-student',[
        PlacementCellController::class,
        'jobNeedyStudentView'
    ])->name('jobNeedyStudentView');
    //PlacementCellController end

    //GalleryController str
    Route::get('/video/gallary',[
        GalleryController::class,
        'videoPage'
    ])->name('videoPage');

    Route::get('/image/gallary',[
        GalleryController::class,
        'imagePage'
    ])->name('imagePage');
    //GalleryController end

    //InstituteController str
    Route::get('/about-us',[
        InstituteController::class,
        'institutePage'
    ])->name('institutePage');

    Route::get('/principal-speech',[
        InstituteController::class,
        'principalSpeechPage'
        ])->name('principalSpeechPage');

         Route::get('/student',[
        InstituteController::class,
        'student'
        ])->name('student');

    Route::get('/exPrincipal',[
        InstituteController::class,
        'exprincipalPage'
        ])->name('exprincipalPage');

    Route::get('/our-teacher',[
        InstituteController::class,
        'teacherPage'
        ])->name('teacherPage');

    Route::get('/our-staff',[
        InstituteController::class,
        'staffPage'
        ])->name('staffPage');

    Route::get('/our-comittee',[
        InstituteController::class,
        'comitteePage'
        ])->name('comitteePage');
        

    Route::get('/contact-us',[
        InstituteController::class,
        'supportPage'
    ])->name('supportPage');

    //InstituteController str

    Route::get('/admin/creation',[
        CultivationController::class,
        'userType'
    ])->middleware(\App\Http\Middleware\Roles::class.':3')->name('userType');
    
    Route::get('/admin/edit/{id}',[
        CultivationController::class,
        'editUser'
    ])->middleware(\App\Http\Middleware\Roles::class.':3')->name('editUser'); 

    Route::delete('/admin/delete/{id}',[
        CultivationController::class,
        'deleteUser'
    ])->middleware(\App\Http\Middleware\Roles::class.':3')->name('deleteUser');

    Route::post('/save/admin',[
        CultivationController::class,
        'saveUser'
    ])->middleware(\App\Http\Middleware\Roles::class.':3')->name('saveUser');

    // AJAX: fetch allowed subjects for teacher per class/section
    Route::post('/api/teacher/subjects', [
        CultivationController::class,
        'teacherSubjects'
    ])->name('api.teacher.subjects');

    Route::post('/api/teacher/assignment-availability', [
        CultivationController::class,
        'assignmentAvailability'
    ])->name('api.teacher.assignment-availability');

// Debug routes removed
        

    Route::get('/admin/list',[
        CultivationController::class,
        'userRegList'
    ])->middleware(\App\Http\Middleware\Roles::class.':3')->name('userRegList');

    // API endpoints for bulk photo uploads
    Route::get('/api/teachers/list', [
        TeacherController::class,
        'getTeachersList'
    ])->name('api.teachers.list');

    Route::get('/api/staff/list', [
        StaffController::class,
        'getStaffList'
    ])->name('api.staff.list');

    Route::get('/api/governing-body/list', [
        InstituteController::class,
        'getGoverningBodyList'
    ])->name('api.governing-body.list');

    //web font end
