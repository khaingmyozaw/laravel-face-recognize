<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\RoleService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Throwable;

class UserController extends Controller
{
    public function __construct(
        private UserService $service,
        private RoleService $roleService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min(max((int) $request->query('per_page', 12), 1), 100);

        return UserResource::collection($this->service->list($perPage));
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        try {
            $user = $this->service->create($request->validated());
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => __('Unable to create user. Please try again.'),
            ], 500);
        }

        return (new UserResource($user->load('assignedRole')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(User $user): UserResource
    {
        return new UserResource($this->service->find($user->id));
    }

    public function update(UpdateUserRequest $request, User $user): UserResource|JsonResponse
    {
        try {
            $user = $this->service->update($user, $request->validated());
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => __('Unable to update user. Please try again.'),
            ], 500);
        }

        return new UserResource($user->load('assignedRole'));
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->is($request->user())) {
            return response()->json([
                'message' => __('You cannot delete your own account.'),
            ], 422);
        }

        try {
            $this->service->delete($user);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => __('Unable to delete user. Please try again.'),
            ], 500);
        }

        return response()->json([
            'message' => __('User deleted.'),
        ]);
    }

    public function roles(): JsonResponse
    {
        return response()->json([
            'data' => $this->roleService->options(),
        ]);
    }
}
