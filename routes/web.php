<?php

use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\ClassroomTypeController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Schools Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('schools', SchoolController::class);
    Route::resource('classrooms', ClassroomController::class);
    Route::resource('classroom-types', ClassroomTypeController::class);

    // Administration Routes
    Route::resource('roles', RoleController::class);
    Route::resource('permissions', PermissionController::class);
    Route::resource('users', UserController::class);
});

require __DIR__.'/settings.php';
