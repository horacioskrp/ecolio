<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Subject extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'school_id',
        'name',
        'code',
        'description',
        'credit_hours',
    ];

    protected $casts = [
        'credit_hours' => 'integer',
    ];

    /**
     * Get the school this subject belongs to.
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get all class subjects for this subject.
     */
    public function classSubjects(): HasMany
    {
        return $this->hasMany(ClassSubject::class);
    }
}
