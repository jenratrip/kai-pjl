<?php

use App\Models\Resort;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesKaiInMemorySchema;
use Tests\Support\KaiTestRecords;
use Tests\TestCase;

uses(CreatesKaiInMemorySchema::class, KaiTestRecords::class);

beforeEach(function (): void {
    $this->createKaiInMemorySchema();
    Storage::fake('local');
});

function makeProfilePjl(TestCase $test, string $nip): User
{
    $role = Role::query()->firstOrCreate(
        ['name' => 'pjl'],
        ['label' => 'PJL']
    );

    return User::query()->create([
        'nip' => $nip,
        'name' => 'PJL Uji Profil',
        'role_id' => $role->id,
        'password' => 'password123',
        'is_active' => true,
    ]);
}

test('PJL can open profile with an initial avatar and profile links in the shared shell', function (): void {
    $user = makeProfilePjl($this, 'PROFILE-INITIAL');

    $this->actingAs($user)
        ->get(route('user.profile.show'))
        ->assertOk()
        ->assertSeeText('Profil Saya')
        ->assertSeeText('PU')
        ->assertSeeText('Informasi Petugas')
        ->assertSeeText('Keamanan Akun')
        ->assertSee('data-profile-photo-modal', false)
        ->assertDontSeeText('Ubah foto profil')
        ->assertSee(route('user.profile.photo.update'));
});

test('profile shows only identity fields and the resort of the Dinas in the owned session', function (): void {
    $user = makeProfilePjl($this, 'PROFILE-CONTEXT');
    $resort = Resort::query()->create(['nama_resort' => 'Resort Uji']);
    $jpl = $this->kaiJpl();
    $jpl->update(['resort_id' => $resort->id]);
    $dinas = $this->kaiDinas($user, $jpl, $this->kaiShift());

    $this->actingAs($user)
        ->withSession(['dinas_id' => $dinas->id])
        ->get(route('user.profile.show'))
        ->assertOk()
        ->assertSeeText('PJL Uji Profil')
        ->assertSeeText('PROFILE-CONTEXT')
        ->assertSeeText('Resort Uji')
        ->assertSeeText('Aktif');
});

