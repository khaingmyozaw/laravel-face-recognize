<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admins_can_view_roles_index(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('roles.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('roles/index')
                ->has('roles', 2));
    }

    public function test_gate_users_cannot_view_roles(): void
    {
        $gate = User::factory()->gate()->create();

        $this->actingAs($gate)
            ->get(route('roles.index'))
            ->assertForbidden();
    }

    public function test_admins_can_create_roles(): void
    {
        $admin = User::factory()->admin()->create();

        $permissions = [
            Permission::EnrollmentsView->value,
            Permission::EnrollmentsSearch->value,
        ];

        $response = $this->actingAs($admin)->post(route('roles.store'), [
            'name' => 'operator',
            'label' => 'Operator',
            'permissions' => $permissions,
        ]);

        $role = Role::query()->where('name', 'operator')->firstOrFail();

        $response->assertRedirect(route('roles.show', $role));
        $this->assertEqualsCanonicalizing($permissions, $role->permissions);
        $this->assertSame('Operator', $role->label);
    }

    public function test_admins_cannot_create_admin_role(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('roles.store'), [
            'name' => Role::ADMIN,
            'label' => 'Another Admin',
            'permissions' => [Permission::EnrollmentsView->value],
        ])->assertSessionHasErrors('name');
    }

    public function test_admins_can_update_role_permissions(): void
    {
        $admin = User::factory()->admin()->create();
        $role = Role::query()->where('name', Role::GATE)->firstOrFail();

        $permissions = [
            Permission::EnrollmentsView->value,
            Permission::EnrollmentsSearch->value,
        ];

        $response = $this->actingAs($admin)->put(route('roles.update', $role), [
            'name' => $role->name,
            'label' => $role->label,
            'permissions' => $permissions,
        ]);

        $response->assertRedirect(route('roles.show', $role));
        $this->assertEqualsCanonicalizing(
            $permissions,
            $role->fresh()->permissions,
        );
    }

    public function test_admins_cannot_update_admin_role(): void
    {
        $admin = User::factory()->admin()->create();
        $role = Role::query()->where('name', Role::ADMIN)->firstOrFail();

        $this->actingAs($admin)->put(route('roles.update', $role), [
            'name' => $role->name,
            'label' => 'Changed',
            'permissions' => [Permission::EnrollmentsView->value],
        ])->assertForbidden();

        $this->assertSame('Admin', $role->fresh()->label);
    }

    public function test_admins_cannot_edit_admin_role_page(): void
    {
        $admin = User::factory()->admin()->create();
        $role = Role::query()->where('name', Role::ADMIN)->firstOrFail();

        $this->actingAs($admin)
            ->get(route('roles.edit', $role))
            ->assertForbidden();
    }

    public function test_updated_role_permissions_affect_authorization(): void
    {
        $admin = User::factory()->admin()->create();
        $gateUser = User::factory()->gate()->create();
        $role = Role::query()->where('name', Role::GATE)->firstOrFail();

        $this->actingAs($admin)->put(route('roles.update', $role), [
            'name' => $role->name,
            'label' => $role->label,
            'permissions' => [Permission::EnrollmentsSearch->value],
        ])->assertRedirect();

        $gateUser->refresh()->load('assignedRole');

        $this->assertTrue($gateUser->can(Permission::EnrollmentsSearch->value));
        $this->assertFalse($gateUser->can(Permission::EnrollmentsView->value));
    }

    public function test_admins_can_delete_unused_roles(): void
    {
        $admin = User::factory()->admin()->create();
        $role = Role::factory()->create([
            'name' => 'temp',
            'label' => 'Temporary',
        ]);

        $response = $this->actingAs($admin)->delete(route('roles.destroy', $role));

        $response->assertRedirect(route('roles.index'));
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_admins_cannot_delete_roles_with_users(): void
    {
        $admin = User::factory()->admin()->create();
        $role = Role::query()->where('name', Role::GATE)->firstOrFail();
        User::factory()->gate()->create();

        $this->actingAs($admin)
            ->from(route('roles.show', $role))
            ->delete(route('roles.destroy', $role))
            ->assertRedirect(route('roles.show', $role))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    public function test_admins_cannot_delete_admin_role(): void
    {
        $admin = User::factory()->admin()->create();
        $role = Role::query()->where('name', Role::ADMIN)->firstOrFail();

        $this->actingAs($admin)
            ->delete(route('roles.destroy', $role))
            ->assertForbidden();

        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }
}
