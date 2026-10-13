<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\Auth\LoginController;

// Admin
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\UserImportController as AdminUserImportController;
use App\Http\Controllers\Admin\JplController;
use App\Http\Controllers\Admin\JplImportController;
use App\Http\Controllers\Admin\ResortController;
use App\Http\Controllers\Admin\WilayahController;
use App\Http\Controllers\Admin\WilayahImportController;

// User / PJL
use App\Http\Controllers\User\DashboardController as UserDashboardController;
use App\Http\Controllers\User\RealisasiKaController;
use App\Http\Controllers\User\CatatanDinasController;
use App\Http\Controllers\User\SerahTerimaDinasController;

// Superadmin
use App\Http\Controllers\Superadmin\DashboardController as SuperadminDashboardController;
use App\Http\Controllers\Superadmin\UserController as SuperadminUserController;
use App\Http\Controllers\Superadmin\UserImportController as SuperadminUserImportController;
use App\Http\Controllers\Superadmin\ActivityLogController;
use App\Http\Controllers\Superadmin\SystemMonitoringController;
use App\Http\Controllers\Superadmin\JplController as SuperadminJplController;


/*
|--------------------------------------------------------------------------
| Root
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    if (!Auth::check()) {
        return redirect()->route('login');
    }

    $user = Auth::user();

    if ($user->isSuperadmin()) {
        return redirect()->route('superadmin.dashboard');
    }

    if ($user->hasRole('admin')) {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('user.dashboard');
});


/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [
        LoginController::class,
        'showLoginForm',
    ])->name('login');

    Route::post('/login', [
        LoginController::class,
        'login',
    ])->name('login.store');
});

Route::post('/logout', [
    LoginController::class,
    'logout',
])->middleware('auth')->name('logout');


/*
|--------------------------------------------------------------------------
| User / PJL
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:pjl'])
    ->prefix('user')
    ->name('user.')
    ->group(function () {

        Route::get('/dashboard', [
            UserDashboardController::class,
            'index',
        ])->name('dashboard');

        // Realisasi KA
        Route::get('/realisasi-ka', [
            RealisasiKaController::class,
            'index',
        ])->name('realisasi-ka');

        Route::post('/realisasi-ka', [
            RealisasiKaController::class,
            'store',
        ])->name('realisasi-ka.store');

        // Catatan Dinas
        Route::get('/catatan-dinas', [
            CatatanDinasController::class,
            'index',
        ])->name('catatan-dinas');

        Route::post('/catatan-dinas', [
            CatatanDinasController::class,
            'store',
        ])->name('catatan-dinas.store');

        // Serah Terima Dinas
        Route::get('/serah-terima-dinas', [
            SerahTerimaDinasController::class,
            'index',
        ])->name('serah-terima-dinas');

        Route::post('/serah-terima-dinas', [
            SerahTerimaDinasController::class,
            'store',
        ])->name('serah-terima-dinas.store');
    });


/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin,superadmin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('/dashboard', [
            DashboardController::class,
            'index',
        ])->name('dashboard');

        // Manajemen User
        Route::get('/users', [
            AdminUserController::class,
            'index',
        ])->name('users.index');

        Route::get('/users/import/template', [
            AdminUserImportController::class,
            'downloadTemplate',
        ])->name('users.import.template');

        Route::post('/users/import/preview', [
            AdminUserImportController::class,
            'preview',
        ])->name('users.import.preview');

        Route::post('/users/import/confirm', [
            AdminUserImportController::class,
            'confirm',
        ])->name('users.import.confirm');

        Route::get('/users/{id}', [
            AdminUserController::class,
            'show',
        ])->name('users.show');

        Route::get('/users/{id}/edit', [
            AdminUserController::class,
            'edit',
        ])->name('users.edit');

        Route::put('/users/{id}', [
            AdminUserController::class,
            'update',
        ])->name('users.update');

        Route::patch('/users/{id}/status', [
            AdminUserController::class,
            'toggleStatus',
        ])->name('users.toggle-status');

        // Import JPL
        Route::get('/jpl/import', [
            JplImportController::class,
            'create',
        ])->name('jpl.import');

        Route::post('/jpl/import/preview', [
            JplImportController::class,
            'preview',
        ])->name('jpl.import.preview.store');

        Route::get('/jpl/import/preview', [
            JplImportController::class,
            'showPreview',
        ])->name('jpl.import.preview');

        Route::post('/jpl/import/confirm', [
            JplImportController::class,
            'confirm',
        ])->name('jpl.import.confirm');

        // Master Data JPL
        Route::resource('jpl', JplController::class);

        // Master Data Resort
        Route::resource('resort', ResortController::class)
            ->except(['destroy']);

        Route::patch('/resort/{resort}/toggle-status', [
            ResortController::class,
            'toggleStatus',
        ])->name('resort.toggle-status');

        // Import Wilayah
        Route::get('/wilayah/import/template', [
            WilayahController::class,
            'downloadTemplate',
        ])->name('wilayah.import.template');

        Route::post('/wilayah/import/preview', [
            WilayahImportController::class,
            'preview',
        ])->name('wilayah.import.preview.store');

        Route::get('/wilayah/import/preview', [
            WilayahImportController::class,
            'showPreview',
        ])->name('wilayah.import.preview');

        Route::post('/wilayah/import/confirm', [
            WilayahImportController::class,
            'confirm',
        ])->name('wilayah.import.confirm');

        Route::resource('wilayah', WilayahController::class)
            ->except(['show', 'destroy']);

        Route::patch('/wilayah/{wilayah}/toggle-status', [
            WilayahController::class,
            'toggleStatus',
        ])->name('wilayah.toggle-status');
    });


/*
|--------------------------------------------------------------------------
| Superadmin
|--------------------------------------------------------------------------
*/