test('PJL can change only their own password after verifying the current password', function (): void {
    $user = makeProfilePjl($this, 'PROFILE-PASSWORD');
    $other = makeProfilePjl($this, 'PROFILE-PASSWORD-OTHER');

    $this->actingAs($user)
        ->put(route('user.profile.password.update'), [
            'current_password' => 'password123',
            'password' => 'new-password-456',
            'password_confirmation' => 'new-password-456',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(Hash::check('new-password-456', $user->fresh()->password))->toBeTrue()
        ->and(Hash::check('password123', $other->fresh()->password))->toBeTrue();
});

test('PJL password update rejects an incorrect current password and mismatched confirmation', function (): void {
    $user = makeProfilePjl($this, 'PROFILE-PASSWORD-INVALID');

    $this->actingAs($user)
        ->from(route('user.profile.show'))
        ->put(route('user.profile.password.update'), [
            'current_password' => 'incorrect-password',
            'password' => 'new-password-456',
            'password_confirmation' => 'different-password',
        ])
        ->assertRedirect(route('user.profile.show'))
        ->assertSessionHasErrors(['current_password', 'password']);

    expect(Hash::check('password123', $user->fresh()->password))->toBeTrue();
});

test('PJL can upload a private profile photo for their own account', function (): void {
    $user = makeProfilePjl($this, 'PROFILE-UPLOAD');

    $this->actingAs($user)
        ->post(route('user.profile.photo.update'), [
            'photo' => UploadedFile::fake()->image('profil.png'),
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $path = $user->fresh()->photo;
    expect($path)->toStartWith('profile-photos/'.$user->id.'/');
    Storage::disk('local')->assertExists($path);

    $this->actingAs($user->fresh())
        ->get(route('user.profile.photo'))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png')
        ->assertHeader('Cache-Control', 'no-store, private');

    $profile = $this->actingAs($user->fresh())
        ->get(route('user.profile.show'))
        ->assertOk();
    expect(substr_count($profile->getContent(), route('user.profile.photo')))->toBeGreaterThanOrEqual(3);
});

test('replacing a profile photo keeps the old file until the new photo is saved', function (): void {
    $user = makeProfilePjl($this, 'PROFILE-REPLACE');
    $oldPath = 'profile-photos/'.$user->id.'/old.png';
    Storage::disk('local')->put($oldPath, UploadedFile::fake()->image('old.png')->getContent());
    $user->update(['photo' => $oldPath]);

    $this->actingAs($user)
        ->from(route('user.profile.show'))
        ->post(route('user.profile.photo.update'), [
            'photo' => UploadedFile::fake()->image('new.png'),
        ])
        ->assertRedirect(route('user.profile.show'));

    $newPath = $user->fresh()->photo;
    expect($newPath)->not->toBe($oldPath);
    Storage::disk('local')->assertMissing($oldPath);
    Storage::disk('local')->assertExists($newPath);
});

test('invalid image format and files over two megabytes are rejected without replacing the current photo', function (): void {
    $user = makeProfilePjl($this, 'PROFILE-VALIDATION');
    $oldPath = 'profile-photos/'.$user->id.'/current.png';
    Storage::disk('local')->put($oldPath, UploadedFile::fake()->image('current.png')->getContent());
    $user->update(['photo' => $oldPath]);

    $this->actingAs($user)
        ->from(route('user.profile.show'))
        ->post(route('user.profile.photo.update'), [
            'photo' => UploadedFile::fake()->create('profile.gif', 20, 'image/gif'),
        ])
        ->assertRedirect(route('user.profile.show'))
        ->assertSessionHasErrors('photo');

    $this->actingAs($user)
        ->from(route('user.profile.show'))
        ->post(route('user.profile.photo.update'), [
            'photo' => UploadedFile::fake()->image('large.png')->size(2049),
        ])
        ->assertRedirect(route('user.profile.show'))
        ->assertSessionHasErrors('photo');

    expect($user->fresh()->photo)->toBe($oldPath);
    Storage::disk('local')->assertExists($oldPath);
});

test('a PJL cannot view or mutate another user profile photo and an Admin cannot access profile routes', function (): void {
    $first = makeProfilePjl($this, 'PROFILE-FIRST');
    $other = makeProfilePjl($this, 'PROFILE-OTHER');
    $otherPath = 'profile-photos/'.$other->id.'/other.png';
    Storage::disk('local')->put($otherPath, UploadedFile::fake()->image('other.png')->getContent());
    $other->update(['photo' => $otherPath]);

    $this->actingAs($first)
        ->get(route('user.profile.photo').'?user_id='.$other->id)
        ->assertNotFound();

    $this->actingAs($first)
        ->post(route('user.profile.photo.update'), [
            'photo' => UploadedFile::fake()->image('first.png'),
        ])
        ->assertRedirect();

    expect($other->fresh()->photo)->toBe($otherPath);
    Storage::disk('local')->assertExists($otherPath);

    $adminRole = Role::query()->firstOrCreate(
        ['name' => 'admin'],
        ['label' => 'Admin']
    );
    $admin = $this->kaiUser($adminRole, 'PROFILE-ADMIN');
    $this->actingAs($admin)
        ->get(route('user.profile.show'))
        ->assertForbidden();
});

test('deleting a profile photo resets it to the default initials avatar', function (): void {
    $user = makeProfilePjl($this, 'PROFILE-DELETE');
    $path = 'profile-photos/'.$user->id.'/current.png';
    Storage::disk('local')->put($path, UploadedFile::fake()->image('current.png')->getContent());
    $user->update(['photo' => $path]);

    $this->actingAs($user)
        ->delete(route('user.profile.photo.destroy'))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($user->fresh()->photo)->toBeNull();
    Storage::disk('local')->assertMissing($path);
    $this->actingAs($user)
        ->get(route('user.profile.show'))
        ->assertOk()
        ->assertSeeText('PU');
});
