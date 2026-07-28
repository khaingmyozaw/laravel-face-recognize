<?php

namespace Tests\Feature\Api;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_users_via_api(): void
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('mobile')['plainTextToken'];
        $gateRoleId = Role::query()->where('name', Role::GATE)->value('id');

        $create = $this->withToken($token)->postJson('/api/users', [
            'name' => 'New Gate',
            'email' => 'new-gate@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role_id' => $gateRoleId,
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.email', 'new-gate@example.com')
            ->assertJsonPath('data.role', 'gate')
            ->assertJsonPath('data.role_id', $gateRoleId);

        $userId = $create->json('data.id');

        $this->withToken($token)
            ->getJson('/api/users')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);

        $this->withToken($token)
            ->getJson('/api/users/roles')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->withToken($token)
            ->putJson("/api/users/{$userId}", [
                'name' => 'Updated Gate',
                'email' => 'new-gate@example.com',
                'role_id' => $gateRoleId,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Gate');

        $this->withToken($token)
            ->deleteJson("/api/users/{$userId}")
            ->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $userId]);
    }

    public function test_gate_user_cannot_access_user_management(): void
    {
        $user = User::factory()->gate()->create();
        $token = $user->createToken('mobile')['plainTextToken'];

        $this->withToken($token)
            ->getJson('/api/users')
            ->assertForbidden();
    }

    public function test_admin_cannot_delete_own_account_via_api(): void
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('mobile')['plainTextToken'];

        $this->withToken($token)
            ->deleteJson("/api/users/{$admin->id}")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'You cannot delete your own account.');
    }
}
