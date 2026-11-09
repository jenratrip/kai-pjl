<?php

use App\Models\Inventaris;
use Illuminate\Support\Facades\Route;
use Tests\Support\CreatesKaiInMemorySchema;
use Tests\Support\KaiTestRecords;

uses(CreatesKaiInMemorySchema::class, KaiTestRecords::class);

beforeEach(function () {
    $this->createKaiInMemorySchema();
});

test('Superadmin can view inventory list, JPL detail, and item detail without changing records', function () {
    $superadmin = $this->kaiUser($this->kaiRole('superadmin'), 'SA-INVENTORY-READ');
    $jpl = $this->kaiJpl('JPL-INVENTORY-READ');
    $inventaris = Inventaris::query()->create([
        'jpl_id' => $jpl->id,
        'nama_barang' => 'Senter Pemeriksaan',
        'kategori' => 'alat_pendukung',
        'kode_barang' => 'INV-READ-001',
        'jumlah' => 4,
        'is_active' => true,
        'kondisi_terakhir' => 'baik',
    ]);
    $originalAttributes = $inventaris->fresh()->getAttributes();

    $this->actingAs($superadmin)
        ->get(route('superadmin.inventaris.index'))
        ->assertOk()
        ->assertSee(route('superadmin.inventaris.index'))
        ->assertSee($jpl->kode_jpl)
        ->assertSee($jpl->nama_jpl);

    $this->get(route('superadmin.inventaris.jpl.show', $jpl->id))
        ->assertOk()
        ->assertSee($inventaris->nama_barang)
        ->assertSee($inventaris->kode_barang);

    $this->get(route('superadmin.inventaris.show', $inventaris->id))
        ->assertOk()
        ->assertSee($inventaris->nama_barang)
        ->assertSee($jpl->kode_jpl);

    expect($inventaris->fresh()->getAttributes())->toBe($originalAttributes);
});

test('Admin and PJL cannot access Superadmin inventory pages', function (string $roleName) {
    $user = $this->kaiUser($this->kaiRole($roleName), 'DENIED-INVENTORY-'.$roleName);
    $jpl = $this->kaiJpl('JPL-DENIED-INVENTORY-'.$roleName);
    $inventaris = Inventaris::query()->create([
        'jpl_id' => $jpl->id,
        'nama_barang' => 'Barang Terbatas',
        'kategori' => 'alat_pendukung',
        'jumlah' => 1,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->get(route('superadmin.inventaris.index'))
        ->assertForbidden();

    $this->get(route('superadmin.inventaris.jpl.show', $jpl->id))
        ->assertForbidden();

    $this->get(route('superadmin.inventaris.show', $inventaris->id))
        ->assertForbidden();
})->with([
    'admin' => 'admin',
    'pjl' => 'pjl',
]);

test('Superadmin inventory exposes only GET routes and no mutation endpoints', function () {
    $routeNames = array_keys(Route::getRoutes()->getRoutesByName());
    $inventoryRouteNames = array_values(array_filter(
        $routeNames,
        fn (string $routeName): bool => str_starts_with($routeName, 'superadmin.inventaris.')
    ));

    sort($inventoryRouteNames);

    expect($inventoryRouteNames)->toBe([
        'superadmin.inventaris.index',
        'superadmin.inventaris.jpl.show',
        'superadmin.inventaris.show',
    ]);

    foreach ($inventoryRouteNames as $routeName) {
        $route = Route::getRoutes()->getByName($routeName);
        $methods = $route->methods();

        $this->assertContains('GET', $methods);
        $this->assertNotContains('POST', $methods);
        $this->assertNotContains('PUT', $methods);
        $this->assertNotContains('PATCH', $methods);
        $this->assertNotContains('DELETE', $methods);
    }

    expect(Route::has('superadmin.inventaris.create'))->toBeFalse()
        ->and(Route::has('superadmin.inventaris.store'))->toBeFalse()
        ->and(Route::has('superadmin.inventaris.edit'))->toBeFalse()
        ->and(Route::has('superadmin.inventaris.update'))->toBeFalse()
        ->and(Route::has('superadmin.inventaris.destroy'))->toBeFalse();
});
