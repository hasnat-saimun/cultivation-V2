<?php

namespace Tests\Support;

use RuntimeException;

final class TestDatabaseSafety
{
    public static function assertSafe(string $appEnv, ?string $databaseName): void
    {
        $normalizedEnv = strtolower(trim($appEnv));
        $normalizedDb = strtolower(trim((string) $databaseName));

        if ($normalizedEnv !== 'testing') {
            throw new RuntimeException('APP_ENV must be testing for automated tests.');
        }

        if ($normalizedDb === '') {
            throw new RuntimeException('Database name is empty.');
        }

        if (!str_contains($normalizedDb, 'test')) {
            throw new RuntimeException('Database name must contain "test".');
        }

        if (in_array($normalizedDb, ['cultivation', 'cultivationbackup'], true)) {
            throw new RuntimeException('Unsafe operational database is blocked for tests.');
        }
    }
}
