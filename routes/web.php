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

    if (! auth()->check()) {
        return redirect()->route('login');
    }

    $user = auth()->user();


    /*
    |--------------------------------------------------------------------------
    | Superadmin
    |--------------------------------------------------------------------------
    */

    if ($user->isSuperadmin()) {
        return redirect()->route('superadmin.dashboard');
    }


    /*
    |--------------------------------------------------------------------------
    | Admin
    |--------------------------------------------------------------------------
    */

    if ($user->hasRole('admin')) {
        return redirect()->route('admin.dashboard');
    }


    /*
    |--------------------------------------------------------------------------
    | PJL
    |--------------------------------------------------------------------------
    |
    | Dashboard PJL belum dibuat.
    | Untuk sementara kembali ke login.
    |
    */

    return redirect()->route('login');
});


/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Login Page
    |--------------------------------------------------------------------------
    */

    Route::get('/login', [
        LoginController::class,
        'showLoginForm'
    ])->name('login');


    /*
    |--------------------------------------------------------------------------
    | Login Process
    |--------------------------------------------------------------------------
    */

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
])
    ->middleware('auth')
    ->name('logout');


/*
|--------------------------------------------------------------------------
| Admin & Superadmin - Operational Management
|--------------------------------------------------------------------------
|
| Superadmin memiliki seluruh akses Admin.
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
            'index'
        ])->name('dashboard');


        /*
        |--------------------------------------------------------------------------
        | Master Data JPL
        |--------------------------------------------------------------------------
        */

        Route::resource('jpl', JplController::class);


        /*
        |--------------------------------------------------------------------------
        | Master Data Wilayah
        |--------------------------------------------------------------------------
        |
        | Digunakan untuk mengelola data wilayah/resort hierarchy.
        |
        */


        /*
        |--------------------------------------------------------------------------
        | Download Template Import Wilayah
        |--------------------------------------------------------------------------
        */

        Route::get('/wilayah/import/template', [
            WilayahController::class,
            'downloadTemplate'
        ])->name('wilayah.import.template');


        /*
        |--------------------------------------------------------------------------
        | Upload & Preview Import Wilayah
        |--------------------------------------------------------------------------
        |
        | POST digunakan untuk membaca file Excel,
        | melakukan parsing, kemudian melakukan validasi
        | sebelum data disimpan ke database.
        |
        */

        Route::post('/wilayah/import/preview', [
            WilayahImportController::class,
            'preview'
        ])->name('wilayah.import.preview.store');


        /*
        |--------------------------------------------------------------------------
        | Halaman Preview Import Wilayah
        |--------------------------------------------------------------------------
        |
        | Menampilkan hasil validasi file Excel.
        |
        */

        Route::get('/wilayah/import/preview', [
            WilayahImportController::class,
            'showPreview'
        ])->name('wilayah.import.preview');


        /*
        |--------------------------------------------------------------------------
        | Confirm Import Wilayah
        |--------------------------------------------------------------------------
        |
        | Data baru akan disimpan ke database setelah Admin
        | menekan tombol "Konfirmasi & Simpan".
        |
        */

        Route::post('/wilayah/import/confirm', [
            WilayahImportController::class,
            'confirm'
        ])->name('wilayah.import.confirm');


        /*
        |--------------------------------------------------------------------------
        | Resource Wilayah
        |--------------------------------------------------------------------------
        |
        | show dan destroy tidak digunakan.
        |
        */

        Route::resource('wilayah', WilayahController::class)
            ->except([
                'show',
                'destroy'
            ]);


        /*
        |--------------------------------------------------------------------------
        | Toggle Status Wilayah
        |--------------------------------------------------------------------------
        |
        | Mengaktifkan / menonaktifkan wilayah tanpa menghapus data.
        |
        */

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


        /*
        |--------------------------------------------------------------------------
        | Dashboard
        |--------------------------------------------------------------------------
        */

        Route::get('/dashboard', [
            SuperadminDashboardController::class,
            'index'
        ])->name('dashboard');


        /*
        |--------------------------------------------------------------------------
        | Manajemen User
        |--------------------------------------------------------------------------
        */


        /*
        |--------------------------------------------------------------------------
        | List User
        |--------------------------------------------------------------------------
        */

        Route::get('/users', [
            UserController::class,
            'index'
        ])->name('users.index');


        /*
        |--------------------------------------------------------------------------
        | Form Tambah User
        |--------------------------------------------------------------------------
        */

        Route::get('/users/create', [
            UserController::class,
            'create'
        ])->name('users.create');


        /*
        |--------------------------------------------------------------------------
        | Simpan User Baru
        |--------------------------------------------------------------------------
        */

        Route::post('/users', [
            UserController::class,
            'store'
        ])->name('users.store');


        /*
        |--------------------------------------------------------------------------
        | Import User via Excel
        |--------------------------------------------------------------------------
        |
        | Semua route import diletakkan sebelum
        | route /users/{user}.
        |
        */


        /*
        |--------------------------------------------------------------------------
        | Download Template Excel
        |--------------------------------------------------------------------------
        */

        Route::get('/users/import/template', [
            UserImportController::class,
            'downloadTemplate'
        ])->name('users.import.template');


        /*
        |--------------------------------------------------------------------------
        | Preview dan Validasi File Excel
        |--------------------------------------------------------------------------
        */

        Route::post('/users/import/preview', [
            UserImportController::class,
            'preview'
        ])->name('users.import.preview');


        /*
        |--------------------------------------------------------------------------
        | Confirm dan Proses Import Excel
        |--------------------------------------------------------------------------
        */

        Route::post('/users/import/confirm', [
            UserImportController::class,
            'confirm'
        ])->name('users.import.confirm');


        /*
        |--------------------------------------------------------------------------
        | Detail User
        |--------------------------------------------------------------------------
        */

        Route::get('/users/{user}', [
            UserController::class,
            'show'
        ])->name('users.show');


        /*
        |--------------------------------------------------------------------------
        | Form Edit User
        |--------------------------------------------------------------------------
        */

        Route::get('/users/{user}/edit', [
            UserController::class,
            'edit'
        ])->name('users.edit');


        /*
        |--------------------------------------------------------------------------
        | Update User
        |--------------------------------------------------------------------------
        */

        Route::put('/users/{user}', [
            UserController::class,
            'update'
        ])->name('users.update');


        /*
        |--------------------------------------------------------------------------
        | Toggle Status User
        |--------------------------------------------------------------------------
        */

        Route::patch('/users/{user}/toggle-status', [
            UserController::class,
            'toggleStatus'
        ])->name('users.toggle-status');

    });