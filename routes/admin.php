<?php

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Dimuat oleh routes/web.php di dalam Route::prefix('admin').
| Semua path di sini otomatis berada di bawah /admin (contoh: '/dash' => /admin/dash).
| Controller: App\Http\Controllers\Admin, view: resources/views/admin.
| Login dilakukan satu pintu di "/" (PortalLoginController).
|
*/

use App\Http\Controllers\Admin\AccountController as Account;
use App\Http\Controllers\Admin\DashController as Dash;
use App\Http\Controllers\Admin\HistoryController as History;
use App\Http\Controllers\Admin\LoginController as Login;
use App\Http\Controllers\Admin\NewsPromoController as NewsPromo;
use App\Http\Controllers\Admin\SurveyPublishController as SurveyPublish;
use App\Http\Controllers\Admin\SurveyResultController as SurveyResult;
use App\Http\Controllers\Admin\SurveyTemplateController as SurveyTemplate;
use App\Http\Controllers\Admin\SysSpecController as SysSpec;
use App\Http\Controllers\Admin\PermitController as Permit;
use App\Http\Controllers\Admin\ManagementController as Management;
use App\Http\Controllers\Admin\NewSurveyController as NewSurvey;
use App\Http\Controllers\Admin\OvertimeController as Overtime;
use Illuminate\Support\Facades\Route;

// /admin -> dashboard kalau sudah login, kalau belum ke halaman login "/"
Route::get('/', [Login::class, 'index']);
Route::get('/logout', [Login::class, 'logout']);

