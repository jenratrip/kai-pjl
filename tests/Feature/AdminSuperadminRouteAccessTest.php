<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::create('roles', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('label')->nullable();
        $table->timestamps();
    });

    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('role_id')->nullable();
        $table->string('nip')->nullable();
        $table->string('name');
        $table->string('email')->nullable();
        $table->string('phone')->nullable();
        $table->string('photo')->nullable();
        $table->string('password');
        $table->boolean('is_active')->default(true);
        $table->rememberToken();
        $table->timestamps();
    });

    Schema::create('resorts', function (Blueprint $table) {
        $table->id();
        $table->string('nama_resort');
        $table->text('keterangan')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });

    Schema::create('jpl', function (Blueprint $table) {
        $table->id();
        $table->string('kode_jpl')->nullable();
        $table->string('nama_jpl');
        $table->unsignedBigInteger('resort_id')->nullable();
        $table->string('antara')->nullable();
        $table->string('km_hm')->nullable();
        $table->string('lokasi')->nullable();
        $table->string('kota_kabupaten')->nullable();
        $table->string('provinsi')->nullable();
        $table->string('jenis_jalur_ka')->nullable();
        $table->string('konst_jalan')->nullable();
        $table->string('titik_koordinat')->nullable();
        $table->decimal('lebar_jln', 8, 2)->nullable();
        $table->string('status_penjagaan')->nullable();
        $table->text('keterangan')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });

    Schema::create('dinas', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->unsignedBigInteger('jpl_id')->nullable();
        $table->unsignedBigInteger('shift_id')->nullable();
        $table->date('tanggal_dinas')->nullable();
        $table->dateTime('jam_mulai')->nullable();
        $table->dateTime('jam_selesai')->nullable();
        $table->string('status')->default('berlangsung');
        $table->boolean('is_pengganti')->default(false);
        $table->unsignedBigInteger('user_digantikan_id')->nullable();
        $table->timestamps();
    });

    Schema::create('realisasi_ka_settings', function (Blueprint $table) {
        $table->id();
        $table->unsignedInteger('toleransi_keterlambatan_menit')->default(5);
        $table->unsignedInteger('peringatan_keterlambatan_tinggi_menit')->default(10);
        $table->unsignedInteger('jarak_minimum_antar_ka_menit')->default(0);
        $table->unsignedInteger('batas_penutupan_palang_menit')->default(0);
        $table->boolean('warning_keterlambatan_aktif')->default(false);
        $table->boolean('warning_keterlambatan_tinggi_aktif')->default(false);
        $table->boolean('warning_antar_ka_aktif')->default(false);
        $table->boolean('warning_penutupan_palang_aktif')->default(false);
        $table->unsignedBigInteger('updated_by')->nullable();
        $table->timestamps();
    });

    Schema::create('catatan_dinas', function (Blueprint $table) {
        $table->id();
    });

    Schema::create('serah_terima', function (Blueprint $table) {
        $table->id();
    });

    Schema::create('shifts', function (Blueprint $table) {
        $table->id();
    });

    Schema::create('jadwal_ka_batches', function (Blueprint $table) {
        $table->id();
    });

    Schema::create('jadwal_ka', function (Blueprint $table) {
        $table->id();
    });

    Schema::create('inventaris', function (Blueprint $table) {
        $table->id();
    });
});

function createUserWithRole(string $roleName): User
{
    $role = Role::create([
        'name' => $roleName,
        'label' => ucfirst($roleName),
    ]);

    return User::create([
        'role_id' => $role->id,
        'nip' => $roleName.'-test',
        'name' => ucfirst($roleName).' Test',
        'email' => $roleName.'@example.test',
        'password' => 'password',
        'is_active' => true,
    ]);
}

it('allows an authenticated superadmin to view the read-only JPL page', function () {
    $superadmin = createUserWithRole('superadmin');

    $this->withoutVite()
        ->actingAs($superadmin)
        ->get(route('superadmin.jpl.index'))
        ->assertOk()
        ->assertViewIs('superadmin.jpl.index')
        ->assertSeeText('Daftar Master Data JPL')
        ->assertDontSeeText('Import JPL')
        ->assertDontSeeText('Koreksi');
});

