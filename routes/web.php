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

    //security form
    Route::get('/dashboard', action: [VisitorController::class, 'index'])->name('dashboard');
    Route::post('/visitor/{id}/check-in', [VisitorController::class, 'checkIn']);
    Route::post('/visitor/{id}/check-out', [VisitorController::class, 'checkOut']);
    Route::get('/api/visitors', [VisitorController::class, 'refreshVisitorTablePage']);
    Route::get('/visitor/visitor-inside', [VisitorController::class, 'getVisitorInside']);
    Route::post('/visitor/checkout-by-pass', [VisitorController::class, 'checkOutByPass']);
    Route::post('/visitor/check-acknowledgement', [VisitorController::class, 'checkAcknowledgement']);
    Route::get('/visitor/form/', [VisitorController::class, 'getVisitorForm']);
    Route::post('/visitor/submit',[VisitorController::class, 'store']);

    //route for purpose and site and gate pass
    Route::apiResource('purposes', PurposeController::class);
    Route::apiResource('sites', SiteController::class);
    Route::apiResource('gate-passes', GatePassController::class);

    //route for get total available gate pass
    Route::get('/gate-pass/total', [GatePassController::class, 'getGatePassData']);

});

require __DIR__ . '/auth.php';
