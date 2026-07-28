<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class RoleController extends Controller
{
    public function __construct(
        private RoleService $service,
    ) {}

    public function index(): Response
    {
        return Inertia::render('roles/index', [
            'roles' => $this->service->list(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('roles/create', [
            'permissionOptions' => $this->service->permissionOptions(),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        try {
            $role = $this->service->create($request->validated());
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'name' => __('Unable to create role. Please try again.'),
            ]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Role created.'),
        ]);

        return to_route('roles.show', $role);
    }

    public function show(Role $role): Response
    {
        return Inertia::render('roles/show', [
            'role' => $this->service->find($role->id),
            'permissionOptions' => $this->service->permissionOptions(),
        ]);
    }

    public function edit(Role $role): Response
    {
        $this->service->assertMutable($role);

        return Inertia::render('roles/edit', [
            'role' => $this->service->find($role->id),
            'permissionOptions' => $this->service->permissionOptions(),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        try {
            $this->service->update($role, $request->validated());
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'name' => __('Unable to update role. Please try again.'),
            ]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Role updated.'),
        ]);

        return to_route('roles.show', $role);
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->service->assertMutable($role);

        try {
            $this->service->delete($role);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'role' => __('Unable to delete role. Please try again.'),
            ]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Role deleted.'),
        ]);

        return to_route('roles.index');
    }
}
