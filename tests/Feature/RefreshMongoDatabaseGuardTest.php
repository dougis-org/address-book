<?php

use Illuminate\Support\Facades\DB;

test('refusing to drop a database that is not the testing database', function () {
    config(['database.connections.mongodb.database' => 'production']);
    DB::purge('mongodb');

    expect(fn () => $this->refreshMongoDatabase())
        ->toThrow(RuntimeException::class, 'testing');
});
