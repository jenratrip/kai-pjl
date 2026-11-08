<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
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

    Schema::create('wilayah', function (Blueprint $table) {
        $table->id();
        $table->string('nama_wilayah');
        $table->text('keterangan')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });

    Schema::create('resorts', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('wilayah_id')->nullable();
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
