<?php

use App\Http\Controllers\GatePassController;
use App\Http\Controllers\VisitorController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\VisitorStaffAcknowledgementController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    // Check if user is already authenticated
    if (auth()->check()) {
        $user = auth()->user();

        // Redirect based on role
        switch ($user->role) {
            case 'admin':
                return redirect()->route('admin.visitordashboard');
            case 'guard':
                return redirect()->route('security.visitordashboard');
            default:
                return redirect()->route('receptionist.visitordashboard');
        }
    }

    // If not authenticated, show login page
    return Inertia::render('Auth/Login');
})->name('login');

// Admin-only routes
Route::middleware(['auth','password.age', 'role:admin'])->group(function () {
    Route::get('/admin/visitor/dashboard', [VisitorController::class, 'getAdminVisitorDashboard'])
        ->name('admin.visitordashboard');

    //api for admin reporting dashboard
    Route::get('/admin/visitor/report-dashboard', [VisitorController::class, 'getAdminVisitorReportingDashboard']);

    //api to fetch admin table data (table data can load every data inside table)
    Route::get('/admin/visitor/table-data', [VisitorController::class, 'getAdminVisitorTableData']);

    //api to generate report
    Route::post('/admin/visitor/generate-report', [VisitorController::class, 'generateReport'])->name("admin.generateReport");

    //api for admin reporting
    Route::get('/admin/visitor/get-statistic-all-sites', [VisitorController::class, 'getStatisticAllSites']);
    Route::get('/admin/visitor/get-statistic-by-sites', [VisitorController::class, 'getStatisticBySites']);

    //route to reprint sticker if sticker missing
    Route::get('/reprint/{ack_number}', [VisitorStaffAcknowledgementController::class, 'reprintVisitorSticker']);
});

// Guard-only routes
Route::middleware(['auth','password.age', 'role:guard'])->group(function () {
    //route to get visitor dashboard
    Route::get('/visitor/dashboard', [VisitorController::class, 'getVisitorDashboard'])
        ->name('security.visitordashboard');
});

// Receptionist-only routes
Route::middleware(['auth','password.age','role:receptionist'])->group(function () {
    Route::get('/admin/visitor/staff-verification', [VisitorController::class, 'getStaffVerification'])
        ->name('receptionist.visitordashboard');
});

Route::middleware('auth')->group(function () {
    //profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    //api to refresh visitor list
    Route::get('/api/visitors', [VisitorController::class, 'refreshVisitorTablePage']);

    //api to fetch list visitor inside
    Route::get('/visitor/visitor-inside', [VisitorController::class, 'getVisitorInside']);

    //api to scan qr code at guard house
    Route::post('/visitor/scan-by-pass', [VisitorController::class, 'scan']);

    //api to edit remarks(edit remark driver lorry)
    Route::post('/visitors/{visitorId}/remarks', [VisitorController::class, 'editRemarks'])->name('visitors.editRemarks');

    //api to get visitor table data
    Route::get('visitor/table-data', [VisitorController::class, 'getVisitorTableData']);

    //api to print sticker testing
    Route::get('/print-sticker/{ackId}/{totalPax}/{ackNumber}', action: [VisitorController::class, 'printSticker']);

    //route for purpose and site and gate pass
    Route::apiResource('sites', SiteController::class);
    Route::apiResource('gate-passes', GatePassController::class);

    //route for get total available gate pass
    Route::get('/gate-pass/total', [GatePassController::class, 'getGatePassData']);

    //route to get the visitor details for staff verification
    Route::get('/visitor-staff-acknowledgement-details', [VisitorStaffAcknowledgementController::class, 'getVisitorStaffAcknowledgementDetails']);

    //route to get all the verified visitor
    Route::get('/get-verified-visitors', [VisitorStaffAcknowledgementController::class, 'getAllVerifiedVisitor']);

    //route for staff to verify visitor
    Route::post('/verify-visitor', [VisitorStaffAcknowledgementController::class, 'verifyVisitorAcknowledgement']);

    // Route::post('/visitor/scan-by-pass', [VisitorController::class, 'scanByPass']);
});

//api to fetch form by site
Route::get('/visitor/form/{site}', [VisitorController::class, 'getVisitorForm']);

//api to submit visitor form
Route::post('/visitor/submit', [VisitorController::class, 'store']);

//api to check acknowledgement whether visitor come for first time or not
Route::post('/visitor/check-acknowledgement', [VisitorController::class, 'checkAcknowledgement']);

require __DIR__ . '/auth.php';
