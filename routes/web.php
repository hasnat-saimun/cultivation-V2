<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BackofficeController;
use App\Http\Controllers\AcademicController;
use App\Http\Controllers\CultivationController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\FrontController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\GradeListController;
use App\Http\Controllers\InstituteController;
use App\Http\Controllers\MarksheetController;
use App\Http\Controllers\NoticeController;
use App\Http\Controllers\PlacementCellController;
use App\Http\Controllers\individualController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\admissionController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\cashCalculasController;
use App\Http\Controllers\tuitionController;
use App\Http\Controllers\registerController;
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

Route::get('/',[
    FrontController::class,
    'homePage'
])->name('homePage');

Route::post('/admin/login/confirm',[
    FrontController::class ,
    'cultivationLogin'
])->name('cultivationLogin');
Route::get('/admin/login',[
    FrontController::class,
    'adminLogin'
])->name('adminLogin');
Route::get('/admin/logout',[
    FrontController::class,
    'adminLogout'
])->name('adminLogout');
Route::post('/admin/register',[
    FrontController::class ,
    'adminRegister'
])->name('adminRegister');

Route::middleware(['superAdmin'])->group (function(){
    
    //Cultivation Part
    
    Route::get('/admin/dashboard',[
        CultivationController::class,
        'cultivationIndex'
    ])->name('cultivationIndex');

    Route::get('/admin/profile',[
        CultivationController::class,
        'adminProfile'
    ])->name('adminProfile');

    Route::post('/admin/profile/save',[
        CultivationController::class ,
        'saveAdminProfile'
    ])->name('saveAdminProfile');

    Route::post('/admin/profile/password/save',[
        CultivationController::class ,
        'changeAdminPassword'
    ])->name('changeAdminPassword');

    Route::get('/admin/notice/list',[
        NoticeController::class ,
        'noticeList'
    ])->name('noticeList');

    Route::get('/admin/notice/new',[
        NoticeController::class ,
        'newNotice'
    ])->name('newNotice');

    Route::post('/admin/notice/save',[
        NoticeController::class ,
        'saveNotice'
    ])->name('saveNotice');

    Route::get('/admin/notice/edit/{id}',[
        NoticeController::class ,
        'editNotice'
    ])->name('editNotice');

    Route::post('/admin/notice/update',[
        NoticeController::class ,
        'updateNotice'
    ])->name('updateNotice');

    Route::get('/admin/notice/delete/{id}',[
        NoticeController::class ,
        'delNotice'
    ])->name('delNotice');

    Route::get('/admin/notice/preview/{id}',[
        NoticeController::class ,
        'prevNotice'
    ])->name('prevNotice');

    //notice ends here

    //Image str

    Route::get('/admin/institute/photo/',[
        GalleryController::class,
        'newPhoto'
    ])->name('newPhoto');

    Route::post('/admin/photo/save',[
        GalleryController::class ,
        'savePhoto'
    ])->name('savePhoto');

    Route::get('/admin/photo/edit/{id}',[
        GalleryController::class ,
        'editPhoto'
    ])->name('editPhoto');

    Route::get('/admin/photo/content/delete/{id}',[
        GalleryController::class ,
        'delPhotoContent'
    ])->name('delPhotoContent');

    Route::get('/admin/photo/delete/{id}',[
        GalleryController::class ,
        'delPhoto'
    ])->name('delPhoto');

    //image end

    //video str

    Route::get('/admin/institute/video/',[
        GalleryController::class,
        'newVideo'
    ])->name('newVideo');

    Route::post('/admin/video/save',[
        GalleryController::class ,
        'saveVideo'
    ])->name('saveVideo');

    Route::get('/admin/video/edit/{id}',[
        GalleryController::class ,
        'editVideo'
    ])->name('editVideo');

    Route::get('/admin/video/content/delete/{id}',[
        GalleryController::class ,
        'delVideoContent'
    ])->name('delVideoContent');

    Route::get('/admin/video/delete/{id}',[
        GalleryController::class ,
        'delVideo'
    ])->name('delVideo');

    //video end

    Route::get('/admin/institute/info/',[
        InstituteController::class,
        'insInfo'
    ])->name('insInfo');

    Route::get('/admin/institute/info/img/del/{id}',[
        InstituteController::class ,
        'delHeroImg'
    ])->name('delHeroImg');

    Route::post('/admin/institute/details/',[
        InstituteController::class ,
        'insDetails'
    ])->name('insDetails');

    Route::get('/admin/institute/principal/speech',[
        InstituteController::class ,
        'principalSpeech'
    ])->name('principalSpeech');

    Route::post('/admin/institute/principal/speech/save',[
        InstituteController::class ,
        'savePrincipalSpeech'
    ])->name('savePrincipalSpeech');

    Route::get('/admin/institute/principal/exList',[
        InstituteController::class,
        'exPrincipal'
    ])->name('exPrincipal');

    Route::get('/admin/institute/view/exPrincipal/{id}',[
        InstituteController::class,
        'viewExPrincipal'
    ])->name('viewExPrincipal');

    Route::post('/admin/institute/principal/exList/save',[
        InstituteController::class ,
        'saveExPrincipal'
    ])->name('saveExPrincipal');

    Route::get('/admin/institute/principal/exList/edit/{id}',[
        InstituteController::class ,
        'editExPrincipal'
    ])->name('editExPrincipal');

    Route::get('/admin/academic/exPlc/content/delete/{id}',[
        InstituteController::class ,
        'delexPlcCon'
    ])->name('delexPlcCon');
    Route::get('/admin/institute/principal/exList/del/{id}',[
        InstituteController::class ,
        'delExPrincipal'
    ])->name('delExPrincipal');

    Route::get('/admin/institute/committee/',[
        InstituteController::class ,
        'managingCommittee'
    ])->name('managingCommittee');

    Route::post('/admin/institute/committee/save',[
        InstituteController::class ,
        'saveManagingCommittee'
    ])->name('saveManagingCommittee');

    Route::get('/admin/institute/committee/view/{id}',[
        InstituteController::class ,
        'viewManagingCommittee'
    ])->name('viewManagingCommittee');

    Route::get('/admin/institute/committee/edit/{id}',[
        InstituteController::class ,
        'editManagingCommittee'
    ])->name('editManagingCommittee');

    Route::get('/admin/institute/committee/dlt/image/{id}',[
        InstituteController::class ,
        'delImgContent'
    ])->name('delImgContent');

    Route::get('/admin/institute/committee/del/{id}',[
        InstituteController::class ,
        'delManagingCommittee'
    ])->name('delManagingCommittee');

    // institute info ends here

    Route::get('/admin/academic/syllabus/',[
        AcademicController::class ,
        'syllabusManage'
    ])->name('syllabusManage');

    Route::post('/admin/academic/syllabus/save',[
        AcademicController::class ,
        'saveSyllabus'
    ])->name('saveSyllabus');

    Route::get('/admin/academic/syllabus/edit/{id}',[
        AcademicController::class ,
        'editSyllabus'
    ])->name('editSyllabus');

    Route::get('/admin/academic/syllabus/content/delete/{id}',[
        AcademicController::class ,
        'delSyllabusContent'
    ])->name('delSyllabusContent');

    Route::get('/admin/academic/syllabus/del/{id}',[
        AcademicController::class ,
        'delSyllabus'
    ])->name('delSyllabus');


    Route::get('/admin/academic/classRoutine/',[
        AcademicController::class ,
        'classRoutineManage'
    ])->name('classRoutineManage');

    Route::post('/admin/academic/classRoutine/save',[
        AcademicController::class ,
        'saveClassRoutine'
    ])->name('saveClassRoutine');

    Route::get('/admin/academic/classRoutine/edit/{id}',[
        AcademicController::class ,
        'editClassRoutine'
    ])->name('editClassRoutine');

    Route::get('/admin/academic/classRoutine/del/{id}',[
        AcademicController::class ,
        'delClassRoutine'
    ])->name('delClassRoutine');

    Route::get('/admin/academic/classRoutine/content/delete/{id}',[
        AcademicController::class ,
        'delClassRoutineContent'
    ])->name('delClassRoutineContent');

    Route::get('/admin/academic/examRoutine/',[
        AcademicController::class ,
        'examRoutineManage'
    ])->name('examRoutineManage');

    Route::post('/admin/academic/examRoutine/save',[
        AcademicController::class ,
        'saveExamRoutine'
    ])->name('saveExamRoutine');

    Route::get('/admin/academic/examRoutine/edit/{id}',[
        AcademicController::class ,
        'editExamRoutine'
    ])->name('editExamRoutine');

    Route::get('/admin/academic/examRoutine/del/{id}',[
        AcademicController::class ,
        'delExamRoutine'
    ])->name('delExamRoutine');

    Route::get('/admin/academic/examRoutine/content/delete/{id}',[
        AcademicController::class ,
        'delExamRoutineContent'
    ])->name('delExamRoutineContent');

    Route::get('/admin/academic/semisterPlan/',[
        AcademicController::class,
        'semisterPlanManage'
    ])->name('semisterPlanManage');

    Route::post('/admin/academic/semisterPlan/save',[
        AcademicController::class ,
        'saveSemisterPlan'
    ])->name('saveSemisterPlan');

    Route::get('/admin/academic/semisterPlan/edit/{id}',[
        AcademicController::class ,
        'editSemisterPlan'
    ])->name('editSemisterPlan');

    Route::get('/admin/academic/semisterPlan/del/{id}',[
        AcademicController::class ,
        'delSemisterPlan'
    ])->name('delSemisterPlan');

    Route::get('/admin/academic/semisterPlan/content/delete/{id}',[
        AcademicController::class ,
        'delSemisterPlanContent'
    ])->name('delSemisterPlanContent');

    Route::get('/admin/placement/jobPlacement/',[
        PlacementCellController::class ,
        'placementCell'
    ])->name('placementCell');

    Route::post('/admin/placement/placementCell/save',[
        PlacementCellController::class ,
        'savePlacementCell'
    ])->name('savePlacementCell');

    Route::get('/admin/placement/placementCell/edit/{id}',[
        PlacementCellController::class ,
        'editPlc'
    ])->name('editPlc');


    Route::get('/admin/academic/placementCell/content/delete/{id}',[
        PlacementCellController::class ,
        'delPlcCon'
    ])->name('delPlcCon');

    Route::get('/admin/placement/placementCell/delete/{id}',[
        PlacementCellController::class ,
        'delPlc'
    ])->name('delPlc');

    Route::get('/admin/placement/needyStudentPanel/',[
        PlacementCellController::class ,
        'needyStudentPanel'
    ])->name('needyStudentPanel');

    Route::post('/admin/placement/needyStudentPanel/save',[
        PlacementCellController::class ,
        'saveNeedyStdPanel'
    ])->name('saveNeedyStdPanel');

    Route::get('/admin/placement/needyStudentPanel/edit/{id}',[
        PlacementCellController::class ,
        'editNeedyStdPanel'
    ])->name('editNeedyStdPanel');


    Route::get('/admin/academic/needyStudentPanel/photo/delete/{id}',[
        PlacementCellController::class ,
        'delNeedyStdPanelCon'
    ])->name('delNeedyStdPanelCon');
    Route::get('/admin/academic/needyStudentPanel/documents/delete/{id}',[
        PlacementCellController::class ,
        'delNeedyStdPaneldoc'
    ])->name('delNeedyStdPaneldoc');

    Route::get('/admin/placement/needyStudentPanel/delete/{id}',[
        PlacementCellController::class ,
        'delNeedyStdPanel'
    ])->name('delNeedyStdPanel');
    //academic info ends here

    //
    Route::get('/admin/configuration',[
        CultivationController::class ,
        'serverConfig'
    ])->name('serverConfig');
    Route::post('/ademin/configuration/save',[
        CultivationController::class ,
        'saveConfig'
    ])->name('saveConfig');
    Route::get('/ademin/sign/del/{id}',[
        CultivationController::class ,
        'delSign'
    ])->name('delSign');
    Route::post('/ademin/sign/save',[
        CultivationController::class,
        'saveSign'
    ])->name('saveSign');
    Route::get('/ademin/logo/del/{id}',[
        CultivationController::class ,
        'delLogo'
    ])->name('delLogo');
    Route::post('/ademin/logo/save',[
        CultivationController::class ,
        'saveLogo'
    ])->name('saveLogo');
    Route::get('/ademin/favicon/del/{id}',[
        CultivationController::class ,
        'delFavicon'
    ])->name('delFavicon');
    Route::post('/ademin/favicon/save',[
        CultivationController::class ,
        'saveFavicon'
    ])->name('saveFavicon');
    Route::get('/ademin/avatar/del/{id}',[
        CultivationController::class ,
        'delAvatar'
    ])->name('delAvatar');
    Route::post('/ademin/avatar/save',[
        CultivationController::class ,
        'saveAvatar'
    ])->name('saveAvatar');

    //Account Part
    Route::get('/ademin/account',[
        BackofficeController::class ,
        'accountPart'
    ])->name('accountPart');

    //Fees str
    Route::get('/ademin/add-fees',[
        individualController::class, //add Fees
        'feesForm'
    ])->name('feesForm');

    Route::get('/ademin/edit-fees-data/{id}',[
        individualController::class, //edit Fees
        'editFees'
    ])->name('editFees');

    Route::post('/ademin/update-fees',[
        individualController::class, //update Fees
        'updateFees'
    ])->name('updateFees');


    Route::post('/ademin/save-fees',[
        individualController::class, //add Fees
        'saveFees'
    ])->name('saveFees');

    Route::get('/ademin/delete-fees-data/{id}',[
        individualController::class,      // delete Fees
        'deleteFees'
    ])->name('deleteFees');

    //Fees end

    //cashCalculas str
    Route::get('/admin/cash-calculas-from',[
        cashCalculasController::class,    //cashCalculas main page
        'cashCalculasView'
    ])->name('cashCalculasView');

    Route::get('/ademin/get-report',[
        cashCalculasController::class,    //reportList page
        'reportListView'
    ])->name('reportListView');

    Route::get('/admin/single-report/{id}',[
        cashCalculasController::class,    // report single page
        'singleView'
    ])->name('singleView');


    Route::post('/admin/save-cash-calculas',[
        cashCalculasController::class,    //saveCashCalculas brackhand
        'saveCashCalculas'
    ])->name('saveCashCalculas');

    Route::get('/admin/edit-cash-calculas/{id}',[
        cashCalculasController::class,     // edit calculas 
        'editCashCalculas'
    ])->name('editCashCalculas');

    Route::post('/admin/update-cash-calculas',[
        cashCalculasController::class,   //update calculas
        'updateCashCalculas'
    ])->name('updateCashCalculas');

    Route::get('/admin/delete-calculas-data/{id}',[
        cashCalculasController::class,      // delete calculas
        'dltCalculasData'
    ])->name('dltCalculasData');

    Route::get('/admin/calculas-repot-generate/{id}',[
        cashCalculasController::class,   // calculas Report
        'cashReport'
    ])->name('cashReport');

    Route::get('/admin/calculas-date-repot-generate',[
        cashCalculasController::class,   // calculas Report
        'cashDateReport'
    ])->name('cashDateReport');

    Route::post('/admin/calculas-date-repot-recipit',[
        cashCalculasController::class, //  free
        'getCashReport'
    ])->name('getCashReport');
    //cashCalculas end

        //Tuition str
        Route::get('/admin/getStudentForTutionFee/{stdId}',[
        tuitionController::class,
        'getStudentForTutionFee'
    ])->name('getStudentForTutionFee');

    Route::get('/admin/add-tuition-fee',[
        tuitionController::class,   //add tuition free
        'tuitionFee'
    ])->name('tuitionFee');

    Route::post('/admin/save-tuition-fee',[
        tuitionController::class,
        'saveTuitionfee'
    ])->name('saveTuitionfee');

    Route::get('/admin/tuition-fee-list',[
        tuitionController::class,   // tuition free list
        'tuitionFeeList'
    ])->name('tuitionFeeList');

    Route::get('/admin/tuition-fee-view/{id}',[
        tuitionController::class,   // tuition free view
        'tuitionFeeView'
    ])->name('tuitionFeeView');

    Route::get('/admin/edit-tuition-fee/{id}',[
        tuitionController::class, //edit tuition free
        'editTuitionFee'
    ])->name('editTuitionFee');

    Route::post('/admin/update-tuition-fee',[
        tuitionController::class, //update tuition free
        'updateTuitionFee'
    ])->name('updateTuitionFee');

    Route::get('/admin/delete-tuition-fee/{id}',[
        tuitionController::class,      // delete tuition free
        'dltTuitionFee'
    ])->name('dltTuitionFee');

    Route::get('/admin/tuition-repot-generate/{id}',[
        tuitionController::class,   // tuition free tuitionReport
        'tuitionReport'
    ])->name('tuitionReport');

    Route::get('/admin/student/fees/generate',[
        tuitionController::class, //edit Fees
        'feesReport'
    ])->name('feesReport');

    Route::post('/admin/student/fees/generate/report',[
        tuitionController::class, //update tuition free
        'getFeesReport'
    ])->name('getFeesReport');
    //Tuition end

    //Account part end

    //Academic Part
    Route::get('/admin/academic',[
        BackofficeController::class ,
        'index'
    ])->name('academicPart');
    //Student route declaration
    Route::get('/admin/student/admit',[
        admissionController::class ,
        'admitStudent'
    ])->name('admitStudent');
    Route::post('/admin/student/admit/confirm',[
        admissionController::class ,
        'confirmAdmit'
    ])->name('confirmAdmit');
    Route::get('/admin/view/student/{stdId}',[
        admissionController::class,
        'viewAdmission'
    ])->name('viewAdmission');
    Route::get('/admin/student/edit/{stdId}',[
        admissionController::class ,
        'editStudent'
    ])->name('editStudent');

    Route::post('/admin/student/edit/confirm',[
        admissionController::class ,
        'updateAdmit'
    ])->name('updateAdmit');


    Route::post('/admin/student/photo/update',[
        admissionController::class ,
        'stdPhotoUpdate'
    ])->name('stdPhotoUpdate');


    Route::get('/admin/student/del/avatar/{stdId}',[
        admissionController::class ,
        'delStudentPhoto'
    ])->name('delStudentPhoto');

    Route::get('/admin/student/del/{stdId}',[
        admissionController::class ,
        'delStudent'
    ])->name('delStudent');


    Route::get('/admin/student/list',[
        admissionController::class,
        'studentList'
    ])->name('studentList');

    Route::get('/admin/student/idCard/{stdId}',[
        admissionController::class ,
        'stdIdCard'
    ])->name('stdIdCard');

    Route::get('/admin/student/promotion',[
        admissionController::class ,
        'studentPromotion'
    ])->name('studentPromotion');


    Route::post('/admin/student/promotion/getData',[
        admissionController::class ,
        'getPromotionData'
    ])->name('getPromotionData');

    Route::post('/admin/student/promotion/confirm',[
        admissionController::class ,
        'confirmPromotData'
    ])->name('confirmPromotData');


    //Teacher route declaration

    Route::get('/admin/teacher/admit',[
        TeacherController::class ,
        'addTeacher'
    ])->name('addTeacher');
    Route::post('/admin/teacher/admit/confirm',[
        TeacherController::class ,
        'confirmTeacher'
    ])->name('confirmTeacher');
    Route::get('/admin/view/teacher/{profileId}',[
        TeacherController::class,
        'viewTeacher'
    ])->name('viewTeacher');
    Route::get('/admin/teacher/edit/{profileId}',[
        TeacherController::class ,
        'editTeacher'
    ])->name('editTeacher');
    Route::post('/admin/teacher/edit/confirm',[
        TeacherController::class ,
        'updateTeacher'
    ])->name('updateTeacher');
    Route::get('/admin/teacher/del/{profileId}',[
        TeacherController::class ,
        'delTeacher'
    ])->name('delTeacher');
    Route::get('/admin/teacher/del/avatar/{profileId}',[
        TeacherController::class ,
        'delTeacherPhoto'
    ])->name('delTeacherPhoto');


    Route::post('/admin/teacher/avatar/update',[
        TeacherController::class,
        'updateTeacherPhoto'
    ])->name('updateTeacherPhoto');

    Route::get('/admin/teacher/list',[
        TeacherController::class ,
        'teacherList'
    ])->name('teacherList');

    //Teacher route declaration

    Route::get('/admin/staff/admit',[
        StaffController::class ,
        'addStaff'
    ])->name('addStaff');
    Route::post('/admin/staff/admit/confirm',[
        StaffController::class ,
        'confirmStaff'
    ])->name('confirmStaff');
    Route::get('/admin/view/staff/{profileId}',[
        StaffController::class,
        'viewStaff'
    ])->name('viewStaff');
    Route::get('/admin/staff/edit/{profileId}',[
        StaffController::class ,
        'editStaff'
    ])->name('editStaff');
    Route::post('/admin/staff/edit/confirm',[
        StaffController::class ,
        'updateStaff'
    ])->name('updateStaff');
    Route::get('/admin/staff/del/{profileId}',[
        StaffController::class ,
        'delStaff'
    ])->name('delStaff');
    Route::get('/admin/staff/del/avatar/{profileId}',[
        StaffController::class ,
        'delStaffPhoto'
    ])->name('delStaffPhoto');

    Route::post('/admin/staff/avatar/update',[
        StaffController::class,
        'updateStaffPhoto'
    ])->name('updateStaffPhoto');

    Route::get('/admin/staff/list',[
        StaffController::class ,
        'staffList'
    ])->name('staffList');


    //Classes route declaration

    Route::get('/admin/class/create',[
        individualController::class ,
        'createClass'
    ])->name('createClass');
    Route::post('/admin/class/create/confirm',[
        individualController::class ,
        'confirmClass'
    ])->name('confirmClass');
    Route::get('/admin/class/edit/{itemId}',[
        individualController::class ,
        'editClass'
    ])->name('editClass');
    Route::post('/admin/class/edit/confirm',[
        individualController::class ,
        'updateClass'
    ])->name('updateClass');
    Route::get('/admin/class/del/{itemId}',[
        individualController::class ,
        'delClass'
    ])->name('delClass');

    Route::get('/admin/class/list',[
        individualController::class ,
        'allClasses'
    ])->name('allClasses');


    //Department route declaration

    Route::get('/admin/department/create',[
        individualController::class ,
        'createDepartment'
    ])->name('createDepartment');
    Route::post('/admin/department/create/confirm',[
        individualController::class ,
        'confirmDepartment'
    ])->name('confirmDepartment');
    Route::get('/admin/department/edit/{itemId}',[
        individualController::class ,
        'editDepartment'
    ])->name('editDepartment');
    Route::post('/admin/department/edit/confirm',[
        individualController::class ,
        'updateDepartment'
    ])->name('updateDepartment');
    Route::get('/admin/department/del/{itemId}',[
        individualController::class ,
        'delDepartment'
    ])->name('delDepartment');

    Route::get('/admin/department/list',[
        individualController::class ,
        'allDepartment'
    ])->name('allDepartment');

    //Section route declaration

    Route::get('/admin/section/create',[
        individualController::class ,
        'createSection'
    ])->name('createSection');
    Route::post('/admin/Section/create/confirm',[
        individualController::class ,
        'confirmSection'
    ])->name('confirmSection');
    Route::get('/admin/Section/edit/{itemId}',[
        individualController::class ,
        'editSection'
    ])->name('editSection');
    Route::post('/admin/Section/edit/confirm',[
        individualController::class ,
        'updateSection'
    ])->name('updateSection');
    Route::get('/admin/Section/del/{itemId}',[
        individualController::class ,
        'delSection'
    ])->name('delSection');

    Route::get('/admin/Section/list',[
        individualController::class ,
        'allSection'
    ])->name('allSection');

    //Session route declaration

    Route::get('/admin/session/create',[
        individualController::class ,
        'createSession'
    ])->name('createSession');
    Route::post('/admin/session/create/confirm',[
        individualController::class ,
        'confirmSession'
    ])->name('confirmSession');
    Route::get('/admin/session/edit/{itemId}',[
        individualController::class ,
        'editSession'
    ])->name('editSession');
    Route::post('/admin/session/edit/confirm',[
        individualController::class ,
        'updateSession'
    ])->name('updateSession');
    Route::get('/admin/session/del/{itemId}',[
        individualController::class ,
        'delSession'
    ])->name('delSession');

    Route::get('/admin/session/list',[
        individualController::class ,
        'allSession'
    ])->name('allSession');

    //Result Part
    Route::get('/admin/result',[
        BackofficeController::class,
        'resultPart'
    ])->name('resultPart');
    
    //Subject route declaration

    Route::get('/admin/subject/create',[
        SubjectController::class ,
        'createSubject'
    ])->name('createSubject');
    Route::post('/admin/subject/create/confirm',[
        SubjectController::class ,
        'confirmSubject'
    ])->name('confirmSubject');
    Route::get('/admin/subject/edit/{itemId}',[
        SubjectController::class ,
        'editSubject'
    ])->name('editSubject');
    Route::post('/admin/subject/edit/confirm',[
        SubjectController::class ,
        'updateSubject'
    ])->name('updateSubject');
    Route::get('/admin/subject/del/{itemId}',[
        SubjectController::class ,
        'delSubject'
    ])->name('delSubject');

    Route::get('/admin/subject/list',[
        SubjectController::class,
        'allSubject'
    ])->name('allSubject');


    //Exam route declaration

    Route::get('/admin/exam/create',[
        ExamController::class ,
        'createExam'
    ])->name('createExam');
    Route::post('/admin/exam/create/confirm',[
        ExamController::class ,
        'confirmExam'
    ])->name('confirmExam');
    Route::get('/admin/exam/edit/{itemId}',[
        ExamController::class ,
        'editExam'
    ])->name('editExam');
    Route::post('/admin/exam/edit/confirm',[
        ExamController::class ,
        'updateExam'
    ])->name('updateExam');
    Route::get('/admin/exam/del/{itemId}',[
        ExamController::class ,
        'delExam'
    ])->name('delExam');

    Route::get('/admin/exam/list',[
        ExamController::class ,
        'allExam'
    ])->name('allExam');


    //Marks route declaration

    Route::get('/admin/marks/add',[
        MarksheetController::class ,
        'addMarks'
    ])->name('addMarks');
    Route::post('/admin/marks/add/getData',[
        MarksheetController::class ,
        'getMarks'
    ])->name('getMarks');
    Route::post('/admin/marks/add/confirm',[
        MarksheetController::class ,
        'confirmMarks'
    ])->name('confirmMarks');

    Route::get('/admin/marksheet/create',[
        MarksheetController::class ,
        'createMarksheet'
    ])->name('createMarksheet');

    Route::get('/admin/marksheet/all',[
        MarksheetController::class ,
        'allMarksheet'
    ])->name('allMarksheet');

    Route::post('/admin/marksheet/generate',[
        MarksheetController::class ,
        'generateMarksheet'
    ])->name('generateMarksheet');


    //Admit Card route declaration

    Route::get('/admin/admit/card/creation',[
        ExamController::class ,
        'admitCard'
    ])->name('admitCard');
    Route::post('/admin/admit/card/getData',[
        ExamController::class ,
        'getAdmitCard'
    ])->name('getAdmitCard');

    //Attend Sheet route declaration

    Route::get('/admin/attend/sheet/creation',[
        ExamController::class ,
        'attendSheet'
    ])->name('attendSheet');
    Route::post('/admin/attend/sheet/getData',[
        ExamController::class ,
        'getAttendSheet'
    ])->name('getAttendSheet');

    //grade route declaration

    Route::get('/admin/grade/create',[
        GradeListController::class ,
        'createGrade'
    ])->name('createGrade');
    Route::post('/admin/grade/create/confirm',[
        GradeListController::class ,
        'confirmGrade'
    ])->name('confirmGrade');
    Route::get('/admin/grade/edit/{itemId}',[
        GradeListController::class ,
        'editGrade'
    ])->name('editGrade');
    Route::post('/admin/grade/edit/confirm',[
        GradeListController::class ,
        'updateGrade'
    ])->name('updateGrade');
    Route::get('/admin/grade/del/{itemId}',[
        GradeListController::class ,
        'delGrade'
    ])->name('delGrade');

    Route::get('/admin/grade/list',[
        GradeListController::class ,
        'allGrade'
    ])->name('allGrade');

    //admin school Requst str
    Route::get('/admin/register/request',[
        registerController::class ,
        'registerForm'
    ])->name('registerForm');

    Route::get('/admin/register/list',[
        registerController::class ,
        'registerList'
    ])->name('registerList');

    Route::post('/admin/register/save',[
        registerController::class ,
        'saveRegForm'
    ])->name('saveRegForm');


    Route::get('/admin/register/logo/delete/{regId}',[
        registerController::class ,
        'registerLogoDel'
    ])->name('registerLogoDel');

    Route::post('/admin/register/logo/update',[
        registerController::class ,
        'registerLogoUpdate'
    ])->name('registerLogoUpdate');

    Route::get('/admin/register/view/{regId}',[
        registerController::class ,
        'registerView'
    ])->name('registerView');

    Route::get('/admin/register/edit/{regId}',[
        registerController::class ,
        'registerEdit'
    ])->name('registerEdit');

    Route::post('/admin/register/update',[
        registerController::class,
        'registerUpdate'
    ])->name('registerUpdate');

    Route::get('/admin/register/del/{regId}',[
        registerController::class ,
        'registerDel'
    ])->name('registerDel');
    //admin school Requst end

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
    //MarksheetController end

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

    //web font end

});






