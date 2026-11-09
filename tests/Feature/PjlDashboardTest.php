<?php

use Illuminate\Support\Facades\Route;
use Tests\Support\CreatesKaiInMemorySchema;
use Tests\Support\KaiTestRecords;

uses(CreatesKaiInMemorySchema::class, KaiTestRecords::class);

beforeEach(function (): void {
    $this->createKaiInMemorySchema();
});

test('the removed PJL dashboard route is unavailable and the PJL entry redirects to Realisasi KA', function (): void {
    $user = $this->kaiUser($this->kaiRole('pjl'), 'NO-PJL-DASHBOARD');

    expect(Route::has('user.dashboard'))->toBeFalse();

    $this->actingAs($user)
        ->get('/user/dashboard')
        ->assertNotFound();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertRedirect(route('user.realisasi-ka'));
});
