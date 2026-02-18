<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function registration_returns_token_and_allows_access()
    {
        $payload = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        $response = $this->postJson('/api/register', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure(['token', 'user']);

        $token = $response->json('token');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/quotes')
            ->assertStatus(200);
    }

    /** @test */
    public function login_returns_token_and_protects_routes()
    {
        $user = \App\Models\User::factory()->create([
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
        ]);

        $response = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password']);

        $response->assertStatus(200)->assertJsonStructure(['token', 'user']);

        $token = $response->json('token');

        // Access protected route
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/quotes')
            ->assertStatus(200);
    }
}
