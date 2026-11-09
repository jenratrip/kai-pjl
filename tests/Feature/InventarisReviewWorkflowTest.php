<?php

use App\Models\ActivityLog;
use App\Models\Dinas;
use App\Models\Inventaris;
use App\Models\Jpl;
use App\Models\RealisasiInventaris;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Schema::create('roles', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('label')->nullable();
        $table->timestamps();
    });

    Schema::create('users', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('role_id')->nullable();
        $table->string('nip')->nullable();
        $table->string('name');
        $table->string('email')->nullable();
        $table->string('password');
        $table->boolean('is_active')->default(true);
        $table->rememberToken();
        $table->timestamps();
    });

    Schema::create('activity_logs', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('user_id')->nullable();
        $table->string('action');
        $table->string('module');
        $table->text('description')->nullable();
        $table->string('subject_type')->nullable();
        $table->unsignedBigInteger('subject_id')->nullable();
        $table->json('old_values')->nullable();
        $table->json('new_values')->nullable();
        $table->string('ip_address')->nullable();
        $table->text('user_agent')->nullable();
        $table->timestamps();
    });

    Schema::create('jpl', function (Blueprint $table): void {
        $table->id();
        $table->string('kode_jpl')->nullable();
        $table->string('nama_jpl');
        $table->timestamps();
    });

    Schema::create('shifts', function (Blueprint $table): void {
        $table->id();
        $table->string('nama_shift');
        $table->string('jam_mulai')->nullable();
        $table->string('jam_selesai')->nullable();
        $table->timestamps();
    });

    Schema::create('dinas', function (Blueprint $table): void {
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

    Schema::create('inventaris', function (Blueprint $table): void {
        $table->id();
        $table->string('kode_barang')->nullable();
        $table->string('nama_barang');
        $table->string('kategori');
        $table->string('lokasi')->nullable();
        $table->unsignedBigInteger('jpl_id')->nullable();
        $table->unsignedInteger('jumlah')->default(1);
        $table->text('keterangan')->nullable();
        $table->boolean('is_active')->default(true);
        $table->string('kondisi_terakhir', 30)->default('baik');
        $table->unsignedInteger('jumlah_aktual_terakhir')->nullable();
        $table->string('status_tinjauan', 30)->default('normal');
        $table->text('catatan_masalah')->nullable();
        $table->timestamp('dilaporkan_pada')->nullable();
        $table->unsignedBigInteger('dilaporkan_oleh')->nullable();
        $table->text('catatan_tinjauan')->nullable();
        $table->unsignedBigInteger('ditinjau_oleh')->nullable();
        $table->timestamp('ditinjau_pada')->nullable();
        $table->timestamps();
    });

    Schema::create('realisasi_inventaris', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('dinas_id');
        $table->unsignedBigInteger('inventaris_id');
        $table->string('status')->nullable();
        $table->string('kondisi')->nullable();
        $table->text('catatan')->nullable();
        $table->string('bukti_foto_path')->nullable();
        $table->unsignedInteger('jumlah_aktual')->nullable();
        $table->timestamp('diperiksa_pada')->nullable();
        $table->unsignedBigInteger('diperiksa_oleh')->nullable();
        $table->timestamps();
    });
});

function createInventarisWorkflowUser(string $roleName): User
{
    $role = Role::create([
        'name' => $roleName,
        'label' => ucfirst($roleName),
    ]);

    return User::create([
        'role_id' => $role->id,
        'nip' => $roleName.'-'.fake()->unique()->numerify('#####'),
        'name' => ucfirst($roleName).' Test',
        'email' => $roleName.'-'.fake()->unique()->safeEmail(),
        'password' => 'password',
        'is_active' => true,
    ]);
}

function createInventarisWorkflowJpl(string $code): Jpl
{
    return Jpl::create([
        'kode_jpl' => $code,
        'nama_jpl' => 'JPL '.$code,
    ]);
}

function createInventarisWorkflowItem(Jpl $jpl, array $attributes = []): Inventaris
{
    return Inventaris::create(array_merge([
        'nama_barang' => 'Lampu Senter',
        'kategori' => 'alat_pendukung',
        'lokasi' => 'Pos JPL',
        'jpl_id' => $jpl->id,
        'jumlah' => 2,
        'is_active' => true,
        'kondisi_terakhir' => 'baik',
        'status_tinjauan' => 'normal',
    ], $attributes));
}

function createInventarisWorkflowDinas(User $user, Jpl $jpl, Shift $shift, array $attributes = []): Dinas
{
    return Dinas::create(array_merge([
        'user_id' => $user->id,
        'jpl_id' => $jpl->id,
        'shift_id' => $shift->id,
        'tanggal_dinas' => '2026-11-08',
        'status' => 'berlangsung',
        'is_pengganti' => false,
    ], $attributes));
}

