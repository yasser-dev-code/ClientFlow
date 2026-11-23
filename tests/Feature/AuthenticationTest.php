<?php

namespace Tests\Feature;

use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_to_login_from_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_active_user_can_login_and_logout(): void
    {
        $user = User::factory()->create(['password' => 'StrongPass123']);

        $this->post(route('login'), ['email' => $user->email, 'password' => 'StrongPass123'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->inactive()->create(['password' => 'StrongPass123']);

        $this->post(route('login'), ['email' => $user->email, 'password' => 'StrongPass123'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_registration_creates_active_employee_account(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Amal Zahra',
            'email' => 'amal@example.test',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $user = User::where('email', 'amal@example.test')->firstOrFail();
        $this->assertSame(UserRole::Employee, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertAuthenticatedAs($user);
    }
}
