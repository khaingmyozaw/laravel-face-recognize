<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enrollments extends Model
{
    protected $table = 'enrollments';

    protected $fillable = [
        'name',
        'father_name',
        'mother_name',
        'nrc',
        'is_f_alive',
        'is_m_alive',
        'remark',
        'address',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_f_alive' => 'boolean',
            'is_m_alive' => 'boolean',
        ];
    }

    /**
     * @return HasMany<EnrollmentImages, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(EnrollmentImages::class, 'enrollment_id');
    }
}
