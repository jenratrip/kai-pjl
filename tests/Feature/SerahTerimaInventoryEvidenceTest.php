<?php

use App\Models\Dinas;
use App\Models\Inventaris;
use App\Models\Jpl;
use App\Models\RealisasiInventaris;
use App\Models\Role;
use App\Models\SerahTerima;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Support\CreatesKaiInMemorySchema;
use Tests\Support\KaiTestRecords;

uses(CreatesKaiInMemorySchema::class, KaiTestRecords::class);

beforeEach(function (): void {
    $this->createKaiInMemorySchema();
    Storage::fake('local');
});

function makeSerahTerimaInventoryContext(): array
{
    $role = Role::query()->create(['name' => 'pjl', 'label' => 'PJL']);
    $suffix = (string) Str::uuid();
    $sender = User::query()->create([
        'nip' => 'ST-INV-'.$suffix,
        'name' => 'PJL Pemeriksa',
        'role_id' => $role->id,
        'password' => 'password123',
        'is_active' => true,
    ]);
    $recipient = User::query()->create([
        'nip' => 'ST-INV-REC-'.$suffix,
        'name' => 'PJL Penerima',
        'role_id' => $role->id,
        'password' => 'password123',
        'is_active' => true,
    ]);
    $jpl = Jpl::query()->create([
        'kode_jpl' => 'ST-INV-'.$suffix,
        'nama_jpl' => 'JPL Uji Inventaris Serah Terima',
        'is_active' => true,
    ]);
    $shift = Shift::query()->create([
        'nama_shift' => 'Shift Uji',
        'jam_mulai' => '00:00:00',
        'jam_selesai' => '23:59:59',
    ]);
    $dinas = Dinas::query()->create([
        'user_id' => $sender->id,
        'jpl_id' => $jpl->id,
        'shift_id' => $shift->id,
        'tanggal_dinas' => now()->toDateString(),
        'jam_mulai' => now(),
        'status' => 'berlangsung',
        'is_pengganti' => false,
    ]);
    $inventaris = Inventaris::query()->create([
        'jpl_id' => $jpl->id,
        'nama_barang' => 'Radio komunikasi',
        'kode_barang' => 'RADIO-01',
        'kategori' => 'Komunikasi',
        'jumlah' => 1,
        'is_active' => true,
        'status_tinjauan' => 'normal',
    ]);

    return [$sender, $recipient, $dinas, $jpl, $inventaris];
}

