<?php

use App\Models\Dinas;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\Support\CreatesKaiInMemorySchema;
use Tests\Support\KaiTestRecords;

uses(CreatesKaiInMemorySchema::class, KaiTestRecords::class);

beforeEach(function () {
    $this->createKaiInMemorySchema();
});

/**
 * @param  array<int, string>  $codes
 */
function makeDinasRosterWorkbookUpload(array $codes, string $nip = 'PJL-001', string $jobTitle = 'JPL 351'): UploadedFile
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Jadwal Oktober 2026');
    $sheet->setCellValue('A1', 'JADWAL DINAS');
    $sheet->setCellValue('E3', 'BULAN OKTOBER 2026');
    $sheet->fromArray(['No.', 'NAMA', 'JABATAN', 'NIPKWT', 'TANGGAL'], null, 'A6');

    for ($day = 1; $day <= 31; $day++) {
        $column = Coordinate::stringFromColumnIndex(4 + $day);
        $sheet->setCellValue("{$column}7", $day);
        $sheet->setCellValue("{$column}8", $codes[$day] ?? 'P');
    }

    $sheet->fromArray([1, 'Petugas Contoh', $jobTitle, $nip], null, 'A8');
    $sheet->setCellValue('A19', 'P');
    $sheet->setCellValue('B19', 'Pagi');
    $sheet->setCellValue('A20', 'L');
    $sheet->setCellValue('B20', 'Libur');

    $temporaryPath = tempnam(sys_get_temp_dir(), 'roster-preview-');
    if ($temporaryPath === false) {
        throw new RuntimeException('File sementara workbook tidak dapat dibuat.');
    }

    try {
        (new Xlsx($spreadsheet))->save($temporaryPath);
        $contents = file_get_contents($temporaryPath);
    } finally {
        unlink($temporaryPath);
    }

    return UploadedFile::fake()->createWithContent('roster.xlsx', $contents === false ? '' : $contents);
}

/**
 * @param  list<array{name: string, job_title: string, nip: string, codes?: array<int, string>}>  $staff
 */
function makeDinasRosterWorkbookUploadForStaff(array $staff): UploadedFile
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Jadwal Dinas Oktober 2026');
    $sheet->fromArray(['No.', 'NAMA', 'JABATAN', 'NIPKWT', 'TANGGAL'], null, 'A6');

    for ($day = 1; $day <= 31; $day++) {
        $column = Coordinate::stringFromColumnIndex(4 + $day);
        $sheet->setCellValue("{$column}7", $day);
    }

    foreach ($staff as $index => $person) {
        $row = 8 + $index;
        $sheet->fromArray([$index + 1, $person['name'], $person['job_title'], $person['nip']], null, "A{$row}");

        for ($day = 1; $day <= 31; $day++) {
            $column = Coordinate::stringFromColumnIndex(4 + $day);
            $sheet->setCellValue("{$column}{$row}", $person['codes'][$day] ?? 'P');
        }
    }

    $temporaryPath = tempnam(sys_get_temp_dir(), 'roster-preview-');
    if ($temporaryPath === false) {
        throw new RuntimeException('File sementara workbook tidak dapat dibuat.');
    }

    try {
        (new Xlsx($spreadsheet))->save($temporaryPath);
        $contents = file_get_contents($temporaryPath);
    } finally {
        unlink($temporaryPath);
    }

    return UploadedFile::fake()->createWithContent('roster.xlsx', $contents === false ? '' : $contents);
}

/**
 * @return list<array{name: string, job_title: string, nip: string}>
 */
function makeFiftyRosterStaff(): array
{
    $staff = [];

    for ($index = 1; $index <= 50; $index++) {
        $jplCode = $index <= 17 ? '351' : ($index <= 34 ? '352' : '353');
        $staff[] = [
            'name' => "Petugas Dummy {$index}",
            'job_title' => "JPL {$jplCode}",
            'nip' => sprintf('NIP-%03d', $index),
        ];
    }

    return $staff;
}

