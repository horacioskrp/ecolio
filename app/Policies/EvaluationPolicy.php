<?php

namespace App\Policies;

use App\Models\Evaluation;
use App\Models\SubjectAssignment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EvaluationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_evaluations');
    }

    public function view(User $user, Evaluation $evaluation): bool
    {
        return $user->can('view_evaluations');
    }

    public function update(User $user, Evaluation $evaluation): bool
    {
        return $user->can('edit_evaluations');
    }

    public function delete(User $user, Evaluation $evaluation): bool
    {
        return $user->can('delete_evaluations');
    }

    /**
     * Saisir/modifier les notes d'une évaluation.
     *
     * Règle métier centralisée : impossible sur une évaluation clôturée
     * (verrouillée) — il faut passer par une réclamation de notes.
     */
    public function enterMarks(User $user, Evaluation $evaluation): Response
    {
        if (! $user->can('create_marks')) {
            return Response::deny("Vous n'avez pas l'autorisation de saisir des notes.");
        }

        if ($evaluation->locked_at !== null) {
            return Response::deny('Cette évaluation est clôturée. Veuillez déposer une réclamation pour modifier les notes.');
        }

        // Cloisonnement enseignant : quelqu'un qui porte des affectations est un
        // enseignant, et ne saisit que sur SES classes/matières. Les profils sans
        // aucune affectation (administration, direction) restent transverses —
        // sans quoi on bloquerait les corrections et les remplacements.
        $classSubject = $evaluation->classSubject;

        if (! $classSubject || ! SubjectAssignment::where('teacher_id', $user->id)->where('active', true)->exists()) {
            return Response::allow();
        }

        $assigned = SubjectAssignment::where('teacher_id', $user->id)
            ->where('class_id', $classSubject->class_id)
            ->where('subject_id', $classSubject->subject_id)
            ->where('active', true)
            ->exists();

        return $assigned
            ? Response::allow()
            : Response::deny("Vous n'êtes pas affecté à cette matière pour cette classe.");
    }
}