test('Rusak requires its description and photo before a report is persisted', function (): void {
    [$sender, , , , $inventaris] = makeSerahTerimaInventoryContext();

    $this->actingAs($sender)
        ->withHeaders(['Accept' => 'application/json'])
        ->post(route('user.serah-terima-dinas.inventaris.store'), [
            'inventaris_id' => $inventaris->id,
            'kondisi' => 'rusak',
            'jumlah_aktual' => 1,
            'catatan' => 'Ada retak pada bodi.',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('bukti_foto');

    expect(RealisasiInventaris::query()->count())->toBe(0);
});

test('Rusak report stores its description and private photo for the correct Dinas and item', function (): void {
    [$sender, , $dinas, , $inventaris] = makeSerahTerimaInventoryContext();

    $this->actingAs($sender)
        ->withHeaders(['Accept' => 'application/json'])
        ->post(route('user.serah-terima-dinas.inventaris.store'), [
            'inventaris_id' => $inventaris->id,
            'kondisi' => 'rusak',
            'jumlah_aktual' => 1,
            'catatan' => 'Ada retak pada bodi.',
            'bukti_foto' => UploadedFile::fake()->image('radio.png'),
        ])
        ->assertOk()
        ->assertJsonPath('ok', true);

    $realisasi = RealisasiInventaris::query()->sole();
    expect($realisasi->dinas_id)->toBe($dinas->id)
        ->and($realisasi->inventaris_id)->toBe($inventaris->id)
        ->and($realisasi->catatan)->toBe('Ada retak pada bodi.')
        ->and($realisasi->bukti_foto_path)->toStartWith('inventaris/bukti/');
    Storage::disk('local')->assertExists($realisasi->bukti_foto_path);
});

test('a replacement inventory photo replaces the old file only after the new report saves successfully', function (): void {
    [$sender, , $dinas, , $inventaris] = makeSerahTerimaInventoryContext();
    $oldPath = 'inventaris/bukti/old-radio.png';
    Storage::disk('local')->put($oldPath, UploadedFile::fake()->image('old-radio.png')->getContent());
    RealisasiInventaris::query()->create([
        'dinas_id' => $dinas->id,
        'inventaris_id' => $inventaris->id,
        'status' => 'silang',
        'kondisi' => 'rusak',
        'jumlah_aktual' => 1,
        'catatan' => 'Bodi rusak.',
        'bukti_foto_path' => $oldPath,
    ]);

    $this->actingAs($sender)
        ->withHeaders(['Accept' => 'application/json'])
        ->post(route('user.serah-terima-dinas.inventaris.store'), [
            'inventaris_id' => $inventaris->id,
            'kondisi' => 'rusak',
            'jumlah_aktual' => 1,
            'catatan' => 'Bodi retak di sisi lain.',
            'bukti_foto' => UploadedFile::fake()->image('new-radio.png'),
        ])
        ->assertOk();

    $newPath = RealisasiInventaris::query()->sole()->bukti_foto_path;
    expect($newPath)->not->toBe($oldPath);
    Storage::disk('local')->assertMissing($oldPath);
    Storage::disk('local')->assertExists($newPath);
});

test('an invalid inventory image is rejected before changing its saved report or photo', function (): void {
    [$sender, , $dinas, , $inventaris] = makeSerahTerimaInventoryContext();
    $oldPath = 'inventaris/bukti/current-radio.png';
    Storage::disk('local')->put($oldPath, UploadedFile::fake()->image('current-radio.png')->getContent());
    RealisasiInventaris::query()->create([
        'dinas_id' => $dinas->id,
        'inventaris_id' => $inventaris->id,
        'status' => 'silang',
        'kondisi' => 'rusak',
        'jumlah_aktual' => 1,
        'catatan' => 'Bodi rusak.',
        'bukti_foto_path' => $oldPath,
    ]);

    $this->actingAs($sender)
        ->withHeaders(['Accept' => 'application/json'])
        ->post(route('user.serah-terima-dinas.inventaris.store'), [
            'inventaris_id' => $inventaris->id,
            'kondisi' => 'rusak',
            'jumlah_aktual' => 1,
            'catatan' => 'Bodi retak.',
            'bukti_foto' => UploadedFile::fake()->create('report.gif', 20, 'image/gif'),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('bukti_foto');

    expect(RealisasiInventaris::query()->sole()->catatan)->toBe('Bodi rusak.')
        ->and(RealisasiInventaris::query()->sole()->bukti_foto_path)->toBe($oldPath);
    Storage::disk('local')->assertExists($oldPath);
});

test('Hilang requires a description but accepts an optional supporting photo', function (): void {
    [$sender, , , , $inventaris] = makeSerahTerimaInventoryContext();

    $this->actingAs($sender)
        ->withHeaders(['Accept' => 'application/json'])
        ->post(route('user.serah-terima-dinas.inventaris.store'), [
            'inventaris_id' => $inventaris->id,
            'kondisi' => 'hilang',
            'jumlah_aktual' => 0,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('catatan');

    $this->actingAs($sender)
        ->withHeaders(['Accept' => 'application/json'])
        ->post(route('user.serah-terima-dinas.inventaris.store'), [
            'inventaris_id' => $inventaris->id,
            'kondisi' => 'hilang',
            'jumlah_aktual' => 0,
            'catatan' => 'Barang tidak ditemukan saat pemeriksaan.',
        ])
        ->assertOk();

    expect(RealisasiInventaris::query()->sole()->bukti_foto_path)->toBeNull();
});

test('Perlu Perbaikan follows existing optional evidence rules and rejects an attached photo', function (): void {
    [$sender, , , , $inventaris] = makeSerahTerimaInventoryContext();

    $this->actingAs($sender)
        ->withHeaders(['Accept' => 'application/json'])
        ->post(route('user.serah-terima-dinas.inventaris.store'), [
            'inventaris_id' => $inventaris->id,
            'kondisi' => 'perlu_perbaikan',
            'jumlah_aktual' => 1,
        ])
        ->assertOk();

    expect(RealisasiInventaris::query()->sole()->kondisi)->toBe('perlu_perbaikan');

    $otherInventaris = Inventaris::query()->create([
        'jpl_id' => $inventaris->jpl_id,
        'nama_barang' => 'Telepon uji',
        'jumlah' => 1,
        'is_active' => true,
    ]);

    $this->actingAs($sender)
        ->withHeaders(['Accept' => 'application/json'])
        ->post(route('user.serah-terima-dinas.inventaris.store'), [
            'inventaris_id' => $otherInventaris->id,
            'kondisi' => 'perlu_perbaikan',
            'jumlah_aktual' => 1,
            'bukti_foto' => UploadedFile::fake()->image('not-allowed.png'),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('bukti_foto');
});

test('stored report evidence is copied into the Serah Terima snapshot and can only be read by its parties', function (): void {
    [$sender, $recipient, $dinas, , $inventaris] = makeSerahTerimaInventoryContext();
    $path = 'inventaris/bukti/snapshot-radio.png';
    Storage::disk('local')->put($path, UploadedFile::fake()->image('snapshot.png')->getContent());
    RealisasiInventaris::query()->create([
        'dinas_id' => $dinas->id,
        'inventaris_id' => $inventaris->id,
        'status' => 'silang',
        'kondisi' => 'rusak',
        'jumlah_aktual' => 1,
        'catatan' => 'Lensa retak.',
        'bukti_foto_path' => $path,
        'diperiksa_pada' => now(),
        'diperiksa_oleh' => $sender->id,
    ]);
    $serahTerima = SerahTerima::query()->create([
        'dinas_id' => $dinas->id,
        'penerima_user_id' => $recipient->id,
        'waktu_serah_terima' => now(),
        'ringkasan' => [
            'inventaris' => [[
                'inventaris_id' => $inventaris->id,
                'nama_barang' => $inventaris->nama_barang,
                'jumlah_master' => 1,
                'jumlah_aktual' => 1,
                'kondisi' => 'rusak',
                'status' => 'silang',
                'catatan' => 'Lensa retak.',
                'bukti_foto_path' => $path,
            ]],
        ],
        'status' => 'menunggu_penerima',
        'status_review' => 'menunggu_review',
    ]);

    $this->actingAs($recipient)
        ->get(route('user.serah-terima-dinas.show', $serahTerima))
        ->assertOk()
        ->assertSee(route('user.serah-terima-dinas.inventaris.foto-bukti', [$serahTerima, $inventaris->id]));

    $this->actingAs($recipient)
        ->get(route('user.serah-terima-dinas.inventaris.foto-bukti', [$serahTerima, $inventaris->id]))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png')
        ->assertHeader('Cache-Control', 'no-store, private');

    $outsider = $this->kaiUser(
        Role::query()->where('name', 'pjl')->firstOrFail(),
        'ST-INV-OUTSIDE'
    );
    $this->actingAs($outsider)
        ->get(route('user.serah-terima-dinas.inventaris.foto-bukti', [$serahTerima, $inventaris->id]))
        ->assertNotFound();

    expect(data_get($serahTerima->fresh()->ringkasan, 'inventaris.0.bukti_foto_path'))->toBe($path);
    Storage::disk('local')->assertExists($path);
});

test('saving a Serah Terima captures the stored inventory report photo and description in its historical snapshot', function (): void {
    [$sender, $recipient, $dinas, , $inventaris] = makeSerahTerimaInventoryContext();
    $path = 'inventaris/bukti/report-snapshot.png';
    Storage::disk('local')->put($path, UploadedFile::fake()->image('report.png')->getContent());
    RealisasiInventaris::query()->create([
        'dinas_id' => $dinas->id,
        'inventaris_id' => $inventaris->id,
        'status' => 'silang',
        'kondisi' => 'rusak',
        'jumlah_aktual' => 1,
        'catatan' => 'Radio retak.',
        'bukti_foto_path' => $path,
        'diperiksa_pada' => now(),
        'diperiksa_oleh' => $sender->id,
    ]);

    $signature = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVR4nGP4z8AAAAMBAQDJ/pLvAAAAAElFTkSuQmCC';
    $this->actingAs($sender)
        ->post(route('user.serah-terima-dinas.store'), [
            'penerima_user_id' => $recipient->id,
            'ringkasan' => json_encode(['catatan' => 'Periksa radio.']),
            'tanda_tangan_penyerah_action' => 'set',
            'tanda_tangan_penyerah' => $signature,
            'foto_bersama' => UploadedFile::fake()->image('bersama.png'),
        ])
        ->assertRedirect(route('user.serah-terima-dinas'));

    $serahTerima = SerahTerima::query()->sole();
    expect(data_get($serahTerima->ringkasan, 'inventaris.0.bukti_foto_path'))->toBe($path)
        ->and(data_get($serahTerima->ringkasan, 'inventaris.0.catatan'))->toBe('Radio retak.');
});

test('Serah Terima inventory popup conditions and confirmation controls are present in the actual page partial', function (): void {
    $blade = file_get_contents(resource_path('views/user/serahterimacatatan/partials/inventaris.blade.php'));
    $script = file_get_contents(resource_path('js/serah-terima-inventaris.js'));

    expect($blade)
        ->toContain('data-report-dialog')
        ->toContain('data-report-condition')
        ->toContain('data-report-description')
        ->toContain('data-report-photo')
        ->toContain('data-report-preview')
        ->toContain('data-report-cancel')
        ->toContain('data-report-confirm')
        ->and($script)->toContain("routeSerahTerimaCondition('perlu_perbaikan'")
        ->and($script)->toContain("routeSerahTerimaCondition('baik'");
});
