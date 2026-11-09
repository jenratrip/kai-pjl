<?php

use App\Models\ActivityLog;
use App\Models\Dinas;
use App\Models\Jpl;
use App\Models\Role;
use App\Models\SerahTerima;
use App\Models\Shift;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Support\CreatesKaiInMemorySchema;
use Tests\Support\KaiTestRecords;
use Tests\TestCase;

uses(CreatesKaiInMemorySchema::class, KaiTestRecords::class);

beforeEach(function () {
    $this->createKaiInMemorySchema();
    Storage::fake('local');
});

function makeRecipientWorkflowSerahTerima(TestCase $test, array $attributes = []): array
{
    $pjlRole = Role::query()->firstOrCreate(
        ['name' => 'pjl'],
        ['label' => 'PJL']
    );
    $suffix = (string) Str::uuid();
    $sender = User::query()->create([
        'nip' => 'P-ST-SENDER-'.$suffix,
        'name' => 'Penyerah '.$suffix,
        'role_id' => $pjlRole->id,
        'password' => 'password123',
        'is_active' => true,
    ]);
    $recipient = User::query()->create([
        'nip' => 'P-ST-RECIPIENT-'.$suffix,
        'name' => 'Penerima '.$suffix,
        'role_id' => $pjlRole->id,
        'password' => 'password123',
        'is_active' => true,
    ]);
    $jpl = Jpl::query()->create([
        'kode_jpl' => 'JPL-'.$suffix,
        'nama_jpl' => 'JPL Pengujian',
        'is_active' => true,
    ]);
    $shift = Shift::query()->create([
        'nama_shift' => 'Shift Pengujian',
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
    $serahTerima = SerahTerima::query()->create(array_merge([
        'dinas_id' => $dinas->id,
        'penerima_user_id' => $recipient->id,
        'waktu_serah_terima' => now(),
        'ringkasan' => [
            'kereta_api' => [[
                'nomor' => 'KA-HISTORI',
                'nama' => 'Nama historis',
                'jadwal' => '10:15',
                'status' => 'JALAN',
            ]],
        ],
        'tanda_tangan_penyerah' => 'serah-terima/'.$sender->id.'/sender.png',
        'tanda_tangan_penerima' => null,
        'status' => 'menunggu_penerima',
        'status_review' => 'menunggu_review',
    ], $attributes));

    return [$sender, $recipient, $dinas, $serahTerima];
}

test('a recipient sees incoming Serah Terima history addressed to their account only', function () {
    [, $recipient, , $incoming] = makeRecipientWorkflowSerahTerima($this);
    [, , , $unrelated] = makeRecipientWorkflowSerahTerima($this);

    $response = $this->actingAs($recipient)
        ->get(route('user.serah-terima-dinas.riwayat'));

    $response->assertOk()
        ->assertSeeText('Menunggu Konfirmasi Penerima')
        ->assertDontSeeText('User A')
        ->assertDontSeeText('User B')
        ->assertViewHas('pengajuanMasuk', function ($records) use ($incoming, $unrelated): bool {
            return $records->total() === 1
                && $records->getCollection()->sole()->is($incoming)
                && ! $records->getCollection()->contains(fn ($record) => $record->is($unrelated));
        });
});

test('the designated recipient can view stored Serah Terima details without recalculating the summary', function () {
    [, $recipient, , $serahTerima] = makeRecipientWorkflowSerahTerima($this);
    $storedSummary = $serahTerima->ringkasan;
    $schedule = $this->kaiSchedule(
        $serahTerima->dinas->jpl,
        $this->kaiTrain('KA-HISTORI'),
        ['jam_lewat' => '23:59:00']
    );

    $response = $this->actingAs($recipient)
        ->get(route('user.serah-terima-dinas.show', $serahTerima));

    $response->assertOk()
        ->assertViewHas('modePenerima', true)
        ->assertSeeText('Menunggu Konfirmasi Penerima')
        ->assertDontSeeText('User A')
        ->assertDontSeeText('User B')
        ->assertSeeText('Tanda tangan penerima')
        ->assertSeeText('Terima dan Tanda Tangani')
        ->assertSeeText('Penerima belum memberikan tanda tangan.')
        ->assertViewHas('dataRealisasiKA', function ($records): bool {
            return $records->first()['nomor'] === 'KA-HISTORI'
                && $records->first()['nama'] === 'Nama historis'
                && $records->first()['jadwal'] === '10:15';
        });

    expect($serahTerima->fresh()->ringkasan)->toBe($storedSummary)
        ->and($schedule->jam_lewat)->toBe('23:59:00');
});

test('a sender can see the complete signature and closing section in locked history', function () {
    Storage::fake('public');

    [$sender, , , $serahTerima] = makeRecipientWorkflowSerahTerima($this, [
        'status' => 'selesai',
        'status_review' => 'disetujui',
        'tanda_tangan_penerima' => 'serah-terima/recipient.png',
    ]);
    Storage::disk('public')->put('serah-terima/sender.png', 'stored sender signature');
    Storage::disk('public')->put('serah-terima/recipient.png', 'stored recipient signature');

    $this->actingAs($sender)
        ->get(route('user.serah-terima-dinas.show', $serahTerima))
        ->assertOk()
        ->assertSeeText('Tanda tangan')
        ->assertSeeText('Tanda tangan penyerah')
        ->assertSeeText('Tanda tangan penerima')
        ->assertSeeText('Cetak')
        ->assertSeeText('Kembali ke riwayat');
});

test('a PJL who is neither sender nor recipient cannot access a Serah Terima detail', function () {
    [$sender, , , $serahTerima] = makeRecipientWorkflowSerahTerima($this);
    $outsider = User::query()->create([
        'nip' => 'P-ST-OUTSIDER',
        'name' => 'Petugas luar',
        'role_id' => $sender->role_id,
        'password' => 'password123',
        'is_active' => true,
    ]);

    $this->actingAs($outsider)
        ->get(route('user.serah-terima-dinas.show', $serahTerima))
        ->assertNotFound();
});

test('the sender can edit while awaiting confirmation and after an admin requests revision', function (array $attributes) {
    [$sender, , , $serahTerima] = makeRecipientWorkflowSerahTerima($this, $attributes);

    $this->actingAs($sender)
        ->get(route('user.serah-terima-dinas.edit', $serahTerima))
        ->assertOk()
        ->assertViewHas('modeEdit', true);
})->with([
    'waiting for recipient' => [[
        'status' => 'menunggu_penerima',
        'status_review' => 'menunggu_review',
    ]],
    'revision requested' => [[
        'status' => 'selesai',
        'status_review' => 'perlu_revisi',
    ]],
]);

test('the sender cannot edit a Serah Terima already confirmed by the recipient', function () {
    [$sender, , , $serahTerima] = makeRecipientWorkflowSerahTerima($this, [
        'status' => 'selesai',
        'status_review' => 'menunggu_review',
        'tanda_tangan_penerima' => 'serah-terima/recipient.png',
    ]);

    $this->actingAs($sender)
        ->get(route('user.serah-terima-dinas.edit', $serahTerima))
        ->assertRedirect(route('user.serah-terima-dinas.show', $serahTerima));
});

test('the sender cannot bypass the status edit rule through the save endpoint', function () {
    [$sender, $recipient, , $serahTerima] = makeRecipientWorkflowSerahTerima($this, [
        'status' => 'selesai',
        'status_review' => 'menunggu_review',
        'tanda_tangan_penerima' => 'serah-terima/recipient.png',
    ]);
    $storedSummary = $serahTerima->ringkasan;

    $this->actingAs($sender)
        ->from(route('user.serah-terima-dinas.edit', $serahTerima))
        ->post(route('user.serah-terima-dinas.store'), [
            'serah_terima_id' => $serahTerima->id,
            'penerima_user_id' => $recipient->id,
            'ringkasan' => json_encode(['kereta_api' => []]),
            'tanda_tangan_penyerah_action' => 'keep',
        ])
        ->assertRedirect(route('user.serah-terima-dinas.edit', $serahTerima))
        ->assertSessionHasErrors('dinas');

    expect($serahTerima->fresh()->ringkasan)->toBe($storedSummary)
        ->and($serahTerima->fresh()->status)->toBe('selesai');
});

test('only the designated recipient can confirm and the confirmation advances the status once', function () {
    [, $recipient, $dinas, $serahTerima] = makeRecipientWorkflowSerahTerima($this);
    $snapshot = [
        'format_version' => 1,
        'snapshot_at' => now()->toIso8601String(),
        'dinas_id' => $dinas->id,
        'jpl_id' => $dinas->jpl_id,
        'shift_id' => $dinas->shift_id,
        'tanggal_dinas' => now()->toDateString(),
        'jadwal_ka_batch_id' => null,
        'source' => 'legacy',
        'jadwal' => [],
    ];
    $dinas->update(['jadwal_ka_snapshot' => $snapshot]);
    $storedSummary = $serahTerima->ringkasan;
    $outsider = User::query()->create([
        'nip' => 'P-ST-WRONG-RECIPIENT',
        'name' => 'Penerima lain',
        'role_id' => $recipient->role_id,
        'password' => 'password123',
        'is_active' => true,
    ]);
    $signature = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVR4nGP4z8AAAAMBAQDJ/pLvAAAAAElFTkSuQmCC';
    Storage::fake('public');

    $this->actingAs($outsider)
        ->post(route('user.serah-terima-dinas.konfirmasi', $serahTerima), [
            'tanda_tangan_penerima' => $signature,
        ])
        ->assertForbidden();

    expect($serahTerima->fresh()->status)->toBe('menunggu_penerima');
    expect($serahTerima->fresh()->tanda_tangan_penerima)->toBeNull()
        ->and($serahTerima->fresh()->ringkasan)->toBe($storedSummary)
        ->and($dinas->fresh()->jadwal_ka_snapshot)->toBe($snapshot);

    $this->actingAs($recipient)
        ->post(route('user.serah-terima-dinas.konfirmasi', $serahTerima), [
            'tanda_tangan_penerima' => $signature,
        ])
        ->assertRedirect();

    $serahTerima->refresh();

    expect($serahTerima->status)->toBe('selesai')
        ->and($serahTerima->status_review)->toBe('menunggu_review')
        ->and($serahTerima->tanda_tangan_penerima)->not->toBeNull();

    expect($serahTerima->ringkasan)->toBe($storedSummary)
        ->and($dinas->fresh()->jadwal_ka_snapshot)->toBe($snapshot)
        ->and($dinas->fresh()->status)->toBe('berlangsung');

    $activity = ActivityLog::query()->sole();
    expect($activity->action)->toBe('confirm_recipient')
        ->and($activity->module)->toBe('serah_terima')
        ->and($activity->user_id)->toBe($recipient->id)
        ->and($activity->subject_type)->toBe(SerahTerima::class)
        ->and($activity->subject_id)->toBe($serahTerima->id)
        ->and($activity->old_values)->toBe([
            'dinas_id' => $dinas->id,
            'penerima_user_id' => $recipient->id,
            'status' => 'menunggu_penerima',
            'status_review' => 'menunggu_review',
        ])
        ->and($activity->new_values)->toBe([
            'dinas_id' => $dinas->id,
            'penerima_user_id' => $recipient->id,
            'status' => 'selesai',
            'status_review' => 'menunggu_review',
        ])
        ->and($activity->new_values)->not->toHaveKey('tanda_tangan_penerima')
        ->and($activity->new_values)->not->toHaveKey('ringkasan');

    Storage::disk('local')->assertExists($serahTerima->tanda_tangan_penerima);

    $signatureUrl = route('user.serah-terima-dinas.tanda-tangan', [$serahTerima, 'penerima']);
    $this->actingAs($recipient)
        ->get($signatureUrl)
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png')
        ->assertHeader('Cache-Control', 'no-store, private');

    $this->actingAs($serahTerima->dinas->user)
        ->get($signatureUrl)
        ->assertOk();

    $this->actingAs($outsider)
        ->get($signatureUrl)
        ->assertNotFound();

    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-ST-SIGNATURE');
    $this->actingAs($admin)
        ->get(route('admin.serah-terima-dinas.tanda-tangan', [$serahTerima, 'penerima']))
        ->assertOk();

    $this->actingAs($serahTerima->dinas->user)
        ->get(route('user.serah-terima-dinas.show', $serahTerima))
        ->assertOk()
        ->assertSeeText('Serah terima berstatus Sudah Diterima oleh')
        ->assertSeeText('Waktu konfirmasi:')
        ->assertViewHas('waktuPenerimaan', fn ($timestamp): bool => (string) $timestamp === (string) $activity->created_at);

    $this->actingAs($recipient)
        ->get(route('user.serah-terima-dinas.riwayat'))
        ->assertOk()
        ->assertSeeText('Sudah Diterima');

    $storedRecipientSignature = $serahTerima->tanda_tangan_penerima;

    $this->actingAs($recipient)
        ->post(route('user.serah-terima-dinas.konfirmasi', $serahTerima), [
            'tanda_tangan_penerima' => $signature,
        ])
        ->assertStatus(422);

    expect($serahTerima->fresh()->tanda_tangan_penerima)
        ->toBe($storedRecipientSignature)
        ->and($serahTerima->fresh()->ringkasan)->toBe($storedSummary)
        ->and($dinas->fresh()->jadwal_ka_snapshot)->toBe($snapshot);
});

test('the sender can save a PNG signature when submitting a Serah Terima', function () {
    [$sender, $recipient, $dinas, $serahTerima] = makeRecipientWorkflowSerahTerima($this);
    $snapshot = [
        'format_version' => 1,
        'snapshot_at' => now()->toIso8601String(),
        'dinas_id' => $dinas->id,
        'jpl_id' => $dinas->jpl_id,
        'shift_id' => $dinas->shift_id,
        'tanggal_dinas' => now()->toDateString(),
        'jadwal_ka_batch_id' => null,
        'source' => 'legacy',
        'jadwal' => [],
    ];
    $dinas->update(['jadwal_ka_snapshot' => $snapshot]);
    $serahTerima->delete();
    $signature = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVR4nGP4z8AAAAMBAQDJ/pLvAAAAAElFTkSuQmCC';
    Storage::fake('public');
    Storage::fake('local');
    $fotoBersama = UploadedFile::fake()->image('foto-bersama.png');

    $this->actingAs($sender)
        ->post(route('user.serah-terima-dinas.store'), [
            'penerima_user_id' => $recipient->id,
            'ringkasan' => json_encode(['kereta_api' => []]),
            'tanda_tangan_penyerah_action' => 'set',
            'tanda_tangan_penyerah' => $signature,
            'foto_bersama' => $fotoBersama,
        ])
        ->assertRedirect(route('user.serah-terima-dinas'));

    $serahTerima = SerahTerima::query()->sole();

    expect($serahTerima->tanda_tangan_penyerah)->not->toBeEmpty()
        ->and($serahTerima->tanda_tangan_penerima)->toBeNull()
        ->and($serahTerima->status)->toBe('menunggu_penerima')
        ->and($serahTerima->status_review)->toBe('menunggu_review');

    Storage::disk('local')->assertExists($serahTerima->tanda_tangan_penyerah);
    expect(data_get($serahTerima->ringkasan, 'foto_bukti_path'))
        ->toStartWith('serah-terima/bukti/');
    Storage::disk('local')->assertExists(
        data_get($serahTerima->ringkasan, 'foto_bukti_path')
    );
    expect($dinas->fresh()->jadwal_ka_snapshot)->toBe($snapshot);

    $activity = ActivityLog::query()->sole();
    expect($activity->action)->toBe('create')
        ->and($activity->module)->toBe('serah_terima')
        ->and($activity->user_id)->toBe($sender->id)
        ->and($activity->subject_type)->toBe(SerahTerima::class)
        ->and($activity->subject_id)->toBe($serahTerima->id)
        ->and($activity->old_values)->toBeNull()
        ->and($activity->new_values)->toBe([
            'dinas_id' => $dinas->id,
            'penerima_user_id' => $recipient->id,
            'status' => 'menunggu_penerima',
            'status_review' => 'menunggu_review',
            'sender_confirmation' => 'confirmed',
        ])
        ->and($activity->new_values)->not->toHaveKey('ringkasan')
        ->and($activity->new_values)->not->toHaveKey('tanda_tangan_penyerah');
});

test('the saved joint photo is private, visible to the sender and recipient, and unchanged by reading', function () {
    [$sender, $recipient, , $serahTerima] = makeRecipientWorkflowSerahTerima($this);
    Storage::fake('local');
    $path = 'serah-terima/bukti/receipt-proof.png';
    Storage::disk('local')->put($path, UploadedFile::fake()->image('proof.png')->getContent());
    $serahTerima->update([
        'ringkasan' => array_merge($serahTerima->ringkasan, [
            'foto_bukti_path' => $path,
        ]),
    ]);

    $this->actingAs($sender)
        ->get(route('user.serah-terima-dinas.foto-bukti', $serahTerima))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png')
        ->assertHeader('Cache-Control', 'no-store, private');

    $this->actingAs($recipient)
        ->get(route('user.serah-terima-dinas.show', $serahTerima))
        ->assertOk()
        ->assertSee(route('user.serah-terima-dinas.foto-bukti', $serahTerima));

    $this->actingAs($recipient)
        ->get(route('user.serah-terima-dinas.foto-bukti', $serahTerima))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png');

    expect(data_get($serahTerima->fresh()->ringkasan, 'foto_bukti_path'))->toBe($path);
    Storage::disk('local')->assertExists($path);
});

test('another PJL cannot retrieve a stored joint photo', function () {
    [$sender, , , $serahTerima] = makeRecipientWorkflowSerahTerima($this);
    $outsider = User::query()->create([
        'nip' => 'P-ST-PHOTO-OUTSIDER',
        'name' => 'Petugas luar foto',
        'role_id' => $sender->role_id,
        'password' => 'password123',
        'is_active' => true,
    ]);
    Storage::fake('local');
    $path = 'serah-terima/bukti/receipt-proof.png';
    Storage::disk('local')->put($path, UploadedFile::fake()->image('proof.png')->getContent());
    $serahTerima->update([
        'ringkasan' => array_merge($serahTerima->ringkasan, [
            'foto_bukti_path' => $path,
        ]),
    ]);

    $this->actingAs($outsider)
        ->get(route('user.serah-terima-dinas.foto-bukti', $serahTerima))
        ->assertNotFound();
});

test('an Admin cannot retrieve a PJL Serah Terima photo through the user endpoint', function () {
    [, , , $serahTerima] = makeRecipientWorkflowSerahTerima($this);
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-ST-PHOTO-ACCESS');

    $this->actingAs($admin)
        ->get(route('user.serah-terima-dinas.foto-bukti', $serahTerima))
        ->assertForbidden();
});

test('a sender cannot submit a new Serah Terima without a joint photo', function () {
    [$sender, $recipient, , $serahTerima] = makeRecipientWorkflowSerahTerima($this);
    $serahTerima->delete();

    $this->actingAs($sender)
        ->from(route('user.serah-terima-dinas'))
        ->post(route('user.serah-terima-dinas.store'), [
            'penerima_user_id' => $recipient->id,
            'ringkasan' => json_encode(['kereta_api' => []]),
            'tanda_tangan_penyerah_action' => 'set',
            'tanda_tangan_penyerah' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVR4nGP4z8AAAAMBAQDJ/pLvAAAAAElFTkSuQmCC',
        ])
        ->assertRedirect(route('user.serah-terima-dinas'))
        ->assertSessionHasErrors('foto_bersama');

    expect(SerahTerima::query()->count())->toBe(0);
});

test('a sender must add a photo when updating a legacy Serah Terima without evidence', function () {
    [$sender, $recipient, , $serahTerima] = makeRecipientWorkflowSerahTerima($this);
    $storedSummary = $serahTerima->ringkasan;

    $this->actingAs($sender)
        ->from(route('user.serah-terima-dinas.edit', $serahTerima))
        ->post(route('user.serah-terima-dinas.store'), [
            'serah_terima_id' => $serahTerima->id,
            'penerima_user_id' => $recipient->id,
            'ringkasan' => json_encode(['kereta_api' => []]),
            'tanda_tangan_penyerah_action' => 'keep',
        ])
        ->assertRedirect(route('user.serah-terima-dinas.edit', $serahTerima))
        ->assertSessionHasErrors('foto_bersama');

    expect($serahTerima->fresh()->ringkasan)->toBe($storedSummary)
        ->and(ActivityLog::query()->count())->toBe(0);
});

it('rejects unsupported and oversized joint photo uploads', function (UploadedFile $photo) {
    [$sender, $recipient, , $serahTerima] = makeRecipientWorkflowSerahTerima($this);
    $serahTerima->delete();
    Storage::fake('public');
    Storage::fake('local');

    $this->actingAs($sender)
        ->from(route('user.serah-terima-dinas'))
        ->post(route('user.serah-terima-dinas.store'), [
            'penerima_user_id' => $recipient->id,
            'ringkasan' => json_encode(['kereta_api' => []]),
            'tanda_tangan_penyerah_action' => 'set',
            'tanda_tangan_penyerah' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVR4nGP4z8AAAAMBAQDJ/pLvAAAAAElFTkSuQmCC',
            'foto_bersama' => $photo,
        ])
        ->assertRedirect(route('user.serah-terima-dinas'))
        ->assertSessionHasErrors('foto_bersama');

    expect(SerahTerima::query()->count())->toBe(0);
})->with([
    'unsupported format' => [UploadedFile::fake()->create('evidence.txt', 10, 'text/plain')],
    'over the 5 MB limit' => [UploadedFile::fake()->create('evidence.png', 5121, 'image/png')],
]);

test('a sender update and recipient confirmation each create one sanitized Serah Terima log', function () {
    [$sender, $recipient, $dinas, $serahTerima] = makeRecipientWorkflowSerahTerima($this);
    $dinas->update(['jadwal_ka_snapshot' => [
        'format_version' => 1,
        'snapshot_at' => now()->toIso8601String(),
        'dinas_id' => $dinas->id,
        'jpl_id' => $dinas->jpl_id,
        'shift_id' => $dinas->shift_id,
        'tanggal_dinas' => now()->toDateString(),
        'jadwal_ka_batch_id' => null,
        'source' => 'legacy',
        'jadwal' => [],
    ]]);
    Storage::fake('public');
    Storage::fake('local');
    $fotoBersama = UploadedFile::fake()->image('foto-bersama.png');
    $signature = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVR4nGP4z8AAAAMBAQDJ/pLvAAAAAElFTkSuQmCC';

    $this->actingAs($sender)
        ->post(route('user.serah-terima-dinas.store'), [
            'serah_terima_id' => $serahTerima->id,
            'penerima_user_id' => $recipient->id,
            'ringkasan' => json_encode(['catatan' => 'Catatan privat yang tidak boleh dicatat.']),
            'tanda_tangan_penyerah_action' => 'keep',
            'foto_bersama' => $fotoBersama,
        ])
        ->assertRedirect(route('user.serah-terima-dinas.riwayat'));

    expect(ActivityLog::query()->count())->toBe(1);
    $senderActivity = ActivityLog::query()->sole();
    expect($senderActivity->action)->toBe('update')
        ->and($senderActivity->description)->toContain('dikonfirmasi kembali oleh penyerah')
        ->and($senderActivity->new_values)->not->toHaveKey('ringkasan')
        ->and(json_encode($senderActivity->new_values))->not->toContain('Catatan privat');

    $this->actingAs($recipient)
        ->post(route('user.serah-terima-dinas.konfirmasi', $serahTerima), [
            'tanda_tangan_penerima' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVR4nGP4z8AAAAMBAQDJ/pLvAAAAAElFTkSuQmCC',
        ])
        ->assertRedirect();

    expect(ActivityLog::query()->count())->toBe(2)
        ->and(ActivityLog::query()->where('action', 'confirm_recipient')->count())->toBe(1);
});

test('a failed sender activity log rolls back the Serah Terima save', function () {
    [$sender, $recipient, $dinas, $serahTerima] = makeRecipientWorkflowSerahTerima($this);
    $dinas->update(['jadwal_ka_snapshot' => [
        'format_version' => 1,
        'snapshot_at' => now()->toIso8601String(),
        'dinas_id' => $dinas->id,
        'jpl_id' => $dinas->jpl_id,
        'shift_id' => $dinas->shift_id,
        'tanggal_dinas' => now()->toDateString(),
        'jadwal_ka_batch_id' => null,
        'source' => 'legacy',
        'jadwal' => [],
    ]]);
    $storedSummary = $serahTerima->ringkasan;
    Storage::fake('public');
    Storage::fake('local');
    $fotoBersama = UploadedFile::fake()->image('foto-bersama.png');
    $signature = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVR4nGP4z8AAAAMBAQDJ/pLvAAAAAElFTkSuQmCC';
    $this->mock(ActivityLogService::class)
        ->shouldReceive('log')
        ->once()
        ->andThrow(new RuntimeException('Activity log unavailable.'));

    $this->actingAs($sender)
        ->post(route('user.serah-terima-dinas.store'), [
            'serah_terima_id' => $serahTerima->id,
            'penerima_user_id' => $recipient->id,
            'ringkasan' => json_encode(['kereta_api' => []]),
            'tanda_tangan_penyerah_action' => 'set',
            'tanda_tangan_penyerah' => $signature,
            'foto_bersama' => $fotoBersama,
        ])
        ->assertServerError();

    expect($serahTerima->fresh()->ringkasan)->toBe($storedSummary)
        ->and($serahTerima->fresh()->status)->toBe('menunggu_penerima')
        ->and(ActivityLog::query()->count())->toBe(0);
    Storage::disk('local')->assertDirectoryEmpty('serah-terima/signatures/'.$sender->id);
    Storage::disk('local')->assertDirectoryEmpty('serah-terima/bukti');
});

test('a failed recipient activity log rolls back confirmation and signature storage', function () {
    [, $recipient, , $serahTerima] = makeRecipientWorkflowSerahTerima($this);
    $storedSummary = $serahTerima->ringkasan;
    Storage::fake('public');
    $signature = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVR4nGP4z8AAAAMBAQDJ/pLvAAAAAElFTkSuQmCC';
    $this->mock(ActivityLogService::class)
        ->shouldReceive('log')
        ->once()
        ->andThrow(new RuntimeException('Activity log unavailable.'));

    $this->actingAs($recipient)
        ->post(route('user.serah-terima-dinas.konfirmasi', $serahTerima), [
            'tanda_tangan_penerima' => $signature,
        ])
        ->assertServerError();

    expect($serahTerima->fresh()->status)->toBe('menunggu_penerima')
        ->and($serahTerima->fresh()->tanda_tangan_penerima)->toBeNull()
        ->and($serahTerima->fresh()->ringkasan)->toBe($storedSummary)
        ->and(ActivityLog::query()->count())->toBe(0);
    Storage::disk('local')->assertDirectoryEmpty('serah-terima/signatures/'.$recipient->id);
});

test('an Admin review is logged without recording its free-text revision note', function () {
    [, , , $serahTerima] = makeRecipientWorkflowSerahTerima($this);
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-ST-REVIEW-LOG');

    $this->actingAs($admin)
        ->patch(route('admin.serah-terima-dinas.update', $serahTerima), [
            'status_review' => 'perlu_revisi',
            'catatan_revisi' => 'Catatan revisi rahasia.',
        ])
        ->assertRedirect(route('admin.serah-terima-dinas.show', $serahTerima));

    $serahTerima->refresh();
    $activity = ActivityLog::query()->sole();

    expect($serahTerima->status_review)->toBe('perlu_revisi')
        ->and($activity->action)->toBe('review')
        ->and($activity->module)->toBe('serah_terima')
        ->and($activity->user_id)->toBe($admin->id)
        ->and($activity->subject_type)->toBe(SerahTerima::class)
        ->and($activity->subject_id)->toBe($serahTerima->id)
        ->and($activity->old_values)->toBe(['status_review' => 'menunggu_review'])
        ->and($activity->new_values)->toBe(['status_review' => 'perlu_revisi']);

    expect(json_encode($activity->new_values))->not->toContain('Catatan revisi rahasia.');
});

test('the sender can replace a signature after an admin requests revision', function () {
    [$sender, $recipient, $dinas, $serahTerima] = makeRecipientWorkflowSerahTerima($this, [
        'status' => 'selesai',
        'status_review' => 'perlu_revisi',
    ]);
    $snapshot = [
        'format_version' => 1,
        'snapshot_at' => now()->toIso8601String(),
        'dinas_id' => $dinas->id,
        'jpl_id' => $dinas->jpl_id,
        'shift_id' => $dinas->shift_id,
        'tanggal_dinas' => now()->toDateString(),
        'jadwal_ka_batch_id' => null,
        'source' => 'legacy',
        'jadwal' => [],
    ];
    $dinas->update(['jadwal_ka_snapshot' => $snapshot]);
    $oldSignature = $serahTerima->tanda_tangan_penyerah;
    $signature = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVR4nGP4z8AAAAMBAQDJ/pLvAAAAAElFTkSuQmCC';
    Storage::fake('public');
    Storage::fake('local');
    $fotoBersama = UploadedFile::fake()->image('foto-bersama.png');
    Storage::disk('public')->put($oldSignature, 'old signature');

    $this->actingAs($sender)
        ->post(route('user.serah-terima-dinas.store'), [
            'serah_terima_id' => $serahTerima->id,
            'penerima_user_id' => $recipient->id,
            'ringkasan' => json_encode(['kereta_api' => []]),
            'tanda_tangan_penyerah_action' => 'set',
            'tanda_tangan_penyerah' => $signature,
            'foto_bersama' => $fotoBersama,
        ])
        ->assertRedirect(route('user.serah-terima-dinas.riwayat'));

    $serahTerima->refresh();

    expect($serahTerima->tanda_tangan_penyerah)->not->toBe($oldSignature)
        ->and($serahTerima->tanda_tangan_penerima)->toBeNull()
        ->and($serahTerima->status)->toBe('menunggu_penerima')
        ->and($serahTerima->status_review)->toBe('menunggu_review');

    Storage::disk('public')->assertMissing($oldSignature);
    Storage::disk('local')->assertExists($serahTerima->tanda_tangan_penyerah);
    expect($dinas->fresh()->jadwal_ka_snapshot)->toBe($snapshot);
});

test('the sender cannot change either signature after an admin locks the Serah Terima', function () {
    [$sender, $recipient, $dinas, $serahTerima] = makeRecipientWorkflowSerahTerima($this, [
        'status' => 'selesai',
        'status_review' => 'disetujui',
        'tanda_tangan_penerima' => 'serah-terima/recipient-approved.png',
    ]);
    $snapshot = [
        'format_version' => 1,
        'snapshot_at' => now()->toIso8601String(),
        'dinas_id' => $dinas->id,
        'jpl_id' => $dinas->jpl_id,
        'shift_id' => $dinas->shift_id,
        'tanggal_dinas' => now()->toDateString(),
        'jadwal_ka_batch_id' => null,
        'source' => 'legacy',
        'jadwal' => [],
    ];
    $dinas->update(['jadwal_ka_snapshot' => $snapshot]);
    $storedSummary = $serahTerima->ringkasan;
    $storedSenderSignature = $serahTerima->tanda_tangan_penyerah;
    $storedRecipientSignature = $serahTerima->tanda_tangan_penerima;
    $signature = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVR4nGP4z8AAAAMBAQDJ/pLvAAAAAElFTkSuQmCC';
    Storage::fake('public');
    Storage::disk('public')->put($storedSenderSignature, 'stored sender signature');
    Storage::disk('public')->put($storedRecipientSignature, 'stored recipient signature');

    $this->actingAs($sender)
        ->from(route('user.serah-terima-dinas.edit', $serahTerima))
        ->post(route('user.serah-terima-dinas.store'), [
            'serah_terima_id' => $serahTerima->id,
            'penerima_user_id' => $recipient->id,
            'ringkasan' => json_encode(['kereta_api' => []]),
            'tanda_tangan_penyerah_action' => 'set',
            'tanda_tangan_penyerah' => $signature,
        ])
        ->assertRedirect(route('user.serah-terima-dinas.edit', $serahTerima))
        ->assertSessionHasErrors('dinas');

    $serahTerima->refresh();

    expect($serahTerima->tanda_tangan_penyerah)->toBe($storedSenderSignature)
        ->and($serahTerima->tanda_tangan_penerima)->toBe($storedRecipientSignature)
        ->and($serahTerima->ringkasan)->toBe($storedSummary)
        ->and($serahTerima->status)->toBe('selesai')
        ->and($serahTerima->status_review)->toBe('disetujui')
        ->and($dinas->fresh()->jadwal_ka_snapshot)->toBe($snapshot);

    Storage::disk('public')->assertExists($storedSenderSignature);
    Storage::disk('public')->assertExists($storedRecipientSignature);
});

it('rejects empty and invalid sender signature payloads without saving the Serah Terima', function (?string $signature) {
    [$sender, $recipient, $dinas] = makeRecipientWorkflowSerahTerima($this);
    $snapshot = ['format_version' => 1, 'dinas_id' => $dinas->id, 'jpl_id' => $dinas->jpl_id, 'jadwal' => []];
    $dinas->update(['jadwal_ka_snapshot' => $snapshot]);
    Storage::fake('public');
    Storage::fake('local');
    $payload = [
        'penerima_user_id' => $recipient->id,
        'ringkasan' => json_encode(['kereta_api' => []]),
        'tanda_tangan_penyerah_action' => 'set',
        'foto_bersama' => UploadedFile::fake()->image('foto-bersama.png'),
    ];

    if ($signature !== null) {
        $payload['tanda_tangan_penyerah'] = $signature;
    }

    $this->actingAs($sender)
        ->from(route('user.serah-terima-dinas'))
        ->post(route('user.serah-terima-dinas.store'), $payload)
        ->assertRedirect(route('user.serah-terima-dinas'))
        ->assertSessionHasErrors('tanda_tangan_penyerah');

    expect(SerahTerima::query()->count())->toBe(1)
        ->and($dinas->fresh()->jadwal_ka_snapshot)->toBe($snapshot);

    Storage::disk('local')->assertDirectoryEmpty('serah-terima/signatures/'.$sender->id);
})->with([
    'empty signature' => [null],
    'invalid data URL' => ['not-an-image'],
    'invalid PNG bytes' => ['data:image/png;base64,'.base64_encode('not a PNG image')],
]);

it('rejects empty and invalid recipient signatures without confirming the Serah Terima', function (?string $signature) {
    [$sender, $recipient, $dinas, $serahTerima] = makeRecipientWorkflowSerahTerima($this);
    $snapshot = ['format_version' => 1, 'dinas_id' => $dinas->id, 'jpl_id' => $dinas->jpl_id, 'jadwal' => []];
    $dinas->update(['jadwal_ka_snapshot' => $snapshot]);
    $storedSummary = $serahTerima->ringkasan;
    Storage::fake('public');
    $payload = [];

    if ($signature !== null) {
        $payload['tanda_tangan_penerima'] = $signature;
    }

    $this->actingAs($recipient)
        ->from(route('user.serah-terima-dinas.show', $serahTerima))
        ->post(route('user.serah-terima-dinas.konfirmasi', $serahTerima), $payload)
        ->assertRedirect(route('user.serah-terima-dinas.show', $serahTerima))
        ->assertSessionHasErrors('tanda_tangan_penerima');

    expect($serahTerima->fresh()->status)->toBe('menunggu_penerima')
        ->and($serahTerima->fresh()->tanda_tangan_penerima)->toBeNull()
        ->and($serahTerima->fresh()->ringkasan)->toBe($storedSummary)
        ->and($dinas->fresh()->jadwal_ka_snapshot)->toBe($snapshot);

    Storage::disk('local')->assertDirectoryEmpty('serah-terima/signatures/'.$recipient->id);
})->with([
    'empty signature' => [null],
    'invalid data URL' => ['not-an-image'],
    'invalid PNG bytes' => ['data:image/png;base64,'.base64_encode('not a PNG image')],
]);

test('a PJL who is not the sender cannot change the sender signature', function () {
    [$sender, , $dinas, $serahTerima] = makeRecipientWorkflowSerahTerima($this);
    $outsider = User::query()->create([
        'nip' => 'P-ST-SIGNATURE-OUTSIDER',
        'name' => 'Petugas luar tanda tangan',
        'role_id' => $sender->role_id,
        'password' => 'password123',
        'is_active' => true,
    ]);
    $storedSignature = $serahTerima->tanda_tangan_penyerah;
    $storedSummary = $serahTerima->ringkasan;
    $snapshot = ['format_version' => 1, 'dinas_id' => $dinas->id, 'jpl_id' => $dinas->jpl_id, 'jadwal' => []];
    $dinas->update(['jadwal_ka_snapshot' => $snapshot]);
    Storage::fake('public');
    $signature = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVR4nGP4z8AAAAMBAQDJ/pLvAAAAAElFTkSuQmCC';

    $this->actingAs($outsider)
        ->post(route('user.serah-terima-dinas.store'), [
            'serah_terima_id' => $serahTerima->id,
            'penerima_user_id' => $serahTerima->penerima_user_id,
            'ringkasan' => json_encode(['kereta_api' => []]),
            'tanda_tangan_penyerah_action' => 'set',
            'tanda_tangan_penyerah' => $signature,
        ])
        ->assertNotFound();

    expect($serahTerima->fresh()->tanda_tangan_penyerah)->toBe($storedSignature)
        ->and($serahTerima->fresh()->ringkasan)->toBe($storedSummary)
        ->and($dinas->fresh()->jadwal_ka_snapshot)->toBe($snapshot);

    Storage::disk('local')->assertDirectoryEmpty('serah-terima/signatures/'.$outsider->id);
});

test('a failed database save removes the new signature and preserves stored history and snapshot', function () {
    [$sender, $recipient, $dinas, $serahTerima] = makeRecipientWorkflowSerahTerima($this);
    $snapshot = ['format_version' => 1, 'dinas_id' => $dinas->id, 'jpl_id' => $dinas->jpl_id, 'jadwal' => []];
    $dinas->update(['jadwal_ka_snapshot' => $snapshot]);
    $storedSummary = $serahTerima->ringkasan;
    $storedSignature = $serahTerima->tanda_tangan_penyerah;
    Storage::fake('public');
    Storage::fake('local');
    $fotoBersama = UploadedFile::fake()->image('foto-bersama.png');
    Storage::disk('public')->put($storedSignature, 'existing signature');
    SerahTerima::updating(function (SerahTerima $record): void {
        if ($record->isDirty('tanda_tangan_penyerah')) {
            throw new RuntimeException('Simulated database persistence failure.');
        }
    });
    $signature = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVR4nGP4z8AAAAMBAQDJ/pLvAAAAAElFTkSuQmCC';

    $this->actingAs($sender)
        ->post(route('user.serah-terima-dinas.store'), [
            'serah_terima_id' => $serahTerima->id,
            'penerima_user_id' => $recipient->id,
            'ringkasan' => json_encode(['kereta_api' => [['nomor' => 'CHANGED']]]),
            'tanda_tangan_penyerah_action' => 'set',
            'tanda_tangan_penyerah' => $signature,
            'foto_bersama' => $fotoBersama,
        ])
        ->assertServerError();

    $serahTerima->refresh();

    expect(SerahTerima::query()->count())->toBe(1)
        ->and($serahTerima->tanda_tangan_penyerah)->toBe($storedSignature)
        ->and($serahTerima->ringkasan)->toBe($storedSummary)
        ->and($serahTerima->status)->toBe('menunggu_penerima')
        ->and($serahTerima->status_review)->toBe('menunggu_review')
        ->and($dinas->fresh()->jadwal_ka_snapshot)->toBe($snapshot);

    Storage::disk('public')->assertExists($storedSignature);
    Storage::disk('local')->assertDirectoryEmpty('serah-terima/signatures/'.$sender->id);
    Storage::disk('local')->assertDirectoryEmpty('serah-terima/bukti');
});

test('Admin cannot access PJL Serah Terima recipient routes', function () {
    [, , , $serahTerima] = makeRecipientWorkflowSerahTerima($this);
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-ST-ACCESS');

    $this->actingAs($admin)
        ->get(route('user.serah-terima-dinas.riwayat'))
        ->assertForbidden();

    $this->actingAs($admin)
        ->post(route('user.serah-terima-dinas.konfirmasi', $serahTerima), [
            'tanda_tangan_penerima' => 'not-a-signature',
        ])
        ->assertForbidden();
});

test('PJL Serah Terima lookup and mutation routes have scoped rate limits', function () {
    $limits = [
        'user.serah-terima-dinas.cari-penerima' => 'throttle:30,1',
        'user.serah-terima-dinas.konfirmasi' => 'throttle:10,1',
        'user.serah-terima-dinas.catatan.store' => 'throttle:20,1',
        'user.serah-terima-dinas.inventaris.store' => 'throttle:60,1',
        'user.serah-terima-dinas.store' => 'throttle:10,1',
    ];

    foreach ($limits as $routeName => $throttle) {
        $route = Route::getRoutes()->getByName($routeName);

        expect($route)->not->toBeNull();
        expect($route->gatherMiddleware())
            ->toContain('auth')
            ->toContain('role:pjl')
            ->toContain($throttle);
    }

    $legacyStoreRoute = Route::getRoutes()->match(
        Request::create('/user/serah-terima-dinas', 'POST')
    );

    expect($legacyStoreRoute->gatherMiddleware())
        ->toContain('auth')
        ->toContain('role:pjl')
        ->toContain('throttle:10,1');

    $adminReviewRoute = Route::getRoutes()->getByName(
        'admin.serah-terima-dinas.update'
    );

    expect($adminReviewRoute->gatherMiddleware())
        ->toContain('auth')
        ->toContain('role:admin,superadmin')
        ->toContain('throttle:10,1');
});
