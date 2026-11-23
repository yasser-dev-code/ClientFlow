<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class DemoSeederSecurityTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_demo_accounts_use_the_configured_password_in_testing(): void
    {
        $password = Str::password(32);
        config()->set('demo.admin_password', $password);

        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@clientflow.ma')->firstOrFail();

        $this->assertTrue(Hash::check($password, $admin->password));
    }

    public function test_demo_seeding_requires_a_configured_password(): void
    {
        config()->set('demo.admin_password');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DEMO_ADMIN_PASSWORD must be configured before seeding demo accounts.');

        $this->seed(DatabaseSeeder::class);
    }

    public function test_demo_accounts_are_not_seeded_outside_local_or_testing(): void
    {
        config()->set('demo.admin_password', Str::password(32));

        try {
            $this->app->detectEnvironment(fn (): string => 'production');
            $this->app->make(DatabaseSeeder::class)->run();

            $this->assertDatabaseCount('users', 0);
        } finally {
            $this->app->detectEnvironment(fn (): string => 'testing');
        }
    }
}
