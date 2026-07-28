<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_receive_bearer_token(): void
    {
        $user = User::factory()->create([
            'email' => 'gate@example.com',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'gate@example.com',
            'password' => 'password',
            'device_name' => 'iphone',
        ]);

        $response->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.email', 'gate@example.com')
            ->assertJsonStructure(['token', 'token_type', 'user' => ['id', 'permissions']]);

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertSame($user->id, $response->json('user.id'));
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'gate@example.com',
        ]);

        $this->postJson('/api/login', [
            'email' => 'gate@example.com',
            'password' => 'wrong-password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_authenticated_user_can_fetch_profile_and_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile')['plainTextToken'];

        $this->withToken($token)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);

        $this->withToken($token)
            ->postJson('/api/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->withToken($token)
            ->getJson('/api/user')
            ->assertUnauthorized();
    }

    public function test_authenticated_user_can_update_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);
        $token = $user->createToken('mobile')['plainTextToken'];

        $this->withToken($token)
            ->putJson('/api/user', [
                'name' => 'New Name',
                'email' => 'new@example.com',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.email', 'new@example.com');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);
    }

    public function test_authenticated_user_can_update_password(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile')['plainTextToken'];

        $this->withToken($token)
            ->putJson('/api/user/password', [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Password updated.');

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'new-password',
            'device_name' => 'iphone',
        ])->assertOk();
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson('/api/enrollments')->assertUnauthorized();
    }
}
