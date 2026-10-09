<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\LoginController;

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\JplController;
use App\Http\Controllers\Admin\WilayahController;
use App\Http\Controllers\Admin\WilayahImportController;

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

    // Dashboard PJL
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
        'showLoginForm'
    ])->name('login');

    Route::post('/login', [
        LoginController::class,
        'login'
    ])->name('login.store');

});


/*
|--------------------------------------------------------------------------
| Logout
|--------------------------------------------------------------------------
*/

Route::post('/logout', [
    LoginController::class,
    'logout'
])->middleware('auth')->name('logout');


/*
|--------------------------------------------------------------------------
| User / PJL
|--------------------------------------------------------------------------
*/

Route::middleware('auth')
    ->prefix('user')
    ->name('user.')
    ->group(function () {

        Route::get('/dashboard', function () {
            return view('user.dashboard');
        })->name('dashboard');

        Route::get('/realisasi-ka', function () {
            return view('user.realisasi-ka');
        })->name('realisasi-ka');

        Route::get('/catatan-dinas', function () {
            return view('user.catatan-dinas');
        })->name('catatan-dinas');

        Route::get('/serah-terima-dinas', function () {
            return view('user.serah-terima-dinas');
        })->name('serah-terima-dinas');

    });


/*
|--------------------------------------------------------------------------
| Admin - Operational Management
|--------------------------------------------------------------------------
|
| Superadmin juga memiliki akses ke bagian Admin
| sesuai middleware role yang digunakan backend.
|
*/

Route::middleware(['auth', 'role:admin,superadmin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // Dashboard Admin
        Route::get('/dashboard', [
            DashboardController::class,
            'index'
        ])->name('dashboard');

        // Master Data JPL
        Route::resource('jpl', JplController::class);

        // Download Template Import Wilayah
        Route::get('/wilayah/import/template', [
            WilayahController::class,
            'downloadTemplate'
        ])->name('wilayah.import.template');

        // Preview dan validasi file Excel
        Route::post('/wilayah/import/preview', [
            WilayahImportController::class,
            'preview'
        ])->name('wilayah.import.preview.store');

        // Halaman Preview Import
        Route::get('/wilayah/import/preview', [
            WilayahImportController::class,
            'showPreview'
        ])->name('wilayah.import.preview');

        // Konfirmasi dan simpan import
        Route::post('/wilayah/import/confirm', [
            WilayahImportController::class,
            'confirm'
        ])->name('wilayah.import.confirm');

        // Resource Wilayah
        Route::resource('wilayah', WilayahController::class)
            ->except([
                'show',
                'destroy'
            ]);

        // Aktifkan/nonaktifkan wilayah
        Route::patch('/wilayah/{wilayah}/toggle-status', [
            WilayahController::class,
            'toggleStatus'
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

        // Dashboard Superadmin
        Route::get('/dashboard', [
            SuperadminDashboardController::class,
            'index'
        ])->name('dashboard');

        // Daftar User
        Route::get('/users', [
            UserController::class,
            'index'
        ])->name('users.index');

        // Form Tambah User
        Route::get('/users/create', [
            UserController::class,
            'create'
        ])->name('users.create');

        // Simpan User Baru
        Route::post('/users', [
            UserController::class,
            'store'
        ])->name('users.store');

        // Download Template Excel
        Route::get('/users/import/template', [
            UserImportController::class,
            'downloadTemplate'
        ])->name('users.import.template');

        // Preview dan validasi import
        Route::post('/users/import/preview', [
            UserImportController::class,
            'preview'
        ])->name('users.import.preview');

        // Konfirmasi import
        Route::post('/users/import/confirm', [
            UserImportController::class,
            'confirm'
        ])->name('users.import.confirm');

        // Detail User
        Route::get('/users/{user}', [
            UserController::class,
            'show'
        ])->name('users.show');

        // Form Edit User
        Route::get('/users/{user}/edit', [
            UserController::class,
            'edit'
        ])->name('users.edit');

        // Update User
        Route::put('/users/{user}', [
            UserController::class,
            'update'
        ])->name('users.update');

        // Aktifkan/nonaktifkan User
        Route::patch('/users/{user}/toggle-status', [
            UserController::class,
            'toggleStatus'
        ])->name('users.toggle-status');

    });
