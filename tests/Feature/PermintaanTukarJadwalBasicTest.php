<?php

use App\Models\Dinas;
use App\Models\PermintaanTukarJadwal;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use App\Models\Jpl;
use App\Services\TukarJadwalService;
use Illuminate\Support\Str;
use Tests\Support\CreatesKaiInMemorySchema;
use Tests\Support\KaiTestRecords;

uses(CreatesKaiInMemorySchema::class, KaiTestRecords::class);

beforeEach(function () {
    $this->createKaiInMemorySchema();
    $this->service = new TukarJadwalService();
});

/**
 * Helper function to create test scenario with two PJL users and two schedules.
 */
function createSwapScenario(TestCase $test): array
{
    $pjlRole = Role::firstOrCreate(['name' => 'pjl'], ['label' => 'PJL']);
    $suffix = Str::uuid();

    $userA = User::create([
        'nip' => "A-{$suffix}",
        'name' => 'User A',
        'role_id' => $pjlRole->id,
        'password' => 'password',
        'is_active' => true,
    ]);

    $userB = User::create([
        'nip' => "B-{$suffix}",
        'name' => 'User B',
        'role_id' => $pjlRole->id,
        'password' => 'password',
        'is_active' => true,
    ]);

    $jpl = Jpl::create([
        'kode_jpl' => 'JPL-' . $suffix,
        'nama_jpl' => 'JPL Test',
        'is_active' => true,
    ]);

    $shift = Shift::create([
        'nama_shift' => 'Shift Test',
        'jam_mulai' => '00:00:00',
        'jam_selesai' => '23:59:59',
    ]);

    $tomorrow = now()->addDay();
    $dayAfter = now()->addDays(2);

    $dinasA = Dinas::create([
        'user_id' => $userA->id,
        'jpl_id' => $jpl->id,
        'shift_id' => $shift->id,
        'tanggal_dinas' => $tomorrow->toDateString(),
        'jam_mulai' => $tomorrow->setHour(6),
        'jam_selesai' => $tomorrow->setHour(14),
        'status' => 'dijadwalkan',
        'is_pengganti' => false,
    ]);

    $dinasB = Dinas::create([
        'user_id' => $userB->id,
        'jpl_id' => $jpl->id,
        'shift_id' => $shift->id,
        'tanggal_dinas' => $dayAfter->toDateString(),
        'jam_mulai' => $dayAfter->setHour(14),
        'jam_selesai' => $dayAfter->setHour(22),
        'status' => 'dijadwalkan',
        'is_pengganti' => false,
    ]);

    return [$userA, $userB, $jpl, $shift, $dinasA, $dinasB];
}

// ============ Database & Model Tests ============

test('permintaan_tukar_jadwal table created with proper structure', function () {
    $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('permintaan_tukar_jadwal'));

    $columns = \Illuminate\Support\Facades\Schema::getColumns('permintaan_tukar_jadwal');
    $columnNames = array_column($columns, 'name');

    expect($columnNames)->toContain(
        'id',
        'dinas_pemohon_id',
        'dinas_tujuan_id',
        'pemohon_id',
        'penerima_id',
        'alasan_pemohon',
        'alasan_penerima',
        'alasan_gagal',
        'status',
        'diajukan_pada',
        'dijawab_pada',
        'selesai_pada'
    );
});

test('PermintaanTukarJadwal model can be created', function () {
    [$userA, $userB, $jpl, $shift, $dinasA, $dinasB] = createSwapScenario($this);

    $permintaan = PermintaanTukarJadwal::create([
        'dinas_pemohon_id' => $dinasA->id,
        'dinas_tujuan_id' => $dinasB->id,
        'pemohon_id' => $userA->id,
        'penerima_id' => $userB->id,
        'alasan_pemohon' => 'Saya memiliki acara penting.',
        'status' => 'menunggu',
    ]);

    expect($permintaan->exists)->toBeTrue();
    expect($permintaan->status)->toBe('menunggu');
    expect($permintaan->isPending())->toBeTrue();
});

