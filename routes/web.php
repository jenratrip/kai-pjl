<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\LoginController;

// ======================================================
// ADMIN CONTROLLERS
// ======================================================

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\UserImportController as AdminUserImportController;
use App\Http\Controllers\Admin\DinasController;
use App\Http\Controllers\Admin\ShiftController;
use App\Http\Controllers\Admin\JplController;
use App\Http\Controllers\Admin\JplImportController;
use App\Http\Controllers\Admin\ResortController;
use App\Http\Controllers\Admin\WilayahController;
use App\Http\Controllers\Admin\WilayahImportController;
use App\Http\Controllers\Admin\InventarisController as AdminInventarisController;
use App\Http\Controllers\Admin\SerahTerimaDinasController as AdminSerahTerimaDinasController;
use App\Http\Controllers\Admin\JadwalKaController;
use App\Http\Controllers\Admin\JadwalKaImportController;
use App\Http\Controllers\Admin\RealisasiKaSettingController;

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
use App\Http\Controllers\Superadmin\UserController as SuperadminUserController;
use App\Http\Controllers\Superadmin\UserImportController as SuperadminUserImportController;
use App\Http\Controllers\Superadmin\ActivityLogController;
use App\Http\Controllers\Superadmin\SystemMonitoringController;
use App\Http\Controllers\Superadmin\JplController as SuperadminJplController;


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
        return redirect()->route('user.realisasi-ka');
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

        // DASHBOARD

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


        // SERAH TERIMA DINAS

        Route::get('/serah-terima-dinas', [
            SerahTerimaDinasController::class,
            'index',
        ])->name('serah-terima-dinas');

        // Riwayat serah terima milik petugas.
        Route::get('/serah-terima-dinas/riwayat', [
            SerahTerimaDinasController::class,
            'riwayat',
        ])->name('serah-terima-dinas.riwayat');

        // Membuka kembali serah terima dari riwayat (hanya yang belum terkunci).
        Route::get('/serah-terima-dinas/riwayat/{serahTerima}/edit', [
            SerahTerimaDinasController::class,
            'edit',
        ])->whereNumber('serahTerima')->name('serah-terima-dinas.edit');

        // Melihat serah terima dari riwayat (hanya-baca, untuk yang sudah disetujui).
        Route::get('/serah-terima-dinas/riwayat/{serahTerima}', [
            SerahTerimaDinasController::class,
            'show',
        ])->whereNumber('serahTerima')->name('serah-terima-dinas.show');

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

        // Endpoint kompatibilitas lama.
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

        // DASHBOARD

        Route::get('/dashboard', [
            AdminDashboardController::class,
            'index',
        ])->name('dashboard');


        // PENGATURAN REALISASI KA

        Route::get('/pengaturan-realisasi-ka', [
            RealisasiKaSettingController::class,
            'index',
        ])->name('realisasi-ka-settings.index');

        Route::put('/pengaturan-realisasi-ka', [
            RealisasiKaSettingController::class,
            'update',
        ])->name('realisasi-ka-settings.update');


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

        // Dipertahankan sesuai routing sebelumnya.
        Route::post('/serah-terima-dinas/{serahTerima}/konfirmasi', [
            SerahTerimaDinasController::class,
            'konfirmasi',
        ])->name('serah-terima-dinas.konfirmasi');


        // MASTER DATA SHIFT

        Route::resource('shift', ShiftController::class)
            ->except(['create', 'show', 'edit']);


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


        // JADWAL KA

        Route::get('/jadwal-ka', [
            JadwalKaController::class,
            'index',
        ])->name('jadwal-ka.index');

        Route::get('/jadwal-ka/import', [
            JadwalKaImportController::class,
            'create',
        ])->name('jadwal-ka.import');

        Route::post('/jadwal-ka/import/preview', [
            JadwalKaImportController::class,
            'preview',
        ])->name('jadwal-ka.import.preview');

        Route::post('/jadwal-ka/import/confirm', [
            JadwalKaImportController::class,
            'confirm',
        ])->name('jadwal-ka.import.confirm');

        Route::get('/jadwal-ka/{jpl}', [
            JadwalKaController::class,
            'show',
        ])->whereNumber('jpl')->name('jadwal-ka.show');


        // IMPORT JPL ADMIN + SUPERADMIN

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

        Route::post('/jpl/{id}/activate', [
            JplController::class,
            'activate',
        ])->whereNumber('id')->name('jpl.activate');

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


        // INVENTARIS KHUSUS ADMIN

        Route::middleware('role:admin')->group(function () {

            Route::get('/inventaris/jpl/{jpl}', [
                AdminInventarisController::class,
                'showJpl',
            ])->name('inventaris.jpl.show');

            Route::resource('inventaris', AdminInventarisController::class)
                ->parameters([
                    'inventaris' => 'inventaris',
                ]);
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

        // DASHBOARD

        Route::get('/dashboard', [
            SuperadminDashboardController::class,
            'index',
        ])->name('dashboard');


        // MANAJEMEN USER SISTEM

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
        ])->name('users.import.preview.store');

        Route::get('/users/import/preview', [
            SuperadminUserImportController::class,
            'showPreview',
        ])->name('users.import.preview');

        Route::post('/users/import/confirm', [
            SuperadminUserImportController::class,
            'confirm',
        ])->name('users.import.confirm');

        Route::get('/users/{user}/edit', [
            SuperadminUserController::class,
            'edit',
        ])->name('users.edit');

        Route::get('/users/{user}', [
            SuperadminUserController::class,
            'show',
        ])->name('users.show');

        Route::put('/users/{user}', [
            SuperadminUserController::class,
            'update',
        ])->name('users.update');

        Route::patch('/users/{user}/toggle-status', [
            SuperadminUserController::class,
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