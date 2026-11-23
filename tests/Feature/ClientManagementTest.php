<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ClientManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_create_update_and_delete_client(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('clients.store'), [
            'name' => 'Meryem Alaoui', 'email' => 'meryem@example.test', 'company' => 'Rif Digital', 'phone' => '+212600000001',
        ])->assertRedirect();
        $client = Client::where('email', 'meryem@example.test')->firstOrFail();
        $this->assertSame('Rif Digital', $client->company);

        $this->actingAs($admin)->put(route('clients.update', $client), [
            'name' => 'Meryem Alaoui', 'email' => 'meryem@example.test', 'company' => 'Rif Digital Studio',
        ])->assertRedirect(route('clients.show', $client));
        $this->assertDatabaseHas('clients', ['id' => $client->id, 'company' => 'Rif Digital Studio']);

        $this->actingAs($admin)->delete(route('clients.destroy', $client))->assertRedirect(route('clients.index'));
        $this->assertSoftDeleted($client);
    }

    public function test_client_creation_rejects_missing_name_and_invalid_email(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('clients.store'), ['email' => 'not-an-email'])
            ->assertSessionHasErrors(['name', 'email']);

        $this->assertDatabaseCount('clients', 0);
    }
}
