<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ClassSubject extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'class_id',
        'subject_id',
        'teacher_id',
    ];

    /**
     * Get the class this assignment belongs to.
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * Get the subject for this assignment.
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get the teacher teaching this subject in the class.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Get all grades for this class-subject combination.
     */
    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }
}
