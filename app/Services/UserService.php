<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Throwable;

class UserService
{
    public function __construct(
        private UserRepository $repository,
    ) {}

    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function list(int $perPage = 12): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }

    public function find(int $id): User
    {
        return $this->repository->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function create(array $data): User
    {
        return DB::transaction(fn (): User => $this->repository->create($data));
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            if (! array_key_exists('password', $data) || blank($data['password'])) {
                unset($data['password']);
            }

            return $this->repository->update($user, $data);
        });
    }

    /**
     * @throws Throwable
     */
    public function delete(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $this->repository->delete($user);
        });
    }
}
