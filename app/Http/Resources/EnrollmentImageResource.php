<?php

namespace App\Http\Resources;

use App\Models\EnrollmentImages;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin EnrollmentImages
 */
class EnrollmentImageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'enrollment_id' => $this->enrollment_id,
            'url' => $this->url,
            'embedding_dim' => $this->embedding_dim,
            'bbox' => $this->bbox,
            'det_score' => $this->det_score,
            'face_count' => $this->face_count,
            'has_embedding' => $this->embedding_dim !== null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
