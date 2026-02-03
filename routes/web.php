<?php

use App\Http\Controllers\EncryptionSettingController;
use App\Http\Controllers\ManageContainer\InspectionAnswerController;
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
use App\Http\Controllers\ManageContainer\ShipmentTransportApprovalController;
use App\Http\Controllers\ManageContainer\ArchiveContainerReportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ManageVisitor\VisitorController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\ManageVisitor\VisitorStaffAcknowledgementController;
use App\Http\Controllers\ContainerShipmentController;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (auth()->check()) {
        $user = Auth::user();

        // First-time login → force password update
        if ($user->is_first_time_login) {  
            return redirect()->route('password.expired')->with('reason', 'first_time');
        }

        // if user never changed password, fallback to created_at
        $lastChanged = $user->password_changed_at ?? $user->created_at;

        if (Carbon::parse($lastChanged)->addDays(180)->isPast()) {
            // force redirect to password update page
            return redirect()->route('password.expired')->with('reason', 'expired');
        }

        return Inertia::render('Welcome'); // main dashboard for logged-in users
    }

    return Inertia::render('Auth/Login'); // same page for guests, just different sidebar/content
})->name('welcome');

//Route to get the welcome page
Route::middleware('password.age')->get('/dashboard', function () {
    return Inertia::render('Welcome');
})->name('dashboard');

