<?php

use Tests\Support\CreatesKaiInMemorySchema;
use Tests\Support\KaiTestRecords;

uses(CreatesKaiInMemorySchema::class, KaiTestRecords::class);

beforeEach(function () {
    $this->createKaiInMemorySchema();
});

test('Wilayah and Resort management routes and sidebar links are unavailable', function () {
    $routeNames = collect(app('router')->getRoutes()->getRoutes())
        ->map(fn ($route) => $route->getName())
        ->filter();

    expect($routeNames->filter(fn ($name) => str_starts_with($name, 'admin.wilayah.')))->toBeEmpty()
        ->and($routeNames->filter(fn ($name) => str_starts_with($name, 'admin.resort.')))->toBeEmpty()
        ->and($routeNames->filter(fn ($name) => str_starts_with($name, 'superadmin.wilayah.')))->toBeEmpty()
        ->and($routeNames->filter(fn ($name) => str_starts_with($name, 'superadmin.resort.')))->toBeEmpty()
        ->and(view()->exists('admin.wilayah.index'))->toBeFalse()
        ->and(view()->exists('admin.resort.index'))->toBeFalse();

    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-STATIC-DAOP');
    $this->actingAs($admin);
    $sidebar = view('components.admin.sidebar')->render();

    expect($sidebar)->toContain('JPL')
        ->and($sidebar)->not->toContain('route(\'admin.wilayah.index\')')
        ->and($sidebar)->not->toContain('route(\'admin.resort.index\')')
        ->and($sidebar)->not->toContain('>Wilayah</span>')
        ->and($sidebar)->not->toContain('>Resort</span>');

    $this->get('/admin/wilayah')->assertNotFound();
    $this->get('/admin/resort')->assertNotFound();
});