function createRosterHolidayTable(): void
{
    Schema::create('jadwal_dinas_libur', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('user_id');
        $table->foreignId('jpl_id');
        $table->date('tanggal_dinas');
        $table->unique(['user_id', 'jpl_id', 'tanggal_dinas']);
        $table->timestamps();
    });
}

function createRosterPjl(string $nip, string $name = 'Akun PJL'): User
{
    $role = Role::query()->firstOrCreate(['name' => 'pjl'], ['label' => 'PJL']);

    return User::query()->create([
        'nip' => $nip,
        'name' => $name,
        'role_id' => $role->id,
        'password' => 'password123',
        'is_active' => true,
    ]);
}

it('previews the actual workbook layout and never writes roster or Dinas data', function () {
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-ROSTER-PREVIEW');
    createRosterPjl('PJL001', 'Akun Roster');
    $jpl351 = $this->kaiJpl('351');
    $this->kaiJpl('352');
    Shift::query()->create(['nama_shift' => 'Pagi', 'jam_mulai' => '06:00:00', 'jam_selesai' => '14:00:00']);
    Shift::query()->create(['nama_shift' => 'Siang', 'jam_mulai' => '14:00:00', 'jam_selesai' => '22:00:00']);
    Shift::query()->create(['nama_shift' => 'Malam', 'jam_mulai' => '22:00:00', 'jam_selesai' => '06:00:00']);

    $codes = array_fill(1, 31, 'P');
    $codes[1] = 'L';
    $codes[2] = 'S';
    $codes[3] = 'M';

    $response = $this->withoutVite()
        ->actingAs($admin)
        ->withHeader('Accept', 'application/json')
        ->post(route('admin.dinas.import.preview'), [
            'file' => makeDinasRosterWorkbookUpload($codes),
            'month' => 10,
            'year' => 2026,
        ]);

    $response->assertOk()
        ->assertJsonPath('validation.is_valid', true)
        ->assertJsonPath('validation.staff_count', 1)
        ->assertJsonPath('validation.invalid_staff_count', 0)
        ->assertJsonPath('preview.staff.0.assignment.status', 'cocok')
        ->assertJsonPath('preview.staff.0.assignment.jpl.id', $jpl351->id)
        ->assertJsonPath('preview.staff.0.assignment.jpl.code', '351');

    $preview = $response->json('preview');
    $this->assertSame('Petugas Contoh', $preview['staff'][0]['name']);
    $this->assertSame('PJL-001', $preview['staff'][0]['nipkwt']);
    $this->assertSame('cocok', $preview['staff'][0]['account']['status']);
    $this->assertTrue($preview['staff'][0]['days'][0]['holiday']);
    $this->assertSame('L', $preview['staff'][0]['days'][0]['code']);
    $this->assertSame('S', $preview['staff'][0]['days'][1]['code']);
    $this->assertSame('M', $preview['staff'][0]['days'][2]['code']);
    $this->assertSame('Akun Roster', $preview['staff'][0]['account']['name']);
    $shiftConfiguration = $response->json('shiftConfiguration');
    $this->assertSame('06:00:00', $shiftConfiguration['P']['start']);
    $this->assertSame('14:00:00', $shiftConfiguration['P']['end']);
    $this->assertSame('06:00:00', $shiftConfiguration['P']['actual_start']);
    $this->assertSame('14:00:00', $shiftConfiguration['P']['actual_end']);

    $this->assertDatabaseCount('dinas', 0);
});

it('marks a missing NIP account as not matched without using the workbook name', function () {
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-ROSTER-MISSING');

    $response = $this->withoutVite()
        ->actingAs($admin)
        ->withHeader('Accept', 'application/json')
        ->post(route('admin.dinas.import.preview'), [
            'file' => makeDinasRosterWorkbookUpload(array_fill(1, 31, 'P'), 'NO-ACCOUNT'),
            'month' => 10,
            'year' => 2026,
        ]);

    $response->assertOk();
    $account = $response->json('preview.staff.0.account');
    $this->assertSame('tidak_cocok', $account['status']);
    $this->assertSame('Akun dengan NIP yang cocok tidak ditemukan.', $account['detail']);
    $this->assertNull($account['name']);
});

