<?php

namespace Tests\Feature;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that fetching indices works when the database is empty.
     */
    public function test_can_fetch_empty_client_indices(): void
    {
        $response = $this->getJson('/api/v1/clients/indices');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'dnis' => [],
                    'emails' => [],
                    'phone_numbers' => [],
                ],
            ]);
    }

    /**
     * Test that fetching indices returns unique client data.
     */
    public function test_can_fetch_unique_client_indices(): void
    {
        Client::create([
            'uuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            'dni' => '1234567890',
            'first_name' => 'John',
            'first_last_name' => 'Smith',
            'email' => 'john.doe@example.com',
            'phone_number' => '1234567890',
            'address' => '123 Main St',
        ]);

        Client::create([
            'uuid' => 'b0eebc99-9c0b-4ef8-bb6d-6bb9bd380a12',
            'dni' => '0987654321',
            'first_name' => 'Jane',
            'first_last_name' => 'Doe',
            'email' => 'jane.doe@example.com',
            'phone_number' => '0987654321',
            'address' => '456 Side St',
        ]);

        $response = $this->getJson('/api/v1/clients/indices');

        $response->assertStatus(200);

        $data = $response->json('data');

        $this->assertContains('1234567890', $data['dnis']);
        $this->assertContains('0987654321', $data['dnis']);
        $this->assertContains('john.doe@example.com', $data['emails']);
        $this->assertContains('jane.doe@example.com', $data['emails']);
        $this->assertContains('1234567890', $data['phone_numbers']);
        $this->assertContains('0987654321', $data['phone_numbers']);
    }
}
