<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AcademicYear extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'school_id',
        'year',
        'start_date',
        'end_date',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get the school this academic year belongs to.
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

}

