<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Grade extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'student_id',
        'class_subject_id',
        'term',
        'score',
        'comments',
    ];

    protected $casts = [
        'score' => 'decimal:2',
    ];

    /**
     * Get the student for this grade.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the class subject for this grade.
     */
    public function classSubject(): BelongsTo
    {
        return $this->belongsTo(ClassSubject::class);
    }
}
