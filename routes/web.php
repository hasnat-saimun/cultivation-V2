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

Route::post('/login/confirm',[
    FrontController::class ,
    'cultivationLogin'
])->name('cultivationLogin');
Route::get('/login',[
    FrontController::class,
    'adminLogin'
])->name('adminLogin');
Route::get('/logout',[
    FrontController::class,
    'adminLogout'
])->name('adminLogout');
Route::post('/register',[
    FrontController::class ,
    'adminRegister'
])->name('adminRegister');

Route::middleware(['adminGuard'])->group (function(){
    
Route::get('/',[
    FrontController::class,
    'homePage'
])->name('homePage');

//Result Part
Route::get('/result',[
    BackofficeController::class,
    'resultPart'
])->name('resultPart');

//Cultivation Part
Route::get('/dashboard',[
    CultivationController::class,
    'cultivationIndex'
])->name('cultivationIndex');

});




//Cultivation Part
Route::get('/dashboard',[
    CultivationController::class,
    'cultivationIndex'
])->name('cultivationIndex');
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
Route::get('/notice/delete/{id}',[
    NoticeController::class ,
    'delNotice'
])->name('delNotice');
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

Route::get('/photo/content/delete/{id}',[
    GalleryController::class ,
    'delPhotoContent'
])->name('delPhotoContent');

