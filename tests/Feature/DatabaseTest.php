<?php

use Illuminate\Support\Facades\DB;

test('tests run against MongoDB', function () {
    expect(config('database.default'))->toBe('mongodb');
    expect(DB::connection()->getDriverName())->toBe('mongodb');
    expect(config('database.connections.mongodb.database'))->toBe('testing');

    $ping = DB::connection()->getDatabase()->command(['ping' => 1])->toArray();

    expect($ping[0]->ok)->toEqual(1);
});
