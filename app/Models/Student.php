<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Services\MatriculeService;

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

    /**
     * Générer un matricule pour cet élève
     *
     * @return string
     */
    public function generateMatricule(): string
    {
        $matriculeService = app(MatriculeService::class);

        return $matriculeService->generateStudentMatricule();
    }

    /**
     * Générer un numéro d'enregistrement si absent
     *
     * @return string
     */
    public function generateRegistrationNumber(): string
    {
        $matriculeService = app(MatriculeService::class);

        if ($this->registration_number) {
            return $this->registration_number;
        }

        return $matriculeService->generateRegistrationNumber();
    }

    /**
     * Boot the model
     */
    public static function boot()
    {
        parent::boot();

        // Auto-générer le numéro d'enregistrement avant création
        static::creating(function ($model) {
            if (empty($model->registration_number)) {
                $model->registration_number = $model->generateRegistrationNumber();
            }
        });
    }
}
