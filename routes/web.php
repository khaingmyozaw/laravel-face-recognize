<?php

use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('enrollments.index')
        : redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('dashboard', '/enrollments')->name('dashboard');

    Route::get('enrollments/search', [EnrollmentController::class, 'search'])
        ->name('enrollments.search');
    Route::post('enrollments/search', [EnrollmentController::class, 'identify'])
        ->name('enrollments.identify');
    Route::post('enrollments/search/video', [EnrollmentController::class, 'identifyVideo'])
        ->name('enrollments.identify-video');

    Route::get('enrollments/export', [EnrollmentController::class, 'export'])
        ->name('enrollments.export');
    Route::get('enrollments/import/template', [EnrollmentController::class, 'importTemplate'])
        ->name('enrollments.import.template');
    Route::post('enrollments/import', [EnrollmentController::class, 'import'])
        ->name('enrollments.import');

    Route::resource('enrollments', EnrollmentController::class);
    Route::delete(
        'enrollments/{enrollment}/images/{image}',
        [EnrollmentController::class, 'destroyImage'],
    )->name('enrollments.images.destroy');

    Route::middleware('admin')->group(function () {
        Route::resource('users', UserController::class);
    });
});

require __DIR__.'/settings.php';
