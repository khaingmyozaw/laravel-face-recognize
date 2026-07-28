<?php

namespace App\Services;

use App\Enums\Permission;
use App\Models\Role;
use App\Repositories\RoleRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class RoleService
{
    public function __construct(
        private RoleRepository $repository,
    ) {}

    /**
     * @return Collection<int, Role>
     */
    public function list(): Collection
    {
        return $this->repository->all();
    }

    public function find(int $id): Role
    {
        return $this->repository->findOrFail($id);
    }

    /**
     * @param  array{name: string, label: string, permissions?: list<string>}  $data
     *
     * @throws Throwable
     */
    public function create(array $data): Role
    {
        $payload = $this->normalizedPayload($data);

        return DB::transaction(
            fn (): Role => $this->repository->create($payload),
        );
    }

    /**
     * @param  array{name: string, label: string, permissions?: list<string>}  $data
     *
     * @throws Throwable
     */
    public function update(Role $role, array $data): Role
    {
        $this->assertMutable($role);

        $payload = $this->normalizedPayload($data);

        return DB::transaction(
            fn (): Role => $this->repository->update($role, $payload),
        );
    }

    /**
     * @throws Throwable
     */
    public function delete(Role $role): void
    {
        $this->assertMutable($role);

        if (($role->users_count ?? $role->users()->count()) > 0) {
            throw ValidationException::withMessages([
                'role' => __('Cannot delete a role that still has users assigned.'),
            ]);
        }

        DB::transaction(function () use ($role): void {
            $this->repository->delete($role);
        });
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function permissionOptions(): array
    {
        return collect(Permission::cases())
            ->map(fn (Permission $permission): array => [
                'value' => $permission->value,
                'label' => $permission->label(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: int, label: string}>
     */
    public function options(): array
    {
        return $this->repository->options();
    }

    /**
     * @throws HttpException
     */
    public function assertMutable(Role $role): void
    {
        if ($role->isAdmin()) {
            abort(403, __('The admin role cannot be modified.'));
        }
    }

    /**
     * @param  array{name: string, label: string, permissions?: list<string>}  $data
     * @return array{name: string, label: string, permissions: list<string>}
     */
    private function normalizedPayload(array $data): array
    {
        $permissions = array_values(array_unique(array_filter(
            $data['permissions'] ?? [],
            fn (mixed $permission): bool => is_string($permission)
                && Permission::tryFrom($permission) !== null,
        )));

        return [
            'name' => $data['name'],
            'label' => $data['label'],
            'permissions' => $permissions,
        ];
    }
}
