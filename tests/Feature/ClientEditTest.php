<?php

namespace Tests\Feature;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClientEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_load_client_data_for_editing(): void
    {
        $client = Client::create([
            'uuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            'dni' => '1234567890',
            'first_name' => 'John',
            'first_last_name' => 'Smith',
            'email' => 'john.doe@example.com',
            'phone_number' => '1234567890',
            'address' => '123 Main St',
        ]);

        Livewire::test('clients.edit-client')
            ->assertSet('isOpen', false)
            ->dispatch('edit-client', uuid: $client->uuid)
            ->assertSet('isOpen', true)
            ->assertSet('uuid', $client->uuid)
            ->assertSet('dni', '1234567890')
            ->assertSet('first_name', 'John')
            ->assertSet('first_last_name', 'Smith')
            ->assertSet('email', 'john.doe@example.com')
            ->assertSet('phone_number', '1234567890')
            ->assertSet('address', '123 Main St');
    }

    public function test_can_update_client_successfully(): void
    {
        $client = Client::create([
            'uuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            'dni' => '1234567890',
            'first_name' => 'John',
            'first_last_name' => 'Smith',
            'email' => 'john.doe@example.com',
            'phone_number' => '1234567890',
            'address' => '123 Main St',
        ]);

        Livewire::test('clients.edit-client')
            ->dispatch('edit-client', uuid: $client->uuid)
            ->set('first_name', 'Jonathan')
            ->set('email', 'jonathan.doe@example.com')
            ->call('update')
            ->assertHasNoErrors()
            ->assertSet('isOpen', false)
            ->assertDispatched('client-saved');

        $this->assertDatabaseHas('clients', [
            'uuid' => $client->uuid,
            'first_name' => 'Jonathan',
            'email' => 'jonathan.doe@example.com',
        ]);
    }

    public function test_cannot_update_client_with_duplicate_dni(): void
    {
        $client1 = Client::create([
            'uuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            'dni' => '1234567890',
            'first_name' => 'John',
            'first_last_name' => 'Smith',
            'email' => 'john.doe@example.com',
            'phone_number' => '1234567890',
            'address' => '123 Main St',
        ]);

        $client2 = Client::create([
            'uuid' => 'b0eebc99-9c0b-4ef8-bb6d-6bb9bd380a12',
            'dni' => '0987654321',
            'first_name' => 'Jane',
            'first_last_name' => 'Doe',
            'email' => 'jane.doe@example.com',
            'phone_number' => '0987654321',
            'address' => '456 Side St',
        ]);

        Livewire::test('clients.edit-client')
            ->dispatch('edit-client', uuid: $client2->uuid)
            ->set('dni', '1234567890')
            ->call('update')
            ->assertHasErrors(['dni' => 'unique'])
            ->assertSet('isOpen', true);

        $this->assertDatabaseHas('clients', [
            'uuid' => $client2->uuid,
            'dni' => '0987654321',
        ]);
    }

    public function test_can_update_client_keeping_its_own_dni_and_email(): void
    {
        $client = Client::create([
            'uuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            'dni' => '1234567890',
            'first_name' => 'John',
            'first_last_name' => 'Smith',
            'email' => 'john.doe@example.com',
            'phone_number' => '1234567890',
            'address' => '123 Main St',
        ]);

        Livewire::test('clients.edit-client')
            ->dispatch('edit-client', uuid: $client->uuid)
            ->set('first_name', 'Jonathan')
            ->call('update')
            ->assertHasNoErrors();
    }
}
