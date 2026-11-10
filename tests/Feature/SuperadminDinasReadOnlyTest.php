<?php

use Illuminate\Support\Facades\Route;
use Tests\Support\CreatesKaiInMemorySchema;
use Tests\Support\KaiTestRecords;

uses(CreatesKaiInMemorySchema::class, KaiTestRecords::class);

beforeEach(function () {
    $this->createKaiInMemorySchema();
});

test('Superadmin can filter Dinas records without changing them', function () {
    $superadmin = $this->kaiUser($this->kaiRole('superadmin'), 'SA-DINAS-READ');
    $pjl = $this->kaiUser($this->kaiRole('pjl'), 'PJL-DINAS-READ');
    $jpl = $this->kaiJpl('JPL-DINAS-READ');
    $shift = $this->kaiShift();
    $dinas = $this->kaiDinas($pjl, $jpl, $shift, [
        'tanggal_dinas' => '2026-11-08',
        'status' => 'berlangsung',
    ]);
    $originalAttributes = $dinas->fresh()->getAttributes();

    $response = $this->actingAs($superadmin)
        ->get(route('superadmin.dinas.index', [
            'user_id' => $pjl->id,
            'tanggal_dinas' => '2026-11-08',
        ]));

    $response->assertOk()
        ->assertSee($pjl->name)
        ->assertSee($pjl->nip)
        ->assertSee($jpl->kode_jpl)
        ->assertViewHas('dinas', function ($records) use ($dinas): bool {
            return $records->total() === 1
                && $records->getCollection()->first()->is($dinas);
        });

    expect($dinas->fresh()->getAttributes())->toBe($originalAttributes)
        ->and(Route::getRoutes()->getByName('superadmin.dinas.index')->methods())
        ->toContain('GET')
        ->not->toContain('POST')
        ->not->toContain('PUT')
        ->not->toContain('PATCH')
        ->not->toContain('DELETE');
});

test('Admin and PJL cannot access the Superadmin Dinas list', function (string $roleName) {
    $user = $this->kaiUser($this->kaiRole($roleName), 'DENIED-DINAS-'.$roleName);

    $this->actingAs($user)
        ->get(route('superadmin.dinas.index'))
        ->assertForbidden();
})->with([
    'admin' => 'admin',
    'pjl' => 'pjl',
]);
