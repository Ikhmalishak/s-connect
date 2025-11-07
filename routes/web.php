<?php

use App\Http\Controllers\EncryptionSettingController;
use App\Http\Controllers\GatePassController;
use App\Http\Controllers\MFAController;
use App\Http\Controllers\PasswordPolicyController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\RoomReservationController;
use App\Http\Controllers\UserController;
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

Route::get('/mfa-verify', [MFAController::class, 'showVerifyForm'])->name('mfa.verify');
Route::post('/mfa-verify', [MFAController::class, 'verifyCode'])->name('mfa.verify');
Route::post('/mfa-resend', [MFAController::class, 'resendCode'])->name('mfa.resend');

// Admin-only routes
Route::middleware(['auth', 'password.age', 'role:admin'])->group(function () {
    Route::get('/admin/password-policy', function () {
        return Inertia::render('Security/Visitor/PasswordPolicy');
    })->name('admin.password.policy');

    Route::get('/admin/visitor/dashboard', [VisitorController::class, 'getAdminVisitorDashboard'])
        ->name('admin.visitordashboard');

    //api for admin reporting dashboard
    Route::get('/admin/visitor/report-dashboard', [VisitorController::class, 'getAdminVisitorReportingDashboard']);

    //api for system log interface
    Route::get('/admin/visitor/system-log', [ReportController::class, 'getSystemLogPage']);

    //api to fetch list system log
    Route::get('/admin/visitor/system-log-list', [ReportController::class, 'getSystemLog']);

    //api for fetch all user
    Route::get('/admin/visitor/user-list', [UserController::class, 'getUserTableData']);

    //api for update user
    Route::put('/update-user/{user}', [UserController::class, 'update']);

    //api for reset password user
    Route::post('/reset-password/{user}', [UserController::class, 'resetPassword']);

    //api for delete user
    Route::delete('/delete-user/{user}', [UserController::class, 'destroy']);

    //api for fetch use stats card
    Route::get('/admin/user-stats-card', [UserController::class, 'getUserStatsCard']);

    //api for admin manage user page
    Route::get('/admin/visitor/manage-user', [UserController::class, 'getManageUserPage']);

    //api to fetch admin table data (table data can load every data inside table)
    Route::get('/admin/visitor/table-data', [VisitorController::class, 'getAdminVisitorTableData']);

    //api to generate report
    Route::post('/admin/visitor/generate-report', [VisitorController::class, 'generateReport'])->name("admin.generateReport");

    //api for admin reporting
    Route::get('/admin/visitor/get-report', [ReportController::class, 'getReport']);
    Route::get('admin/visitor/get-report-by-week', [ReportController::class, 'getVisitorByWeek']);

    //route to reprint sticker if sticker missing
    Route::get('/reprint/{ack_number}', [VisitorStaffAcknowledgementController::class, 'reprintVisitorSticker']);

    //route to control password policy
    Route::get('/password-policy', [PasswordPolicyController::class, 'index'])->name('password-policy.edit');
    Route::post('/password-policy', [PasswordPolicyController::class, 'update'])->name('password-policy.update');

    //route to control database encryption
    Route::get('/encryption-settings', [EncryptionSettingController::class, 'index']);
    Route::post('/encryption-settings', [EncryptionSettingController::class, 'update']);
});

// Guard-only routes
Route::middleware(['auth', 'password.age', 'role:guard'])->group(function () {
    //route to get visitor dashboard
    Route::get('/visitor/dashboard', [VisitorController::class, 'getVisitorDashboard'])
        ->name('security.visitordashboard');
});

// Receptionist-only routes
Route::middleware(['auth', 'password.age', 'role:receptionist'])->group(function () {
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

Route::get('/admin/booking/dashboard', [VisitorController::class, 'getBooking'])
    ->name('admin.visitordashboard');

Route::get('/booking/tablet/{id}', [RoomReservationController::class, 'getRoomReservationTabletInterface'])
    ->name('booking.tablet');
Route::get('/room-reservations', [RoomReservationController::class, 'index']);
Route::post('/room-reservations', [RoomReservationController::class, 'store']);
Route::post('/room-reservations/{id}/approve', [RoomReservationController::class, 'approve']);
Route::post('/room-reservations/{id}/reject', [RoomReservationController::class, 'reject']);
Route::post('/room-reservations/{id}/cancel', [RoomReservationController::class, 'cancel']);
Route::get('/room-reservations/{id}/status',[RoomReservationController::class,'getRoomStatus']);

Route::get('/rooms', [RoomController::class, 'getRoomBySite']);
Route::get('/rooms/{id}', [RoomController::class, 'getRoomById']);
Route::post('/rooms', [RoomController::class, 'store']);
Route::put('/rooms/{id}', [RoomController::class, 'update']);
Route::delete('/rooms/{id}', [RoomController::class, 'destroy']);

require __DIR__ . '/auth.php';
