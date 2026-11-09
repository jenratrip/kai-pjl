<?php

use App\Http\Controllers\Admin\CatatanDinasController as AdminCatatanDinasController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DinasController;
// ======================================================
// ADMIN CONTROLLERS
// ======================================================

use App\Http\Controllers\Admin\InventarisController as AdminInventarisController;
use App\Http\Controllers\Admin\JadwalKaController;
use App\Http\Controllers\Admin\JadwalKaImportController;
use App\Http\Controllers\Admin\JplController;
use App\Http\Controllers\Admin\JplImportController;
use App\Http\Controllers\Admin\RealisasiKaController as AdminRealisasiKaController;
use App\Http\Controllers\Admin\RealisasiKaSettingController;
use App\Http\Controllers\Admin\SerahTerimaDinasController as AdminSerahTerimaDinasController;
use App\Http\Controllers\Admin\ShiftController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\UserImportController as AdminUserImportController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordRecoveryController;
use App\Http\Controllers\Superadmin\ActivityLogController;
use App\Http\Controllers\Superadmin\DashboardController as SuperadminDashboardController;
use App\Http\Controllers\Superadmin\DinasController as SuperadminDinasController;
use App\Http\Controllers\Superadmin\InventarisController as SuperadminInventarisController;
// ======================================================
// USER / PJL CONTROLLERS
// ======================================================

use App\Http\Controllers\Superadmin\JadwalKaController as SuperadminJadwalKaController;
use App\Http\Controllers\Superadmin\JplController as SuperadminJplController;
use App\Http\Controllers\Superadmin\SettingsController;
use App\Http\Controllers\Superadmin\SystemMonitoringController;
use App\Http\Controllers\Superadmin\UserController as SuperadminUserController;
// ======================================================
// SUPERADMIN CONTROLLERS
// ======================================================

use App\Http\Controllers\Superadmin\UserImportController as SuperadminUserImportController;
use App\Http\Controllers\User\CatatanDinasController;
use App\Http\Controllers\User\CoachingMessageController;
use App\Http\Controllers\User\InventarisController as UserInventarisController;
use App\Http\Controllers\User\RealisasiKaController;
use App\Http\Controllers\User\SerahTerimaDinasController;
use App\Http\Controllers\User\UserProfileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// ======================================================
// ROOT / HALAMAN UTAMA
// ======================================================

