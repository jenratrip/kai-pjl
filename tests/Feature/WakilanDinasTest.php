<?php

namespace Tests\Feature;

use App\Http\Controllers\Auth\LoginController;
use App\Services\ActivityLogService;
use App\Models\Resort;
use App\Models\Wilayah;
use App\Models\Dinas;
use App\Models\Jpl;
use App\Models\Shift;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use ReflectionMethod;
use Tests\TestCase;
use Carbon\Carbon;

class WakilanDinasTest extends TestCase
{
use RefreshDatabase;

protected User $wakilan;
protected User $reguler;
protected Jpl $jpl;
protected Shift $shift;
protected Wilayah $wilayah;
protected Resort $resort;

protected function setUp(): void
{
parent::setUp();

$role = Role::create([
'name' => 'pjl',
'label' => 'PJL',
]);

$this->wakilan = User::create([
'nip' => '999999991',
'name' => 'PJL Wakilan',
'role_id' => $role->id,
'password' => Hash::make('password'),
'is_active' => true,
]);

$this->reguler = User::create([
'nip' => '999999992',
'name' => 'PJL Reguler',
'role_id' => $role->id,
'password' => Hash::make('password'),
'is_active' => true,
]);

$this->wilayah = Wilayah::create([
'nama_wilayah' => 'Wilayah Testing',
'is_active' => true,
]);

$this->resort = Resort::create([
'wilayah_id' => $this->wilayah->id,
'nama_resort' => 'Resort Testing',
'is_active' => true,
]);

$this->jpl = Jpl::create([
'resort_id' => $this->resort->id,
'kode_jpl' => 'TEST-351',
'nama_jpl' => 'JPL Testing',
'is_active' => true,
]);

$this->shift = Shift::create([
'nama_shift' => 'Malam',
'jam_mulai' => '23:00:00',
'jam_selesai' => '07:00:00',
]);
}

private function panggilSiapkanDinas(
User $user,
string $tanggal,
bool $isWakilan = true
): Dinas {
$controller = app(LoginController::class);

$method = new ReflectionMethod(
LoginController::class,
'siapkanDinas'
);

$method->setAccessible(true);

return $method->invoke(
$controller,
$user,
$this->jpl,
$this->shift,
$tanggal,
$isWakilan
);
}

public function test_wakilan_mendapatkan_user_reguler_yang_digantikan(): void
{
Dinas::create([
'user_id' => $this->reguler->id,
'jpl_id' => $this->jpl->id,
'shift_id' => $this->shift->id,
'tanggal_dinas' => '2026-10-29',
'status' => 'berlangsung',
'is_pengganti' => false,
]);

$dinas = $this->panggilSiapkanDinas(
$this->wakilan,
'2026-10-29'
);

$this->assertTrue($dinas->is_pengganti);
$this->assertEquals(
$this->reguler->id,
$dinas->user_digantikan_id
);
}

public function test_wakilan_tetap_bisa_dinas_tanpa_reguler(): void
{
$dinas = $this->panggilSiapkanDinas(
$this->wakilan,
'2026-10-29'
);

$this->assertTrue($dinas->is_pengganti);
$this->assertNull($dinas->user_digantikan_id);
$this->assertEquals('berlangsung', $dinas->status);
}

public function test_wakilan_tidak_bisa_menggunakan_dinas_yang_sudah_selesai(): void
{
Dinas::create([
'user_id' => $this->wakilan->id,
'jpl_id' => $this->jpl->id,
'shift_id' => $this->shift->id,
'tanggal_dinas' => '2026-10-29',
'status' => 'selesai',
'is_pengganti' => true,
]);

$this->expectException(
\Illuminate\Validation\ValidationException::class
);

$this->panggilSiapkanDinas(
$this->wakilan,
'2026-10-29'
);
}


public function test_login_wakilan_menyimpan_dinas_dan_user_yang_digantikan(): void
{
// Bekukan waktu agar tanggal dinas konsisten.
$this->travelTo(
Carbon::parse('2026-10-29 23:30:00', 'Asia/Jakarta')
);

// Jadwal Reguler yang akan digantikan.
Dinas::create([
'user_id' => $this->reguler->id,
'jpl_id' => $this->jpl->id,
'shift_id' => $this->shift->id,
'tanggal_dinas' => '2026-10-29',
'status' => 'berlangsung',
'is_pengganti' => false,
]);

// Mock pencatatan aktivitas agar fokus pada alur login.
$this->mock(ActivityLogService::class, function ($mock) {
$mock->shouldReceive('log')->once();
});

$response = $this->post(route('login.store'), [
'nip' => '999999991',
'password' => 'password',
'nomor_pjl' => 'TEST-351',
'dinasan' => $this->shift->id,
'jenis_petugas' => 'wakilan',
]);

// Login berhasil dan diarahkan ke halaman Realisasi KA.
$response->assertRedirect(route('user.realisasi-ka'));

$this->assertAuthenticatedAs($this->wakilan);

// Session petugas login tersimpan dengan benar.
$response->assertSessionHas('petugas_login', function ($session) {
return $session['jenis'] === 'wakilan'
&& $session['jpl_id'] === $this->jpl->id
&& $session['shift_id'] === $this->shift->id
&& !empty($session['dinas_id']);
});

// Pastikan dinas Wakilan tersimpan dan menunjuk ke Reguler.
$dinas = Dinas::where('user_id', $this->wakilan->id)
->where('jpl_id', $this->jpl->id)
->where('shift_id', $this->shift->id)
->whereDate('tanggal_dinas', '2026-10-29')
->first();

$this->assertNotNull($dinas);
$this->assertEquals('berlangsung', $dinas->status);
$this->assertTrue($dinas->is_pengganti);
$this->assertEquals(
$this->reguler->id,
$dinas->user_digantikan_id
);
}


public function test_login_reguler_berhasil_jika_memiliki_jadwal(): void
{
    $this->travelTo(
        Carbon::parse('2026-10-29 23:30:00', 'Asia/Jakarta')
    );

    Dinas::create([
        'user_id' => $this->reguler->id,
        'jpl_id' => $this->jpl->id,
        'shift_id' => $this->shift->id,
        'tanggal_dinas' => '2026-10-29',
        'status' => 'dijadwalkan',
        'is_pengganti' => false,
    ]);

    $this->mock(ActivityLogService::class, function ($mock) {
        $mock->shouldReceive('log')->once();
    });

    $response = $this->post(route('login.store'), [
        'nip' => '999999992',
        'password' => 'password',
        'nomor_pjl' => 'TEST-351',
        'dinasan' => $this->shift->id,
        'jenis_petugas' => 'reguler',
    ]);

    $response->assertRedirect(route('user.realisasi-ka'));

    $this->assertAuthenticatedAs($this->reguler);

    $response->assertSessionHas('petugas_login', function ($session) {
        return $session['jenis'] === 'reguler'
            && $session['jpl_id'] === $this->jpl->id
            && $session['shift_id'] === $this->shift->id
            && !empty($session['dinas_id']);
    });

    $this->assertDatabaseHas('dinas', [
        'user_id' => $this->reguler->id,
        'jpl_id' => $this->jpl->id,
        'shift_id' => $this->shift->id,
        'is_pengganti' => false,
    ]);
}

public function test_login_reguler_ditolak_jika_tidak_memiliki_jadwal(): void
{
    $this->travelTo(
        Carbon::parse('2026-10-29 23:30:00', 'Asia/Jakarta')
    );

    $response = $this->post(route('login.store'), [
        'nip' => '999999992',
        'password' => 'password',
        'nomor_pjl' => 'TEST-351',
        'dinasan' => $this->shift->id,
        'jenis_petugas' => 'reguler',
    ]);

    $response->assertSessionHasErrors('nomor_pjl');

    $this->assertGuest();

    $this->assertDatabaseMissing('dinas', [
        'user_id' => $this->reguler->id,
        'jpl_id' => $this->jpl->id,
        'shift_id' => $this->shift->id,
        'is_pengganti' => false,
    ]);
}

}