it('maps JPL 352 from the workbook job title to the active JPL with the matching code', function () {
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-ROSTER-352');
    createRosterPjl('PJL001');
    $jpl352 = $this->kaiJpl('352');

    $response = $this->actingAs($admin)
        ->withHeader('Accept', 'application/json')
        ->post(route('admin.dinas.import.preview'), [
            'file' => makeDinasRosterWorkbookUpload(array_fill(1, 31, 'L'), 'PJL-001', 'JPL 352'),
            'month' => 10,
            'year' => 2026,
        ]);

    $response->assertOk()
        ->assertJsonPath('preview.staff.0.assignment.status', 'cocok')
        ->assertJsonPath('preview.staff.0.assignment.jpl.id', $jpl352->id)
        ->assertJsonPath('preview.staff.0.assignment.jpl.code', '352');
});

it('marks normalized duplicate NIP candidates as ambiguous', function () {
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-ROSTER-AMBIGUOUS');
    createRosterPjl('PJL001', 'Akun Satu');
    createRosterPjl('P-JL001', 'Akun Dua');

    $response = $this->withoutVite()
        ->actingAs($admin)
        ->withHeader('Accept', 'application/json')
        ->post(route('admin.dinas.import.preview'), [
            'file' => makeDinasRosterWorkbookUpload(array_fill(1, 31, 'P')),
            'month' => 10,
            'year' => 2026,
        ]);

    $response->assertOk();
    $account = $response->json('preview.staff.0.account');
    $this->assertSame('ambigu', $account['status']);
    $this->assertSame('Lebih dari satu akun memiliki NIP yang sama setelah normalisasi.', $account['detail']);
    $this->assertNull($account['name']);
});

it('marks all fifty staff valid when account, active JPL, shifts, and dates pass validation', function () {
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-ROSTER-50-VALID');
    $staff = makeFiftyRosterStaff();

    foreach ($staff as $person) {
        createRosterPjl($person['nip'], $person['name']);
    }

    foreach (['351', '352', '353'] as $code) {
        $this->kaiJpl($code);
    }

    Shift::query()->create(['nama_shift' => 'Pagi', 'jam_mulai' => '06:00:00', 'jam_selesai' => '14:00:00']);

    $response = $this->actingAs($admin)
        ->withHeader('Accept', 'application/json')
        ->post(route('admin.dinas.import.preview'), [
            'file' => makeDinasRosterWorkbookUploadForStaff($staff),
            'month' => 10,
            'year' => 2026,
        ]);

    $response->assertOk()
        ->assertJsonPath('validation.is_valid', true)
        ->assertJsonPath('validation.staff_count', 50)
        ->assertJsonPath('validation.valid_staff_count', 50)
        ->assertJsonPath('validation.invalid_staff_count', 0);

    $previewStaff = $response->json('preview.staff');
    $this->assertCount(50, $previewStaff);
    $this->assertSame(17, collect($previewStaff)->where('assignment.jpl.code', '351')->count());
    $this->assertSame(17, collect($previewStaff)->where('assignment.jpl.code', '352')->count());
    $this->assertSame(16, collect($previewStaff)->where('assignment.jpl.code', '353')->count());
    $this->assertDatabaseCount('dinas', 0);
});