test('PermintaanTukarJadwal relations work correctly', function () {
    [$userA, $userB, $jpl, $shift, $dinasA, $dinasB] = createSwapScenario($this);

    $permintaan = PermintaanTukarJadwal::create([
        'dinas_pemohon_id' => $dinasA->id,
        'dinas_tujuan_id' => $dinasB->id,
        'pemohon_id' => $userA->id,
        'penerima_id' => $userB->id,
        'alasan_pemohon' => 'Test',
        'status' => 'menunggu',
    ]);

    expect($permintaan->dinasPemohon->id)->toBe($dinasA->id);
    expect($permintaan->dinasTujuan->id)->toBe($dinasB->id);
    expect($permintaan->pemohon->id)->toBe($userA->id);
    expect($permintaan->penerima->id)->toBe($userB->id);
});

test('Dinas model has new permintaanTukar relations', function () {
    [$userA, $userB, $jpl, $shift, $dinasA, $dinasB] = createSwapScenario($this);

    $permintaan = PermintaanTukarJadwal::create([
        'dinas_pemohon_id' => $dinasA->id,
        'dinas_tujuan_id' => $dinasB->id,
        'pemohon_id' => $userA->id,
        'penerima_id' => $userB->id,
        'alasan_pemohon' => 'Test',
        'status' => 'menunggu',
    ]);

    $dinasAFresh = Dinas::find($dinasA->id);
    $dinasAPermintaan = $dinasAFresh->permintaanTukarAsalPemohon()->first();

    expect($dinasAPermintaan->id)->toBe($permintaan->id);
});

// ============ Service Validation Tests ============

test('service validates ownership correctly', function () {
    [$userA, $userB, $jpl, $shift, $dinasA, $dinasB] = createSwapScenario($this);

    // Try to swap with wrong ownership
    $result = $this->service->validateSwapRequest(
        $dinasB, // This belongs to userB, not userA
        $dinasA,
        $userA->id,
        $userB->id
    );

    expect($result['valid'])->toBeFalse();
    expect($result['message'])->toContain('tidak milik Anda');
});

test('service rejects self-swap', function () {
    [$userA, $userB, $jpl, $shift, $dinasA, $dinasB] = createSwapScenario($this);

    $result = $this->service->validateSwapRequest(
        $dinasA,
        $dinasB,
        $userA->id,
        $userA->id // Same user!
    );

    expect($result['valid'])->toBeFalse();
    expect($result['message'])->toContain('diri sendiri');
});

test('service validates schedule status', function () {
    [$userA, $userB, $jpl, $shift, $dinasA, $dinasB] = createSwapScenario($this);

    // Change status to berlangsung
    $dinasA->update(['status' => 'berlangsung']);

    $result = $this->service->validateSwapRequest(
        $dinasA,
        $dinasB,
        $userA->id,
        $userB->id
    );

    expect($result['valid'])->toBeFalse();
    expect($result['message'])->toContain('tidak dapat ditukar');
});

test('service accepts valid swap request', function () {
    [$userA, $userB, $jpl, $shift, $dinasA, $dinasB] = createSwapScenario($this);

    $result = $this->service->validateSwapRequest(
        $dinasA,
        $dinasB,
        $userA->id,
        $userB->id
    );

    expect($result['valid'])->toBeTrue();
    expect($result['message'])->toBeNull();
});

// ============ Service Swap Execution Tests ============

test('service executes atomic swap successfully', function () {
    [$userA, $userB, $jpl, $shift, $dinasA, $dinasB] = createSwapScenario($this);

    // Create and approve permintaan
    $permintaan = PermintaanTukarJadwal::create([
        'dinas_pemohon_id' => $dinasA->id,
        'dinas_tujuan_id' => $dinasB->id,
        'pemohon_id' => $userA->id,
        'penerima_id' => $userB->id,
        'alasan_pemohon' => 'Test',
        'status' => 'menunggu',
    ]);

    [$success, $error] = $this->service->executeSwap($permintaan);

    expect($success)->toBeTrue();
    expect($error)->toBeNull();
});

test('service swap exchanges user_id correctly', function () {
    [$userA, $userB, $jpl, $shift, $dinasA, $dinasB] = createSwapScenario($this);

    $originalUserIdA = $dinasA->user_id;
    $originalUserIdB = $dinasB->user_id;

    $permintaan = PermintaanTukarJadwal::create([
        'dinas_pemohon_id' => $dinasA->id,
        'dinas_tujuan_id' => $dinasB->id,
        'pemohon_id' => $userA->id,
        'penerima_id' => $userB->id,
        'alasan_pemohon' => 'Test',
        'status' => 'menunggu',
    ]);

    [$success, $error] = $this->service->executeSwap($permintaan);

    expect($success)->toBeTrue();

    // Refresh from database
    $dinasASwapped = Dinas::find($dinasA->id);
    $dinasBSwapped = Dinas::find($dinasB->id);

    // User IDs should be swapped
    expect($dinasASwapped->user_id)->toBe($originalUserIdB);
    expect($dinasBSwapped->user_id)->toBe($originalUserIdA);
});