Route::get('/', function () {
    if (! Auth::check()) {
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
    Route::get('/forgot-password', [PasswordRecoveryController::class, 'create'])
        ->name('password.request');

    Route::post('/forgot-password', [PasswordRecoveryController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.email');

    Route::get('/reset-password/{user}/{token}', [PasswordRecoveryController::class, 'edit'])
        ->whereNumber('user')
        ->where('token', '[A-Za-z0-9]{64}')
        ->name('password.reset');

    Route::post('/reset-password/{user}/{token}', [PasswordRecoveryController::class, 'update'])
        ->whereNumber('user')
        ->where('token', '[A-Za-z0-9]{64}')
        ->middleware('throttle:10,1')
        ->name('password.update');

    Route::get('/login', [
        LoginController::class,
        'showLoginForm',
    ])->name('login');

    Route::post('/login/cek', [
        LoginController::class,
        'cek',
    ])->middleware('throttle:10,1')->name('login.cek');

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

        Route::get('/profil', [UserProfileController::class, 'show'])
            ->name('profile.show');
        Route::get('/profil/foto', [UserProfileController::class, 'photo'])
            ->name('profile.photo');
        Route::post('/profil/foto', [UserProfileController::class, 'updatePhoto'])
            ->middleware('throttle:10,1')
            ->name('profile.photo.update');
        Route::delete('/profil/foto', [UserProfileController::class, 'destroyPhoto'])
            ->middleware('throttle:10,1')
            ->name('profile.photo.destroy');
        Route::put('/profil/password', [UserProfileController::class, 'updatePassword'])
            ->middleware('throttle:5,1')
            ->name('profile.password.update');

        // REALISASI KA

        Route::get('/realisasi-ka', [
            RealisasiKaController::class,
            'index',
        ])->name('realisasi-ka');

        Route::post('/realisasi-ka', [
            RealisasiKaController::class,
            'store',
        ])->name('realisasi-ka.store');

        // CATATAN DINAS USER

        Route::get('/catatan-dinas', [
            CatatanDinasController::class,
            'index',
        ])->name('catatan-dinas');

        Route::post('/catatan-dinas', [
            CatatanDinasController::class,
            'store',
        ])->name('catatan-dinas.store');

        // PESAN PEMBINAAN PJL

        Route::get('/pesan-pembinaan', [
            CoachingMessageController::class,
            'index',
        ])->name('coaching-messages.index');

        Route::get('/pesan-pembinaan/{message}', [
            CoachingMessageController::class,
            'show',
        ])->whereNumber('message')
            ->name('coaching-messages.show');

        Route::post('/pesan-pembinaan/{message}/dibaca', [
            CoachingMessageController::class,
            'markAsRead',
        ])->whereNumber('message')
            ->middleware('throttle:30,1')
            ->name('coaching-messages.read');

        // SERAH TERIMA DINAS USER

        Route::get('/serah-terima-dinas', [
            SerahTerimaDinasController::class,
            'index',
        ])->name('serah-terima-dinas');

        // PENCARIAN PENERIMA BERDASARKAN NAMA / NIP

        Route::get('/serah-terima-dinas/cari-penerima', [
            SerahTerimaDinasController::class,
            'cariPenerima',
        ])->middleware('throttle:30,1')
            ->name('serah-terima-dinas.cari-penerima');

        // RIWAYAT SERAH TERIMA

        Route::get('/serah-terima-dinas/riwayat', [
            SerahTerimaDinasController::class,
            'riwayat',
        ])->name('serah-terima-dinas.riwayat');

        Route::get('/serah-terima-dinas/riwayat/{serahTerima}/edit', [
            SerahTerimaDinasController::class,
            'edit',
        ])->whereNumber('serahTerima')
            ->name('serah-terima-dinas.edit');

        Route::get('/serah-terima-dinas/riwayat/{serahTerima}/foto-bukti', [
            SerahTerimaDinasController::class,
            'fotoBukti',
        ])->whereNumber('serahTerima')
            ->name('serah-terima-dinas.foto-bukti');

        Route::get('/serah-terima-dinas/riwayat/{serahTerima}/inventaris/{inventaris}/foto-bukti', [
            SerahTerimaDinasController::class,
            'fotoBuktiInventaris',
        ])->whereNumber('serahTerima')
            ->whereNumber('inventaris')
            ->name('serah-terima-dinas.inventaris.foto-bukti');

        Route::get('/serah-terima-dinas/riwayat/{serahTerima}/tanda-tangan/{jenis}', [
            SerahTerimaDinasController::class,
            'tandaTangan',
        ])->whereNumber('serahTerima')
            ->whereIn('jenis', ['penyerah', 'penerima'])
            ->name('serah-terima-dinas.tanda-tangan');

        Route::get('/serah-terima-dinas/riwayat/{serahTerima}', [
            SerahTerimaDinasController::class,
            'show',
        ])->whereNumber('serahTerima')
            ->name('serah-terima-dinas.show');

        // KONFIRMASI SERAH TERIMA OLEH PJL PENERIMA

        Route::post('/serah-terima-dinas/{serahTerima}/konfirmasi', [
            SerahTerimaDinasController::class,
            'konfirmasi',
        ])->whereNumber('serahTerima')
            ->middleware('throttle:10,1')
            ->name('serah-terima-dinas.konfirmasi');

        // SIMPAN CATATAN SERAH TERIMA

        Route::post('/serah-terima-dinas/catatan', [
            SerahTerimaDinasController::class,
            'storeCatatan',
        ])->middleware('throttle:20,1')
            ->name('serah-terima-dinas.catatan.store');

        // SIMPAN REALISASI INVENTARIS SERAH TERIMA

        Route::post('/serah-terima-dinas/inventaris', [
            SerahTerimaDinasController::class,
            'storeInventaris',
        ])->middleware('throttle:60,1')
            ->name('serah-terima-dinas.inventaris.store');

        // SIMPAN SERAH TERIMA DINAS

        Route::post('/serah-terima-dinas/simpan', [
            SerahTerimaDinasController::class,
            'store',
        ])->middleware('throttle:10,1')
            ->name('serah-terima-dinas.store');

        // ENDPOINT KOMPATIBILITAS LAMA

        Route::post('/serah-terima-dinas', [
            SerahTerimaDinasController::class,
            'store',
        ])->middleware('throttle:10,1');

        // INVENTARIS PJL

        Route::get('/inventaris', [
            UserInventarisController::class,
            'index',
        ])->name('inventaris.index');

        Route::post('/inventaris/salin-pemeriksaan', [
            UserInventarisController::class,
            'prepare',
        ])->middleware('throttle:20,1')
            ->name('inventaris.prepare');

        Route::get('/inventaris/{inventaris}/bukti', [
            UserInventarisController::class,
            'fotoBukti',
        ])->whereNumber('inventaris')
            ->name('inventaris.foto-bukti');

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

        // DAFTAR REALISASI KA

        Route::get('/realisasi-ka', [
            AdminRealisasiKaController::class,
            'index',
        ])->name('realisasi-ka.index');

        // MANAJEMEN USER

        Route::get('/users', [
            AdminUserController::class,
            'index',
        ])->name('users.index');

        Route::get('/users/import/template', [
            AdminUserImportController::class,
            'downloadTemplate',
        ])->name('users.import.template');

        Route::get('/users/{id}', [
            AdminUserController::class,
            'show',
        ])->name('users.show');

        // REVIEW CATATAN DINAS

        Route::get('/catatan-dinas', [
            AdminCatatanDinasController::class,
            'index',
        ])->name('catatan-dinas.index');

        Route::get('/catatan-dinas/{catatanDinas}', [
            AdminCatatanDinasController::class,
            'show',
        ])->name('catatan-dinas.show');

        // REVIEW SERAH TERIMA DINAS

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
        ])->whereNumber('serahTerima')
            ->name('serah-terima-dinas.show');

        Route::get('/serah-terima-dinas/{serahTerima}/tanda-tangan/{jenis}', [
            SerahTerimaDinasController::class,
            'tandaTangan',
        ])->whereNumber('serahTerima')
            ->whereIn('jenis', ['penyerah', 'penerima'])
            ->name('serah-terima-dinas.tanda-tangan');

        // MASTER DATA SHIFT

        Route::get('/shift', [
            ShiftController::class,
            'index',
        ])->name('shift.index');

        // JADWAL DINAS PJL

        Route::get('/dinas/export', [
            DinasController::class,
            'export',
        ])->name('dinas.export');

        Route::get('/dinas/template', [
            DinasController::class,
            'template',
        ])->name('dinas.template');

        // JADWAL KA

        Route::get('/jadwal-ka', [
            JadwalKaController::class,
            'index',
        ])->name('jadwal-ka.index');

        // ARSIP JADWAL KA

        Route::get('/jadwal-ka/arsip', [
            JadwalKaController::class,
            'arsip',
        ])->name('jadwal-ka.arsip');

        Route::get('/jadwal-ka/arsip/{tanggal}', [
            JadwalKaController::class,
            'showArsip',
        ])->where('tanggal', '[0-9]{4}-[0-9]{2}-[0-9]{2}')
            ->name('jadwal-ka.arsip.show');

        // DETAIL JADWAL KA BERDASARKAN JPL

        Route::get('/jadwal-ka/{jpl}', [
            JadwalKaController::class,
            'show',
        ])->whereNumber('jpl')
            ->name('jadwal-ka.show');

        // Operasi berikut hanya membaca data. Halaman Admin Dinas yang
        // menyinkronkan status dan pengaturan yang membuat default berada di grup Admin.

        // Admin dan Superadmin hanya melakukan review.
        // Tanda tangan penerima dilakukan oleh PJL penerima.

        // INVENTARIS ADMIN
        // Superadmin tidak dibatasi dari fitur operasional lainnya.
        // Pengelolaan inventaris dibatasi untuk admin pada konfigurasi ini.

        Route::middleware('role:admin')->group(function () {

            Route::get('/inventaris/jpl/{jpl}', [
                AdminInventarisController::class,
                'showJpl',
            ])->whereNumber('jpl')
                ->name('inventaris.jpl.show');

            Route::post('/inventaris/{inventaris}/review', [
                AdminInventarisController::class,
                'review',
            ])->whereNumber('inventaris')
                ->middleware('throttle:20,1')
                ->name('inventaris.review');

            Route::resource('inventaris', AdminInventarisController::class)
                ->parameters([
                    'inventaris' => 'inventaris',
                ]);
        });

        // OPERASI ADMIN YANG MENGUBAH DATA ATAU MEMILIKI EFEK SAMPING

        Route::middleware('role:admin')->group(function () {
            Route::get('/pengaturan-realisasi-ka', [
                RealisasiKaSettingController::class,
                'index',
            ])->name('realisasi-ka-settings.index');

            Route::put('/pengaturan-realisasi-ka', [
                RealisasiKaSettingController::class,
                'update',
            ])->name('realisasi-ka-settings.update');

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

            Route::put('/users/{id}', [
                AdminUserController::class,
                'update',
            ])->name('users.update');

            Route::patch('/users/{id}/status', [
                AdminUserController::class,
                'toggleStatus',
            ])->name('users.toggle-status');

            Route::patch('/catatan-dinas/{catatanDinas}', [
                AdminCatatanDinasController::class,
                'update',
            ])->name('catatan-dinas.update');

            Route::patch('/serah-terima-dinas/{serahTerima}', [
                AdminSerahTerimaDinasController::class,
                'update',
            ])->whereNumber('serahTerima')
                ->middleware('throttle:10,1')
                ->name('serah-terima-dinas.update');

            Route::resource('shift', ShiftController::class)
                ->except(['create', 'show', 'edit', 'index']);

            Route::post('/dinas/import', [
                DinasController::class,
                'import',
            ])->name('dinas.import');

            Route::post('/dinas/import/preview', [
                DinasController::class,
                'previewImport',
            ])->middleware('throttle:5,1')->name('dinas.import.preview');

            Route::get('/dinas/updates', [
                DinasController::class,
                'updates',
            ])->name('dinas.updates');

            Route::resource('dinas', DinasController::class)
                ->except(['create', 'show'])
                ->names('dinas');
        });
    });

// ======================================================
// ADMIN-ONLY OPERATIONS: JPL AND JADWAL KA
// ======================================================

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // JADWAL KA IMPORT

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

        Route::post('/jadwal-ka-batches/{batch}/schedule', [
            JadwalKaController::class,
            'scheduleBatch',
        ])->whereNumber('batch')
            ->name('jadwal-ka-batches.schedule');

        Route::resource('jadwal-ka', JadwalKaController::class)
            ->only(['edit', 'update']);

        Route::patch('/jadwal-ka/{jadwalKa}/toggle-status', [
            JadwalKaController::class,
            'toggleStatus',
        ])->whereNumber('jadwalKa')
            ->name('jadwal-ka.toggle-status');

        Route::get('/jadwal-ka/template', [
            JadwalKaController::class,
            'downloadTemplate',
        ])->name('jadwal-ka.template');

        Route::post('/jadwal-ka/import', [
            JadwalKaController::class,
            'importExcel',
        ])->name('jadwal-ka.import.store');

        // JPL IMPORT AND MUTATIONS

        Route::get('/jpl/import/template', [
            JplImportController::class,
            'downloadTemplate',
        ])->name('jpl.import.template');

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

        Route::post('/jpl/{id}/activate', [
            JplController::class,
            'activate',
        ])->whereNumber('id')
            ->name('jpl.activate');

        Route::resource('jpl', JplController::class);
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

        Route::get('/settings', [
            SettingsController::class,
            'index',
        ])->name('settings.index');

        Route::put('/settings/profile', [
            SettingsController::class,
            'updateProfile',
        ])->name('settings.profile.update');

        Route::put('/settings/password', [
            SettingsController::class,
            'updatePassword',
        ])->name('settings.password.update');

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

        // DAFTAR DINASAN READ-ONLY

        Route::get('/dinas', [
            SuperadminDinasController::class,
            'index',
        ])->name('dinas.index');

        // INVENTARIS READ-ONLY

        Route::get('/inventaris/jpl/{jpl}', [
            SuperadminInventarisController::class,
            'showJpl',
        ])->whereNumber('jpl')
            ->name('inventaris.jpl.show');

        Route::resource('inventaris', SuperadminInventarisController::class)
            ->parameters([
                'inventaris' => 'inventaris',
            ])
            ->only(['index', 'show']);

        // JADWAL KA READ-ONLY

        Route::get('/jadwal-ka', [
            SuperadminJadwalKaController::class,
            'index',
        ])->name('jadwal-ka.index');

        Route::get('/jadwal-ka/arsip', [
            SuperadminJadwalKaController::class,
            'arsip',
        ])->name('jadwal-ka.arsip');

        Route::get('/jadwal-ka/arsip/{tanggal}', [
            SuperadminJadwalKaController::class,
            'showArsip',
        ])->where('tanggal', '[0-9]{4}-[0-9]{2}-[0-9]{2}')
            ->name('jadwal-ka.arsip.show');

        Route::get('/jadwal-ka/{jpl}', [
            SuperadminJadwalKaController::class,
            'show',
        ])->whereNumber('jpl')
            ->name('jadwal-ka.show');

        // MASTER DATA JPL SUPERADMIN

        Route::get('/jpl', [
            SuperadminJplController::class,
            'index',
        ])->name('jpl.index');

    });
