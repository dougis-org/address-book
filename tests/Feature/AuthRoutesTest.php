<?php

use Illuminate\Support\Facades\Route;

test('auth routes are registered', function (string $name) {
    expect(Route::has($name))->toBeTrue();
})->with(['login', 'logout', 'password.request', 'password.reset', 'two-factor.login']);
