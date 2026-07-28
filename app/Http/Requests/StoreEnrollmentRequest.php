<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'father_name' => ['required', 'string', 'max:255'],
            'mother_name' => ['required', 'string', 'max:255'],
            'nrc' => ['nullable', 'string', 'max:255'],
            'is_f_alive' => ['required', 'boolean'],
            'is_m_alive' => ['required', 'boolean'],
            'remark' => ['nullable', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_f_alive' => $this->boolean('is_f_alive'),
            'is_m_alive' => $this->boolean('is_m_alive'),
        ]);
    }
}
