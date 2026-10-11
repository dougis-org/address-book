<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

test('a registered user is stored in the MongoDB users collection', function () {
    $this->post(route('register.store'), [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasNoErrors();

    expect(User::where('email', 'ada@example.com')->exists())->toBeTrue();
    expect(DB::connection('mongodb')->getCollection('users')->countDocuments(['email' => 'ada@example.com']))->toBe(1);
});

test('the database is reset between tests', function () {
    expect(User::count())->toBe(0);
});
