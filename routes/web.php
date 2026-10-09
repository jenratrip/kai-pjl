
<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth.login');
});

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::prefix('user')->group(function () {
    Route::get('/dashboard', function () {
        return view('user.dashboard');
    });

    Route::get('/realisasi-ka', function () {
        return view('user.realisasi-ka');
    });

    Route::get('/catatan-dinas', function () {
        return view('user.catatan-dinas');
    });

    Route::get('/serah-terima-dinas', function () {
        return view('user.serah-terima-dinas');
    });
});