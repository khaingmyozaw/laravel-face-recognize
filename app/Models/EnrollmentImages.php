<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $enrollment_id
 * @property string $file_path
 * @property list<float>|null $embedding
 * @property int|null $embedding_dim
 * @property array<string, mixed>|null $bbox
 * @property float|null $det_score
 * @property int|null $face_count
 * @property-read string $url
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class EnrollmentImages extends Model
{
    protected $table = 'enrollment_images';

    protected $fillable = [
        'file_path',
        'enrollment_id',
        'embedding',
        'embedding_dim',
        'bbox',
        'det_score',
        'face_count',
    ];

    protected $appends = [
        'url',
    ];

    protected $hidden = [
        'embedding',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'embedding' => 'array',
            'embedding_dim' => 'integer',
            'bbox' => 'array',
            'det_score' => 'float',
            'face_count' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Enrollments, $this>
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollments::class, 'enrollment_id');
    }

    /**
     * @return Attribute<non-falsy-string, never>
     */
    protected function url(): Attribute
    {
        return Attribute::get(
            fn (): string => '/storage/'.ltrim($this->file_path, '/'),
        );
    }
}
