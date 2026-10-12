<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\LoginController;

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\JplController;
use App\Http\Controllers\Admin\JplImportController;
use App\Http\Controllers\Admin\ResortController;
use App\Http\Controllers\Admin\WilayahController;
use App\Http\Controllers\Admin\WilayahImportController;

use App\Http\Controllers\User\RealisasiKaController;
use App\Http\Controllers\User\CatatanDinasController;
use App\Http\Controllers\User\SerahTerimaDinasController;

use App\Http\Controllers\Superadmin\DashboardController as SuperadminDashboardController;
use App\Http\Controllers\Superadmin\UserController;
use App\Http\Controllers\Superadmin\UserImportController;


/*
|--------------------------------------------------------------------------
| Root
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    if (!auth()->check()) {
        return redirect()->route('login');
    }

    $user = auth()->user();

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

        /*
        |--------------------------------------------------------------------------
        | Dashboard User
        |--------------------------------------------------------------------------
        */

        Route::get('/dashboard', [
            \App\Http\Controllers\User\DashboardController::class,
            'index',
        ])->name('dashboard');



        /*
        |--------------------------------------------------------------------------
        | Realisasi KA
        |--------------------------------------------------------------------------
        */

        Route::get('/realisasi-ka', [
            RealisasiKaController::class,
            'index',
        ])->name('realisasi-ka');

        Route::post('/realisasi-ka', [
            RealisasiKaController::class,
            'store',
        ])->name('realisasi-ka.store');


       
        /*
        |--------------------------------------------------------------------------
        | Catatan Dinas
        |--------------------------------------------------------------------------
        */

        Route::get('/catatan-dinas', [
            CatatanDinasController::class,
            'index',
        ])->name('catatan-dinas');

        Route::post('/catatan-dinas', [
            CatatanDinasController::class,
            'store',
        ])->name('catatan-dinas.store');



        /*
        |--------------------------------------------------------------------------
        | Serah Terima Dinas
        |--------------------------------------------------------------------------
        */

        // Menampilkan halaman Serah Terima Dinas
        Route::get('/serah-terima-dinas', [
            SerahTerimaDinasController::class,
            'index',
        ])->name('serah-terima-dinas');

        // Menyimpan dokumen Serah Terima Dinas
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
        | Import JPL
        |--------------------------------------------------------------------------
        |
        | Route import diletakkan sebelum Route::resource('jpl', ...).
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

    });