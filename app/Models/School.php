<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class School extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'level',
        'code',
        'address',
        'phone',
        'email',
        'principal',
        'description',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Get all academic years for the school.
     */
    public function academicYears(): HasMany
    {
        return $this->hasMany(AcademicYear::class);
    }

    /**
     * Get all subjects for the school.
     */
    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }
}
