<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Sinkronisasi Status Jadwal Dinas
|--------------------------------------------------------------------------
|
| Setiap menit:
| - Jadwal dijadwalkan menjadi berlangsung saat jam mulai tercapai.
| - Jadwal berlangsung menjadi selesai saat jam selesai tercapai.
|
*/

Artisan::command('dinas:sinkronkan-status', function () {
    $now = now();

    // Selesaikan jadwal yang waktunya sudah berakhir.
    $selesai = DB::table('dinas')
        ->whereIn('status', ['dijadwalkan', 'berlangsung'])
        ->whereNotNull('jam_selesai')
        ->where('jam_selesai', '<=', $now)
        ->update([
            'status' => 'selesai',
            'updated_at' => $now,
        ]);

    // Aktifkan jadwal yang sedang berlangsung.
    $berlangsung = DB::table('dinas')
        ->where('status', 'dijadwalkan')
        ->whereNotNull('jam_mulai')
        ->whereNotNull('jam_selesai')
        ->where('jam_mulai', '<=', $now)
        ->where('jam_selesai', '>', $now)
        ->update([
            'status' => 'berlangsung',
            'updated_at' => $now,
        ]);

    $this->info("Jadwal menjadi berlangsung: {$berlangsung}");
    $this->info("Jadwal menjadi selesai: {$selesai}");
})->purpose('Sinkronisasi otomatis status jadwal dinas');

Schedule::command('dinas:sinkronkan-status')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('jadwal-ka:activate-due-batches')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('app:prune-expired-coaching-message-nonces')
    ->dailyAt('02:30')
    ->withoutOverlapping();
