<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;
use Tests\Support\TestDatabaseSafety;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();

        $environment = (string) $app->environment();
        $defaultConnection = (string) $app['config']->get('database.default');
        $databaseName = (string) $app['config']->get("database.connections.{$defaultConnection}.database", '');

        try {
            TestDatabaseSafety::assertSafe($environment, $databaseName);
        } catch (RuntimeException $exception) {
            throw new RuntimeException(
                'Unsafe test database detected. Tests may only run against a dedicated test database.',
                previous: $exception
            );
        }

        return $app;
    }

    protected function signInGeneralAdmin(): \App\Models\CultivationAdmin
    {
        $admin = new \App\Models\CultivationAdmin();
        $admin->adminName = 'Test General Admin';
        $admin->adminUser = 'test_admin_'.uniqid();
        $admin->userType = \App\Models\CultivationAdmin::ROLE_GENERAL;
        $admin->loginPassword = \Illuminate\Support\Facades\Hash::make('test-password');
        $admin->adminMobile = '01700000000';
        $admin->adminMail = uniqid().'@test.local';
        $admin->save();
        session()->put('cultivationAdmin', (int) $admin->id);

        return $admin;
    }
}
