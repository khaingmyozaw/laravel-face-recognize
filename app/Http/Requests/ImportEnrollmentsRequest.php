<?php

namespace App\Http\Requests;

use App\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;

class ImportEnrollmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Permission::EnrollmentsImport->value) === true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:10240',
            ],
        ];
    }
}