it('allows an admin to complete a pending inventory review', function () {
    $admin = createInventarisWorkflowUser('admin');
    $reporter = createInventarisWorkflowUser('pjl');
    $jpl = createInventarisWorkflowJpl('JPL-01');
    $item = createInventarisWorkflowItem($jpl, [
        'status_tinjauan' => 'perlu_tinjauan',
        'catatan_masalah' => 'Jumlah barang kurang.',
        'dilaporkan_oleh' => $reporter->id,
        'dilaporkan_pada' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.inventaris.show', $item))
        ->assertOk()
        ->assertSeeText('Catatan Hasil Tinjauan')
        ->assertSeeText('Dilaporkan Oleh');

    $response = $this->actingAs($admin)
        ->post(route('admin.inventaris.review', $item), [
            'catatan_tinjauan' => 'Jumlah telah diperiksa dan dilengkapi.',
        ]);

    $response->assertRedirect(route('admin.inventaris.show', $item));
    $this->assertDatabaseHas('inventaris', [
        'id' => $item->id,
        'status_tinjauan' => 'normal',
        'catatan_tinjauan' => 'Jumlah telah diperiksa dan dilengkapi.',
        'ditinjau_oleh' => $admin->id,
    ]);
    expect(Inventaris::findOrFail($item->id)->ditinjau_pada)->not->toBeNull();

    $activity = ActivityLog::query()->sole();
    expect($activity->action)->toBe('review')
        ->and($activity->module)->toBe('inventaris')
        ->and($activity->user_id)->toBe($admin->id)
        ->and($activity->subject_type)->toBe(Inventaris::class)
        ->and($activity->subject_id)->toBe($item->id)
        ->and($activity->old_values)->toBe(['status_tinjauan' => 'perlu_tinjauan'])
        ->and($activity->new_values)->toBe([
            'status_tinjauan' => 'normal',
            'ditinjau_oleh' => $admin->id,
        ]);

    expect(json_encode($activity->new_values))->not->toContain('Jumlah telah diperiksa');
});

it('rejects inventory review attempts by superadmins and PJL users', function () {
    $item = createInventarisWorkflowItem(
        createInventarisWorkflowJpl('JPL-02'),
        ['status_tinjauan' => 'perlu_tinjauan'],
    );

    foreach (['superadmin', 'pjl'] as $roleName) {
        $user = createInventarisWorkflowUser($roleName);

        $this->actingAs($user)
            ->post(route('admin.inventaris.review', $item), [
                'catatan_tinjauan' => 'Catatan tinjauan.',
            ])
            ->assertForbidden();
    }

    $this->assertDatabaseHas('inventaris', [
        'id' => $item->id,
        'status_tinjauan' => 'perlu_tinjauan',
        'catatan_tinjauan' => null,
    ]);

    expect(ActivityLog::query()->count())->toBe(0);
});

it('keeps a pending review unchanged when the review note is missing', function () {
    $admin = createInventarisWorkflowUser('admin');
    $item = createInventarisWorkflowItem(
        createInventarisWorkflowJpl('JPL-03'),
        ['status_tinjauan' => 'perlu_tinjauan'],
    );

    $this->actingAs($admin)
        ->from(route('admin.inventaris.show', $item))
        ->post(route('admin.inventaris.review', $item), [])
        ->assertSessionHasErrors(['catatan_tinjauan']);

    $this->assertDatabaseHas('inventaris', [
        'id' => $item->id,
        'status_tinjauan' => 'perlu_tinjauan',
        'ditinjau_oleh' => null,
    ]);

    expect(ActivityLog::query()->count())->toBe(0);
});

it('rolls back an admin review when its activity log cannot be saved', function () {
    $admin = createInventarisWorkflowUser('admin');
    $item = createInventarisWorkflowItem(
        createInventarisWorkflowJpl('JPL-REVIEW-ROLLBACK'),
        ['status_tinjauan' => 'perlu_tinjauan'],
    );

    $this->mock(ActivityLogService::class)
        ->shouldReceive('log')
        ->once()
        ->andThrow(new RuntimeException('Activity log gagal disimpan.'));

    $this->actingAs($admin)
        ->post(route('admin.inventaris.review', $item), [
            'catatan_tinjauan' => 'Catatan review rollback.',
        ])
        ->assertServerError();

    $this->assertDatabaseHas('inventaris', [
        'id' => $item->id,
        'status_tinjauan' => 'perlu_tinjauan',
        'ditinjau_oleh' => null,
    ]);
    expect(ActivityLog::query()->count())->toBe(0);
});

it('logs admin inventory management without recording free-text fields', function () {
    $admin = createInventarisWorkflowUser('admin');
    $jpl = createInventarisWorkflowJpl('JPL-ADMIN-INVENTORY');

    $this->actingAs($admin)
        ->post(route('admin.inventaris.store'), [
            'kode_barang' => 'INV-LOG-1',
            'nama_barang' => 'Peralatan Audit',
            'kategori' => 'alat_pendukung',
            'lokasi' => 'Pos JPL',
            'jpl_id' => $jpl->id,
            'jumlah' => 3,
            'keterangan' => 'Catatan barang rahasia.',
            'is_active' => 1,
        ])
        ->assertRedirect(route('admin.inventaris.index'));

    $item = Inventaris::query()->sole();
    $createLog = ActivityLog::query()->sole();
    expect($createLog->action)->toBe('create')
        ->and($createLog->module)->toBe('inventaris')
        ->and($createLog->subject_type)->toBe(Inventaris::class)
        ->and($createLog->subject_id)->toBe($item->id)
        ->and($createLog->new_values)->toBe([
            'jpl_id' => $jpl->id,
            'kategori' => 'alat_pendukung',
            'jumlah' => 3,
            'is_active' => true,
            'status_tinjauan' => 'normal',
        ]);

    $this->put(route('admin.inventaris.update', $item), [
        'kode_barang' => 'INV-LOG-1',
        'nama_barang' => 'Peralatan Audit Diperbarui',
        'kategori' => 'alat_pendukung',
        'lokasi' => 'Pos JPL Utara',
        'jpl_id' => $jpl->id,
        'jumlah' => 4,
        'keterangan' => 'Catatan pembaruan rahasia.',
        'is_active' => 1,
    ])->assertRedirect(route('admin.inventaris.index'));

    $updateLog = ActivityLog::query()->where('action', 'update')->sole();
    expect($updateLog->subject_id)->toBe($item->id)
        ->and($updateLog->new_values['jumlah'])->toBe(4)
        ->and($updateLog->new_values['changed_fields'])->toContain('keterangan')
        ->and($updateLog->new_values)->not->toHaveKey('keterangan');

    expect(json_encode($updateLog->new_values))->not->toContain('Catatan pembaruan rahasia');

    RealisasiInventaris::query()->create([
        'dinas_id' => createInventarisWorkflowDinas(
            createInventarisWorkflowUser('pjl'),
            $jpl,
            Shift::create(['nama_shift' => 'Inventaris'])
        )->id,
        'inventaris_id' => $item->id,
        'status' => 'centang',
        'kondisi' => 'baik',
        'jumlah_aktual' => 4,
    ]);

    $this->delete(route('admin.inventaris.destroy', $item))
        ->assertRedirect(route('admin.inventaris.index'));

    expect(ActivityLog::query()->where('action', 'deactivate')->sole()->new_values)
        ->toBe(['is_active' => false]);
});

it('logs deletion of inventory that has no inspection history', function () {
    $admin = createInventarisWorkflowUser('admin');
    $item = createInventarisWorkflowItem(createInventarisWorkflowJpl('JPL-DELETE-INVENTORY'));

    $this->actingAs($admin)
        ->delete(route('admin.inventaris.destroy', $item))
        ->assertRedirect(route('admin.inventaris.index'));

    $this->assertDatabaseMissing('inventaris', ['id' => $item->id]);
    expect(ActivityLog::query()->sole()->action)->toBe('delete');
});

it('shows and saves inventory checks only for the PJL dinas in the session', function () {
    Storage::fake('local');

    $pjl = createInventarisWorkflowUser('pjl');
    $jpl = createInventarisWorkflowJpl('JPL-04');
    $otherJpl = createInventarisWorkflowJpl('JPL-05');
    $shift = Shift::create(['nama_shift' => 'Pagi']);
    $dinas = createInventarisWorkflowDinas($pjl, $jpl, $shift);
    $item = createInventarisWorkflowItem($jpl);
    $otherItem = createInventarisWorkflowItem($otherJpl, [
        'nama_barang' => 'Bendera Semboyan',
    ]);

    $this->actingAs($pjl)
        ->withSession(['dinas_id' => $dinas->id])
        ->get(route('user.inventaris.index'))
        ->assertOk()
        ->assertViewIs('user.inventaris.index')
        ->assertSeeText('Inventaris PJL')
        ->assertSeeText('Lampu Senter')
        ->assertDontSeeText('Bendera Semboyan');

    $this->actingAs($pjl)
        ->withSession(['dinas_id' => $dinas->id])
        ->post(route('user.inventaris.update', $item), [
            '_method' => 'PATCH',
            'dinas_id' => $dinas->id,
            'kondisi' => 'rusak',
            'jumlah_aktual' => 1,
            'catatan' => 'Perlu penggantian.',
            'bukti_foto' => UploadedFile::fake()->image('damaged.jpg'),
        ])
        ->assertRedirect(route('user.inventaris.index'));

    $this->assertDatabaseHas('realisasi_inventaris', [
        'dinas_id' => $dinas->id,
        'inventaris_id' => $item->id,
        'kondisi' => 'rusak',
        'jumlah_aktual' => 1,
        'diperiksa_oleh' => $pjl->id,
    ]);

    $inspectionLog = ActivityLog::query()->sole();
    expect($inspectionLog->action)->toBe('inspection')
        ->and($inspectionLog->module)->toBe('inventaris')
        ->and($inspectionLog->user_id)->toBe($pjl->id)
        ->and($inspectionLog->subject_type)->toBe(RealisasiInventaris::class)
        ->and($inspectionLog->subject_id)->not->toBeNull()
        ->and($inspectionLog->new_values)->toMatchArray([
            'dinas_id' => $dinas->id,
            'inventaris_id' => $item->id,
            'status' => 'silang',
            'kondisi' => 'rusak',
            'jumlah_aktual' => 1,
        ])
        ->and($inspectionLog->new_values)->not->toHaveKey('catatan');

    expect(json_encode($inspectionLog->new_values))->not->toContain('Perlu penggantian');

    $this->actingAs($pjl)
        ->withSession(['dinas_id' => $dinas->id])
        ->patch(route('user.inventaris.update', $otherItem), [
            'dinas_id' => $dinas->id,
            'kondisi' => 'baik',
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('realisasi_inventaris', [
        'dinas_id' => $dinas->id,
        'inventaris_id' => $otherItem->id,
    ]);

    expect(ActivityLog::query()->count())->toBe(1);
});

it('does not fall back to another active Dinas when the session context is invalid', function () {
    $pjl = createInventarisWorkflowUser('pjl');
    $admin = createInventarisWorkflowUser('admin');
    $anotherPjl = createInventarisWorkflowUser('pjl');
    $jpl = createInventarisWorkflowJpl('JPL-07');
    $shift = Shift::create(['nama_shift' => 'Malam']);
    $dinas = createInventarisWorkflowDinas($pjl, $jpl, $shift);
    $foreignDinas = createInventarisWorkflowDinas($anotherPjl, $jpl, $shift);

    $this->actingAs($pjl)
        ->withSession(['dinas_id' => $dinas->id + 100])
        ->get(route('user.inventaris.index'))
        ->assertOk()
        ->assertSeeText('Tidak ada dinas aktif');

    $this->actingAs($admin)
        ->get(route('user.inventaris.index'))
        ->assertForbidden();

    $this->actingAs($admin)
        ->post(route('user.inventaris.prepare'))
        ->assertForbidden();

    $this->actingAs($pjl)
        ->withSession(['dinas_id' => $dinas->id + 100])
        ->patch(route('user.inventaris.update', createInventarisWorkflowItem($jpl)), [
            'dinas_id' => $dinas->id,
            'kondisi' => 'baik',
        ])
        ->assertNotFound();

    $this->actingAs($pjl)
        ->withSession(['dinas_id' => $foreignDinas->id])
        ->post(route('user.inventaris.prepare'))
        ->assertNotFound();

    $this->assertDatabaseCount('realisasi_inventaris', 0);
});

it('keeps GET read-only and lets a PJL prepare shared checks once', function () {
    $primary = createInventarisWorkflowUser('pjl');
    $replacement = createInventarisWorkflowUser('pjl');
    $jpl = createInventarisWorkflowJpl('JPL-06');
    $shift = Shift::create(['nama_shift' => 'Siang']);
    $item = createInventarisWorkflowItem($jpl);

    $previousDinas = createInventarisWorkflowDinas($primary, $jpl, $shift, [
        'tanggal_dinas' => '2026-11-07',
        'status' => 'selesai',
    ]);
    RealisasiInventaris::create([
        'dinas_id' => $previousDinas->id,
        'inventaris_id' => $item->id,
        'status' => 'centang',
        'kondisi' => 'baik',
        'jumlah_aktual' => 2,
        'catatan' => 'Pemeriksaan sebelumnya.',
        'diperiksa_pada' => '2026-11-07 12:00:00',
        'diperiksa_oleh' => $primary->id,
    ]);

    $primaryDinas = createInventarisWorkflowDinas($primary, $jpl, $shift);
    $replacementDinas = createInventarisWorkflowDinas($replacement, $jpl, $shift, [
        'is_pengganti' => true,
        'user_digantikan_id' => $primary->id,
    ]);

    $this->actingAs($replacement)
        ->withSession(['dinas_id' => $replacementDinas->id])
        ->get(route('user.inventaris.index'))
        ->assertOk()
        ->assertDontSeeText('Pemeriksaan sebelumnya.');

    $this->assertDatabaseCount('realisasi_inventaris', 1);
    $this->assertDatabaseMissing('realisasi_inventaris', [
        'dinas_id' => $primaryDinas->id,
        'inventaris_id' => $item->id,
    ]);

    $this->actingAs($replacement)
        ->withSession(['dinas_id' => $replacementDinas->id])
        ->post(route('user.inventaris.prepare'))
        ->assertRedirect(route('user.inventaris.index'))
        ->assertSessionHas('success', '1 pemeriksaan terakhir berhasil disalin ke Dinas ini.');

    $this->assertDatabaseHas('realisasi_inventaris', [
        'dinas_id' => $primaryDinas->id,
        'inventaris_id' => $item->id,
        'kondisi' => 'baik',
        'catatan' => 'Pemeriksaan sebelumnya.',
    ]);
    $this->assertDatabaseCount('realisasi_inventaris', 2);

    $copyLog = ActivityLog::query()->sole();
    expect($copyLog->action)->toBe('copy_previous_inspections')
        ->and($copyLog->module)->toBe('inventaris')
        ->and($copyLog->user_id)->toBe($replacement->id)
        ->and($copyLog->subject_type)->toBe(Dinas::class)
        ->and($copyLog->subject_id)->toBe($primaryDinas->id)
        ->and($copyLog->new_values)->toBe(['copied_count' => 1]);

    $this->actingAs($replacement)
        ->withSession(['dinas_id' => $replacementDinas->id])
        ->post(route('user.inventaris.prepare'))
        ->assertRedirect(route('user.inventaris.index'))
        ->assertSessionHas('success', 'Tidak ada pemeriksaan sebelumnya yang perlu disalin.');

    $this->assertDatabaseCount('realisasi_inventaris', 2);
    expect(ActivityLog::query()->count())->toBe(1);

    $this->actingAs($replacement)
        ->withSession(['dinas_id' => $replacementDinas->id])
        ->patch(route('user.inventaris.update', $item), [
            'dinas_id' => $replacementDinas->id,
            'kondisi' => 'perlu_perbaikan',
            'catatan' => 'Perlu diperbaiki oleh petugas wakilan.',
        ])
        ->assertRedirect(route('user.inventaris.index'));

    $this->assertDatabaseHas('realisasi_inventaris', [
        'dinas_id' => $primaryDinas->id,
        'inventaris_id' => $item->id,
        'kondisi' => 'perlu_perbaikan',
        'diperiksa_oleh' => $replacement->id,
    ]);

    $this->assertDatabaseHas('realisasi_inventaris', [
        'dinas_id' => $previousDinas->id,
        'inventaris_id' => $item->id,
        'kondisi' => 'baik',
        'catatan' => 'Pemeriksaan sebelumnya.',
    ]);

    expect(ActivityLog::query()->count())->toBe(2)
        ->and(ActivityLog::query()->where('action', 'inspection')->sole()->new_values)
        ->not->toHaveKey('catatan');
});

it('rolls back an inventory check and its log when activity logging fails', function () {
    Storage::fake('local');

    $pjl = createInventarisWorkflowUser('pjl');
    $jpl = createInventarisWorkflowJpl('JPL-ROLLBACK-LOG');
    $shift = Shift::create(['nama_shift' => 'Rollback']);
    $dinas = createInventarisWorkflowDinas($pjl, $jpl, $shift);
    $item = createInventarisWorkflowItem($jpl);

    $this->mock(ActivityLogService::class)
        ->shouldReceive('log')
        ->once()
        ->andThrow(new RuntimeException('Activity log gagal disimpan.'));

    $this->actingAs($pjl)
        ->withSession(['dinas_id' => $dinas->id])
        ->post(route('user.inventaris.update', $item), [
            '_method' => 'PATCH',
            'dinas_id' => $dinas->id,
            'kondisi' => 'rusak',
            'jumlah_aktual' => 1,
            'catatan' => 'Catatan rollback.',
            'bukti_foto' => UploadedFile::fake()->image('rollback.jpg'),
        ])
        ->assertServerError();

    $this->assertDatabaseCount('realisasi_inventaris', 0);
    $this->assertDatabaseHas('inventaris', [
        'id' => $item->id,
        'kondisi_terakhir' => 'baik',
        'status_tinjauan' => 'normal',
    ]);
    expect(ActivityLog::query()->count())->toBe(0);
});

it('renders the evidence modal and saved private photo only on a PJL inventory page', function () {
    Storage::fake('local');

    $pjl = createInventarisWorkflowUser('pjl');
    $jpl = createInventarisWorkflowJpl('JPL-PHOTO-VIEW');
    $shift = Shift::create(['nama_shift' => 'Siang']);
    $dinas = createInventarisWorkflowDinas($pjl, $jpl, $shift);
    $item = createInventarisWorkflowItem($jpl);
    $path = 'inventaris/bukti/stored.webp';
    Storage::disk('local')->put($path, 'fake image content');

    RealisasiInventaris::create([
        'dinas_id' => $dinas->id,
        'inventaris_id' => $item->id,
        'status' => 'silang',
        'kondisi' => 'rusak',
        'catatan' => 'Retak pada sisi luar.',
        'bukti_foto_path' => $path,
        'diperiksa_oleh' => $pjl->id,
    ]);

    $this->actingAs($pjl)
        ->withSession(['dinas_id' => $dinas->id])
        ->get(route('user.inventaris.index'))
        ->assertOk()
        ->assertSee('data-evidence-dialog', false)
        ->assertSee('data-evidence-preview', false)
        ->assertSee('data-evidence-cancel', false)
        ->assertSee('data-evidence-confirm', false)
        ->assertSee(route('user.inventaris.foto-bukti', $item), false)
        ->assertSeeText('Retak pada sisi luar.');

    $this->assertDatabaseHas('realisasi_inventaris', [
        'dinas_id' => $dinas->id,
        'inventaris_id' => $item->id,
        'bukti_foto_path' => $path,
    ]);
    Storage::disk('local')->assertExists($path);
});

it('requires both a photo and description before saving a damaged inventory item', function () {
    Storage::fake('local');

    $pjl = createInventarisWorkflowUser('pjl');
    $jpl = createInventarisWorkflowJpl('JPL-RUSAK-REQUIRED');
    $shift = Shift::create(['nama_shift' => 'Malam']);
    $dinas = createInventarisWorkflowDinas($pjl, $jpl, $shift);
    $item = createInventarisWorkflowItem($jpl);

    $this->actingAs($pjl)
        ->withSession(['dinas_id' => $dinas->id])
        ->post(route('user.inventaris.update', $item), [
            '_method' => 'PATCH',
            'dinas_id' => $dinas->id,
            'kondisi' => 'rusak',
            'jumlah_aktual' => 2,
        ], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['catatan']);

    $this->actingAs($pjl)
        ->withSession(['dinas_id' => $dinas->id])
        ->patchJson(route('user.inventaris.update', $item), [
            'dinas_id' => $dinas->id,
            'kondisi' => 'rusak',
            'jumlah_aktual' => 2,
            'catatan' => 'Ada kerusakan.',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['bukti_foto']);

    $this->assertDatabaseCount('realisasi_inventaris', 0);
    expect(ActivityLog::query()->count())->toBe(0);
});

it('saves a complete inventory check without requiring a photo or evidence modal', function () {
    $pjl = createInventarisWorkflowUser('pjl');
    $jpl = createInventarisWorkflowJpl('JPL-BAIK-DIRECT');
    $shift = Shift::create(['nama_shift' => 'Pagi']);
    $dinas = createInventarisWorkflowDinas($pjl, $jpl, $shift);
    $item = createInventarisWorkflowItem($jpl);

    $this->actingAs($pjl)
        ->withSession(['dinas_id' => $dinas->id])
        ->patchJson(route('user.inventaris.update', $item), [
            'dinas_id' => $dinas->id,
            'kondisi' => 'baik',
        ])
        ->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('bukti_foto_url', null);

    $realisasi = RealisasiInventaris::query()->sole();
    expect($realisasi->kondisi)->toBe('baik')
        ->and($realisasi->jumlah_aktual)->toBe(2)
        ->and($realisasi->bukti_foto_path)->toBeNull();
});

it('saves damaged evidence privately and allows only the active owning PJL to view it', function () {
    Storage::fake('local');

    $pjl = createInventarisWorkflowUser('pjl');
    $otherPjl = createInventarisWorkflowUser('pjl');
    $jpl = createInventarisWorkflowJpl('JPL-RUSAK-SAVE');
    $shift = Shift::create(['nama_shift' => 'Malam']);
    $dinas = createInventarisWorkflowDinas($pjl, $jpl, $shift);
    $otherDinas = createInventarisWorkflowDinas($otherPjl, $jpl, $shift, [
        'tanggal_dinas' => '2026-11-09',
    ]);
    $item = createInventarisWorkflowItem($jpl);

    $response = $this->actingAs($pjl)
        ->withSession(['dinas_id' => $dinas->id])
        ->post(route('user.inventaris.update', $item), [
            '_method' => 'PATCH',
            'dinas_id' => $dinas->id,
            'kondisi' => 'rusak',
            'jumlah_aktual' => 1,
            'catatan' => 'Retak pada pegangan.',
            'bukti_foto' => UploadedFile::fake()->image('bukti.jpg'),
        ], ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJsonPath('ok', true);

    $realisasi = RealisasiInventaris::query()->sole();
    expect($realisasi->kondisi)->toBe('rusak')
        ->and($realisasi->catatan)->toBe('Retak pada pegangan.')
        ->and($realisasi->bukti_foto_path)->toStartWith('inventaris/bukti/');
    Storage::disk('local')->assertExists($realisasi->bukti_foto_path);

    $this->actingAs($pjl)
        ->withSession(['dinas_id' => $dinas->id])
        ->get($response->json('bukti_foto_url'))
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    $this->actingAs($otherPjl)
        ->withSession(['dinas_id' => $otherDinas->id])
        ->get(route('user.inventaris.foto-bukti', $item))
        ->assertNotFound();

    $admin = createInventarisWorkflowUser('admin');
    $this->actingAs($admin)
        ->withSession(['dinas_id' => $dinas->id])
        ->get(route('user.inventaris.foto-bukti', $item))
        ->assertForbidden();
});

it('requires a description for lost inventory but allows an optional supporting photo', function () {
    Storage::fake('local');

    $pjl = createInventarisWorkflowUser('pjl');
    $jpl = createInventarisWorkflowJpl('JPL-HILANG-OPTIONAL');
    $shift = Shift::create(['nama_shift' => 'Pagi']);
    $dinas = createInventarisWorkflowDinas($pjl, $jpl, $shift);
    $item = createInventarisWorkflowItem($jpl);

    $this->actingAs($pjl)
        ->withSession(['dinas_id' => $dinas->id])
        ->post(route('user.inventaris.update', $item), [
            '_method' => 'PATCH',
            'dinas_id' => $dinas->id,
            'kondisi' => 'hilang',
            'catatan' => 'Barang tidak ditemukan saat pemeriksaan.',
        ], ['Accept' => 'application/json'])
        ->assertOk();

    $realisasi = RealisasiInventaris::query()->sole();
    expect($realisasi->kondisi)->toBe('hilang')
        ->and($realisasi->catatan)->toBe('Barang tidak ditemukan saat pemeriksaan.')
        ->and($realisasi->bukti_foto_path)->toBeNull();

    $anotherItem = createInventarisWorkflowItem($jpl, ['nama_barang' => 'Lampu Tambahan']);
    $this->actingAs($pjl)
        ->withSession(['dinas_id' => $dinas->id])
        ->patchJson(route('user.inventaris.update', $anotherItem), [
            'dinas_id' => $dinas->id,
            'kondisi' => 'hilang',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['catatan']);

    $withPhoto = createInventarisWorkflowItem($jpl, ['nama_barang' => 'Radio Hilang']);
    $this->actingAs($pjl)
        ->withSession(['dinas_id' => $dinas->id])
        ->post(route('user.inventaris.update', $withPhoto), [
            '_method' => 'PATCH',
            'dinas_id' => $dinas->id,
            'kondisi' => 'hilang',
            'catatan' => 'Foto lokasi terakhir radio.',
            'bukti_foto' => UploadedFile::fake()->image('lokasi.png'),
        ], ['Accept' => 'application/json'])
        ->assertOk();

    expect(RealisasiInventaris::query()->where('inventaris_id', $withPhoto->id)->value('bukti_foto_path'))
        ->toStartWith('inventaris/bukti/');
});

it('rejects invalid evidence files and keeps an old photo when replacement logging fails', function () {
    Storage::fake('local');

    $pjl = createInventarisWorkflowUser('pjl');
    $jpl = createInventarisWorkflowJpl('JPL-FOTO-REPLACE');
    $shift = Shift::create(['nama_shift' => 'Malam']);
    $dinas = createInventarisWorkflowDinas($pjl, $jpl, $shift);
    $item = createInventarisWorkflowItem($jpl);
    $oldPath = 'inventaris/bukti/old.jpg';
    Storage::disk('local')->put($oldPath, 'old evidence');
    $existing = RealisasiInventaris::create([
        'dinas_id' => $dinas->id,
        'inventaris_id' => $item->id,
        'status' => 'silang',
        'kondisi' => 'rusak',
        'catatan' => 'Bukti lama.',
        'bukti_foto_path' => $oldPath,
        'diperiksa_oleh' => $pjl->id,
    ]);

    $this->actingAs($pjl)
        ->withSession(['dinas_id' => $dinas->id])
        ->post(route('user.inventaris.update', $item), [
            '_method' => 'PATCH',
            'dinas_id' => $dinas->id,
            'kondisi' => 'rusak',
            'catatan' => 'Format salah.',
            'bukti_foto' => UploadedFile::fake()->create('bukti.gif', 10, 'image/gif'),
        ], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['bukti_foto']);

    $this->actingAs($pjl)
        ->withSession(['dinas_id' => $dinas->id])
        ->post(route('user.inventaris.update', $item), [
            '_method' => 'PATCH',
            'dinas_id' => $dinas->id,
            'kondisi' => 'rusak',
            'catatan' => 'Foto terlalu besar.',
            'bukti_foto' => UploadedFile::fake()->image('large.jpg')->size(5121),
        ], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['bukti_foto']);

    $this->mock(ActivityLogService::class)
        ->shouldReceive('log')
        ->once()
        ->andThrow(new RuntimeException('Log gagal.'));

    $this->actingAs($pjl)
        ->withSession(['dinas_id' => $dinas->id])
        ->post(route('user.inventaris.update', $item), [
            '_method' => 'PATCH',
            'dinas_id' => $dinas->id,
            'kondisi' => 'rusak',
            'catatan' => 'Foto pengganti.',
            'bukti_foto' => UploadedFile::fake()->image('new.jpg'),
        ], ['Accept' => 'application/json'])
        ->assertServerError();

    expect($existing->fresh()->bukti_foto_path)->toBe($oldPath);
    Storage::disk('local')->assertExists($oldPath);
    expect(Storage::disk('local')->allFiles('inventaris/bukti'))->toBe([$oldPath]);
});

it('deletes the previous evidence only after a replacement is saved successfully', function () {
    Storage::fake('local');

    $pjl = createInventarisWorkflowUser('pjl');
    $jpl = createInventarisWorkflowJpl('JPL-FOTO-REPLACE-SUCCESS');
    $shift = Shift::create(['nama_shift' => 'Siang']);
    $dinas = createInventarisWorkflowDinas($pjl, $jpl, $shift);
    $item = createInventarisWorkflowItem($jpl);
    $oldPath = 'inventaris/bukti/old-proof.jpg';
    Storage::disk('local')->put($oldPath, 'old evidence');
    RealisasiInventaris::create([
        'dinas_id' => $dinas->id,
        'inventaris_id' => $item->id,
        'status' => 'silang',
        'kondisi' => 'rusak',
        'catatan' => 'Bukti lama.',
        'bukti_foto_path' => $oldPath,
        'diperiksa_oleh' => $pjl->id,
    ]);

    $this->actingAs($pjl)
        ->withSession(['dinas_id' => $dinas->id])
        ->post(route('user.inventaris.update', $item), [
            '_method' => 'PATCH',
            'dinas_id' => $dinas->id,
            'kondisi' => 'rusak',
            'catatan' => 'Bukti baru.',
            'bukti_foto' => UploadedFile::fake()->image('replacement.webp'),
        ], ['Accept' => 'application/json'])
        ->assertOk();

    $newPath = RealisasiInventaris::query()->sole()->bukti_foto_path;
    expect($newPath)->not->toBe($oldPath);
    Storage::disk('local')->assertMissing($oldPath);
    Storage::disk('local')->assertExists($newPath);
});

it('does not copy a prior inventory photo as evidence for a new inspection', function () {
    Storage::fake('local');

    $pjl = createInventarisWorkflowUser('pjl');
    $jpl = createInventarisWorkflowJpl('JPL-COPY-NO-PHOTO');
    $shift = Shift::create(['nama_shift' => 'Siang']);
    $item = createInventarisWorkflowItem($jpl);
    $previousDinas = createInventarisWorkflowDinas($pjl, $jpl, $shift, [
        'tanggal_dinas' => '2026-11-07',
        'status' => 'selesai',
    ]);
    $oldPath = 'inventaris/bukti/previous.jpg';
    Storage::disk('local')->put($oldPath, 'previous evidence');
    RealisasiInventaris::create([
        'dinas_id' => $previousDinas->id,
        'inventaris_id' => $item->id,
        'status' => 'silang',
        'kondisi' => 'rusak',
        'catatan' => 'Catatan sebelumnya.',
        'bukti_foto_path' => $oldPath,
        'diperiksa_oleh' => $pjl->id,
    ]);
    $currentDinas = createInventarisWorkflowDinas($pjl, $jpl, $shift);

    $this->actingAs($pjl)
        ->withSession(['dinas_id' => $currentDinas->id])
        ->post(route('user.inventaris.prepare'))
        ->assertRedirect(route('user.inventaris.index'));

    $copy = RealisasiInventaris::query()
        ->where('dinas_id', $currentDinas->id)
        ->where('inventaris_id', $item->id)
        ->sole();

    expect($copy->catatan)->toBe('Catatan sebelumnya.')
        ->and($copy->bukti_foto_path)->toBeNull();
    Storage::disk('local')->assertExists($oldPath);
});
