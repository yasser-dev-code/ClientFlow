<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_employee_is_forbidden_from_admin_management_pages(): void
    {
        $employee = User::factory()->create();

        $this->actingAs($employee)->get(route('clients.create'))->assertForbidden();
        $this->actingAs($employee)->get(route('users.index'))->assertForbidden();
        $this->actingAs($employee)->post(route('clients.store'), ['name' => 'Blocked'])->assertForbidden();
    }

    public function test_admin_can_open_management_pages(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('clients.create'))->assertOk();
        $this->actingAs($admin)->get(route('users.index'))->assertOk();
    }
}
