<?php

namespace Tests\Support;

use App\Models\Dinas;
use App\Models\JadwalKa;
use App\Models\JadwalKaBatch;
use App\Models\Jpl;
use App\Models\KeretaApi;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Support\Str;

trait KaiTestRecords
{
    protected function kaiRole(string $name): Role
    {
        return Role::query()->create(['name' => $name, 'label' => ucfirst($name)]);
    }

    protected function kaiUser(Role $role, string $nip, array $attributes = []): User
    {
        return User::query()->create(array_merge([
            'nip' => $nip,
            'name' => 'Petugas '.$nip,
            'email' => null,
            'phone' => null,
            'role_id' => $role->id,
            'password' => 'password123',
            'is_active' => true,
        ], $attributes));
    }

    protected function kaiJpl(string $kode = 'JPL-01'): Jpl
    {
        return Jpl::query()->create([
            'kode_jpl' => $kode,
            'nama_jpl' => 'JPL Pengujian '.$kode,
            'is_active' => true,
        ]);
    }

    protected function kaiShift(string $start = '00:00:00', string $end = '23:59:59'): Shift
    {
        return Shift::query()->create([
            'nama_shift' => 'Shift Pengujian',
            'jam_mulai' => $start,
            'jam_selesai' => $end,
        ]);
    }

    protected function kaiTrain(string $number = 'KA-001'): KeretaApi
    {
        return KeretaApi::query()->create([
            'nomor_ka' => $number,
            'nama_ka' => 'Kereta '.$number,
        ]);
    }

    protected function kaiSchedule(Jpl $jpl, KeretaApi $train, array $attributes = []): JadwalKa
    {
        return JadwalKa::query()->create(array_merge([
            'kereta_api_id' => $train->id,
            'dari' => 'Stasiun A',
            'ke' => 'Stasiun B',
            'jpl_id' => $jpl->id,
            'jam_lewat' => '10:00:00',
            'hari_berlaku' => null,
            'tanggal_berlaku' => null,
            'is_active' => true,
        ], $attributes));
    }

    protected function kaiBatch(string $status, ?string $effectiveDate): JadwalKaBatch
    {
        return JadwalKaBatch::query()->create([
            'uuid' => (string) Str::uuid(),
            'status' => $status,
            'tanggal_mulai_berlaku' => $effectiveDate,
        ]);
    }

    protected function kaiDinas(User $user, Jpl $jpl, Shift $shift, array $attributes = []): Dinas
    {
        return Dinas::query()->create(array_merge([
            'user_id' => $user->id,
            'jpl_id' => $jpl->id,
            'shift_id' => $shift->id,
            'tanggal_dinas' => now()->toDateString(),
            'jam_mulai' => now(),
            'jam_selesai' => now()->addHours(8),
            'status' => 'berlangsung',
            'is_pengganti' => false,
        ], $attributes));
    }
}