test('service swap preserves other columns', function () {
    [$userA, $userB, $jpl, $shift, $dinasA, $dinasB] = createSwapScenario($this);

    $originalTanggalA = $dinasA->tanggal_dinas;
    $originalJplIdA = $dinasA->jpl_id;
    $originalShiftIdA = $dinasA->shift_id;

    $permintaan = PermintaanTukarJadwal::create([
        'dinas_pemohon_id' => $dinasA->id,
        'dinas_tujuan_id' => $dinasB->id,
        'pemohon_id' => $userA->id,
        'penerima_id' => $userB->id,
        'alasan_pemohon' => 'Test',
        'status' => 'menunggu',
    ]);

    [$success, $error] = $this->service->executeSwap($permintaan);

    expect($success)->toBeTrue();

    $dinasASwapped = Dinas::find($dinasA->id);

    // Other columns should remain unchanged
    expect($dinasASwapped->tanggal_dinas->toDateString())->toBe($originalTanggalA->toDateString());
    expect($dinasASwapped->jpl_id)->toBe($originalJplIdA);
    expect($dinasASwapped->shift_id)->toBe($originalShiftIdA);
});

test('service swap updates permintaan status to berhasil', function () {
    [$userA, $userB, $jpl, $shift, $dinasA, $dinasB] = createSwapScenario($this);

    $permintaan = PermintaanTukarJadwal::create([
        'dinas_pemohon_id' => $dinasA->id,
        'dinas_tujuan_id' => $dinasB->id,
        'pemohon_id' => $userA->id,
        'penerima_id' => $userB->id,
        'alasan_pemohon' => 'Test',
        'status' => 'menunggu',
    ]);

    [$success, $error] = $this->service->executeSwap($permintaan);

    expect($success)->toBeTrue();

    $permintaanSwapped = PermintaanTukarJadwal::find($permintaan->id);
    expect($permintaanSwapped->status)->toBe('berhasil');
    expect($permintaanSwapped->selesai_pada)->not->toBeNull();
});

test('service handles missing dinas gracefully', function () {
    [$userA, $userB, $jpl, $shift, $dinasA, $dinasB] = createSwapScenario($this);

    $permintaan = PermintaanTukarJadwal::create([
        'dinas_pemohon_id' => $dinasA->id,
        'dinas_tujuan_id' => $dinasB->id,
        'pemohon_id' => $userA->id,
        'penerima_id' => $userB->id,
        'alasan_pemohon' => 'Test',
        'status' => 'menunggu',
    ]);

    // Delete one of the dinas
    $dinasA->delete();

    [$success, $error] = $this->service->executeSwap($permintaan);

    expect($success)->toBeFalse();
    expect($error)->toContain('dihapus');
});

test('is_pengganti flag is NOT modified during swap', function () {
    [$userA, $userB, $jpl, $shift, $dinasA, $dinasB] = createSwapScenario($this);

    // Ensure is_pengganti is false initially
    expect($dinasA->is_pengganti)->toBeFalse();
    expect($dinasB->is_pengganti)->toBeFalse();

    $permintaan = PermintaanTukarJadwal::create([
        'dinas_pemohon_id' => $dinasA->id,
        'dinas_tujuan_id' => $dinasB->id,
        'pemohon_id' => $userA->id,
        'penerima_id' => $userB->id,
        'alasan_pemohon' => 'Test',
        'status' => 'menunggu',
    ]);

    [$success, $error] = $this->service->executeSwap($permintaan);

    expect($success)->toBeTrue();

    $dinasASwapped = Dinas::find($dinasA->id);
    $dinasBSwapped = Dinas::find($dinasB->id);

    // is_pengganti should still be false (NOT modified)
    expect($dinasASwapped->is_pengganti)->toBeFalse();
    expect($dinasBSwapped->is_pengganti)->toBeFalse();
});
