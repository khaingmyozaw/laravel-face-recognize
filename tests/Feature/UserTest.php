<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_view_users(): void
    {
        $this->get(route('users.index'))->assertRedirect(route('login'));
    }

    public function test_gate_users_cannot_view_users(): void
    {
        $user = User::factory()->gate()->create();

        $this->actingAs($user)
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_admins_can_view_users_index(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk();
    }

    public function test_admins_can_create_users(): void
    {
        $admin = User::factory()->admin()->create();
        $gateRoleId = Role::query()->where('name', Role::GATE)->value('id');

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Gate Operator',
            'email' => 'operator@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role_id' => $gateRoleId,
        ]);

        $user = User::query()->where('email', 'operator@example.com')->firstOrFail();

        $response->assertRedirect(route('users.show', $user));
        $this->assertDatabaseHas('users', [
            'email' => 'operator@example.com',
            'role_id' => $gateRoleId,
        ]);
    }

    public function test_admins_can_update_users(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->gate()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);
        $adminRoleId = Role::query()->where('name', Role::ADMIN)->value('id');

        $response = $this->actingAs($admin)->put(route('users.update', $user), [
            'name' => 'New Name',
            'email' => 'new@example.com',
            'role_id' => $adminRoleId,
        ]);

        $response->assertRedirect(route('users.show', $user));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'email' => 'new@example.com',
            'role_id' => $adminRoleId,
        ]);
    }

    public function test_admins_can_delete_users(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->gate()->create();

        $response = $this->actingAs($admin)->delete(route('users.destroy', $user));

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_admins_cannot_delete_themselves(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->delete(route('users.destroy', $admin));

        $response->assertRedirect();
        $response->assertSessionHasErrors('user');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_public_registration_assigns_gate_role(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Registered User',
            'email' => 'registered@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $gateRoleId = Role::query()->where('name', Role::GATE)->value('id');

        $response->assertRedirect(route('enrollments.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'registered@example.com',
            'role_id' => $gateRoleId,
        ]);
    }
}
