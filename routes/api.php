<?php

use App\Enums\Permission;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->name('api.login');

Route::middleware('auth:api')->group(function () {
    Route::get('/user', [AuthController::class, 'me'])->name('api.user');
    Route::put('/user', [AuthController::class, 'update'])->name('api.user.update');
    Route::put('/user/password', [AuthController::class, 'updatePassword'])->name('api.user.password');
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');

    Route::middleware('can:'.Permission::EnrollmentsSearch->value)->group(function () {
        Route::post('/enrollments/identify', [EnrollmentController::class, 'identify'])
            ->name('api.enrollments.identify');
        Route::post('/enrollments/identify-video', [EnrollmentController::class, 'identifyVideo'])
            ->name('api.enrollments.identify-video');
    });

    Route::get('/enrollments', [EnrollmentController::class, 'index'])
        ->middleware('can:'.Permission::EnrollmentsView->value)
        ->name('api.enrollments.index');
    Route::post('/enrollments', [EnrollmentController::class, 'store'])
        ->middleware('can:'.Permission::EnrollmentsCreate->value)
        ->name('api.enrollments.store');
    Route::get('/enrollments/{enrollment}', [EnrollmentController::class, 'show'])
        ->middleware('can:'.Permission::EnrollmentsView->value)
        ->name('api.enrollments.show');
    Route::put('/enrollments/{enrollment}', [EnrollmentController::class, 'update'])
        ->middleware('can:'.Permission::EnrollmentsUpdate->value)
        ->name('api.enrollments.update');
    Route::delete('/enrollments/{enrollment}', [EnrollmentController::class, 'destroy'])
        ->middleware('can:'.Permission::EnrollmentsDelete->value)
        ->name('api.enrollments.destroy');
    Route::delete('/enrollments/{enrollment}/images/{image}', [EnrollmentController::class, 'destroyImage'])
        ->middleware('can:'.Permission::EnrollmentsUpdate->value)
        ->name('api.enrollments.images.destroy');

    Route::get('/users/roles', [UserController::class, 'roles'])
        ->middleware('can:'.Permission::UsersView->value)
        ->name('api.users.roles');
    Route::get('/users', [UserController::class, 'index'])
        ->middleware('can:'.Permission::UsersView->value)
        ->name('api.users.index');
    Route::post('/users', [UserController::class, 'store'])
        ->middleware('can:'.Permission::UsersCreate->value)
        ->name('api.users.store');
    Route::get('/users/{user}', [UserController::class, 'show'])
        ->middleware('can:'.Permission::UsersView->value)
        ->name('api.users.show');
    Route::put('/users/{user}', [UserController::class, 'update'])
        ->middleware('can:'.Permission::UsersUpdate->value)
        ->name('api.users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])
        ->middleware('can:'.Permission::UsersDelete->value)
        ->name('api.users.destroy');
});
