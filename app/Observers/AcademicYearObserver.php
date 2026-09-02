<?php

namespace App\Observers;

use App\Models\AcademicYear;

/**
 * Garantit qu'une seule année académique est active à la fois.
 *
 * L'application entière résout « l'année en cours » par
 * `AcademicYear::where('active', true)->first()`. Si deux années portaient le
 * drapeau, ce `first()` renverrait un résultat arbitraire : inscriptions,
 * notes, bulletins et portail se rattacheraient silencieusement à la mauvaise
 * année. Les périodes académiques appliquent déjà cette exclusivité ; on la
 * transpose ici, au niveau du modèle, pour couvrir aussi les seeders et Tinker.
 */
class AcademicYearObserver
{
    public function saved(AcademicYear $year): void
    {
        if (! $year->active) {
            return;
        }

        // `update()` sur le query builder ne redéclenche pas les événements
        // de modèle : pas de récursion.
        AcademicYear::query()
            ->whereKeyNot($year->getKey())
            ->where('active', true)
            ->update(['active' => false]);
    }
}
