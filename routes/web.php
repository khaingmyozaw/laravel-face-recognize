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
