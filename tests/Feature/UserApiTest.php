<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_be_created_via_api()
    {
        $response = $this->postJson('/api/create-users', [
            'name' => 'Amit Rajput',
            'email' => 'amit@example.com',
            'password' => 'secret123'
        ]);

        $response->assertStatus(200); // Check if the response status is 200 Ok
    }

    public function test_get_users_list()
    {
        $response = $this->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJsonCount(3); // Checks total users returned
    }
}
