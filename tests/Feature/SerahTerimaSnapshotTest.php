<?php

use App\Models\ActivityLog;
use App\Models\Inventaris;
use App\Models\RealisasiInventaris;
use App\Models\RealisasiKa;
use App\Models\SerahTerima;
use App\Services\DinasJadwalSnapshotService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Tests\Support\CreatesKaiInMemorySchema;
use Tests\Support\KaiTestRecords;

uses(CreatesKaiInMemorySchema::class, KaiTestRecords::class);

beforeEach(function () {
    $this->createKaiInMemorySchema();
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

test('Serah Terima uses the Dinas snapshot and exact shift boundaries without added tolerance', function () {
    CarbonImmutable::setTestNow('2026-11-05 09:00:00');
    $user = $this->kaiUser($this->kaiRole('pjl'), 'P-ST-SHIFT');
    $jpl = $this->kaiJpl();
    $shift = $this->kaiShift('08:00:00', '16:00:00');
    $train = $this->kaiTrain('SHIFT-BOUNDARY-KA');
    $dinas = $this->kaiDinas($user, $jpl, $shift, [
        'tanggal_dinas' => '2026-11-05',
    ]);

    $beforeShift = $this->kaiSchedule($jpl, $train, [
        'hari_berlaku' => ['Kamis'],
        'jam_lewat' => '07:59:00',
    ]);
    $atShiftStart = $this->kaiSchedule($jpl, $train, [
        'hari_berlaku' => ['Kamis'],
        'jam_lewat' => '08:00:00',
    ]);
    $atShiftEnd = $this->kaiSchedule($jpl, $train, [
        'hari_berlaku' => ['Kamis'],
        'jam_lewat' => '16:00:00',
    ]);
    $afterShift = $this->kaiSchedule($jpl, $train, [
        'hari_berlaku' => ['Kamis'],
        'jam_lewat' => '16:01:00',
    ]);

    $snapshot = app(DinasJadwalSnapshotService::class)->resolveAndStore((int) $dinas->id);

    foreach ([$beforeShift, $atShiftStart, $atShiftEnd, $afterShift] as $schedule) {
        RealisasiKa::query()->create([
            'dinas_id' => $dinas->id,
            'jadwal_ka_id' => $schedule->id,
            'kereta_api_id' => $train->id,
            'is_manual' => false,
            'status_lewat' => 'centang',
        ]);
    }

    expect(collect($snapshot['jadwal'])->pluck('jadwal_ka_id')->all())
        ->toBe([$atShiftStart->id, $atShiftEnd->id]);

    $this->actingAs($user)
        ->get(route('user.serah-terima-dinas'))
        ->assertOk()
        ->assertViewHas('dataRealisasiKA', function ($schedules) use ($atShiftStart, $atShiftEnd): bool {
            return $schedules->pluck('jadwal_ka_id')->all() === [$atShiftStart->id, $atShiftEnd->id];
        });
});

test('Serah Terima omits check detail blocks while stored check values remain unchanged', function () {
    $user = $this->kaiUser($this->kaiRole('pjl'), 'P-ST-INDICATORS');
    $jpl = $this->kaiJpl();
    $shift = $this->kaiShift();
    $train = $this->kaiTrain('INDICATOR-KA');
    $schedule = $this->kaiSchedule($jpl, $train);
    $dinas = $this->kaiDinas($user, $jpl, $shift, [
        'jadwal_ka_snapshot' => [
            'format_version' => 1,
            'snapshot_at' => now()->toIso8601String(),
            'dinas_id' => 1,
            'jpl_id' => $jpl->id,
            'shift_id' => $shift->id,
            'tanggal_dinas' => now()->toDateString(),
            'jadwal_ka_batch_id' => null,
            'source' => 'legacy',
            'jadwal' => [[
                'jadwal_ka_id' => $schedule->id,
                'kereta_api_id' => $train->id,
                'jpl_id' => $jpl->id,
                'nomor_ka' => $train->nomor_ka,
                'nama_ka' => $train->nama_ka,
                'jam_lewat' => '10:00:00',
            ]],
        ],
    ]);
    $snapshot = $dinas->jadwal_ka_snapshot;
    $snapshot['dinas_id'] = $dinas->id;
    $dinas->update(['jadwal_ka_snapshot' => $snapshot]);
    $realisasi = RealisasiKa::query()->create([
        'dinas_id' => $dinas->id,
        'jadwal_ka_id' => $schedule->id,
        'kereta_api_id' => $train->id,
        'status_lewat' => 'centang',
        'status_s21' => 'centang',
        'status_genta' => 'silang',
        'status_pintu_perlintasan' => null,
        'status_s35' => 'centang',
        'is_manual' => false,
    ]);

    $response = $this->actingAs($user)
        ->get(route('user.serah-terima-dinas'))
        ->assertOk()
        ->assertSee('serah-terima-ka-print-table')
        ->assertSee('S.21')
        ->assertSee('Pintu perlintasan');

    $screenList = preg_match(
        '/class="serah-terima-ka-list-screen">(.*?)<\/div>/s',
        $response->getContent(),
        $screenMatch
    ) ? $screenMatch[1] : '';

    expect($screenList)
        ->not->toContain('S.21')
        ->not->toContain('Pintu perlintasan');

    expect($realisasi->fresh()->status_s21)->toBe('centang')
        ->and($realisasi->fresh()->status_genta)->toBe('silang')
        ->and($realisasi->fresh()->status_pintu_perlintasan)->toBeNull();
});

test('Serah Terima indicator rendering supports old yes and no values and leaves empty values blank', function () {
    $html = view('user.serahterimacatatan.partials.ka-check-indicators', [
        'ka' => [
            'semboyan21' => 'ya',
            'genta' => 'tidak',
            'pintu' => '',
            'semboyan35' => null,
        ],
    ])->render();

    expect(substr_count($html, '✓'))->toBe(1)
        ->and(substr_count($html, '✗'))->toBe(1)
        ->and($html)->not->toContain('>ya<')
        ->not->toContain('>tidak<');
});

test('KA yang belum lewat hanya menampilkan nomor dan nama tanpa daftar pemeriksaan', function () {
    $html = view('user.serahterimacatatan.partials.ka-belum-lewat', [
        'ringkasanKA' => [
            'belum' => [[
                'nomor' => '193',
                'nama' => 'JOGLOSEMARKERTO',
                'semboyan21' => 'yes',
                'genta' => 'no',
                'pintu' => 'yes',
                'semboyan35' => 'yes',
            ]],
        ],
    ])->render();

    expect($html)
        ->toContain('193')
        ->toContain('JOGLOSEMARKERTO')
        ->toContain('serah-terima-ka-print-table');

    preg_match('/class="serah-terima-ka-list-screen">(.*?)<\/div>/s', $html, $screenList);

    expect($screenList[1] ?? '')
        ->not->toContain('S.21')
        ->not->toContain('Pintu perlintasan')
        ->not->toContain('Genta')
        ->not->toContain('S.35');
});

test('Serah Terima displays one screen list per KA category and a print table in source order', function () {
    $kaBelum = [
        ['nomor' => 'KA-B1', 'nama' => 'Nama Belum 1', 'jadwal' => '07:10', 'aktual' => '07:12', 'status' => 'BELUM', 'genta' => 'yes', 'semboyan21' => 'no', 'pintu' => '', 'semboyan35' => 'ya', 'keterangan' => 'Pemeriksaan pertama.'],
        ['nomor' => 'KA-B2', 'nama' => 'Nama Belum 2', 'jadwal' => '07:20', 'status' => 'BELUM'],
    ];
    $kaBatal = [
        ['nomor' => 'KA-X1', 'nama' => 'Nama Batal 1', 'jadwal' => '07:30', 'status' => 'BATAL', 'genta' => 'no', 'semboyan21' => 'yes', 'keterangan' => 'Pembatalan tercatat.'],
        ['nomor' => 'KA-X2', 'nama' => 'Nama Batal 2', 'jadwal' => '07:40', 'status' => 'BATAL'],
    ];
    $kaBerjalan = [
        ['nomor' => 'KA-J1', 'nama' => 'Nama Jalan 1', 'jadwal' => '07:50', 'aktual' => '07:51', 'status' => 'JALAN', 'genta' => 'yes', 'semboyan21' => 'yes', 'pintu' => 'yes', 'semboyan35' => 'yes', 'keterangan' => 'Catatan KA berjalan.'],
        ['nomor' => 'KA-J2', 'nama' => 'Nama Jalan 2', 'jadwal' => '08:00', 'status' => 'JALAN'],
    ];

    $renderedSections = [
        view('user.serahterimacatatan.partials.ka-belum-lewat', [
            'ringkasanKA' => ['belum' => $kaBelum],
        ])->render(),
        view('user.serahterimacatatan.partials.ka-batal', [
            'ringkasanKA' => ['batal' => $kaBatal],
        ])->render(),
        view('user.serahterimacatatan.partials.ka-berjalan', [
            'ringkasanKA' => ['berjalan' => $kaBerjalan],
        ])->render(),
    ];

    foreach ($renderedSections as $html) {
        expect(substr_count($html, '<textarea'))->toBe(1)
            ->and(substr_count($html, '<table class="serah-terima-ka-print-table'))->toBe(1)
            ->and($html)->not->toContain('<article');

        preg_match('/class="serah-terima-ka-list-screen">(.*?)<\/div>/s', $html, $screenList);
        $screenHtml = $screenList[1] ?? '';
        expect($screenHtml)
            ->toContain('<textarea')
            ->not->toContain('<dl')
            ->not->toContain('<article')
            ->not->toContain('Genta')
            ->not->toContain('S.21')
            ->not->toContain('Pintu perlintasan')
            ->not->toContain('S.35');

        preg_match('/<textarea[^>]*>(.*?)<\/textarea>/s', $html, $matches);
        $listText = html_entity_decode($matches[1] ?? '', ENT_QUOTES | ENT_HTML5);
        $firstIndex = strpos($listText, '1');
        $secondIndex = strpos($listText, '2');

        expect($firstIndex)->not->toBeFalse()
            ->and($secondIndex)->not->toBeFalse()
            ->and($firstIndex)->toBeLessThan($secondIndex);
    }

    expect($renderedSections[0])
        ->toContain('serah-terima-ka-print-table');

    foreach ([
        [$renderedSections[0], 'KA-B1 - Nama Belum 1', 'KA-B2 - Nama Belum 2', 'KA-B1', 'KA-B2', 'Nama Belum 1', 'Nama Belum 2'],
        [$renderedSections[1], 'KA-X1 - Nama Batal 1', 'KA-X2 - Nama Batal 2', 'KA-X1', 'KA-X2', 'Nama Batal 1', 'Nama Batal 2'],
        [$renderedSections[2], 'KA-J1 - Nama Jalan 1', 'KA-J2 - Nama Jalan 2', 'KA-J1', 'KA-J2', 'Nama Jalan 1', 'Nama Jalan 2'],
    ] as [$html, $firstScreenName, $secondScreenName, $firstNumber, $secondNumber, $firstPrintName, $secondPrintName]) {
        preg_match('/class="serah-terima-ka-list-screen">(.*?)<\/div>/s', $html, $screenList);
        $screenHtml = $screenList[1] ?? '';
        preg_match('/<table class="serah-terima-ka-print-table[^"]*">(.*?)<\/table>/s', $html, $printTable);
        $printText = html_entity_decode($printTable[1] ?? '', ENT_QUOTES | ENT_HTML5);

        expect(substr_count($screenHtml, $firstScreenName))->toBe(1)
            ->and(substr_count($screenHtml, $secondScreenName))->toBe(1)
            ->and(strpos($screenHtml, $firstScreenName))->toBeLessThan(strpos($screenHtml, $secondScreenName))
            ->and(substr_count($printText, $firstNumber))->toBe(1)
            ->and(substr_count($printText, $secondNumber))->toBe(1)
            ->and(substr_count($printText, $firstPrintName))->toBe(1)
            ->and(substr_count($printText, $secondPrintName))->toBe(1)
            ->and(strpos($printText, $firstNumber))->toBeLessThan(strpos($printText, $secondNumber));
    }

    $printTemplate = file_get_contents(resource_path(
        'views/user/serahterimacatatan/partials/actions.blade.php'
    ));

    expect($printTemplate)
        ->toContain('.serah-terima-ka-list-screen')
        ->toContain('.op-header')
        ->toContain('.op-sidebar-overlay')
        ->toContain('.operasional-content')
        ->toContain('padding: 0 !important;')
        ->toContain('display: none !important;')
        ->toContain('overflow-wrap: anywhere;')
        ->toContain('break-inside: avoid;')
        ->toContain('display: table-header-group;')
        ->toContain('size: A4 landscape;')
        ->toContain('break-after: avoid-page;');

    preg_match_all(
        '/\.serah-terima-ka-print-table--details \.serah-terima-ka-print-col-[a-z-]+\s*\{\s*width:\s*(\d+)%;/s',
        $printTemplate,
        $columnWidths
    );

    expect(array_sum(array_map('intval', $columnWidths[1] ?? [])))->toBe(100);

    foreach ([$renderedSections[0], $renderedSections[1]] as $simpleSection) {
        preg_match('/<table class="serah-terima-ka-print-table[^"]*">(.*?)<\/table>/s', $simpleSection, $simpleTable);
        $simplePrintText = html_entity_decode($simpleTable[1] ?? '', ENT_QUOTES | ENT_HTML5);

        expect(substr_count($simplePrintText, '<th scope="col">'))->toBe(2)
            ->and($simplePrintText)
            ->not->toContain('Jadwal:')
            ->not->toContain('Aktual:')
            ->not->toContain('Kondisi')
            ->not->toContain('Pemeriksaan')
            ->not->toContain('Keterangan')
            ->not->toContain('07:10')
            ->not->toContain('07:12')
            ->not->toContain('Pemeriksaan pertama.')
            ->not->toContain('Pembatalan tercatat.');
    }

    expect($renderedSections[2])
        ->toContain('Jadwal: 07:50')
        ->toContain('Aktual: 07:51')
        ->toContain('JALAN')
        ->toContain('Genta: ✓')
        ->toContain('S.21: ✓')
        ->toContain('Pintu perlintasan: ✓')
        ->toContain('S.35: ✓')
        ->toContain('Catatan KA berjalan.')
        ->not->toContain('<textarea class="serah-terima-ka-print-table');

    expect(strpos($renderedSections[0], 'KA-B1 - Nama Belum 1'))
        ->toBeLessThan(strpos($renderedSections[0], 'KA-B2 - Nama Belum 2'));
});

test('saved Serah Terima summary remains the source for KA history after schedule changes', function () {
    $role = $this->kaiRole('pjl');
    $user = $this->kaiUser($role, 'P-ST-1');
    $jpl = $this->kaiJpl();
    $shift = $this->kaiShift();
    $train = $this->kaiTrain('OLD-HISTORY');
    $schedule = $this->kaiSchedule($jpl, $train);
    $dinas = $this->kaiDinas($user, $jpl, $shift);
    $storedSummary = [
        'kereta_api' => [[
            'nomor' => 'OLD-HISTORY',
            'nama' => 'Nama saat serah terima',
            'jadwal_ka_id' => $schedule->id,
            'jadwal' => '10:00',
            'status' => 'JALAN',
        ]],
    ];
    $serahTerima = SerahTerima::query()->create([
        'dinas_id' => $dinas->id,
        'penerima_user_id' => $user->id,
        'ringkasan' => $storedSummary,
        'status' => 'selesai',
        'status_review' => 'disetujui',
    ]);

    $schedule->update(['jam_lewat' => '22:15:00']);
    $train->update(['nomor_ka' => 'NEW-CURRENT']);

    $response = $this->actingAs($user)
        ->get(route('user.serah-terima-dinas.show', $serahTerima));

    $response->assertOk()->assertViewHas('dataRealisasiKA', function (Collection $result): bool {
        return $result->first()['nomor'] === 'OLD-HISTORY'
            && $result->first()['nama'] === 'Nama saat serah terima'
            && $result->first()['jadwal'] === '10:00';
    });

    expect($response->viewData('dataRealisasiKA'))->toHaveCount(1);
});

test('PJL inventory checks entered from Serah Terima are logged without notes', function () {
    $user = $this->kaiUser($this->kaiRole('pjl'), 'P-ST-INVENTORY-LOG');
    $jpl = $this->kaiJpl();
    $shift = $this->kaiShift();
    $dinas = $this->kaiDinas($user, $jpl, $shift);
    $item = Inventaris::query()->create([
        'nama_barang' => 'Lampu Pemeriksaan',
        'kategori' => 'alat_pendukung',
        'jpl_id' => $jpl->id,
        'jumlah' => 2,
        'is_active' => true,
        'kondisi_terakhir' => 'baik',
        'status_tinjauan' => 'normal',
    ]);

    $this->actingAs($user)
        ->post(route('user.serah-terima-dinas.inventaris.store'), [
            'inventaris_id' => $item->id,
            'kondisi' => 'baik',
            'jumlah_aktual' => 2,
            'catatan' => 'Catatan pemeriksaan pribadi.',
        ])
        ->assertSessionHas('success');

    $activity = ActivityLog::query()->sole();
    expect($activity->action)->toBe('inspection')
        ->and($activity->module)->toBe('inventaris')
        ->and($activity->user_id)->toBe($user->id)
        ->and($activity->subject_type)->toBe(RealisasiInventaris::class)
        ->and($activity->new_values)->toMatchArray([
            'dinas_id' => $dinas->id,
            'inventaris_id' => $item->id,
            'status' => 'centang',
            'kondisi' => 'baik',
            'jumlah_aktual' => 2,
        ])
        ->and($activity->new_values)->not->toHaveKey('catatan');

    expect(json_encode($activity->new_values))->not->toContain('Catatan pemeriksaan pribadi.');
});

test('Serah Terima renders stored yes and no checks as symbols without changing values', function () {
    $user = $this->kaiUser($this->kaiRole('pjl'), 'P-ST-INDICATORS');
    $jpl = $this->kaiJpl();
    $shift = $this->kaiShift();
    $train = $this->kaiTrain('INDICATOR-KA');
    $schedule = $this->kaiSchedule($jpl, $train);
    $dinas = $this->kaiDinas($user, $jpl, $shift, [
        'jadwal_ka_snapshot' => [
            'format_version' => 1,
            'snapshot_at' => now()->toIso8601String(),
            'dinas_id' => 1,
            'jpl_id' => $jpl->id,
            'shift_id' => $shift->id,
            'tanggal_dinas' => now()->toDateString(),
            'jadwal_ka_batch_id' => null,
            'source' => 'legacy',
            'jadwal' => [[
                'jadwal_ka_id' => $schedule->id,
                'kereta_api_id' => $train->id,
                'jpl_id' => $jpl->id,
                'nomor_ka' => $train->nomor_ka,
                'nama_ka' => $train->nama_ka,
                'jam_lewat' => '10:00:00',
            ]],
        ],
    ]);
    $snapshot = $dinas->jadwal_ka_snapshot;
    $snapshot['dinas_id'] = $dinas->id;
    $dinas->update(['jadwal_ka_snapshot' => $snapshot]);
    $realisasi = RealisasiKa::query()->create([
        'dinas_id' => $dinas->id,
        'jadwal_ka_id' => $schedule->id,
        'kereta_api_id' => $train->id,
        'status_lewat' => 'centang',
        'status_s21' => 'centang',
        'status_genta' => 'silang',
        'status_pintu_perlintasan' => null,
        'status_s35' => 'centang',
        'is_manual' => false,
    ]);

    $this->actingAs($user)
        ->get(route('user.serah-terima-dinas'))
        ->assertOk()
        ->assertSee('✓')
        ->assertSee('✗')
        ->assertSee('S.21')
        ->assertSee('Pintu perlintasan');

    expect($realisasi->fresh()->status_s21)->toBe('centang')
        ->and($realisasi->fresh()->status_genta)->toBe('silang')
        ->and($realisasi->fresh()->status_pintu_perlintasan)->toBeNull();
});
