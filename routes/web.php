<?php

use App\Http\Controllers\GatePassController;
use App\Http\Controllers\VisitorController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\VisitorStaffAcknowledgementController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Route::get('/', function () {
//     return Inertia::render('Welcome', [
//         'canLogin' => Route::has('login'),
//         'canRegister' => Route::has('register'),
//         'laravelVersion' => Application::VERSION,
//         'phpVersion' => PHP_VERSION,
//     ]);
// });

Route::get('/', function () {
    return Inertia::render('Auth/Login');
})->name('login');

// Route::get('/dashboard', function () {
//     return Inertia::render('Dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    //security form
    Route::get('/visitor/dashboard', [VisitorController::class, 'getVisitorDashboard'])->name('security.visitordashboard');
    Route::get('/admin/visitor/dashboard', [VisitorController::class, 'getAdminVisitorDashboard'])->name('admin.visitordashboard');
    Route::get('/admin/visitor/report-dashboard', [VisitorController::class, 'getAdminVisitorReportingDashboard']);
    Route::get('/admin/visitor/staff-verification', [VisitorController::class, 'getStaffVerification']);
    Route::get('/api/visitors', [VisitorController::class, 'refreshVisitorTablePage']);
    Route::get('/visitor/visitor-inside', [VisitorController::class, 'getVisitorInside']);
    Route::post('/visitor/scan-by-pass', [VisitorController::class, 'scanByPass']);
    Route::post('/visitor/check-acknowledgement', [VisitorController::class, 'checkAcknowledgement']);
    Route::get('/visitor/form/', [VisitorController::class, 'getVisitorForm']);
    Route::post('/visitor/submit', [VisitorController::class, 'store']);
    Route::post('/visitors/{visitorId}/remarks', [VisitorController::class, 'editRemarks'])->name('visitors.editRemarks');
    Route::get('visitor/table-data', [VisitorController::class, 'getVisitorTableData']);
    Route::get('/admin/visitor/table-data', [VisitorController::class, 'getAdminVisitorTableData']);
    Route::post('/admin/visitor/generate-report', [VisitorController::class, 'generateReport'])->name("admin.generateReport");
    Route::get('/admin/visitor/get-statistic-all-sites', [VisitorController::class, 'getStatisticAllSites']);
    Route::get('/admin/visitor/get-statistic-by-sites', [VisitorController::class, 'getStatisticBySites']);
    Route::get('/print-sticker/{ackId}/{totalPax}', [VisitorController::class, 'printSticker']);
    Route::get('/visitors/sticker/{id}', [VisitorController::class, 'generateSticker'])
        ->name('visitors.sticker');

    //route for purpose and site and gate pass
    Route::apiResource('sites', SiteController::class);
    Route::apiResource('gate-passes', GatePassController::class);

    //route for get total available gate pass
    Route::get('/gate-pass/total', [GatePassController::class, 'getGatePassData']);

    //route to get the visitor details for staff verification
    Route::get('/visitor-staff-acknowledgement-details', [VisitorStaffAcknowledgementController::class, 'getVisitorStaffAcknowledgementDetails']);
    Route::get('/get-verified-visitors', [VisitorStaffAcknowledgementController::class, 'getAllVerifiedVisitor']);
    Route::post('/verify-visitor', [VisitorStaffAcknowledgementController::class, 'verifyVisitorAcknowledgement']);
});

Route::get('/visitor/form/{site}', [VisitorController::class, 'getVisitorForm']);
Route::post('/visitor/submit', [VisitorController::class, 'store']);

require __DIR__ . '/auth.php';
