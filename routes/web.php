<?php

use App\Http\Controllers\EncryptionSettingController;
use App\Http\Controllers\ManageContainer\InspectionQuestionController;
use App\Http\Controllers\ManageVisitor\GatePassController;
use App\Http\Controllers\MFAController;
use App\Http\Controllers\PasswordPolicyController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ManageRoomReservation\RoomController;
use App\Http\Controllers\ManageRoomReservation\RoomReservationController;
use App\Http\Controllers\ManageContainer\ShipmentTransportController;
use App\Http\Controllers\ManageContainer\ShipmentTransportInspectionController;
use App\Http\Controllers\ManageContainer\ShipmentTransportPhotoController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ManageVisitor\VisitorController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\ManageVisitor\VisitorStaffAcknowledgementController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (auth()->check()) {
        return Inertia::render('Welcome'); // main dashboard for logged-in users
    }

    return Inertia::render('Auth/Login'); // same page for guests, just different sidebar/content
})->name('welcome');

//Route to get the welcome page
Route::get('/dashboard', function () {
    return Inertia::render('Welcome');
})->name('dashboard');

//routes for manage visitor module
Route::middleware(['auth'])->prefix('visitor')->name('visitor.')->group(function () {

    // Dashboard (view only)
    Route::get('/dashboard', [VisitorController::class, 'dashboard'])
        ->middleware('can:visitor.access')
        ->name('dashboard');

    // Page for Report (only admin can view)
    Route::get('/report', [VisitorController::class, 'getReportDashboard'])
        ->middleware('can:visitor.access')
        ->name('report');

    // CRUD Visitor
    Route::get('/get-visitor-data', [VisitorController::class, 'getVisitorData']);
    Route::get('/get-visitor-table-data', [VisitorController::class, 'getVisitorTableData']);
    Route::get('/get-visitor-inside', [VisitorController::class, 'getVisitorInside']);
    Route::post('/scan-by-pass', [VisitorController::class, 'scan']);
    Route::post('/{visitorId}/remarks', [VisitorController::class, 'editRemarks'])->name('visitors.editRemarks');

    // Reports
    Route::get('/report-data', [ReportController::class, 'visitorReports'])
        ->middleware('can:visitor.report')
        ->name('reports');
    Route::post('/generate-visitor-report', [VisitorController::class, 'generateReport'])->name("admin.generateReport");
    Route::get('/get-visitor-report-data', [ReportController::class, 'getReport']);
    Route::get('/get-report-data-by-week', [ReportController::class, 'getVisitorByWeek']);

    //VisitorSticker
    Route::get('/print-visitor-sticker/{ackId}/{totalPax}/{ackNumber}', action: [VisitorController::class, 'printSticker']);
    Route::get('/reprint-visitor-sticker/{ack_number}', [VisitorStaffAcknowledgementController::class, 'reprintVisitorSticker']);

    //Visitor Acknowledgement
    Route::get('/get-visitor-staff-acknowledgement-details', [VisitorStaffAcknowledgementController::class, 'getVisitorStaffAcknowledgementDetails']);
    Route::get('/get-verified-visitors', [VisitorStaffAcknowledgementController::class, 'getAllVerifiedVisitor']);
    Route::post('/verify-visitor', [VisitorStaffAcknowledgementController::class, 'verifyVisitorAcknowledgement']);

    // Gate Passes
    Route::apiResource('gate-passes', GatePassController::class);
});


//routes for manage room reservation module
Route::middleware(['auth'])->prefix('room-reservation')->name('room-reservation.')->group(function () {

    // Dashboard (view only)
    Route::get('/dashboard', [RoomReservationController::class, 'dashboard'])
        ->middleware('can:visitor.access')
        ->name('dashboard');

    Route::get('/tablet/{id}', [RoomReservationController::class, 'getRoomReservationTabletInterface'])
        ->name('booking.tablet');

    Route::get('/get-room-reservations', [RoomReservationController::class, 'index']); //fetch room reservation
    Route::post('/create-room-reservations', [RoomReservationController::class, 'store']); //create new reservation
    Route::post('/{id}/cancel', [RoomReservationController::class, 'cancel']); //cancel room reservation
    Route::get('/{id}/status', [RoomReservationController::class, 'getRoomStatus']);

    Route::get('/get-room-by-id/{id}', [RoomController::class, 'getRoomById']);
    Route::get('/get-rooms-by-site', [RoomController::class, 'getRoomBySite']);
});

//routes for superadmin
Route::middleware(['auth', 'can:superadmin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/system-log', [ReportController::class, 'getSystemLogPage']);
        Route::get('/manage-user', [UserController::class, 'getManageUserPage']);
        Route::get('/get-system-log-list', [ReportController::class, 'getSystemLog']);
        Route::get('/get-user-list', [UserController::class, 'getUserTableData']);
        Route::put('/update-user/{user}', [UserController::class, 'update']);
        Route::post('/reset-password/{user}', [UserController::class, 'resetPassword']);
        Route::delete('/delete-user/{user}', [UserController::class, 'destroy']);
        Route::get('/user-stats-card', [UserController::class, 'getUserStatsCard']);
        Route::get('/get-password-policy-page',[PasswordPolicyController::class,'getPasswordPolicyPage']);
        Route::get('/password-policy', [PasswordPolicyController::class, 'index'])->name('password-policy.edit');
        Route::post('/password-policy', [PasswordPolicyController::class, 'update'])->name('password-policy.update');
        Route::get('/encryption-settings', [EncryptionSettingController::class, 'index']);
        Route::post('/encryption-settings', [EncryptionSettingController::class, 'update']);
        Route::apiResource('sites', SiteController::class);
    });

Route::middleware('auth')->group(function () {
    //profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

//api to fetch form by site
Route::get('/visitor/form/{site}', [VisitorController::class, 'getVisitorForm']);

//api to submit visitor form
Route::post('/visitor/submit', [VisitorController::class, 'store']);

//api to check acknowledgement whether visitor come for first time or not
Route::post('/visitor/check-acknowledgement', [VisitorController::class, 'checkAcknowledgement']);

Route::get('/mfa-verify', [MFAController::class, 'showVerifyForm'])->name('mfa.verify');
Route::post('/mfa-verify', [MFAController::class, 'verifyCode'])->name('mfa.verify');
Route::post('/mfa-resend', [MFAController::class, 'resendCode'])->name('mfa.resend');

// manage container module
Route::middleware(['auth'])->prefix('container')->name('container.')->group(function () {
    Route::get('/dashboard', [ShipmentTransportController::class, 'dashboard'])
        ->name('dashboard');
});

Route::post('/containers/create', [ShipmentTransportController::class, 'store'])->name('container.create');
Route::get('/containers', [ShipmentTransportController::class, 'index'])->name('container.index');
Route::get('/containers/questions',[InspectionQuestionController::class,'index'])->name('container.question');
Route::post('/containers/create-inspection', [ShipmentTransportInspectionController::class, 'createInspection'])->name('container.create-inspection');
Route::post('/containers/update-inspection/{id}', [ShipmentTransportInspectionController::class, 'updateInspection'])->name('container.update-inspection');
Route::get('/containers/inspection-details/{id}', [ShipmentTransportInspectionController::class, 'getInspectionDetails'])->name('container.inspection-details');
Route::post('containers/create-photo', [ShipmentTransportPhotoController::class, 'store'])->name('container.create-photo');
require __DIR__ . '/auth.php';
