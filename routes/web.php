<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\LoginController;

// ======================================================
// ADMIN CONTROLLERS
// ======================================================

use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\UserImportController as AdminUserImportController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DinasController;
use App\Http\Controllers\Admin\JplController;
use App\Http\Controllers\Admin\JplImportController;
use App\Http\Controllers\Admin\ResortController;
use App\Http\Controllers\Admin\WilayahController;
use App\Http\Controllers\Admin\WilayahImportController;
use App\Http\Controllers\Admin\InventarisController as AdminInventarisController;
use App\Http\Controllers\Admin\SerahTerimaDinasController as AdminSerahTerimaDinasController;

// ======================================================
// USER / PJL CONTROLLERS
// ======================================================

use App\Http\Controllers\User\DashboardController as UserDashboardController;
use App\Http\Controllers\User\RealisasiKaController;
use App\Http\Controllers\User\CatatanDinasController;
use App\Http\Controllers\User\SerahTerimaDinasController;
use App\Http\Controllers\User\InventarisController as UserInventarisController;

// ======================================================
// SUPERADMIN CONTROLLERS
// ======================================================

use App\Http\Controllers\Superadmin\DashboardController as SuperadminDashboardController;
use App\Http\Controllers\Superadmin\UserController;
use App\Http\Controllers\Superadmin\UserImportController;
use App\Http\Controllers\Superadmin\ActivityLogController;
use App\Http\Controllers\Superadmin\SystemMonitoringController;


// ======================================================
// ROOT / HALAMAN UTAMA
// ======================================================

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

    if ($user->hasRole('pjl')) {
        return redirect()->route('user.dashboard');
    }

    abort(403, 'Role pengguna tidak dikenali.');

})->name('home');


// ======================================================
// AUTHENTICATION
// ======================================================

Route::middleware('guest')->group(function () {

    Route::get('/login', [
        LoginController::class,
        'showLoginForm',
    ])->name('login');

    Route::post('/login', [
        LoginController::class,
        'login',
    ])->middleware('throttle:5,1')->name('login.store');

});


// ======================================================
// LOGOUT
// ======================================================

Route::post('/logout', [
    LoginController::class,
    'logout',
])->middleware('auth')->name('logout');


// ======================================================
// USER / PJL
// ======================================================

Route::middleware(['auth', 'role:pjl'])
    ->prefix('user')
    ->name('user.')
    ->group(function () {

        // DASHBOARD PJL

        Route::get('/dashboard', [
            UserDashboardController::class,
            'index',
        ])->name('dashboard');


        // REALISASI KA

        Route::get('/realisasi-ka', [
            RealisasiKaController::class,
            'index',
        ])->name('realisasi-ka');

        Route::post('/realisasi-ka', [
            RealisasiKaController::class,
            'store',
        ])->name('realisasi-ka.store');


        // CATATAN DINAS

        Route::get('/catatan-dinas', [
            CatatanDinasController::class,
            'index',
        ])->name('catatan-dinas');

        Route::post('/catatan-dinas', [
            CatatanDinasController::class,
            'store',
        ])->name('catatan-dinas.store');


        // SERAH TERIMA DINAS PJL

        Route::get('/serah-terima-dinas', [
            SerahTerimaDinasController::class,
            'index',
        ])->name('serah-terima-dinas');

        Route::post('/serah-terima-dinas/catatan', [
            SerahTerimaDinasController::class,
            'storeCatatan',
        ])->name('serah-terima-dinas.catatan.store');

        Route::post('/serah-terima-dinas/inventaris', [
            SerahTerimaDinasController::class,
            'storeInventaris',
        ])->name('serah-terima-dinas.inventaris.store');

        Route::post('/serah-terima-dinas/simpan', [
            SerahTerimaDinasController::class,
            'store',
        ])->name('serah-terima-dinas.store');

        // Endpoint kompatibilitas
        Route::post('/serah-terima-dinas', [
            SerahTerimaDinasController::class,
            'store',
        ]);


        // INVENTARIS PJL

        Route::get('/inventaris', [
            UserInventarisController::class,
            'index',
        ])->name('inventaris.index');

        Route::patch('/inventaris/{inventaris}', [
            UserInventarisController::class,
            'update',
        ])->name('inventaris.update');

    });


// ======================================================
// ADMIN + SUPERADMIN
// FITUR OPERASIONAL BERSAMA
// ======================================================

