<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->slug(2);

        return [
            'name' => $name,
            'label' => ucfirst($name),
            'permissions' => Role::defaultUserPermissions(),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => Role::ADMIN,
            'label' => 'Admin',
            'permissions' => Role::defaultAdminPermissions(),
        ]);
    }

    public function gate(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => Role::GATE,
            'label' => 'Gate',
            'permissions' => Role::defaultUserPermissions(),
        ]);
    }
}
