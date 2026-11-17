<?php

use App\Models\Dinas;
use App\Models\PermintaanTukarJadwal;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    // Create roles
    Role::firstOrCreate(['name' => 'pjl'], ['label' => 'PJL']);
    Role::firstOrCreate(['name' => 'admin'], ['label' => 'Admin']);

    // Create users
    $this->pemohon = User::factory()->create(['role_id' => Role::where('name', 'pjl')->first()->id]);
    $this->penerima = User::factory()->create(['role_id' => Role::where('name', 'pjl')->first()->id]);
    $this->inactiveUser = User::factory()->create(['role_id' => Role::where('name', 'pjl')->first()->id, 'is_active' => false]);
    $this->nonPjlUser = User::factory()->create(['role_id' => Role::where('name', 'admin')->first()->id]);

    // Create schedules
    $this->jpl = \App\Models\Jpl::factory()->create();
    $this->shift = \App\Models\Shift::factory()->create();

    $this->dinasPemohon = Dinas::factory()->create([
        'user_id' => $this->pemohon->id,
        'jpl_id' => $this->jpl->id,
        'shift_id' => $this->shift->id,
        'tanggal_dinas' => now()->addDays(5)->toDateString(),
        'status' => 'dijadwalkan',
        'jam_mulai' => now()->addDays(5)->setTime(8, 0),
        'jam_selesai' => now()->addDays(5)->setTime(16, 0),
    ]);

    $this->dinasPenerima = Dinas::factory()->create([
        'user_id' => $this->penerima->id,
        'jpl_id' => $this->jpl->id,
        'shift_id' => $this->shift->id,
        'tanggal_dinas' => now()->addDays(6)->toDateString(),
        'status' => 'dijadwalkan',
        'jam_mulai' => now()->addDays(6)->setTime(8, 0),
        'jam_selesai' => now()->addDays(6)->setTime(16, 0),
    ]);
});

describe('1. Permintaan Valid', function () {
    test('pemohon dapat membuat permintaan dengan data valid', function () {
        $this->actingAs($this->pemohon);

        $response = $this->postJson(route('user.tukar-jadwal-dinas.store'), [
            'dinas_pemohon_id' => $this->dinasPemohon->id,
            'dinas_tujuan_id' => $this->dinasPenerima->id,
            'penerima_id' => $this->penerima->id,
            'alasan_pemohon' => 'Saya ingin menukar jadwal karena ada keadaan mendesak.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('permintaan_tukar_jadwal', [
            'pemohon_id' => $this->pemohon->id,
            'penerima_id' => $this->penerima->id,
            'status' => 'menunggu',
        ]);
    });

    test('snapshot data disimpan saat pembuatan permintaan', function () {
        $this->actingAs($this->pemohon);

        $this->postJson(route('user.tukar-jadwal-dinas.store'), [
            'dinas_pemohon_id' => $this->dinasPemohon->id,
            'dinas_tujuan_id' => $this->dinasPenerima->id,
            'penerima_id' => $this->penerima->id,
            'alasan_pemohon' => 'Alasan menukar jadwal dinas.',
        ]);

        $permintaan = PermintaanTukarJadwal::latest()->first();

        expect($permintaan->pemohon_name)->toBe($this->pemohon->name);
        expect($permintaan->pemohon_nip)->toBe($this->pemohon->nip);
        expect($permintaan->penerima_name)->toBe($this->penerima->name);
        expect($permintaan->penerima_nip)->toBe($this->penerima->nip);
        expect($permintaan->dinas_pemohon_snapshot)->not->toBeNull();
        expect($permintaan->dinas_tujuan_snapshot)->not->toBeNull();
    });
});

