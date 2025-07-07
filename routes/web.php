<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ItRequestController;
use App\Http\Controllers\VisitorCompanyController;
use App\Http\Controllers\VisitorController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    //visitor company
    Route::get('/listvisitorcompany', [VisitorCompanyController::class, 'getVisitorCompany']);
    
    //it request form
    Route::get('/itrequestform',[ItRequestController::class, 'create']);
    
    //security form
    Route::get('/visitor',[VisitorController::class,'index']);
    Route::get('/visitor/form', [VisitorController::class, 'create']);
    Route::post('/visitor/submit', [VisitorController::class, 'store']);
});

Route::post('/form/submit', [HomeController::class, 'testSubmit']);
Route::get('/table',[ItRequestController::class,'index']);
Route::post('/it-requests',[ItRequestController::class,'store'])->name('itrequestform.store');
require __DIR__.'/auth.php';