it('allows an active superadmin to view operational data without granting mutations', function () {
    $superadmin = createUserWithRole('superadmin');

    $this->withoutVite()
        ->actingAs($superadmin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertViewIs('admin.users.index');
});

it('forbids a superadmin from operational mutation and side-effect routes', function (string $method, string $path) {
    $superadmin = createUserWithRole('superadmin');

    DB::table('catatan_dinas')->insert(['id' => 1]);
    DB::table('serah_terima')->insert(['id' => 1]);
    DB::table('shifts')->insert(['id' => 1]);
    DB::table('jpl')->insert([
        'id' => 1,
        'nama_jpl' => 'JPL Test',
    ]);
    DB::table('jadwal_ka_batches')->insert(['id' => 1]);
    DB::table('jadwal_ka')->insert(['id' => 1]);
    DB::table('inventaris')->insert(['id' => 1]);
    DB::table('dinas')->insert([
        'id' => 1,
        'user_id' => $superadmin->id,
        'shift_id' => 1,
    ]);

    $this->actingAs($superadmin)
        ->call($method, $path)
        ->assertForbidden();
})->with([
    'user import preview' => ['POST', '/admin/users/import/preview'],
    'user import confirmation' => ['POST', '/admin/users/import/confirm'],
    'user template edit form' => ['GET', '/admin/users/1/edit'],
    'user update' => ['PUT', '/admin/users/1'],
    'user status toggle' => ['PATCH', '/admin/users/1/status'],
    'catatan review update' => ['PATCH', '/admin/catatan-dinas/1'],
    'serah terima review update' => ['PATCH', '/admin/serah-terima-dinas/1'],
    'shift creation' => ['POST', '/admin/shift'],
    'shift update' => ['PUT', '/admin/shift/1'],
    'shift deletion' => ['DELETE', '/admin/shift/1'],
    'dinas import' => ['POST', '/admin/dinas/import'],
    'dinas listing with status synchronization' => ['GET', '/admin/dinas'],
    'dinas updates with status synchronization' => ['GET', '/admin/dinas/updates'],
    'dinas edit form' => ['GET', '/admin/dinas/1/edit'],
    'dinas creation' => ['POST', '/admin/dinas'],
    'dinas update' => ['PUT', '/admin/dinas/1'],
    'dinas deletion' => ['DELETE', '/admin/dinas/1'],
    'realisasi setting page with first-or-create' => ['GET', '/admin/pengaturan-realisasi-ka'],
    'realisasi setting update' => ['PUT', '/admin/pengaturan-realisasi-ka'],
    'Jadwal KA import page' => ['GET', '/admin/jadwal-ka/import'],
    'Jadwal KA import template' => ['GET', '/admin/jadwal-ka/template'],
    'Jadwal KA import confirmation' => ['POST', '/admin/jadwal-ka/import/confirm'],
    'Jadwal KA version scheduling' => ['POST', '/admin/jadwal-ka-batches/1/schedule'],
    'Jadwal KA edit form' => ['GET', '/admin/jadwal-ka/1/edit'],
    'Jadwal KA update' => ['PUT', '/admin/jadwal-ka/1'],
    'Jadwal KA status toggle' => ['PATCH', '/admin/jadwal-ka/1/toggle-status'],
    'JPL import page' => ['GET', '/admin/jpl/import'],
    'JPL import preview' => ['POST', '/admin/jpl/import/preview'],
    'JPL import confirmation' => ['POST', '/admin/jpl/import/confirm'],
    'JPL creation' => ['POST', '/admin/jpl'],
    'JPL activation' => ['POST', '/admin/jpl/1/activate'],
    'Admin inventory review' => ['POST', '/admin/inventaris/1/review'],
]);

it('logs out an inactive authenticated account before protected routes continue', function (string $role, string $path) {
    $user = createUserWithRole($role);

    Auth::guard()->login($user);
    $user->update(['is_active' => false]);
    Auth::forgetGuards();

    $this->get($path)
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('nip');

    $this->assertGuest();
})->with([
    'inactive superadmin' => ['superadmin', '/superadmin/dashboard'],
    'inactive admin' => ['admin', '/admin/dashboard'],
    'inactive PJL' => ['pjl', '/user/realisasi-ka'],
]);

it('keeps Admin mutation access and does not grant it to PJL', function () {
    $admin = createUserWithRole('admin');
    $pjl = createUserWithRole('pjl');

    $this->actingAs($admin)
        ->post(route('admin.jpl.store'), [])
        ->assertSessionHasErrors(['nama_jpl', 'resort_id']);

    $this->actingAs($pjl)
        ->post(route('admin.jpl.store'), [])
        ->assertForbidden();
});

it('forbids a superadmin from reaching JPL mutation routes', function () {
    $superadmin = createUserWithRole('superadmin');

    $this->actingAs($superadmin)
        ->post(route('admin.jpl.store'), [])
        ->assertForbidden();

    $this->get('/superadmin/jpl/1/edit')->assertNotFound();
    $this->put('/superadmin/jpl/1', [])->assertNotFound();

    expect(Route::has('superadmin.jpl.import'))->toBeFalse()
        ->and(Route::has('superadmin.jpl.edit'))->toBeFalse()
        ->and(Route::has('superadmin.jpl.update'))->toBeFalse();
});

it('allows an admin to reach JPL creation validation', function () {
    $admin = createUserWithRole('admin');

    $this->actingAs($admin)
        ->post(route('admin.jpl.store'), [])
        ->assertSessionHasErrors(['nama_jpl', 'resort_id']);
});

it('restricts Jadwal KA import to admins while preserving the admin preview endpoint', function () {
    $superadmin = createUserWithRole('superadmin');

    $this->actingAs($superadmin)
        ->post(route('admin.jadwal-ka.import.preview'), [])
        ->assertForbidden();

    $admin = createUserWithRole('admin');

    $this->actingAs($admin)
        ->post(route('admin.jadwal-ka.import.preview'), [])
        ->assertSessionHasErrors(['tanggal_berlaku', 'file']);
});

it('keeps the PJL Realisasi KA route available to PJL users', function () {
    $pjl = createUserWithRole('pjl');

    $this->withoutVite()
        ->actingAs($pjl)
        ->get(route('user.realisasi-ka'))
        ->assertOk()
        ->assertViewIs('user.realisasi-ka.index');
});
