<?php

use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SuperadminMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;

function roleRequest(string $method, string $role, bool $isActive = true): Request
{
    $request = Request::create('/admin/example', $method);
    $request->setUserResolver(fn () => (object) [
        'is_active' => $isActive,
        'role' => (object) ['name' => $role],
    ]);

    return $request;
}

it('allows superadmin to read a shared operational route', function () {
    $response = (new RoleMiddleware)->handle(
        roleRequest('GET', 'superadmin'),
        fn () => response('read-only'),
        'admin',
        'superadmin',
    );

    expect($response->getContent())->toBe('read-only');
});

it('denies superadmin mutations on a shared admin route', function () {
    $this->expectException(HttpException::class);

    (new RoleMiddleware)->handle(
        roleRequest('PATCH', 'superadmin'),
        fn () => response('must not run'),
        'admin',
        'superadmin',
    );
});

it('continues to allow admin mutations on a shared admin route', function () {
    $response = (new RoleMiddleware)->handle(
        roleRequest('PATCH', 'admin'),
        fn () => response('updated'),
        'admin',
        'superadmin',
    );

    expect($response->getContent())->toBe('updated');
});

it('denies pjl access to a shared admin route', function () {
    $this->expectException(HttpException::class);

    (new RoleMiddleware)->handle(
        roleRequest('GET', 'pjl'),
        fn () => response('must not run'),
        'admin',
        'superadmin',
    );
});

it('logs out an inactive superadmin before shared route access', function () {
    Auth::shouldReceive('logout')->once();

    $request = roleRequest('GET', 'superadmin', false);
    $request->setLaravelSession(app('session')->driver());

    $response = (new RoleMiddleware)->handle(
        $request,
        fn () => response('must not run'),
        'admin',
        'superadmin',
    );

    expect($response->getStatusCode())->toBe(302)
        ->and($response->headers->get('Location'))->toBe(route('login'));
});

it('logs out an inactive pjl before role-protected access', function () {
    Auth::shouldReceive('logout')->once();

    $request = roleRequest('POST', 'pjl', false);
    $request->setLaravelSession(app('session')->driver());

    $response = (new RoleMiddleware)->handle(
        $request,
        fn () => response('must not run'),
        'pjl',
    );

    expect($response->getStatusCode())->toBe(302)
        ->and($response->headers->get('Location'))->toBe(route('login'));
});

it('logs out an inactive account before superadmin route access', function () {
    Auth::shouldReceive('logout')->once();

    $request = roleRequest('GET', 'superadmin', false);
    $request->setLaravelSession(app('session')->driver());

    $response = (new SuperadminMiddleware)->handle(
        $request,
        fn () => response('must not run'),
    );

    expect($response->getStatusCode())->toBe(302)
        ->and($response->headers->get('Location'))->toBe(route('login'));
});

it('returns forbidden JSON and logs out an inactive account on ajax requests', function () {
    Auth::shouldReceive('logout')->once();

    $request = Request::create('/admin/example', 'POST', [], [], [], [
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
    ]);
    $request->setUserResolver(fn () => (object) [
        'is_active' => false,
        'role' => (object) ['name' => 'admin'],
    ]);
    $request->setLaravelSession(app('session')->driver());

    $response = (new RoleMiddleware)->handle(
        $request,
        fn () => response('must not run'),
        'admin',
    );

    expect($response->getStatusCode())->toBe(403)
        ->and($response->getData(true)['message'])->toContain('Akun tidak aktif');
});
