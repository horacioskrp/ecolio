<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Student extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'class_id',
        'registration_number',
        'parent_name',
        'parent_phone',
        'parent_email',
        'enrollment_date',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'enrollment_date' => 'date',
    ];

    /**
     * Get the user account for this student.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the class this student is enrolled in.
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'class_id');
    }

    /**
     * Get all attendances for this student.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Get all grades for this student.
     */
    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }
}
