<?php

namespace App\Http\Requests;

use App\Enums\Permission;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = $this->route('role');

        return $this->user()?->can(Permission::RolesUpdate->value) === true
            && $role instanceof Role
            && ! $role->isAdmin();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Role $role */
        $role = $this->route('role');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::notIn([Role::ADMIN]),
                Rule::unique(Role::class, 'name')->ignore($role->id),
            ],
            'label' => ['required', 'string', 'max:255'],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::enum(Permission::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'permissions' => array_values($this->input('permissions', [])),
        ]);
    }
}
