<?php

namespace App\Observers;

use App\Events\MatriculeGenerated;
use App\Models\Student;

class StudentObserver
{
    /**
     * Handle the Student "created" event.
     */
    public function created(Student $student): void
    {
        // Note: registration_number is auto-generated in Student::boot()
        // Dispatch event if registration number was generated
        if ($student->registration_number) {
            MatriculeGenerated::dispatch(
                $student->id, // matricule ID
                'student',
                $student->id,
                'student',
                $student->registration_number
            );
        }
    }

    /**
     * Handle the Student "updated" event.
     */
    public function updated(Student $student): void
    {
        // You can track updates if needed
    }

    /**
     * Handle the Student "deleted" event.
     */
    public function deleted(Student $student): void
    {
        // You can log deletion if needed
    }

    /**
     * Handle the Student "restored" event.
     */
    public function restored(Student $student): void
    {
        // You can log restore if needed
    }

    /**
     * Handle the Student "force deleted" event.
     */
    public function forceDeleted(Student $student): void
    {
        // You can log force delete if needed
    }
}
