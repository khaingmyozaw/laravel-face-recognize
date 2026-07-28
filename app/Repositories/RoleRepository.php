<?php

namespace App\Repositories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;

class RoleRepository
{
    /**
     * @return Collection<int, Role>
     */
    public function all(): Collection
    {
        return Role::query()
            ->withCount('users')
            ->orderBy('label')
            ->get();
    }

    public function findOrFail(int $id): Role
    {
        return Role::query()
            ->withCount('users')
            ->findOrFail($id);
    }

    /**
     * @param  array{name: string, label: string, permissions: list<string>}  $data
     */
    public function create(array $data): Role
    {
        return Role::query()->create($data);
    }

    /**
     * @param  array{name: string, label: string, permissions: list<string>}  $data
     */
    public function update(Role $role, array $data): Role
    {
        $role->update($data);

        return $role->refresh();
    }

    public function delete(Role $role): void
    {
        $role->delete();
    }

    /**
     * @return list<array{value: int, label: string}>
     */
    public function options(): array
    {
        return Role::query()
            ->orderBy('label')
            ->get(['id', 'label'])
            ->map(fn (Role $role): array => [
                'value' => $role->id,
                'label' => $role->label,
            ])
            ->values()
            ->all();
    }
}
