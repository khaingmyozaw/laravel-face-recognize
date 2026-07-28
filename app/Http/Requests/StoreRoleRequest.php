<?php

namespace App\Http\Requests;

use App\Enums\Permission;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Permission::RolesCreate->value) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::notIn([Role::ADMIN]),
                Rule::unique(Role::class, 'name'),
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