it('invalidates the entire roster and keeps all fifty rows when JPL 353 is unavailable', function () {
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-ROSTER-353-MISSING');
    $staff = makeFiftyRosterStaff();

    foreach ($staff as $person) {
        createRosterPjl($person['nip'], $person['name']);
    }

    $this->kaiJpl('351');
    $this->kaiJpl('352');
    Shift::query()->create(['nama_shift' => 'Pagi', 'jam_mulai' => '06:00:00', 'jam_selesai' => '14:00:00']);

    $response = $this->actingAs($admin)
        ->withHeader('Accept', 'application/json')
        ->post(route('admin.dinas.import.preview'), [
            'file' => makeDinasRosterWorkbookUploadForStaff($staff),
            'month' => 10,
            'year' => 2026,
        ]);

    $response->assertOk()
        ->assertJsonPath('validation.is_valid', false)
        ->assertJsonPath('validation.staff_count', 50)
        ->assertJsonPath('validation.invalid_staff_count', 16);

    $previewStaff = $response->json('preview.staff');
    $this->assertCount(50, $previewStaff);
    $this->assertSame(16, collect($previewStaff)->where('assignment.status', 'tidak_cocok')->count());
    $this->assertDatabaseCount('dinas', 0);
});

it('invalidates the entire roster when one account is missing and still returns every employee', function () {
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-ROSTER-ACCOUNT-MISSING');
    $staff = makeFiftyRosterStaff();

    foreach (array_slice($staff, 1) as $person) {
        createRosterPjl($person['nip'], $person['name']);
    }

    foreach (['351', '352', '353'] as $code) {
        $this->kaiJpl($code);
    }
    Shift::query()->create(['nama_shift' => 'Pagi', 'jam_mulai' => '06:00:00', 'jam_selesai' => '14:00:00']);

    $response = $this->actingAs($admin)
        ->withHeader('Accept', 'application/json')
        ->post(route('admin.dinas.import.preview'), [
            'file' => makeDinasRosterWorkbookUploadForStaff($staff),
            'month' => 10,
            'year' => 2026,
        ]);

    $response->assertOk()
        ->assertJsonPath('validation.is_valid', false)
        ->assertJsonPath('validation.invalid_staff_count', 1);

    $previewStaff = $response->json('preview.staff');
    $this->assertCount(50, $previewStaff);
    $this->assertSame('tidak_cocok', $previewStaff[0]['account']['status']);
    $this->assertDatabaseCount('dinas', 0);
});

it('invalidates the entire roster when a workbook JPL code has multiple master candidates', function () {
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-ROSTER-JPL-AMBIGUOUS');
    createRosterPjl('PJL001');
    $this->kaiJpl('351');
    $this->kaiJpl('JPL 351');
    Shift::query()->create(['nama_shift' => 'Pagi', 'jam_mulai' => '06:00:00', 'jam_selesai' => '14:00:00']);

    $response = $this->actingAs($admin)
        ->withHeader('Accept', 'application/json')
        ->post(route('admin.dinas.import.preview'), [
            'file' => makeDinasRosterWorkbookUpload(array_fill(1, 31, 'P')),
            'month' => 10,
            'year' => 2026,
        ]);

    $response->assertOk()
        ->assertJsonPath('validation.is_valid', false)
        ->assertJsonPath('preview.staff.0.assignment.status', 'ambigu')
        ->assertJsonPath('validation.invalid_staff_count', 1);

    $this->assertDatabaseCount('dinas', 0);
});

it('does not expose a roster save endpoint or persist a partial roster', function () {
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-ROSTER-NO-SAVE');
    createRosterPjl('PJL001');
    $this->kaiJpl('351');
    Shift::query()->create(['nama_shift' => 'Pagi', 'jam_mulai' => '06:00:00', 'jam_selesai' => '14:00:00']);

    $this->actingAs($admin)
        ->withHeader('Accept', 'application/json')
        ->post(route('admin.dinas.import.preview'), [
            'file' => makeDinasRosterWorkbookUpload(array_fill(1, 31, 'P'), 'UNKNOWN'),
            'month' => 10,
            'year' => 2026,
        ])
        ->assertOk()
        ->assertJsonPath('validation.is_valid', false);

    expect(Route::has('admin.dinas.roster-import.store'))->toBeFalse();
    $this->assertDatabaseCount('dinas', 0);
});