Route::group(['middleware' => ['admin-auth', 'revalidate']], function () {
    // DashController
    Route::get('/dash', [Dash::class, 'index']);
    Route::post('/dash/data/overtime', [Dash::class, 'getTableOT']);
    Route::post('/dash/dlpdf', [Dash::class, 'generatepdf']);
    Route::post('/dash/data/ticket', [Dash::class, 'getTableTicket']);
    Route::get('/dash/export/{nm?}', [Dash::class, 'export']);
    Route::post('/dash/data/usage', [Dash::class, 'usageData']);

    // ManagementController
    Route::get('/management', [Management::class, 'index']);
    Route::get('/management/ap-aging', [Management::class, 'getApAging']);
    Route::get('/management/ar-aging', [Management::class, 'getArAging']);
    Route::get('/management/revenue-data', [Management::class, 'getRevenueData']);
    Route::get('/management/expense-data', [Management::class, 'getExpenseData']);

    // AccountController
    Route::view('/account/profile', 'admin.account.profile');
    Route::get('/account/getbyemail/{email}', [Account::class, 'getbyemail']);
    Route::post('/account/updateprofile', [Account::class, 'updateprofile']);
    Route::post('/account/savepic', [Account::class, 'savepic']);
    Route::post('/account/changepass', [Account::class, 'changepass']);
    Route::view('/account/reset', 'admin.account.index');
    Route::post('/account/data/', [Account::class, 'getTable']);
    Route::post('/account/resetpass', [Account::class, 'resetpass']);

    // SysSpecController
    Route::get('/systemspec', [SysSpec::class, 'index']);
    Route::post('/systemspec/saveimage', [SysSpec::class, 'imgLogin']);
    Route::get('/systemspec/defaultpass', [SysSpec::class, 'defaultpass']);
    Route::post('/systemspec/defaultpasssave', [SysSpec::class, 'defaultpasssave']);

    // NewsPromoController
    Route::view('/news', 'admin.news.index');
    Route::post('/news/all', [NewsPromo::class, 'getTable']);
    Route::get('/news/form/{type}/{id?}', [NewsPromo::class, 'addform']);
    Route::get('/news/id/{id}', [NewsPromo::class, 'getByID']);
    Route::post('/news/save', [NewsPromo::class, 'save']);
    Route::post('/news/savepic', [NewsPromo::class, 'savePic']);
    Route::post('/news/delete', [NewsPromo::class, 'delete']);

    // OvertimeController (approval request lembur tenant & posting ke billing IFCA)
    Route::get('/overtime/approval', [Overtime::class, 'approval']);
    Route::post('/overtime/data/{tab}', [Overtime::class, 'table'])->whereIn('tab', ['new', 'approved', 'cancelled']);
    Route::post('/overtime/approve', [Overtime::class, 'approve']);
    Route::post('/overtime/cancel', [Overtime::class, 'cancel']);
    Route::get('/overtime/posting', [Overtime::class, 'posting']);
    Route::post('/overtime/posting/data', [Overtime::class, 'postingTable']);
    Route::post('/overtime/posting/save', [Overtime::class, 'postingSave']);

    // HistoryController
    Route::get('/history/ticket', [History::class, 'ticket']);
    Route::post('/history/data/ticket', [History::class, 'getTableTicket']);
    Route::get('/history/overtime', [History::class, 'overtime']);
    Route::post('/history/data/overtime', [History::class, 'getTableOT']);
    Route::view('/history/users', 'admin.history.users');
    Route::post('/history/data/users', [History::class, 'getTableLog']);
    Route::post('/history/dlpdf', [History::class, 'dlpdf']);
    Route::get('/history/export/{type}', [History::class, 'export']);

    // SurveyTemplateController
    Route::view('/survey/questions', 'admin.survey.questions.index');
    Route::post('/survey/questions/all', [SurveyTemplate::class, 'getTable']);
    Route::get('/survey/questions/id/{id}', [SurveyTemplate::class, 'getByID']);
    Route::view('/survey/questions/form', 'admin.survey.questions.form');
    Route::post('/survey/questions/save', [SurveyTemplate::class, 'save']);
    Route::post('/survey/questions/delete', [SurveyTemplate::class, 'delete']);

    // SurveyPublishController
    Route::view('/survey/publish', 'admin.survey.publish.index');
    Route::post('/survey/publish/all', [SurveyPublish::class, 'getTable']);
    Route::post('/survey/publish/allpublished', [SurveyPublish::class, 'getTable_publish']);
    Route::get('/survey/publish/id/{id}', [SurveyPublish::class, 'getByID']);
    Route::get('/survey/publish/form', [SurveyPublish::class, 'form']);
    Route::view('/survey/publish/add', 'admin.survey.publish.publish');
    Route::post('/survey/publish/save', [SurveyPublish::class, 'save']);
    Route::post('/survey/publish/savepublish', [SurveyPublish::class, 'savepublish']);
    Route::post('/survey/publish/delete', [SurveyPublish::class, 'delete']);

    // SurveyResultController
    Route::view('/survey/result', 'admin.survey.result.index');
    Route::post('/survey/result/all', [SurveyResult::class, 'getTable']);
    Route::get('/survey/result/see/{id}', [SurveyResult::class, 'viewresult']);
    Route::get('/survey/result/export/{id}', [SurveyResult::class, 'generatepdf']);

    // NewSurveyController (user survey)
    Route::view('/usersurvey/', 'admin.survey.new.index');
    Route::get('/usersurvey/create', [NewSurvey::class, 'create']);
    Route::post('/usersurvey/store', [NewSurvey::class, 'store']);
    // DataTables (server-side)
    Route::post('/usersurvey/all-draft', [NewSurvey::class, 'getDraftTable']);
    Route::post('/usersurvey/all-published', [NewSurvey::class, 'getPublishedTable']);
    Route::post('/usersurvey/delete', [NewSurvey::class, 'delete']);
    // Publish flow
    Route::get('/usersurvey/publish-form/{id}', [NewSurvey::class, 'publishForm']);
    Route::post('/usersurvey/publish-submit', [NewSurvey::class, 'publishSubmit']);
    // Edit & results
    Route::get('/usersurvey/edit/{id}', [NewSurvey::class, 'edit']);
    Route::post('/usersurvey/update', [NewSurvey::class, 'update']);
    Route::get('/usersurvey/results', [NewSurvey::class, 'allResults']);
    Route::post('/usersurvey/voters', [NewSurvey::class, 'getOptionVoters']);

    // PermitController (Letter Permit) - lihat & buat permit untuk semua tenant
    Route::get('/permit/add', [Permit::class, 'index']);
    Route::get('/permit/letterNo/{id_tenancy}', [Permit::class, 'letterNo'])->whereNumber('id_tenancy');
    Route::get('/permit/lots/{id_tenancy}', [Permit::class, 'lots'])->whereNumber('id_tenancy');
    Route::post('/permit/save', [Permit::class, 'save']);
    Route::get('/permit/edit/{doc_no}', [Permit::class, 'edit'])->where('doc_no', '[A-Za-z0-9\-]+');
    Route::post('/permit/update', [Permit::class, 'update']);
    Route::post('/permit/cancel', [Permit::class, 'cancel']);
    Route::get('/permit/index', [Permit::class, 'history']);
    Route::get('/permit/historyTable', [Permit::class, 'table']);
    // formulir permit belum ditandatangani (dicetak untuk ditandatangani): halaman + file PDF
    Route::get('/permit/unsigned/{doc_no}', [Permit::class, 'unsignedPage'])->where('doc_no', '[A-Za-z0-9\-]+');
    Route::get('/permit/unsigned/{doc_no}/file', [Permit::class, 'unsignedFile'])->where('doc_no', '[A-Za-z0-9\-]+');
    // dokumen bertanda tangan yang sudah diunggah: halaman + file
    Route::get('/permit/signed/{doc_no}', [Permit::class, 'signedPage'])->where('doc_no', '[A-Za-z0-9\-]+');
    Route::get('/permit/signed/{doc_no}/file', [Permit::class, 'signedFile'])->where('doc_no', '[A-Za-z0-9\-]+');
    // unggah dokumen bertanda tangan (-> status Approved)
    Route::post('/permit/upload', [Permit::class, 'uploadSigned']);
});