//routes for manage visitor module
Route::middleware(['auth', 'password.age'])->prefix('visitor')->name('visitor.')->group(function () {

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
Route::middleware(['auth', 'password.age'])->prefix('room-reservation')->name('room-reservation.')->group(function () {

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
Route::middleware(['auth', 'can:superadmin', 'password.age'])
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
        Route::get('/get-password-policy-page', [PasswordPolicyController::class, 'getPasswordPolicyPage']);
        Route::get('/password-policy', [PasswordPolicyController::class, 'index'])->name('password-policy.edit');
        Route::post('/password-policy', [PasswordPolicyController::class, 'update'])->name('password-policy.update');
        Route::get('/encryption-settings', [EncryptionSettingController::class, 'index']);
        Route::post('/encryption-settings', [EncryptionSettingController::class, 'update']);
        Route::apiResource('sites', SiteController::class);

        // Permission management routes
        Route::get('/permissions', [UserController::class, 'getAllPermissions']);
        Route::get('/users/{user}/permissions', [UserController::class, 'getUserPermissions']);
        Route::post('/users/{user}/permissions', [UserController::class, 'manageUserPermissions']);
    });

Route::middleware(['auth', 'password.age'])->group(function () {
    //profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // User permissions API
    Route::get('/api/user/permissions', function () {
        $user = auth()->user();
        return response()->json([
            'permissions' => $user->getAllPermissions()->pluck('name')->toArray()
        ]);
    });
});

//api to fetch form by site
Route::get('/visitor/form/{site}', [VisitorController::class, 'getVisitorForm']);

//api to submit visitor form
Route::post('/visitor/submit', [VisitorController::class, 'store']);

//api to check acknowledgement whether visitor come for first time or not
Route::post('/visitor/check-acknowledgement', [VisitorController::class, 'checkAcknowledgement']);

//api to validate container for visitor registration (public access)
Route::post('/containers/validate-for-visitor', [ShipmentTransportController::class, 'validateContainerForVisitor'])->name('container.validate-for-visitor');

// API route for sites
Route::middleware(['auth'])->get('/api/sites', [SiteController::class, 'index'])->name('sites.index');

Route::get('/mfa-verify', [MFAController::class, 'showVerifyForm'])->name('mfa.verify');
Route::post('/mfa-verify', [MFAController::class, 'verifyCode'])->name('mfa.verify');
Route::post('/mfa-resend', [MFAController::class, 'resendCode'])->name('mfa.resend');

// manage container module
Route::middleware(['auth'])->prefix('container')->name('container.')->group(function () {
    Route::get('/dashboard', [ShipmentTransportController::class, 'dashboard'])
        ->middleware('can:container.access')
        ->name('dashboard');
    Route::get('/stats', [ShipmentTransportController::class, 'getStats'])
        ->middleware('can:container.access')
        ->name('stats');
    Route::get('/shipments', function () {
        return Inertia::render('ManageContainer/ContainerShipments');
    })->middleware('can:container.access')->name('shipments');
    Route::get('/approvals', function () {
        return Inertia::render('ManageContainer/ContainerApprovals');
    })->middleware('can:container.approve')->name('approvals');
    Route::get('/archive', function () {
        return Inertia::render('ManageContainer/ArchiveContainerReports');
    })->middleware('can:container.access')->name('archive');
    Route::get('/shipping-requirements', function () {
        return Inertia::render('ManageContainer/ShippingRequirements');
    })->middleware('can:container.access')->name('shipping-requirements');
    Route::get('/shipping-requirements-approvals', function () {
        return Inertia::render('ManageContainer/ShippingRequirementsApprovals');
    })->middleware('can:container.shipping.approve')->name('shipping-requirements-approvals');
});

// Container shipments API routes
Route::middleware(['auth'])->prefix('api/container-shipments')->name('container-shipments.')->group(function () {
    Route::get('/', [ContainerShipmentController::class, 'index'])->name('index');
    Route::post('/', [ContainerShipmentController::class, 'store'])->name('store');
    Route::get('/{containerShipment}', [ContainerShipmentController::class, 'show'])->name('show');
    Route::put('/{containerShipment}', [ContainerShipmentController::class, 'update'])->name('update');
    Route::delete('/{containerShipment}', [ContainerShipmentController::class, 'destroy'])->name('destroy');
});

// Archive container reports API routes
Route::middleware(['auth'])->prefix('api/archive-container-reports')->name('archive-container-reports.')->group(function () {
    Route::get('/', [App\Http\Controllers\ManageContainer\ArchiveContainerReportController::class, 'index'])->name('index');
});

Route::post('/containers/create', [ShipmentTransportController::class, 'store'])->middleware('can:container.access')->name('container.create');
Route::get('/containers/country-requirements', [ShipmentTransportController::class, 'getCountryRequirements'])->middleware('can:container.access')->name('container.country-requirements');
Route::get('/containers/{shipmentTransport}', [ShipmentTransportController::class, 'getShipmentTransportInfoById'])->middleware('can:container.access')->name('container.show');
Route::put('/containers/{shipmentTransport}', [ShipmentTransportController::class, 'update'])->middleware('can:container.access')->name('container.update');
Route::get('/containers', [ShipmentTransportController::class, 'index'])->middleware('can:container.access')->name('container.index');

Route::get('/containers/{shipmentTransport}/driver-info', [ShipmentTransportController::class, 'getDriverInfo'])->middleware('can:container.access')->name('container.driver-info');
Route::get('/containers/{shipmentTransport}/required-photos', [ShipmentTransportController::class, 'getRequiredPhotos'])->middleware('can:container.access')->name('container.required-photos');
Route::post('/containers/{shipmentTransport}/hold', [ShipmentTransportController::class, 'hold'])->middleware('can:container.quality.access')->name('container.hold');
Route::post('/containers/{shipmentTransport}/release', [ShipmentTransportController::class, 'release'])->middleware('can:container.quality.access')->name('container.release');
Route::get('/containers/{shipmentTransport}/download-report', [ShipmentTransportController::class, 'downloadContainerReport'])->middleware('can:container.shipping.access')->name('container.download-report');

// Shipping requirements management routes
Route::middleware(['auth'])->prefix('api/shipping-requirements')->name('shipping-requirements.')->group(function () {
    Route::get('/', [ShipmentTransportController::class, 'getShippingRequirements'])->name('index');
    Route::post('/', [ShipmentTransportController::class, 'createShippingRequirement'])->name('store');
    Route::put('/{shippingRequirement}', [ShipmentTransportController::class, 'updateShippingRequirement'])->name('update');
    Route::delete('/{shippingRequirement}', [ShipmentTransportController::class, 'deleteShippingRequirement'])->name('destroy');

    // Change request routes
    Route::post('/request-change', [ShipmentTransportController::class, 'requestChange'])->name('request-change');

    // Approval routes
    Route::get('/pending-change-requests', [ShipmentTransportController::class, 'getPendingChangeRequests'])->name('pending-change-requests');
    Route::post('/approve-change/{changeRequestId}', [ShipmentTransportController::class, 'approveChangeRequest'])->name('approve-change');
    Route::post('/reject-change/{changeRequestId}', [ShipmentTransportController::class, 'rejectChangeRequest'])->name('reject-change');
});
Route::get('/containers/questions', [InspectionQuestionController::class, 'index'])->middleware('can:container.approve')->name('container.question');
Route::post('/containers/create-inspection', [ShipmentTransportInspectionController::class, 'createInspection'])->middleware('can:container.approve')->name('container.create-inspection');
Route::post('/containers/update-inspection/{id}', [ShipmentTransportInspectionController::class, 'updateInspection'])->middleware('can:container.approve')->name('container.update-inspection');
Route::get('/containers/inspection-details/{id}', [ShipmentTransportInspectionController::class, 'getInspectionDetails'])->middleware('can:container.approve')->name('container.inspection-details');
Route::post('containers/create-photo', [ShipmentTransportPhotoController::class, 'store'])->middleware('can:container.approve')->name('container.create-photo');
Route::get('containers/{shipmentTransport}/photos', [ShipmentTransportPhotoController::class, 'getPhotos'])->middleware('can:container.approve')->name('container.get-photos');
Route::get('/containers/inspection-answer', [ShipmentTransportInspectionController::class, 'showByShipmentTransportId'])->middleware('can:container.approve')->name('container.get-inspection-answer-by-shipment-transport-id');
Route::post('/containers/submit-security-checking', [ShipmentTransportPhotoController::class, 'submitSecurityChecking'])->middleware('can:container.approve')->name('container.submit-security-checking');

// Container approval routes
Route::middleware(['auth'])->prefix('container-approvals')->name('container-approvals.')->group(function () {
    Route::get('/', [ShipmentTransportApprovalController::class, 'index'])->middleware('can:container.approve')->name('index');
    Route::get('/{approval}/details', [ShipmentTransportApprovalController::class, 'getApprovalDetails'])->middleware('can:container.approve')->name('details');
    Route::post('/{approval}/approve', [ShipmentTransportApprovalController::class, 'approve'])->middleware('can:container.approve')->name('approve');
    Route::post('/{approval}/reject', [ShipmentTransportApprovalController::class, 'reject'])->middleware('can:container.approve')->name('reject');
    Route::get('/{approval}/approve-email', [ShipmentTransportApprovalController::class, 'approveFromEmail'])->middleware('can:container.approve')->name('approve-email');
});

// Power Automate approval result endpoint (public access for external service)
Route::post('/api/approval-result', [ShipmentTransportApprovalController::class, 'receiveApprovalResult']);

use App\Http\Controllers\ApprovalController;

// Route::post('/approvals', [ApprovalController::class, 'create']);
Route::get('/test-approval', [ApprovalController::class, 'create']);
Route::post('/approvals/{id}/approve', [ApprovalController::class, 'approve']);
Route::post('/approvals/{id}/reject', [ApprovalController::class, 'reject']);
Route::get('/approvals/{id}', [ApprovalController::class, 'status']);

require __DIR__ . '/auth.php';