it('stores a valid future roster atomically with its holidays and snapshots', function () {
    createRosterHolidayTable();
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-ROSTER-STORE');
    createRosterPjl('PJL001');
    $jpl = $this->kaiJpl('351');
    $shift = Shift::query()->create(['nama_shift' => 'Pagi', 'jam_mulai' => '06:00:00', 'jam_selesai' => '14:00:00']);
    $batch = $this->kaiBatch('active', '2026-11-08');
    $batch->jpls()->sync([$jpl->id]);
    $codes = array_fill(1, 31, 'P');
    $codes[1] = 'L';
    $staff = [[
        'name' => 'Petugas Contoh',
        'job_title' => 'JPL 351',
        'nip' => 'PJL001',
        'codes' => $codes,
    ]];

    $response = $this->actingAs($admin)
        ->withHeader('Accept', 'application/json')
        ->post(route('admin.dinas.import'), [
            'file' => makeDinasRosterWorkbookUploadForStaff($staff),
            'month' => 12,
            'year' => 2026,
        ]);

    $response->assertOk()
        ->assertJsonPath('imported_count', 30)
        ->assertJsonPath('holiday_count', 1);
    $this->assertDatabaseCount('dinas', 30);
    $this->assertDatabaseCount('jadwal_dinas_libur', 1);
    $this->assertDatabaseHas('jadwal_dinas_libur', [
        'user_id' => User::query()->where('nip', 'PJL001')->value('id'),
        'jpl_id' => $jpl->id,
        'tanggal_dinas' => '2026-12-01',
    ]);
    $this->assertSame($batch->id, Dinas::query()->firstOrFail()->jadwal_ka_batch_id);
    $this->assertDatabaseCount('activity_logs', 1);
});

it('blocks an October roster without a proven historical Jadwal KA source and writes nothing', function () {
    createRosterHolidayTable();
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-ROSTER-OCTOBER-BLOCK');
    createRosterPjl('PJL001');
    $this->kaiJpl('351');
    Shift::query()->create(['nama_shift' => 'Pagi', 'jam_mulai' => '06:00:00', 'jam_selesai' => '14:00:00']);

    $response = $this->actingAs($admin)
        ->withHeader('Accept', 'application/json')
        ->post(route('admin.dinas.import'), [
            'file' => makeDinasRosterWorkbookUpload(array_fill(1, 31, 'P')),
            'month' => 10,
            'year' => 2026,
        ])
        ->assertStatus(409)
        ->assertJsonPath('message', 'Penyimpanan roster diblokir. Selesaikan seluruh persyaratan penyimpanan terlebih dahulu.');

    expect(collect($response->json('errors'))->flatten()->first())
        ->toContain('sumber Jadwal KA historis tidak tersedia');

    $this->assertDatabaseCount('dinas', 0);
    $this->assertDatabaseCount('jadwal_dinas_libur', 0);
    $this->assertDatabaseCount('activity_logs', 0);
});

it('invalidates the entire roster when the matching JPL is inactive', function () {
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-ROSTER-JPL-INACTIVE');
    createRosterPjl('PJL001');
    $jpl = $this->kaiJpl('351');
    $jpl->update(['is_active' => false]);
    Shift::query()->create(['nama_shift' => 'Pagi', 'jam_mulai' => '06:00:00', 'jam_selesai' => '14:00:00']);

    $response = $this->actingAs($admin)
        ->withHeader('Accept', 'application/json')
        ->post(route('admin.dinas.import.preview'), [
            'file' => makeDinasRosterWorkbookUpload(array_fill(1, 31, 'P')),
            'month' => 10,
            'year' => 2026,
        ]);

    $response->assertOk()
        ->assertJsonPath('validation.is_valid', false)
        ->assertJsonPath('preview.staff.0.assignment.status', 'tidak_aktif')
        ->assertJsonPath('validation.invalid_staff_count', 1);
    $this->assertDatabaseCount('dinas', 0);
});

