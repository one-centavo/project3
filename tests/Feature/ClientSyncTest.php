<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_synchronize_new_clients(): void
    {
        $payload = [
            'clients' => [
                [
                    'uuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
                    'dni' => '1234567890',
                    'first_name' => 'John',
                    'second_name' => 'Doe',
                    'first_last_name' => 'Smith',
                    'second_last_name' => 'Johnson',
                    'email' => 'john.doe@example.com',
                    'phone_number' => '1234567890',
                    'address' => '123 Main St',
                    'updated_at' => now()->toDateTimeString(),
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/clients/sync', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Synchronization completed successfully.',
            ]);

        $this->assertDatabaseHas('clients', [
            'uuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            'dni' => '1234567890',
            'first_name' => 'John',
            'email' => 'john.doe@example.com',
        ]);
    }
}
