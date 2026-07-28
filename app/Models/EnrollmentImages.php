<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnrollmentImages extends Model
{
    protected $table = 'enrollment_images';

    protected $fillable = [
        'file_path',
        'enrollment_id',
    ];

    protected $appends = [
        'url',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollments::class, 'enrollment_id');
    }

    protected function url(): Attribute
    {
        return Attribute::get(
            fn (): string => '/storage/'.ltrim($this->file_path, '/'),
        );
    }
}
