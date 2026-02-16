<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Classroom extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'classes';

    protected $fillable = [
        'school_id',
        'academic_year_id',
        'name',
        'code',
        'capacity',
        'teacher_id',
    ];

    protected $casts = [
        'capacity' => 'integer',
    ];

    /**
     * Get the school this class belongs to.
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the academic year this class belongs to.
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Get the teacher for this class.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all students in this class.
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    /**
     * Get all attendances for this class.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Get all class subjects for this class.
     */
    public function classSubjects(): HasMany
    {
        return $this->hasMany(ClassSubject::class);
    }
}
