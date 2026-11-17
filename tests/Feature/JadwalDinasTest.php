<?php

namespace Tests\Feature;

use App\Models\Jpl;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class JadwalDinasTest extends TestCase
{
    use RefreshDatabase;

    private User $pjlUser;

    private User $adminUser;

    private Shift $shiftPagi;

    private Shift $shiftSiang;

    private Shift $shiftMalam;

    private Jpl $jpl;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $pjlRole = Role::create(['name' => 'pjl']);
        $adminRole = Role::create(['name' => 'admin']);

        // Create PJL user
        $this->pjlUser = User::create([
            'nip' => '99999001',
            'name' => 'Test PJL User',
            'email' => 'pjl@example.com',
            'password' => bcrypt('password'),
            'role_id' => $pjlRole->id,
            'is_active' => true,
        ]);

        // Create admin user
        $this->adminUser = User::create([
            'nip' => '99999002',
            'name' => 'Test Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);

        // Create shifts
        $this->shiftPagi = Shift::create([
            'nama_shift' => 'Pagi',
            'jam_mulai' => '06:00:00',
            'jam_selesai' => '14:00:00',
        ]);

        $this->shiftSiang = Shift::create([
            'nama_shift' => 'Siang',
            'jam_mulai' => '14:00:00',
            'jam_selesai' => '22:00:00',
        ]);

        $this->shiftMalam = Shift::create([
            'nama_shift' => 'Malam',
            'jam_mulai' => '22:00:00',
            'jam_selesai' => '06:00:00',
        ]);

        // Create JPL
        $this->jpl = Jpl::create([
            'kode_jpl' => 'JPL001',
            'nama_jpl' => 'Test JPL',
            'resort_id' => 1,
            'is_active' => true,
        ]);
    }

    public function test_pjl_can_access_jadwal_saya_page(): void
    {
        $response = $this->actingAs($this->pjlUser)
            ->get(route('user.jadwal-dinas'));

        $response->assertStatus(200);
        $response->assertViewIs('user.jadwal-dinas.index');
        $response->assertViewHas('month');
        $response->assertViewHas('year');
        $response->assertViewHas('summary');
        $response->assertViewHas('schedules');
    }

    public function test_admin_cannot_access_jadwal_saya_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('user.jadwal-dinas'));

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $response = $this->get(route('user.jadwal-dinas'));

        $response->assertRedirect(route('login'));
    }

    public function test_pjl_sees_only_their_own_schedules(): void
    {
        $otherPjlRole = Role::where('name', 'pjl')->first();
        $otherPjl = User::create([
            'nip' => '99999003',
            'name' => 'Other PJL User',
            'email' => 'other@example.com',
            'password' => bcrypt('password'),
            'role_id' => $otherPjlRole->id,
            'is_active' => true,
        ]);

        $currentMonth = now()->startOfMonth()->toDateString();
        $endOfMonth = now()->endOfMonth()->toDateString();

        // Create schedule for PJL user
        $this->pjlUser->dinas()->create([
            'jpl_id' => $this->jpl->id,
            'shift_id' => $this->shiftPagi->id,
            'tanggal_dinas' => now()->toDateString(),
            'jam_mulai' => now()->setTime(6, 0),
            'jam_selesai' => now()->setTime(14, 0),
            'status' => 'dijadwalkan',
        ]);

        // Create schedule for other PJL user
        $otherPjl->dinas()->create([
            'jpl_id' => $this->jpl->id,
            'shift_id' => $this->shiftSiang->id,
            'tanggal_dinas' => now()->toDateString(),
            'jam_mulai' => now()->setTime(14, 0),
            'jam_selesai' => now()->setTime(22, 0),
            'status' => 'dijadwalkan',
        ]);

        $response = $this->actingAs($this->pjlUser)
            ->get(route('user.jadwal-dinas'));

        $response->assertStatus(200);

        $schedules = $response->viewData('schedules');
        $this->assertCount(1, $schedules);
        $this->assertEquals($this->shiftPagi->nama_shift, $schedules[0]['shift_name']);
    }

    public function test_filter_by_month_and_year_works(): void
    {
        $futureDate = now()->addMonth();

        $this->pjlUser->dinas()->create([
            'jpl_id' => $this->jpl->id,
            'shift_id' => $this->shiftPagi->id,
            'tanggal_dinas' => $futureDate->toDateString(),
            'jam_mulai' => $futureDate->setTime(6, 0),
            'jam_selesai' => $futureDate->setTime(14, 0),
            'status' => 'dijadwalkan',
        ]);

        // Request current month (should have no schedules)
        $response = $this->actingAs($this->pjlUser)
            ->get(route('user.jadwal-dinas', [
                'month' => now()->month,
                'year' => now()->year,
            ]));

        $schedules = $response->viewData('schedules');
        $this->assertEmpty($schedules);

        // Request future month
        $response = $this->actingAs($this->pjlUser)
            ->get(route('user.jadwal-dinas', [
                'month' => $futureDate->month,
                'year' => $futureDate->year,
            ]));

        $schedules = $response->viewData('schedules');
        $this->assertCount(1, $schedules);
    }

    public function test_summary_calculates_correctly(): void
    {
        $date1 = now()->toDateString();
        $date2 = now()->addDay()->toDateString();
        $date3 = now()->addDays(2)->toDateString();

        // Create three shifts for PJL user
        $this->pjlUser->dinas()->create([
            'jpl_id' => $this->jpl->id,
            'shift_id' => $this->shiftPagi->id,
            'tanggal_dinas' => $date1,
            'jam_mulai' => now()->setTime(6, 0),
            'jam_selesai' => now()->setTime(14, 0),
            'status' => 'dijadwalkan',
        ]);

        $this->pjlUser->dinas()->create([
            'jpl_id' => $this->jpl->id,
            'shift_id' => $this->shiftSiang->id,
            'tanggal_dinas' => $date2,
            'jam_mulai' => now()->addDay()->setTime(14, 0),
            'jam_selesai' => now()->addDay()->setTime(22, 0),
            'status' => 'dijadwalkan',
        ]);

        $this->pjlUser->dinas()->create([
            'jpl_id' => $this->jpl->id,
            'shift_id' => $this->shiftMalam->id,
            'tanggal_dinas' => $date3,
            'jam_mulai' => now()->addDays(2)->setTime(22, 0),
            'jam_selesai' => now()->addDays(3)->setTime(6, 0),
            'status' => 'dijadwalkan',
        ]);

        $response = $this->actingAs($this->pjlUser)
            ->get(route('user.jadwal-dinas', [
                'month' => now()->month,
                'year' => now()->year,
            ]));

        $summary = $response->viewData('summary');
        $this->assertEquals(3, $summary['total_hari_bertugas']);
        $this->assertEquals(1, $summary['shift_pagi']);
        $this->assertEquals(1, $summary['shift_siang']);
        $this->assertEquals(1, $summary['shift_malam']);
    }

    public function test_holidays_are_displayed(): void
    {
        $date = now()->toDateString();

        // Create holiday for PJL user
        DB::table('jadwal_dinas_libur')->insert([
            'user_id' => $this->pjlUser->id,
            'jpl_id' => $this->jpl->id,
            'tanggal_dinas' => $date,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->pjlUser)
            ->get(route('user.jadwal-dinas', [
                'month' => now()->month,
                'year' => now()->year,
            ]));

        $schedules = $response->viewData('schedules');
        $this->assertCount(1, $schedules);
        $this->assertEquals('holiday', $schedules[0]['type']);
        $this->assertEquals('libur', $schedules[0]['shift_type']);
    }

    public function test_replacement_staff_information_is_shown(): void
    {
        $originalPjlRole = Role::where('name', 'pjl')->first();
        $originalPjl = User::create([
            'nip' => '99999004',
            'name' => 'Original PJL',
            'email' => 'original@example.com',
            'password' => bcrypt('password'),
            'role_id' => $originalPjlRole->id,
            'is_active' => true,
        ]);

        // Create replacement schedule
        $this->pjlUser->dinas()->create([
            'jpl_id' => $this->jpl->id,
            'shift_id' => $this->shiftPagi->id,
            'tanggal_dinas' => now()->toDateString(),
            'jam_mulai' => now()->setTime(6, 0),
            'jam_selesai' => now()->setTime(14, 0),
            'status' => 'dijadwalkan',
            'is_pengganti' => true,
            'user_digantikan_id' => $originalPjl->id,
        ]);

        $response = $this->actingAs($this->pjlUser)
            ->get(route('user.jadwal-dinas'));

        $schedules = $response->viewData('schedules');
        $this->assertCount(1, $schedules);
        $this->assertTrue($schedules[0]['is_pengganti']);
        $this->assertEquals('Original PJL', $schedules[0]['user_digantikan']);
    }

    public function test_empty_state_shown_when_no_schedules(): void
    {
        $response = $this->actingAs($this->pjlUser)
            ->get(route('user.jadwal-dinas', [
                'month' => now()->month,
                'year' => now()->year,
            ]));

        $response->assertStatus(200);
        $this->assertFalse($response->viewData('hasData'));
        $response->assertSeeText('Belum Ada Jadwal');
    }

    public function test_shift_times_displayed_correctly(): void
    {
        $date = now()->toDateString();

        $this->pjlUser->dinas()->create([
            'jpl_id' => $this->jpl->id,
            'shift_id' => $this->shiftMalam->id,
            'tanggal_dinas' => $date,
            'jam_mulai' => now()->setTime(22, 0),
            'jam_selesai' => now()->addDay()->setTime(6, 0),
            'status' => 'dijadwalkan',
        ]);

        $response = $this->actingAs($this->pjlUser)
            ->get(route('user.jadwal-dinas'));

        $schedules = $response->viewData('schedules');
        $this->assertEquals('22:00', $schedules[0]['jam_mulai']);
        $this->assertEquals('06:00', $schedules[0]['jam_selesai']);
    }

    public function test_status_values_displayed_correctly(): void
    {
        $date = now()->toDateString();

        $this->pjlUser->dinas()->create([
            'jpl_id' => $this->jpl->id,
            'shift_id' => $this->shiftPagi->id,
            'tanggal_dinas' => $date,
            'jam_mulai' => now()->setTime(6, 0),
            'jam_selesai' => now()->setTime(14, 0),
            'status' => 'dijadwalkan',
        ]);

        $response = $this->actingAs($this->pjlUser)
            ->get(route('user.jadwal-dinas'));

        $schedules = $response->viewData('schedules');
        $this->assertEquals('Dijadwalkan', $schedules[0]['status_display']);
    }
}
