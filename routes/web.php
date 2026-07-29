<?php

use App\Enums\Permission;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\RoleController;
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

    Route::middleware('can:'.Permission::EnrollmentsSearch->value)->group(function () {
        Route::get('enrollments/search', [EnrollmentController::class, 'search'])
            ->name('enrollments.search');
        Route::post('enrollments/search', [EnrollmentController::class, 'identify'])
            ->name('enrollments.identify');
        Route::post('enrollments/search/video', [EnrollmentController::class, 'identifyVideo'])
            ->name('enrollments.identify-video');
    });

    Route::get('enrollments/export', [EnrollmentController::class, 'export'])
        ->middleware('can:'.Permission::EnrollmentsExport->value)
        ->name('enrollments.export');

    Route::middleware('can:'.Permission::EnrollmentsImport->value)->group(function () {
        Route::get('enrollments/import/template', [EnrollmentController::class, 'importTemplate'])
            ->name('enrollments.import.template');
        Route::post('enrollments/import', [EnrollmentController::class, 'import'])
            ->name('enrollments.import');
    });

    Route::get('enrollments', [EnrollmentController::class, 'index'])
        ->middleware('can:'.Permission::EnrollmentsView->value)
        ->name('enrollments.index');
    Route::get('enrollments/create', [EnrollmentController::class, 'create'])
        ->middleware('can:'.Permission::EnrollmentsCreate->value)
        ->name('enrollments.create');
    Route::post('enrollments', [EnrollmentController::class, 'store'])
        ->middleware('can:'.Permission::EnrollmentsCreate->value)
        ->name('enrollments.store');
    Route::get('enrollments/{enrollment}', [EnrollmentController::class, 'show'])
        ->middleware('can:'.Permission::EnrollmentsView->value)
        ->name('enrollments.show');
    Route::get('enrollments/{enrollment}/edit', [EnrollmentController::class, 'edit'])
        ->middleware('can:'.Permission::EnrollmentsUpdate->value)
        ->name('enrollments.edit');
    Route::put('enrollments/{enrollment}', [EnrollmentController::class, 'update'])
        ->middleware('can:'.Permission::EnrollmentsUpdate->value)
        ->name('enrollments.update');
    Route::delete('enrollments/{enrollment}', [EnrollmentController::class, 'destroy'])
        ->middleware('can:'.Permission::EnrollmentsDelete->value)
        ->name('enrollments.destroy');
    Route::delete(
        'enrollments/{enrollment}/images/{image}',
        [EnrollmentController::class, 'destroyImage'],
    )
        ->middleware('can:'.Permission::EnrollmentsUpdate->value)
        ->name('enrollments.images.destroy');

    Route::get('users', [UserController::class, 'index'])
        ->middleware('can:'.Permission::UsersView->value)
        ->name('users.index');
    Route::get('users/create', [UserController::class, 'create'])
        ->middleware('can:'.Permission::UsersCreate->value)
        ->name('users.create');
    Route::post('users', [UserController::class, 'store'])
        ->middleware('can:'.Permission::UsersCreate->value)
        ->name('users.store');
    Route::get('users/{user}', [UserController::class, 'show'])
        ->middleware('can:'.Permission::UsersView->value)
        ->name('users.show');
    Route::get('users/{user}/edit', [UserController::class, 'edit'])
        ->middleware('can:'.Permission::UsersUpdate->value)
        ->name('users.edit');
    Route::put('users/{user}', [UserController::class, 'update'])
        ->middleware('can:'.Permission::UsersUpdate->value)
        ->name('users.update');
    Route::delete('users/{user}', [UserController::class, 'destroy'])
        ->middleware('can:'.Permission::UsersDelete->value)
        ->name('users.destroy');

    Route::get('roles', [RoleController::class, 'index'])
        ->middleware('can:'.Permission::RolesView->value)
        ->name('roles.index');
    Route::get('roles/create', [RoleController::class, 'create'])
        ->middleware('can:'.Permission::RolesCreate->value)
        ->name('roles.create');
    Route::post('roles', [RoleController::class, 'store'])
        ->middleware('can:'.Permission::RolesCreate->value)
        ->name('roles.store');
    Route::get('roles/{role}', [RoleController::class, 'show'])
        ->middleware('can:'.Permission::RolesView->value)
        ->name('roles.show');
    Route::get('roles/{role}/edit', [RoleController::class, 'edit'])
        ->middleware('can:'.Permission::RolesUpdate->value)
        ->name('roles.edit');
    Route::put('roles/{role}', [RoleController::class, 'update'])
        ->middleware('can:'.Permission::RolesUpdate->value)
        ->name('roles.update');
    Route::delete('roles/{role}', [RoleController::class, 'destroy'])
        ->middleware('can:'.Permission::RolesDelete->value)
        ->name('roles.destroy');

    Route::middleware('can:'.Permission::BackupsDownload->value)->group(function () {
        Route::get('backups', [BackupController::class, 'index'])
            ->name('backups.index');
        Route::get('backups/database', [BackupController::class, 'database'])
            ->name('backups.database');
        Route::get('backups/images', [BackupController::class, 'images'])
            ->name('backups.images');
    });
});

require __DIR__.'/settings.php';