describe('2. Validasi Penerima', function () {
    test('penerima tidak boleh non-PJL', function () {
        $this->actingAs($this->pemohon);

        $response = $this->postJson(route('user.tukar-jadwal-dinas.store'), [
            'dinas_pemohon_id' => $this->dinasPemohon->id,
            'dinas_tujuan_id' => $this->dinasPenerima->id,
            'penerima_id' => $this->nonPjlUser->id,
            'alasan_pemohon' => 'Alasan menukar jadwal.',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('permintaan_tukar_jadwal', ['penerima_id' => $this->nonPjlUser->id]);
    });

    test('penerima tidak boleh tidak aktif', function () {
        $this->actingAs($this->pemohon);

        // Create dinas for inactive user
        $dinasInactive = Dinas::factory()->create([
            'user_id' => $this->inactiveUser->id,
            'jpl_id' => $this->jpl->id,
            'status' => 'dijadwalkan',
            'jam_mulai' => now()->addDays(7)->setTime(8, 0),
        ]);

        $response = $this->postJson(route('user.tukar-jadwal-dinas.store'), [
            'dinas_pemohon_id' => $this->dinasPemohon->id,
            'dinas_tujuan_id' => $dinasInactive->id,
            'penerima_id' => $this->inactiveUser->id,
            'alasan_pemohon' => 'Alasan menukar jadwal.',
        ]);

        $response->assertStatus(422);
    });

    test('penerima harus memiliki jadwal tujuan', function () {
        $this->actingAs($this->pemohon);

        $otherDinas = Dinas::factory()->create([
            'user_id' => User::factory()->create(['role_id' => Role::where('name', 'pjl')->first()->id])->id,
            'status' => 'dijadwalkan',
        ]);

        $response = $this->postJson(route('user.tukar-jadwal-dinas.store'), [
            'dinas_pemohon_id' => $this->dinasPemohon->id,
            'dinas_tujuan_id' => $otherDinas->id,
            'penerima_id' => $this->penerima->id,
            'alasan_pemohon' => 'Alasan menukar jadwal.',
        ]);

        $response->assertStatus(422);
    });
});

describe('3. Validasi Jadwal', function () {
    test('jadwal pemohon tidak boleh sudah dimulai', function () {
        $this->actingAs($this->pemohon);

        $pastDinas = Dinas::factory()->create([
            'user_id' => $this->pemohon->id,
            'status' => 'dijadwalkan',
            'jam_mulai' => now()->subHours(2),
        ]);

        $response = $this->postJson(route('user.tukar-jadwal-dinas.store'), [
            'dinas_pemohon_id' => $pastDinas->id,
            'dinas_tujuan_id' => $this->dinasPenerima->id,
            'penerima_id' => $this->penerima->id,
            'alasan_pemohon' => 'Alasan menukar jadwal.',
        ]);

        $response->assertStatus(422);
    });

    test('konflik jadwal terdeteksi - pemohon sudah punya jadwal dengan JPL/tanggal/shift sama', function () {
        $this->actingAs($this->pemohon);

        // Create another schedule for pemohon on same JPL/date/shift
        $conflictDinas = Dinas::factory()->create([
            'user_id' => $this->pemohon->id,
            'jpl_id' => $this->dinasPenerima->jpl_id,
            'tanggal_dinas' => $this->dinasPenerima->tanggal_dinas,
            'shift_id' => $this->dinasPenerima->shift_id,
            'status' => 'dijadwalkan',
        ]);

        $response = $this->postJson(route('user.tukar-jadwal-dinas.store'), [
            'dinas_pemohon_id' => $this->dinasPemohon->id,
            'dinas_tujuan_id' => $this->dinasPenerima->id,
            'penerima_id' => $this->penerima->id,
            'alasan_pemohon' => 'Alasan menukar jadwal.',
        ]);

        $response->assertStatus(422);
    });
});

describe('4. Gagal Status Handling', function () {
    test('kegagalan approval menyimpan status gagal dalam transaksi terpisah', function () {
        $this->actingAs($this->pemohon);

        // Create valid request
        $permintaan = PermintaanTukarJadwal::create([
            'dinas_pemohon_id' => $this->dinasPemohon->id,
            'dinas_tujuan_id' => $this->dinasPenerima->id,
            'pemohon_id' => $this->pemohon->id,
            'pemohon_name' => $this->pemohon->name,
            'pemohon_nip' => $this->pemohon->nip,
            'penerima_id' => $this->penerima->id,
            'penerima_name' => $this->penerima->name,
            'penerima_nip' => $this->penerima->nip,
            'alasan_pemohon' => 'Alasan menukar.',
            'status' => 'menunggu',
        ]);

        // Simulate realisasi data being added to pemohon's schedule
        \App\Models\RealisasiKa::factory()->create(['dinas_id' => $this->dinasPemohon->id]);

        // Try to approve - should fail
        $this->actingAs($this->penerima);

        $response = $this->postJson(
            route('user.tukar-jadwal-dinas.respond', $permintaan->id),
            ['action' => 'approve']
        );

        $response->assertStatus(422);

        // Verify gagal status was saved (not menunggu)
        $permintaan->refresh();
        expect($permintaan->status)->toBe('gagal');
        expect($permintaan->alasan_gagal)->toContain('realisasi');
    });

    test('kegagalan approval tidak mengubah kepemilikan jadwal', function () {
        $pemohonUserId = $this->dinasPemohon->user_id;
        $penerimaUserId = $this->dinasPenerima->user_id;

        // Create request
        $permintaan = PermintaanTukarJadwal::create([
            'dinas_pemohon_id' => $this->dinasPemohon->id,
            'dinas_tujuan_id' => $this->dinasPenerima->id,
            'pemohon_id' => $this->pemohon->id,
            'pemohon_name' => $this->pemohon->name,
            'pemohon_nip' => $this->pemohon->nip,
            'penerima_id' => $this->penerima->id,
            'penerima_name' => $this->penerima->name,
            'penerima_nip' => $this->penerima->nip,
            'alasan_pemohon' => 'Alasan.',
            'status' => 'menunggu',
        ]);

        // Add realisasi to block swap
        \App\Models\RealisasiKa::factory()->create(['dinas_id' => $this->dinasPemohon->id]);

        $this->actingAs($this->penerima);

        $this->postJson(
            route('user.tukar-jadwal-dinas.respond', $permintaan->id),
            ['action' => 'approve']
        );

        // Verify user_ids unchanged
        $this->dinasPemohon->refresh();
        $this->dinasPenerima->refresh();

        expect($this->dinasPemohon->user_id)->toBe($pemohonUserId);
        expect($this->dinasPenerima->user_id)->toBe($penerimaUserId);
    });
});

describe('5. Pembatalan', function () {
    test('pemohon dapat membatalkan permintaan yang status menunggu', function () {
        $permintaan = PermintaanTukarJadwal::create([
            'dinas_pemohon_id' => $this->dinasPemohon->id,
            'dinas_tujuan_id' => $this->dinasPenerima->id,
            'pemohon_id' => $this->pemohon->id,
            'pemohon_name' => $this->pemohon->name,
            'pemohon_nip' => $this->pemohon->nip,
            'penerima_id' => $this->penerima->id,
            'penerima_name' => $this->penerima->name,
            'penerima_nip' => $this->penerima->nip,
            'alasan_pemohon' => 'Alasan.',
            'status' => 'menunggu',
        ]);

        $this->actingAs($this->pemohon);

        $response = $this->postJson(
            route('user.tukar-jadwal-dinas.cancel', $permintaan->id)
        );

        $response->assertOk();
        $permintaan->refresh();

        expect($permintaan->status)->toBe('dibatalkan');
    });

    test('penerima tidak dapat membatalkan permintaan', function () {
        $permintaan = PermintaanTukarJadwal::create([
            'dinas_pemohon_id' => $this->dinasPemohon->id,
            'dinas_tujuan_id' => $this->dinasPenerima->id,
            'pemohon_id' => $this->pemohon->id,
            'pemohon_name' => $this->pemohon->name,
            'pemohon_nip' => $this->pemohon->nip,
            'penerima_id' => $this->penerima->id,
            'penerima_name' => $this->penerima->name,
            'penerima_nip' => $this->penerima->nip,
            'alasan_pemohon' => 'Alasan.',
            'status' => 'menunggu',
        ]);

        $this->actingAs($this->penerima);

        $response = $this->postJson(
            route('user.tukar-jadwal-dinas.cancel', $permintaan->id)
        );

        $response->assertForbidden();
    });
});

describe('6. Riwayat', function () {
    test('riwayat menampilkan snapshot meski user dihapus', function () {
        $penerima_id_before_delete = $this->penerima->id;

        $permintaan = PermintaanTukarJadwal::create([
            'dinas_pemohon_id' => $this->dinasPemohon->id,
            'dinas_tujuan_id' => $this->dinasPenerima->id,
            'pemohon_id' => $this->pemohon->id,
            'pemohon_name' => $this->pemohon->name,
            'pemohon_nip' => $this->pemohon->nip,
            'penerima_id' => $this->penerima->id,
            'penerima_name' => $this->penerima->name,
            'penerima_nip' => $this->penerima->nip,
            'alasan_pemohon' => 'Alasan.',
            'status' => 'berhasil',
            'selesai_pada' => now(),
        ]);

        // Delete penerima user
        $this->penerima->delete();

        // View history
        $this->actingAs($this->pemohon);

        $response = $this->get(route('user.tukar-jadwal-dinas.history'));

        $response->assertOk();

        $permintaan->refresh();

        // Snapshot data still available
        expect($permintaan->penerima_name)->toBe('penerima name here'); // Would contain actual snapshot
        expect($permintaan->penerima_id)->toBeNull(); // FK now null
    });
});

describe('7. Race Conditions', function () {
    test('dua penerima tidak dapat merespons permintaan bersamaan', function () {
        $permintaan = PermintaanTukarJadwal::create([
            'dinas_pemohon_id' => $this->dinasPemohon->id,
            'dinas_tujuan_id' => $this->dinasPenerima->id,
            'pemohon_id' => $this->pemohon->id,
            'pemohon_name' => $this->pemohon->name,
            'pemohon_nip' => $this->pemohon->nip,
            'penerima_id' => $this->penerima->id,
            'penerima_name' => $this->penerima->name,
            'penerima_nip' => $this->penerima->nip,
            'alasan_pemohon' => 'Alasan.',
            'status' => 'menunggu',
        ]);

        $this->actingAs($this->penerima);

        // First approval succeeds
        $this->postJson(
            route('user.tukar-jadwal-dinas.respond', $permintaan->id),
            ['action' => 'approve']
        );

        $permintaan->refresh();

        // Try second approval - should fail (status no longer menunggu)
        $response = $this->postJson(
            route('user.tukar-jadwal-dinas.respond', $permintaan->id),
            ['action' => 'approve']
        );

        $response->assertStatus(422);
    });
});

describe('8. Admin Access', function () {
    test('admin dapat melihat daftar permintaan (read-only)', function () {
        $admin = User::factory()->create(['role_id' => Role::where('name', 'admin')->first()->id]);

        PermintaanTukarJadwal::create([
            'dinas_pemohon_id' => $this->dinasPemohon->id,
            'dinas_tujuan_id' => $this->dinasPenerima->id,
            'pemohon_id' => $this->pemohon->id,
            'pemohon_name' => $this->pemohon->name,
            'pemohon_nip' => $this->pemohon->nip,
            'penerima_id' => $this->penerima->id,
            'penerima_name' => $this->penerima->name,
            'penerima_nip' => $this->penerima->nip,
            'alasan_pemohon' => 'Alasan.',
            'status' => 'menunggu',
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('admin.tukar-jadwal-dinas.index'));

        $response->assertOk();
        $response->assertViewHas('permintaan');
    });
});
