<?php

use App\Models\Dinas;
use App\Models\RealisasiKa;
use App\Models\Shift;
use Tests\Support\CreatesKaiInMemorySchema;
use Tests\Support\KaiTestRecords;

uses(CreatesKaiInMemorySchema::class, KaiTestRecords::class);

beforeEach(function () {
    $this->createKaiInMemorySchema();
});

it('updates shift hours without changing shift IDs or stored Dinas history', function () {
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-SHIFT-UPDATE');
    $pjlRole = $this->kaiRole('pjl');
    $jpl = $this->kaiJpl('JPL-SHIFT-UPDATE');

    $shiftChanges = [
        1 => ['Pagi', '07:00:00', '15:00:00', '06:00', '14:00'],
        2 => ['Siang', '15:00:00', '23:00:00', '14:00', '22:00'],
        3 => ['Malam', '23:00:00', '07:00:00', '22:00', '06:00'],
    ];

    $storedRecords = [];

    foreach ($shiftChanges as $shiftId => [$name, $oldStart, $oldEnd, $newStart, $newEnd]) {
        $shift = Shift::query()->create([
            'id' => $shiftId,
            'nama_shift' => $name,
            'jam_mulai' => $oldStart,
            'jam_selesai' => $oldEnd,
        ]);
        $pjl = $this->kaiUser($pjlRole, 'PJL-SHIFT-'.$shiftId);
        $snapshot = [
            'format_version' => 1,
            'shift_id' => $shiftId,
            'jadwal' => [['jadwal_ka_id' => 100 + $shiftId]],
        ];
        $startDateTime = $shiftId === 3
            ? '2026-10-01 23:00:00'
            : '2026-10-01 '.$oldStart;
        $endDateTime = $shiftId === 3
            ? '2026-10-02 07:00:00'
            : '2026-10-01 '.$oldEnd;

        $dinas = Dinas::query()->create([
            'user_id' => $pjl->id,
            'jpl_id' => $jpl->id,
            'shift_id' => $shift->id,
            'tanggal_dinas' => '2026-10-01',
            'jam_mulai' => $startDateTime,
            'jam_selesai' => $endDateTime,
            'status' => 'selesai',
            'jadwal_ka_snapshot' => $snapshot,
        ]);
        $realisasi = RealisasiKa::query()->create([
            'dinas_id' => $dinas->id,
            'status_lewat' => 'jalan',
            'jam_aktual' => '10:05:00',
            'status_s21' => 'ya',
            'status_genta' => 'ya',
            'status_pintu_perlintasan' => 'ya',
            'status_s35' => 'ya',
        ]);

        $storedRecords[$shiftId] = [
            'dinas' => $dinas,
            'realisasi' => $realisasi,
            'snapshot' => $snapshot,
            'start' => $startDateTime,
            'end' => $endDateTime,
            'new_start' => $newStart,
            'new_end' => $newEnd,
        ];
    }

    foreach ($shiftChanges as $shiftId => [$name, , , $newStart, $newEnd]) {
        $this->actingAs($admin)
            ->put(route('admin.shift.update', $shiftId), [
                'nama_shift' => $name,
                'jam_mulai' => $newStart,
                'jam_selesai' => $newEnd,
            ])
            ->assertRedirect(route('admin.shift.index'));
    }

    foreach ($storedRecords as $shiftId => $records) {
        $this->assertDatabaseHas('shifts', [
            'id' => $shiftId,
            'nama_shift' => $shiftChanges[$shiftId][0],
            'jam_mulai' => $records['new_start'],
            'jam_selesai' => $records['new_end'],
        ]);

        expect($records['dinas']->fresh()->jam_mulai->toDateTimeString())
            ->toBe($records['start'])
            ->and($records['dinas']->fresh()->jam_selesai->toDateTimeString())
            ->toBe($records['end'])
            ->and($records['dinas']->fresh()->jadwal_ka_snapshot)
            ->toBe($records['snapshot'])
            ->and($records['realisasi']->fresh()->status_lewat)
            ->toBe('jalan')
            ->and($records['realisasi']->fresh()->jam_aktual)
            ->toBe('10:05:00');
    }

    expect(Shift::query()->orderBy('id')->pluck('id')->all())->toBe([1, 2, 3]);
});

it('renders shift editing controls for Admin and explains overnight shifts', function () {
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-SHIFT-PAGE');
    $this->kaiShift('23:00:00', '07:00:00');

    $this->withoutVite()
        ->actingAs($admin)
        ->get(route('admin.shift.index'))
        ->assertOk()
        ->assertSeeText('Manajemen Shift')
        ->assertSeeText('Edit')
        ->assertSeeText('Shift lintas tengah malam didukung');
});
