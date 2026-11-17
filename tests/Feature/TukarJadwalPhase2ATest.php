<?php

use App\Models\Dinas;
use App\Models\PermintaanTukarJadwal;
use App\Models\RealisasiInventaris;
use App\Models\RealisasiKa;
use App\Models\Role;
use App\Models\SerahTerima;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Tukar Jadwal - Phase 2A Corrections', function () {
    beforeEach(function () {
        $this->pjlRole = Role::firstOrCreate(['name' => 'pjl'], ['label' => 'PJL']);
        $this->adminRole = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Admin']);

        $this->pemohon = User::factory()->create(['role_id' => $this->pjlRole->id]);
        $this->penerima = User::factory()->create(['role_id' => $this->pjlRole->id]);
        $this->inactiveUser = User::factory()->create(['role_id' => $this->pjlRole->id, 'is_active' => false]);

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

    test('1. Valid swap request creation', function () {
        $this->actingAs($this->pemohon);

        $response = $this->postJson(route('user.tukar-jadwal-dinas.store'), [
            'dinas_pemohon_id' => $this->dinasPemohon->id,
            'dinas_tujuan_id' => $this->dinasPenerima->id,
            'penerima_id' => $this->penerima->id,
            'alasan_pemohon' => 'Saya ingin menukar jadwal karena ada acara mendadak.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('permintaan_tukar_jadwal', [
            'pemohon_id' => $this->pemohon->id,
            'status' => 'menunggu',
        ]);
    });

    test('2. Snapshot fields are populated correctly', function () {
        $this->actingAs($this->pemohon);

        $this->postJson(route('user.tukar-jadwal-dinas.store'), [
            'dinas_pemohon_id' => $this->dinasPemohon->id,
            'dinas_tujuan_id' => $this->dinasPenerima->id,
            'penerima_id' => $this->penerima->id,
            'alasan_pemohon' => 'Permintaan tukar jadwal.',
        ]);

        $permintaan = PermintaanTukarJadwal::latest()->first();

        expect($permintaan->pemohon_name)->toBe($this->pemohon->name);
        expect($permintaan->pemohon_nip)->toBe($this->pemohon->nip);
        expect($permintaan->penerima_name)->toBe($this->penerima->name);
        expect($permintaan->penerima_nip)->toBe($this->penerima->nip);

        // Check snapshot contains JPL and Resort info
        $snapPemohon = $permintaan->dinas_pemohon_snapshot;
        expect($snapPemohon['jpl']['nama_jpl'])->toBe($this->jpl->nama_jpl);
        expect($snapPemohon['shift']['nama_shift'])->toBe($this->shift->nama_shift);
        expect($snapPemohon['tanggal_dinas'])->toBe($this->dinasPemohon->tanggal_dinas->toDateString());
        expect($snapPemohon['jam_mulai'])->not->toBeNull();
    });

    test('3. Jadwal bukan milik pemohon - rejected', function () {
        $otherUser = User::factory()->create(['role_id' => $this->pjlRole->id]);
        $this->actingAs($otherUser);

        $response = $this->postJson(route('user.tukar-jadwal-dinas.store'), [
            'dinas_pemohon_id' => $this->dinasPemohon->id,
            'dinas_tujuan_id' => $this->dinasPenerima->id,
            'penerima_id' => $this->penerima->id,
            'alasan_pemohon' => 'Permintaan tukar jadwal.',
        ]);

        $response->assertStatus(403);
    });

    test('4. Penerima bukan PJL aktif - rejected', function () {
        $this->actingAs($this->pemohon);

        $inactiveDinas = Dinas::factory()->create([
            'user_id' => $this->inactiveUser->id,
            'status' => 'dijadwalkan',
            'jam_mulai' => now()->addDays(7)->setTime(8, 0),
        ]);

        $response = $this->postJson(route('user.tukar-jadwal-dinas.store'), [
            'dinas_pemohon_id' => $this->dinasPemohon->id,
            'dinas_tujuan_id' => $inactiveDinas->id,
            'penerima_id' => $this->inactiveUser->id,
            'alasan_pemohon' => 'Permintaan tukar jadwal.',
        ]);

        $response->assertStatus(422);
    });

    test('5. Jadwal sudah dimulai - rejected at creation', function () {
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
            'alasan_pemohon' => 'Permintaan tukar jadwal.',
        ]);

        $response->assertStatus(422);
    });

    test('6. Jadwal has realisasi KA - rejected at approval', function () {
        $this->actingAs($this->pemohon);

        $permintaan = PermintaanTukarJadwal::create([
            'dinas_pemohon_id' => $this->dinasPemohon->id,
            'dinas_tujuan_id' => $this->dinasPenerima->id,
            'pemohon_id' => $this->pemohon->id,
            'pemohon_name' => $this->pemohon->name,
            'pemohon_nip' => $this->pemohon->nip,
            'penerima_id' => $this->penerima->id,
            'penerima_name' => $this->penerima->name,
            'penerima_nip' => $this->penerima->nip,
            'alasan_pemohon' => 'Permintaan tukar jadwal.',
            'status' => 'menunggu',
        ]);

        // Add realisasi KA to block swap
        RealisasiKa::factory()->create(['dinas_id' => $this->dinasPemohon->id]);

        $this->actingAs($this->penerima);

        $response = $this->postJson(route('user.tukar-jadwal-dinas.respond', $permintaan->id), [
            'action' => 'approve',
        ]);

        $response->assertStatus(422);

        // Verify gagal status is recorded
        $permintaan->refresh();
        expect($permintaan->status)->toBe('gagal');
    });

    test('7. Jadwal locked by serah terima - rejected at approval', function () {
        $this->actingAs($this->pemohon);

        $permintaan = PermintaanTukarJadwal::create([
            'dinas_pemohon_id' => $this->dinasPemohon->id,
            'dinas_tujuan_id' => $this->dinasPenerima->id,
            'pemohon_id' => $this->pemohon->id,
            'pemohon_name' => $this->pemohon->name,
            'pemohon_nip' => $this->pemohon->nip,
            'penerima_id' => $this->penerima->id,
            'penerima_name' => $this->penerima->name,
            'penerima_nip' => $this->penerima->nip,
            'alasan_pemohon' => 'Permintaan tukar jadwal.',
            'status' => 'menunggu',
        ]);

        // Lock with approved serah terima
        SerahTerima::factory()->create([
            'dinas_id' => $this->dinasPemohon->id,
            'status_review' => 'disetujui',
        ]);

        $this->actingAs($this->penerima);

        $response = $this->postJson(route('user.tukar-jadwal-dinas.respond', $permintaan->id), [
            'action' => 'approve',
        ]);

        $response->assertStatus(422);
        $permintaan->refresh();
        expect($permintaan->status)->toBe('gagal');
    });

    test('8. Swap failure stores gagal status in separate transaction', function () {
        $permintaan = PermintaanTukarJadwal::create([
            'dinas_pemohon_id' => $this->dinasPemohon->id,
            'dinas_tujuan_id' => $this->dinasPenerima->id,
            'pemohon_id' => $this->pemohon->id,
            'pemohon_name' => $this->pemohon->name,
            'pemohon_nip' => $this->pemohon->nip,
            'penerima_id' => $this->penerima->id,
            'penerima_name' => $this->penerima->name,
            'penerima_nip' => $this->penerima->nip,
            'alasan_pemohon' => 'Permintaan tukar jadwal.',
            'status' => 'menunggu',
        ]);

        // Add realisasi to trigger failure
        RealisasiKa::factory()->create(['dinas_id' => $this->dinasPemohon->id]);

        $this->actingAs($this->penerima);

        $this->postJson(route('user.tukar-jadwal-dinas.respond', $permintaan->id), [
            'action' => 'approve',
        ]);

        $permintaan->refresh();
        expect($permintaan->status)->toBe('gagal');
        expect($permintaan->alasan_gagal)->not->toBeNull();
        expect($permintaan->dijawab_pada)->not->toBeNull();
        expect($permintaan->selesai_pada)->not->toBeNull();
    });

    test('9. Failure does not change user_id', function () {
        $pemohonUserId = $this->dinasPemohon->user_id;
        $penerimaUserId = $this->dinasPenerima->user_id;

        $permintaan = PermintaanTukarJadwal::create([
            'dinas_pemohon_id' => $this->dinasPemohon->id,
            'dinas_tujuan_id' => $this->dinasPenerima->id,
            'pemohon_id' => $this->pemohon->id,
            'pemohon_name' => $this->pemohon->name,
            'pemohon_nip' => $this->pemohon->nip,
            'penerima_id' => $this->penerima->id,
            'penerima_name' => $this->penerima->name,
            'penerima_nip' => $this->penerima->nip,
            'alasan_pemohon' => 'Permintaan tukar jadwal.',
            'status' => 'menunggu',
        ]);

        // Add realisasi to trigger failure
        RealisasiKa::factory()->create(['dinas_id' => $this->dinasPemohon->id]);

        $this->actingAs($this->penerima);

        $this->postJson(route('user.tukar-jadwal-dinas.respond', $permintaan->id), [
            'action' => 'approve',
        ]);

        $this->dinasPemohon->refresh();
        $this->dinasPenerima->refresh();

        expect($this->dinasPemohon->user_id)->toBe($pemohonUserId);
        expect($this->dinasPenerima->user_id)->toBe($penerimaUserId);
    });

    test('10. Successful swap changes only user_id', function () {
        $permintaan = PermintaanTukarJadwal::create([
            'dinas_pemohon_id' => $this->dinasPemohon->id,
            'dinas_tujuan_id' => $this->dinasPenerima->id,
            'pemohon_id' => $this->pemohon->id,
            'pemohon_name' => $this->pemohon->name,
            'pemohon_nip' => $this->pemohon->nip,
            'penerima_id' => $this->penerima->id,
            'penerima_name' => $this->penerima->name,
            'penerima_nip' => $this->penerima->nip,
            'alasan_pemohon' => 'Permintaan tukar jadwal.',
            'status' => 'menunggu',
        ]);

        $originalJplId = $this->dinasPemohon->jpl_id;
        $originalTanggal = $this->dinasPemohon->tanggal_dinas;

        $this->actingAs($this->penerima);

        $response = $this->postJson(route('user.tukar-jadwal-dinas.respond', $permintaan->id), [
            'action' => 'approve',
        ]);

        $response->assertOk();

        $this->dinasPemohon->refresh();
        $this->dinasPenerima->refresh();

        // user_id should be swapped
        expect($this->dinasPemohon->user_id)->toBe($this->penerima->id);
        expect($this->dinasPenerima->user_id)->toBe($this->pemohon->id);

        // Other fields must NOT change
        expect($this->dinasPemohon->jpl_id)->toBe($originalJplId);
        expect($this->dinasPemohon->tanggal_dinas)->toBe($originalTanggal);
    });

    test('11. Rejection stores alasan_penerima', function () {
        $permintaan = PermintaanTukarJadwal::create([
            'dinas_pemohon_id' => $this->dinasPemohon->id,
            'dinas_tujuan_id' => $this->dinasPenerima->id,
            'pemohon_id' => $this->pemohon->id,
            'pemohon_name' => $this->pemohon->name,
            'pemohon_nip' => $this->pemohon->nip,
            'penerima_id' => $this->penerima->id,
            'penerima_name' => $this->penerima->name,
            'penerima_nip' => $this->penerima->nip,
            'alasan_pemohon' => 'Permintaan tukar jadwal.',
            'status' => 'menunggu',
        ]);

        $this->actingAs($this->penerima);

        $this->postJson(route('user.tukar-jadwal-dinas.respond', $permintaan->id), [
            'action' => 'reject',
            'alasan_penerima' => 'Saya tidak bisa menukar jadwal pada waktu itu.',
        ]);

        $permintaan->refresh();
        expect($permintaan->status)->toBe('ditolak');
        expect($permintaan->alasan_penerima)->toBe('Saya tidak bisa menukar jadwal pada waktu itu.');
    });

    test('12. History preserved after user deletion', function () {
        $permintaan = PermintaanTukarJadwal::create([
            'dinas_pemohon_id' => $this->dinasPemohon->id,
            'dinas_tujuan_id' => $this->dinasPenerima->id,
            'pemohon_id' => $this->pemohon->id,
            'pemohon_name' => $this->pemohon->name,
            'pemohon_nip' => $this->pemohon->nip,
            'penerima_id' => $this->penerima->id,
            'penerima_name' => $this->penerima->name,
            'penerima_nip' => $this->penerima->nip,
            'alasan_pemohon' => 'Permintaan tukar jadwal.',
            'status' => 'berhasil',
            'selesai_pada' => now(),
        ]);

        // Delete penerima
        $this->penerima->delete();

        $permintaan->refresh();

        // FK should be null but snapshots preserve identity
        expect($permintaan->penerima_id)->toBeNull();
        expect($permintaan->penerima_name)->not->toBeNull();
        expect($permintaan->penerima_nip)->not->toBeNull();
    });

    test('13. Concurrent approvals prevented', function () {
        $permintaan = PermintaanTukarJadwal::create([
            'dinas_pemohon_id' => $this->dinasPemohon->id,
            'dinas_tujuan_id' => $this->dinasPenerima->id,
            'pemohon_id' => $this->pemohon->id,
            'pemohon_name' => $this->pemohon->name,
            'pemohon_nip' => $this->pemohon->nip,
            'penerima_id' => $this->penerima->id,
            'penerima_name' => $this->penerima->name,
            'penerima_nip' => $this->penerima->nip,
            'alasan_pemohon' => 'Permintaan tukar jadwal.',
            'status' => 'menunggu',
        ]);

        $this->actingAs($this->penerima);

        // First approval succeeds
        $this->postJson(route('user.tukar-jadwal-dinas.respond', $permintaan->id), [
            'action' => 'approve',
        ]);

        $permintaan->refresh();

        // Second approval should fail (status no longer menunggu)
        $response = $this->postJson(route('user.tukar-jadwal-dinas.respond', $permintaan->id), [
            'action' => 'approve',
        ]);

        $response->assertStatus(422);
    });
});