Route::get('/photo/delete/{id}',[
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

Route::get('/video/content/delete/{id}',[
    GalleryController::class ,
    'delVideoContent'
])->name('delVideoContent');

Route::get('/video/delete/{id}',[
    GalleryController::class ,
    'delVideo'
])->name('delVideo');

//video end

Route::get('/institute/info/',[
    InstituteController::class,
    'insInfo'
])->name('insInfo');

Route::get('/institute/info/img/del/{id}',[
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

Route::get('/academic/exPlc/content/delete/{id}',[
    InstituteController::class ,
    'delexPlcCon'
])->name('delexPlcCon');
Route::get('/institute/principal/exList/del/{id}',[
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

Route::get('/institute/committee/dlt/image/{id}',[
    InstituteController::class ,
    'delImgContent'
])->name('delImgContent');

Route::get('/institute/committee/del/{id}',[
    InstituteController::class ,
    'delManagingCommittee'
])->name('delManagingCommittee');

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

Route::get('/academic/syllabus/content/delete/{id}',[
    AcademicController::class ,
    'delSyllabusContent'
])->name('delSyllabusContent');

Route::get('/academic/syllabus/del/{id}',[
    AcademicController::class ,
    'delSyllabus'
])->name('delSyllabus');


Route::get('/academic/classRoutine/',[
    AcademicController::class ,
    'classRoutineManage'
])->name('classRoutineManage');

Route::post('/academic/classRoutine/save',[
    AcademicController::class ,
    'saveClassRoutine'
])->name('saveClassRoutine');

Route::get('/academic/classRoutine/edit/{id}',[
    AcademicController::class ,
    'editClassRoutine'
])->name('editClassRoutine');

Route::get('/academic/classRoutine/del/{id}',[
    AcademicController::class ,
    'delClassRoutine'
])->name('delClassRoutine');

Route::get('/academic/classRoutine/content/delete/{id}',[
    AcademicController::class ,
    'delClassRoutineContent'
])->name('delClassRoutineContent');

Route::get('/academic/examRoutine/',[
    AcademicController::class ,
    'examRoutineManage'
])->name('examRoutineManage');

Route::post('/academic/examRoutine/save',[
    AcademicController::class ,
    'saveExamRoutine'
])->name('saveExamRoutine');

Route::get('/academic/examRoutine/edit/{id}',[
    AcademicController::class ,
    'editExamRoutine'
])->name('editExamRoutine');

Route::get('/academic/examRoutine/del/{id}',[
    AcademicController::class ,
    'delExamRoutine'
])->name('delExamRoutine');

Route::get('/academic/examRoutine/content/delete/{id}',[
    AcademicController::class ,
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

Route::get('/academic/semisterPlan/del/{id}',[
    AcademicController::class ,
    'delSemisterPlan'
])->name('delSemisterPlan');

Route::get('/academic/semisterPlan/content/delete/{id}',[
    AcademicController::class ,
    'delSemisterPlanContent'
])->name('delSemisterPlanContent');

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


Route::get('/academic/placementCell/content/delete/{id}',[
    PlacementCellController::class ,
    'delPlcCon'
])->name('delPlcCon');

Route::get('/placement/placementCell/delete/{id}',[
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


Route::get('/academic/needyStudentPanel/photo/delete/{id}',[
    PlacementCellController::class ,
    'delNeedyStdPanelCon'
])->name('delNeedyStdPanelCon');
Route::get('/academic/needyStudentPanel/documents/delete/{id}',[
    PlacementCellController::class ,
    'delNeedyStdPaneldoc'
])->name('delNeedyStdPaneldoc');

Route::get('/placement/needyStudentPanel/delete/{id}',[
    PlacementCellController::class ,
    'delNeedyStdPanel'
])->name('delNeedyStdPanel');
//academic info ends here

//
Route::get('/configuration',[
    CultivationController::class ,
    'serverConfig'
])->name('serverConfig');
Route::post('/configuration/save',[
    CultivationController::class ,
    'saveConfig'
])->name('saveConfig');
Route::get('/sign/del/{id}',[
    CultivationController::class ,
    'delSign'
])->name('delSign');
Route::post('/sign/save',[
    CultivationController::class,
    'saveSign'
])->name('saveSign');
Route::get('/logo/del/{id}',[
    CultivationController::class ,
    'delLogo'
])->name('delLogo');
Route::post('/logo/save',[
    CultivationController::class ,
    'saveLogo'
])->name('saveLogo');
Route::get('/favicon/del/{id}',[
    CultivationController::class ,
    'delFavicon'
])->name('delFavicon');
Route::post('/favicon/save',[
    CultivationController::class ,
    'saveFavicon'
])->name('saveFavicon');
Route::get('/avatar/del/{id}',[
    CultivationController::class ,
    'delAvatar'
])->name('delAvatar');
Route::post('/avatar/save',[
    CultivationController::class ,
    'saveAvatar'
])->name('saveAvatar');

//Account Part
Route::get('/account',[
    BackofficeController::class ,
    'accountPart'
])->name('accountPart');

//Fees str
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

Route::get('/delete-fees-data/{id}',[
    individualController::class,      // delete Fees
    'deleteFees'
])->name('deleteFees');

//Fees end

    //cashCalculas str
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

    Route::get('/delete-calculas-data/{id}',[
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
    //cashCalculas end

     //Tuition str
     Route::get('/getStudentForTutionFee/{stdId}',[
        tuitionController::class,
        'getStudentForTutionFee'
    ])->name('getStudentForTutionFee');

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

    Route::get('/delete-tuition-fee/{id}',[
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
    admissionController::class ,
    'admitStudent'
])->name('admitStudent');
Route::post('/student/admit/confirm',[
    admissionController::class ,
    'confirmAdmit'
])->name('confirmAdmit');
Route::get('/view/student/{stdId}',[
    admissionController::class,
    'viewAdmission'
])->name('viewAdmission');
Route::get('/student/edit/{stdId}',[
    admissionController::class ,
    'editStudent'
])->name('editStudent');

Route::post('/student/edit/confirm',[
    admissionController::class ,
    'updateAdmit'
])->name('updateAdmit');


Route::post('/student/photo/update',[
    admissionController::class ,
    'stdPhotoUpdate'
])->name('stdPhotoUpdate');


Route::get('/student/del/avatar/{stdId}',[
    admissionController::class ,
    'delStudentPhoto'
])->name('delStudentPhoto');

Route::get('/student/del/{stdId}',[
    admissionController::class ,
    'delStudent'
])->name('delStudent');


Route::get('/student/list',[
    admissionController::class,
    'studentList'
])->name('studentList');

Route::get('/student/idCard/{stdId}',[
    admissionController::class ,
    'stdIdCard'
])->name('stdIdCard');

Route::get('/student/promotion',[
    admissionController::class ,
    'studentPromotion'
])->name('studentPromotion');


Route::post('/student/promotion/getData',[
    admissionController::class ,
    'getPromotionData'
])->name('getPromotionData');

Route::post('/student/promotion/confirm',[
    admissionController::class ,
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
Route::get('/teacher/del/{profileId}',[
    TeacherController::class ,
    'delTeacher'
])->name('delTeacher');
Route::get('/teacher/del/avatar/{profileId}',[
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
Route::get('/staff/del/{profileId}',[
    StaffController::class ,
    'delStaff'
])->name('delStaff');
Route::get('/staff/del/avatar/{profileId}',[
    StaffController::class ,
    'delStaffPhoto'
])->name('delStaffPhoto');

Route::post('/staff/avatar/update',[
    StaffController::class,
    'updateStaffPhoto'
])->name('updateStaffPhoto');

Route::get('/staff/list',[
    StaffController::class ,
    'staffList'
])->name('staffList');


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
Route::get('/class/del/{itemId}',[
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
Route::get('/department/del/{itemId}',[
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
Route::get('/Section/del/{itemId}',[
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
Route::get('/session/del/{itemId}',[
    individualController::class ,
    'delSession'
])->name('delSession');

Route::get('/session/list',[
    individualController::class ,
    'allSession'
])->name('allSession');

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
Route::get('/subject/del/{itemId}',[
    SubjectController::class ,
    'delSubject'
])->name('delSubject');

Route::get('/subject/list',[
    SubjectController::class,
    'allSubject'
])->name('allSubject');


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
Route::get('/exam/del/{itemId}',[
    ExamController::class ,
    'delExam'
])->name('delExam');

Route::get('/exam/list',[
    ExamController::class ,
    'allExam'
])->name('allExam');


//Marks route declaration

Route::get('/marks/add',[
    MarksheetController::class ,
    'addMarks'
])->name('addMarks');
Route::post('/marks/add/getData',[
    MarksheetController::class ,
    'getMarks'
])->name('getMarks');
Route::post('/marks/add/confirm',[
    MarksheetController::class ,
    'confirmMarks'
])->name('confirmMarks');

Route::get('/marksheet/create',[
    MarksheetController::class ,
    'createMarksheet'
])->name('createMarksheet');

Route::get('/marksheet/all',[
    MarksheetController::class ,
    'allMarksheet'
])->name('allMarksheet');

Route::post('/marksheet/generate',[
    MarksheetController::class ,
    'generateMarksheet'
])->name('generateMarksheet');


//Admit Card route declaration

Route::get('/admit/card/creation',[
    ExamController::class ,
    'admitCard'
])->name('admitCard');
Route::post('/admit/card/getData',[
    ExamController::class ,
    'getAdmitCard'
])->name('getAdmitCard');

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
Route::get('/grade/del/{itemId}',[
    GradeListController::class ,
    'delGrade'
])->name('delGrade');

Route::get('/grade/list',[
    GradeListController::class ,
    'allGrade'
])->name('allGrade');

//admin school Requst str
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


Route::get('/register/logo/delete/{regId}',[
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

Route::get('/register/del/{regId}',[
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





