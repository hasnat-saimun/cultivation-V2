<?php

namespace Tests\Unit;

use RuntimeException;
use Tests\Support\TestDatabaseSafety;
use PHPUnit\Framework\TestCase;

class TestDatabaseSafetyGuardTest extends TestCase
{
    public function test_rejects_cultivationbackup_database(): void
    {
        $this->expectException(RuntimeException::class);

        TestDatabaseSafety::assertSafe('testing', 'cultivationbackup');
    }

    public function test_rejects_cultivation_database(): void
    {
        $this->expectException(RuntimeException::class);

        TestDatabaseSafety::assertSafe('testing', 'cultivation');
    }

    public function test_rejects_non_testing_environment(): void
    {
        $this->expectException(RuntimeException::class);

        TestDatabaseSafety::assertSafe('local', 'cultivation_test');
    }

    public function test_accepts_cultivation_test_database(): void
    {
        TestDatabaseSafety::assertSafe('testing', 'cultivation_test');
        $this->assertTrue(true);
    }
}
