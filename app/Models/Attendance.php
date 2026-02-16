<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Attendance extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'student_id',
        'class_id',
        'date',
        'status',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Get the student for this attendance record.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the class for this attendance record.
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }
}
