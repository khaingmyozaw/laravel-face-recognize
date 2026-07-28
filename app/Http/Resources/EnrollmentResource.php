<?php

namespace App\Http\Resources;

use App\Models\Enrollments;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Enrollments
 */
class EnrollmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'father_name' => $this->father_name,
            'mother_name' => $this->mother_name,
            'nrc' => $this->nrc,
            'is_f_alive' => $this->is_f_alive,
            'is_m_alive' => $this->is_m_alive,
            'remark' => $this->remark,
            'address' => $this->address,
            'images' => EnrollmentImageResource::collection($this->whenLoaded('images')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
