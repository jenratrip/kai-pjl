
<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\Auth\LoginController;

use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\UserImportController as AdminUserImportController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\JplController;
use App\Http\Controllers\Admin\JplImportController;
use App\Http\Controllers\Admin\ResortController;
use App\Http\Controllers\Admin\WilayahController;
use App\Http\Controllers\Admin\WilayahImportController;

use App\Http\Controllers\User\DashboardController as UserDashboardController;
use App\Http\Controllers\User\RealisasiKaController;
use App\Http\Controllers\User\CatatanDinasController;
use App\Http\Controllers\User\SerahTerimaDinasController;

use App\Http\Controllers\Superadmin\DashboardController as SuperadminDashboardController;
use App\Http\Controllers\Superadmin\UserController;
use App\Http\Controllers\Superadmin\UserImportController;
use App\Http\Controllers\Superadmin\ActivityLogController;
use App\Http\Controllers\Superadmin\SystemMonitoringController;


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


/*
|--------------------------------------------------------------------------
| Logout
|--------------------------------------------------------------------------
*/

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

        Route::get('/realisasi-ka', [
            RealisasiKaController::class,
            'index',
        ])->name('realisasi-ka');

        Route::post('/realisasi-ka', [
            RealisasiKaController::class,
            'store',
        ])->name('realisasi-ka.store');

        Route::get('/catatan-dinas', [
            CatatanDinasController::class,
            'index',
        ])->name('catatan-dinas');

        Route::post('/catatan-dinas', [
            CatatanDinasController::class,
            'store',
        ])->name('catatan-dinas.store');

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
| Admin - Operational Management
|--------------------------------------------------------------------------
|
| Admin mengelola data operasional.
| Superadmin juga dapat mengakses bagian Admin.
|
*/

Route::middleware(['auth', 'role:admin,superadmin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Dashboard Admin
        |--------------------------------------------------------------------------
        */

        Route::get('/dashboard', [
            DashboardController::class,
            'index',
        ])->name('dashboard');

        /*
        |--------------------------------------------------------------------------
        | Manajemen User
        |--------------------------------------------------------------------------
        */

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
            'show'
        ])->name('users.show');

        Route::get('/users/{id}/edit', [
            AdminUserController::class, 
            'edit'
        ])->name('users.edit');

        Route::put('/users/{id}', [
            AdminUserController::class, 
            'update'
        ])->name('users.update');

        Route::patch('/users/{id}/status', [
            AdminUserController::class, 
            'toggleStatus'
        ])->name('users.toggle-status');


        /*
        |--------------------------------------------------------------------------
        | Import JPL
        |--------------------------------------------------------------------------
        |
        | Route import harus diletakkan sebelum Route::resource('jpl', ...).
        |
        */

        // Halaman upload import JPL
        Route::get('/jpl/import', [
            JplImportController::class,
            'create',
        ])->name('jpl.import');

        // Upload file dan menampilkan preview
        Route::post('/jpl/import/preview', [
            JplImportController::class,
            'preview',
        ])->name('jpl.import.preview.store');

        // Halaman preview import
        Route::get('/jpl/import/preview', [
            JplImportController::class,
            'showPreview',
        ])->name('jpl.import.preview');

        // Konfirmasi dan simpan hasil import
        Route::post('/jpl/import/confirm', [
            JplImportController::class,
            'confirm',
        ])->name('jpl.import.confirm');


        /*
        |--------------------------------------------------------------------------
        | Master Data JPL
        |--------------------------------------------------------------------------
        */

        Route::resource('jpl', JplController::class);


        /*
        |--------------------------------------------------------------------------
        | Master Data Resort
        |--------------------------------------------------------------------------
        */

        Route::resource('resort', ResortController::class)
            ->except(['destroy']);

        // Aktifkan/nonaktifkan resort
        Route::patch('/resort/{resort}/toggle-status', [
            ResortController::class,
            'toggleStatus',
        ])->name('resort.toggle-status');


        /*
        |--------------------------------------------------------------------------
        | Master Data Wilayah
        |--------------------------------------------------------------------------
        |
        | Route import diletakkan sebelum resource wilayah.
        |
        */

        // Download template Excel wilayah
        Route::get('/wilayah/import/template', [
            WilayahController::class,
            'downloadTemplate',
        ])->name('wilayah.import.template');

        // Upload file dan validasi/preview
        Route::post('/wilayah/import/preview', [
            WilayahImportController::class,
            'preview',
        ])->name('wilayah.import.preview.store');

        // Halaman preview import wilayah
        Route::get('/wilayah/import/preview', [
            WilayahImportController::class,
            'showPreview',
        ])->name('wilayah.import.preview');

        // Konfirmasi dan simpan hasil import
        Route::post('/wilayah/import/confirm', [
            WilayahImportController::class,
            'confirm',
        ])->name('wilayah.import.confirm');

        // Resource master data wilayah
        Route::resource('wilayah', WilayahController::class)
            ->except(['show', 'destroy']);

        // Aktifkan/nonaktifkan wilayah
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

        /*
        |--------------------------------------------------------------------------
        | Dashboard Superadmin
        |--------------------------------------------------------------------------
        */

        Route::get('/dashboard', [
            SuperadminDashboardController::class,
            'index',
        ])->name('dashboard');


        /*
        |--------------------------------------------------------------------------
        | Manajemen User
        |--------------------------------------------------------------------------
        */

        // Daftar user
        Route::get('/users', [
            UserController::class,
            'index',
        ])->name('users.index');

        // Form tambah user
        Route::get('/users/create', [
            UserController::class,
            'create',
        ])->name('users.create');

        // Simpan user baru
        Route::post('/users', [
            UserController::class,
            'store',
        ])->name('users.store');

        // Download template Excel user
        Route::get('/users/import/template', [
            UserImportController::class,
            'downloadTemplate',
        ])->name('users.import.template');

        // Upload dan preview import user
        Route::post('/users/import/preview', [
            UserImportController::class,
            'preview',
        ])->name('users.import.preview');

        // Konfirmasi import user
        Route::post('/users/import/confirm', [
            UserImportController::class,
            'confirm',
        ])->name('users.import.confirm');

        // Detail user
        Route::get('/users/{user}', [
            UserController::class,
            'show',
        ])->name('users.show');

        // Form edit user
        Route::get('/users/{user}/edit', [
            UserController::class,
            'edit',
        ])->name('users.edit');

        // Update user
        Route::put('/users/{user}', [
            UserController::class,
            'update',
        ])->name('users.update');

        // Aktifkan/nonaktifkan user
        Route::patch('/users/{user}/toggle-status', [
            UserController::class,
            'toggleStatus',
        ])->name('users.toggle-status');

        // Monitoring Activity Log
        Route::get('/activities', [
            ActivityLogController::class,
            'index',
        ])->name('activities.index');

        Route::get('/activities/{activity}', [
            ActivityLogController::class,
            'show',
        ])->name('activities.show');

        Route::get('/monitoring', [
            SystemMonitoringController::class,
            'index',
        ])->name('monitoring.index');

        // Master Data JPL
        
        // Import JPL untuk Superadmin
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
