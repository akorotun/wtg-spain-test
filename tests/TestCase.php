<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (config('app.env') !== 'testing') {
            throw new RuntimeException('Tests must run in the testing environment.');
        }

        if (config('database.default') !== 'mysql') {
            throw new RuntimeException('Tests must run on MySQL.');
        }

        $database = config('database.connections.mysql.database');
        if (!str_ends_with($database, '_test')) {
            throw new RuntimeException("Unsafe testing database: {$database}");
        }
    }
}
