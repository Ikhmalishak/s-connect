<?php

use App\Http\Controllers\GatePassController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ItRequestController;
use App\Http\Controllers\VisitorCompanyController;
use App\Http\Controllers\VisitorController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurposeController;
use App\Http\Controllers\SiteController;
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

// Route::get('/', function () {
//     return Inertia::render('Auth/Login');
// })->name('login');

// Route::get('/dashboard', function () {
//     return Inertia::render('Dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    //it request form
    Route::get('/itrequestform', [ItRequestController::class, 'create']);

    //security form
    Route::get('/dashboard', action: [VisitorController::class, 'index'])->name('dashboard');
    Route::get('/visitor/form', [VisitorController::class, 'getVisitorForm']);
    Route::get('/visitor/acknowledge', [VisitorController::class, 'getVisitorAcknowledgeForm']);
    Route::post('/visitor/{id}/check-in', [VisitorController::class, 'checkIn']);
    Route::post('/visitor/{id}/check-out', [VisitorController::class, 'checkOut']);
    Route::get('/visitor/{visitor}/edit', [VisitorController::class, 'edit']);
    Route::put('/visitor/{visitor}', [VisitorController::class, 'update']);
    Route::post('/visitor/{visitor}/acknowledge', [VisitorController::class, 'updateAcknowledge']);
    Route::get('/api/visitors', [VisitorController::class, 'refreshVisitorTablePage']);
    Route::get('/visitor/form/archive', [VisitorController::class, 'getArchivedVisitorForm']);
    Route::get('/visitor/visitor-inside',[VisitorController::class, 'getVisitorInside']);
    Route::post('/visitor/checkout-by-pass', [VisitorController::class, 'checkOutByPass']);
    Route::post('/visitor/check-acknowledgement', [ VisitorController::class, 'checkAcknowledgement']);

    //route for purpose and site and gate pass
    Route::apiResource('purposes', PurposeController::class);
    Route::apiResource('sites', SiteController::class);
    Route::apiResource('gate-passes',GatePassController::class);

    //route for get total available gate pass
    Route::get('/gate-pass/total', [GatePassController::class, 'getGatePassData']);

});

Route::get('/visitor/form', [VisitorController::class, 'getArchivedVisitorForm']);
Route::get('/visitor/form/new', [VisitorController::class, 'getVisitorForm']);
Route::post('/form/submit', [HomeController::class, 'testSubmit']);
Route::get('/table', [ItRequestController::class, 'index']);
Route::post('/visitor/submit', action: [VisitorController::class, 'store']);
Route::middleware(['auth', 'role:guard'])->group(function () {
});


//visitor company
Route::get('/listvisitorcompany', [VisitorCompanyController::class, 'getVisitorCompany']);
require __DIR__ . '/auth.php';
