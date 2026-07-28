<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_has_all_permissions(): void
    {
        $admin = User::factory()->admin()->create();

        foreach (Permission::cases() as $permission) {
            $this->assertTrue(
                $admin->hasPermission($permission),
                "Admin missing permission [{$permission->value}]",
            );
            $this->assertTrue($admin->can($permission->value));
        }
    }

    public function test_gate_has_enrollment_permissions_only(): void
    {
        $gate = User::factory()->gate()->create();

        $this->assertTrue($gate->hasPermission(Permission::EnrollmentsView));
        $this->assertTrue($gate->hasPermission(Permission::EnrollmentsSearch));
        $this->assertFalse($gate->hasPermission(Permission::UsersView));
        $this->assertFalse($gate->can(Permission::UsersCreate->value));
    }

    public function test_gate_users_cannot_access_user_routes(): void
    {
        $gate = User::factory()->gate()->create();

        $this->actingAs($gate)
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_shared_inertia_auth_includes_permissions(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('enrollments.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('auth.permissions')
                ->where('auth.permissions', $admin->permissionValues()));
    }
}
