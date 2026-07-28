<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\RoleService;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class UserController extends Controller
{
    public function __construct(
        private UserService $service,
        private RoleService $roleService,
    ) {}

    public function index(): Response
    {
        return Inertia::render('users/index', [
            'users' => $this->service->list(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('users/create', [
            'roles' => $this->roleService->options(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        try {
            $user = $this->service->create($request->validated());
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'email' => __('Unable to create user. Please try again.'),
            ]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('User created.'),
        ]);

        return to_route('users.show', $user);
    }

    public function show(User $user): Response
    {
        return Inertia::render('users/show', [
            'user' => $this->service->find($user->id),
        ]);
    }

    public function edit(User $user): Response
    {
        return Inertia::render('users/edit', [
            'user' => $this->service->find($user->id),
            'roles' => $this->roleService->options(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        try {
            $this->service->update($user, $request->validated());
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'email' => __('Unable to update user. Please try again.'),
            ]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('User updated.'),
        ]);

        return to_route('users.show', $user);
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->is(auth()->user())) {
            return back()->withErrors([
                'user' => __('You cannot delete your own account.'),
            ]);
        }

        try {
            $this->service->delete($user);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'user' => __('Unable to delete user. Please try again.'),
            ]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('User deleted.'),
        ]);

        return to_route('users.index');
    }
}