Route::middleware(['auth', 'role:admin,superadmin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // DASHBOARD ADMIN

        Route::get('/dashboard', [
            AdminDashboardController::class,
            'index',
        ])->name('dashboard');


        // MANAJEMEN USER

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

        Route::get('/users/{id}/edit', [
            AdminUserController::class,
            'edit',
        ])->name('users.edit');

        Route::get('/users/{id}', [
            AdminUserController::class,
            'show',
        ])->name('users.show');

        Route::put('/users/{id}', [
            AdminUserController::class,
            'update',
        ])->name('users.update');

        Route::patch('/users/{id}/status', [
            AdminUserController::class,
            'toggleStatus',
        ])->name('users.toggle-status');


        // SERAH TERIMA DINAS ADMIN

        Route::get('/serah-terima-dinas/updates', [
            AdminSerahTerimaDinasController::class,
            'updates',
        ])->name('serah-terima-dinas.updates');

        Route::get('/serah-terima-dinas', [
            AdminSerahTerimaDinasController::class,
            'index',
        ])->name('serah-terima-dinas.index');

        Route::get('/serah-terima-dinas/{serahTerima}', [
            AdminSerahTerimaDinasController::class,
            'show',
        ])->name('serah-terima-dinas.show');

        Route::patch('/serah-terima-dinas/{serahTerima}', [
            AdminSerahTerimaDinasController::class,
            'update',
        ])->name('serah-terima-dinas.update');

        // Perlu verifikasi implementasi method konfirmasi()
        Route::post('/serah-terima-dinas/{serahTerima}/konfirmasi', [
            SerahTerimaDinasController::class,
            'konfirmasi',
        ])->name('serah-terima-dinas.konfirmasi');


        // JADWAL DINAS PJL

        Route::get('/dinas/export', [
            DinasController::class,
            'export',
        ])->name('dinas.export');

        Route::get('/dinas/template', [
            DinasController::class,
            'template',
        ])->name('dinas.template');

        Route::post('/dinas/import', [
            DinasController::class,
            'import',
        ])->name('dinas.import');

        Route::get('/dinas/updates', [
            DinasController::class,
            'updates',
        ])->name('dinas.updates');

        Route::resource('dinas', DinasController::class)
            ->except(['create', 'show'])
            ->names('dinas');


        // IMPORT JPL

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


        // MASTER DATA JPL

        Route::resource('jpl', JplController::class);


        // MASTER DATA RESORT

        Route::resource('resort', ResortController::class)
            ->except(['destroy']);

        Route::patch('/resort/{resort}/toggle-status', [
            ResortController::class,
            'toggleStatus',
        ])->name('resort.toggle-status');


        // IMPORT WILAYAH

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


        // MASTER DATA WILAYAH

        Route::patch('/wilayah/{wilayah}/toggle-status', [
            WilayahController::class,
            'toggleStatus',
        ])->name('wilayah.toggle-status');

        Route::resource('wilayah', WilayahController::class)
            ->except(['show', 'destroy']);


        // INVENTARIS ADMIN

        Route::middleware('role:admin')->group(function () {

            Route::resource(
                'inventaris',
                AdminInventarisController::class
            );

        });

    });


// ======================================================
// SUPERADMIN
// FITUR TEKNIS KHUSUS
// ======================================================

Route::middleware(['auth', 'superadmin'])
    ->prefix('superadmin')
    ->name('superadmin.')
    ->group(function () {

        // DASHBOARD SUPERADMIN

        Route::get('/dashboard', [
            SuperadminDashboardController::class,
            'index',
        ])->name('dashboard');


        // MANAJEMEN USER SISTEM

        Route::get('/users', [
            UserController::class,
            'index',
        ])->name('users.index');

        Route::get('/users/create', [
            UserController::class,
            'create',
        ])->name('users.create');

        Route::post('/users', [
            UserController::class,
            'store',
        ])->name('users.store');

        Route::get('/users/import/template', [
            UserImportController::class,
            'downloadTemplate',
        ])->name('users.import.template');

        Route::post('/users/import/preview', [
            UserImportController::class,
            'preview',
        ])->name('users.import.preview');

        Route::post('/users/import/confirm', [
            UserImportController::class,
            'confirm',
        ])->name('users.import.confirm');

        Route::get('/users/{user}/edit', [
            UserController::class,
            'edit',
        ])->name('users.edit');

        Route::get('/users/{user}', [
            UserController::class,
            'show',
        ])->name('users.show');

        Route::put('/users/{user}', [
            UserController::class,
            'update',
        ])->name('users.update');

        Route::patch('/users/{user}/toggle-status', [
            UserController::class,
            'toggleStatus',
        ])->name('users.toggle-status');


        // ACTIVITY LOG

        Route::get('/activities', [
            ActivityLogController::class,
            'index',
        ])->name('activities.index');

        Route::get('/activities/{activity}', [
            ActivityLogController::class,
            'show',
        ])->name('activities.show');


        // SYSTEM MONITORING

        Route::get('/monitoring', [
            SystemMonitoringController::class,
            'index',
        ])->name('monitoring.index');


        // IMPORT JPL SUPERADMIN

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


        // MASTER DATA JPL SUPERADMIN

        Route::get('/jpl', [
            \App\Http\Controllers\Superadmin\JplController::class,
            'index',
        ])->name('jpl.index');

        Route::get('/jpl/{jpl}/edit', [
            \App\Http\Controllers\Superadmin\JplController::class,
            'edit',
        ])->name('jpl.edit');

        Route::put('/jpl/{jpl}', [
            \App\Http\Controllers\Superadmin\JplController::class,
            'update',
        ])->name('jpl.update');

    });
