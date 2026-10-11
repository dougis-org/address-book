<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\DB;
use RuntimeException;

trait RefreshMongoDatabase
{
    /**
     * Start every test from an empty MongoDB database.
     */
    protected function refreshMongoDatabase(): void
    {
        $database = DB::connection('mongodb')->getDatabase();

        if ($database->getDatabaseName() !== 'testing') {
            throw new RuntimeException(
                "Refusing to drop MongoDB database [{$database->getDatabaseName()}]: tests must use the 'testing' database."
            );
        }

        $database->drop();
    }
}