Route::middleware('superadmin')
    ->prefix('superadmin')
    ->name('superadmin.')
    ->group(function () {

        // Dashboard
        Route::get('/dashboard', [
            SuperadminDashboardController::class,
            'index',
        ])->name('dashboard');

        // Manajemen User
        Route::get('/users', [
            SuperadminUserController::class,
            'index',
        ])->name('users.index');

        Route::get('/users/create', [
            SuperadminUserController::class,
            'create',
        ])->name('users.create');

        Route::post('/users', [
            SuperadminUserController::class,
            'store',
        ])->name('users.store');

        Route::get('/users/import/template', [
            SuperadminUserImportController::class,
            'downloadTemplate',
        ])->name('users.import.template');

        Route::post('/users/import/preview', [
            SuperadminUserImportController::class,
            'preview',
        ])->name('users.import.preview');

        Route::post('/users/import/confirm', [
            SuperadminUserImportController::class,
            'confirm',
        ])->name('users.import.confirm');

        Route::get('/users/{user}', [
            SuperadminUserController::class,
            'show',
        ])->name('users.show');

        Route::get('/users/{user}/edit', [
            SuperadminUserController::class,
            'edit',
        ])->name('users.edit');

        Route::put('/users/{user}', [
            SuperadminUserController::class,
            'update',
        ])->name('users.update');

        Route::patch('/users/{user}/toggle-status', [
            SuperadminUserController::class,
            'toggleStatus',
        ])->name('users.toggle-status');

        // Activity Log
        Route::get('/activities', [
            ActivityLogController::class,
            'index',
        ])->name('activities.index');

        Route::get('/activities/{activity}', [
            ActivityLogController::class,
            'show',
        ])->name('activities.show');

        // System Monitoring
        Route::get('/monitoring', [
            SystemMonitoringController::class,
            'index',
        ])->name('monitoring.index');

        // Import JPL
        Route::get('/jpl/import', [
            JplImportController::class,
            'create',
        ])->name('jpl.import');

        Route::post('/jpl/import/preview', [
            JplImportController::class,
            'preview',
        ])->name('jpl.import.preview.store');

        Route::get('/jpl/import/preview', [
            JplImportController::class,
            'showPreview',
        ])->name('jpl.import.preview');

        Route::post('/jpl/import/confirm', [
            JplImportController::class,
            'confirm',
        ])->name('jpl.import.confirm');

        // Master Data JPL Superadmin
        Route::get('/jpl', [
            SuperadminJplController::class,
            'index',
        ])->name('jpl.index');

        Route::get('/jpl/{jpl}/edit', [
            SuperadminJplController::class,
            'edit',
        ])->name('jpl.edit');

        Route::put('/jpl/{jpl}', [
            SuperadminJplController::class,
            'update',
        ])->name('jpl.update');
    });