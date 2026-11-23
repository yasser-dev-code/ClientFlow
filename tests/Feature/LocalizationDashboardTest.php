<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LocalizationDashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_language_switcher_stores_supported_locale(): void
    {
        $this->post(route('locale.update', 'en'))
            ->assertRedirect()
            ->assertSessionHas('locale', 'en');
    }

    public function test_dashboard_uses_database_statistics_and_selected_locale(): void
    {
        $user = User::factory()->admin()->create();
        Client::factory()->count(3)->create();

        $response = $this->withSession(['locale' => 'en'])->actingAs($user)->get(route('dashboard'));

        $response->assertOk()->assertSee('Overview')->assertViewHas('statistics', fn (array $statistics): bool => $statistics['clients'] === 3);
    }
}