it('invalidates duplicate petugas instead of treating repeated rows as separate valid entries', function () {
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-ROSTER-DUPLICATE-STAFF');
    createRosterPjl('PJL001');
    $this->kaiJpl('351');
    Shift::query()->create(['nama_shift' => 'Pagi', 'jam_mulai' => '06:00:00', 'jam_selesai' => '14:00:00']);
    $staff = [
        ['name' => 'Petugas Satu', 'job_title' => 'JPL 351', 'nip' => 'PJL001'],
        ['name' => 'Petugas Duplikat', 'job_title' => 'JPL 351', 'nip' => 'P-JL001'],
    ];

    $response = $this->actingAs($admin)
        ->withHeader('Accept', 'application/json')
        ->post(route('admin.dinas.import.preview'), [
            'file' => makeDinasRosterWorkbookUploadForStaff($staff),
            'month' => 10,
            'year' => 2026,
        ]);

    $response->assertOk()
        ->assertJsonPath('validation.is_valid', false)
        ->assertJsonPath('validation.invalid_staff_count', 2);
    $this->assertCount(2, $response->json('preview.staff'));
    $this->assertDatabaseCount('dinas', 0);
});

it('reports unknown shift codes and invalid dates rather than treating them as a shift', function () {
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-ROSTER-CODE');

    $codes = array_fill(1, 31, 'P');
    $codes[7] = 'X';

    $response = $this->withoutVite()
        ->actingAs($admin)
        ->withHeader('Accept', 'application/json')
        ->post(route('admin.dinas.import.preview'), [
            'file' => makeDinasRosterWorkbookUpload($codes),
            'month' => 2,
            'year' => 2026,
        ]);

    $response->assertOk();
    $this->assertFalse($response->json('validation.is_valid'));
    $preview = $response->json('preview');
    $this->assertContains('Baris 8, tanggal 7: kode “X” tidak dikenal; gunakan P, S, M, atau L.', $preview['staff'][0]['errors']);
    $this->assertContains('Baris 8, kolom tanggal 29: jadwal terisi untuk tanggal yang tidak ada pada periode terpilih.', $preview['staff'][0]['errors']);
});

it('allows only Admin to generate the roster preview', function (string $role) {
    $user = $this->kaiUser($this->kaiRole($role), 'ROLE-ROSTER-'.$role);

    $this->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->post(route('admin.dinas.import.preview'), [
            'file' => makeDinasRosterWorkbookUpload(array_fill(1, 31, 'P')),
            'month' => 10,
            'year' => 2026,
        ])
        ->assertForbidden();
})->with(['superadmin', 'pjl']);

it('rejects blank or unknown workbook job titles instead of guessing a JPL', function (string $jobTitle) {
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-ROSTER-JPL');
    $this->kaiJpl('351');

    $response = $this->actingAs($admin)
        ->withHeader('Accept', 'application/json')
        ->post(route('admin.dinas.import.preview'), [
            'file' => makeDinasRosterWorkbookUpload(array_fill(1, 31, 'P'), 'PJL-001', $jobTitle),
            'month' => 10,
            'year' => 2026,
        ]);

    $response->assertOk()
        ->assertJsonPath('preview.staff.0.assignment.status', 'tidak_cocok')
        ->assertJsonPath('preview.staff.0.assignment.jpl', null);
})->with(['', 'Penempatan tidak diketahui']);

it('uses one import action for all formats on the existing Dinas page', function () {
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-ROSTER-TABS');

    $this->actingAs($admin)
        ->get(route('admin.dinas.index'))
        ->assertOk()
        ->assertSeeText('Impor Jadwal Dinas')
        ->assertSee('accept=".csv,.txt,.xlsx', false)
        ->assertDontSeeText('Dinas biasa (CSV)')
        ->assertDontSeeText('Roster bulanan (Excel)')
        ->assertSee(route('admin.dinas.import'), false);

    $this->assertFalse(Route::has('admin.dinas.roster-import'));
    $this->assertTrue(Route::has('admin.dinas.import.preview'));
});
