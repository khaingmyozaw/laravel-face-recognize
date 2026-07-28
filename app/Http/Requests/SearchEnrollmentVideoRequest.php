<?php

namespace App\Http\Requests;

use App\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;

class SearchEnrollmentVideoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Permission::EnrollmentsSearch->value) === true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        $maxKb = (int) config('services.face.video_max_kb', 51200);

        return [
            'video' => [
                'required',
                'file',
                'mimes:mp4,mov,m4v,webm,mkv,avi',
                'max:'.$maxKb,
            ],
        ];
    }
}
