<?php

use Illuminate\Support\Facades\DB;

test('migrations leave a unique index on users.email', function () {
    $this->artisan('migrate', ['--force' => true])->assertSuccessful();

    $indexes = iterator_to_array(DB::connection('mongodb')->getCollection('users')->listIndexes());

    $unique = collect($indexes)->first(
        fn ($index) => $index->isUnique() && array_keys((array) $index->getKey()) === ['email']
    );

    expect($unique)->not->toBeNull();
});

test('migrations create no SQL-only collections', function () {
    $this->artisan('migrate', ['--force' => true])->assertSuccessful();

    $names = collect(iterator_to_array(DB::connection('mongodb')->getDatabase()->listCollectionNames()));

    expect($names->all())->not->toContain('passkeys');
});
