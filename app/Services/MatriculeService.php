<?php

namespace App\Services;

use App\Models\User;
use App\Models\Student;
use App\Constants\Roles;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Service pour générer les matricules uniques des utilisateurs et élèves
 */
class MatriculeService
{
    /**
     * Préfixes pour chaque rôle
     */
    protected const ROLE_PREFIXES = [
        Roles::ADMINISTRATOR => 'ADM',
        Roles::DIRECTOR => 'DIR',
        Roles::TEACHER => 'PROF',
        Roles::ACCOUNTING => 'COMPT',
        Roles::SECRETARIAT => 'SEC',
    ];

    /**
     * Générer un matricule pour un utilisateur
     *
     * @param string $role Le rôle de l'utilisateur
     * @param string|null $schoolCode Le code de l'école (optionnel)
     * @return string
     */
    public function generateUserMatricule(string $role, ?string $schoolCode = null): string
    {
        $prefix = self::ROLE_PREFIXES[$role] ?? 'USR';
        $year = date('y'); // Année courante (ex: 26 pour 2026)
        $sequence = $this->getNextSequence($prefix);

        // Format: ADM26001, PROF26001, etc.
        return "{$prefix}{$year}" . str_pad($sequence, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Générer un matricule pour un élève
     *
     * @param string|null $schoolCode Le code de l'école
     * @param string|null $classCode Le code de la classe (optionnel)
     * @return string
     */
    public function generateStudentMatricule(?string $schoolCode = null, ?string $classCode = null): string
    {
        $year = date('y'); // Année courante
        $prefix = 'STU';

        // Utiliser le code de l'école s'il est fourni, sinon utiliser un code générique
        if ($schoolCode) {
            $schoolCode = substr(strtoupper($schoolCode), 0, 3);
        } else {
            $schoolCode = 'ECO';
        }

        $sequence = $this->getNextStudentSequence($schoolCode, $year);

        // Format: ECOSTU26001, ou LOMESTU26001 pour Lomé
        return "{$schoolCode}{$prefix}{$year}" . str_pad($sequence, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Générer un numéro d'enregistrement unique pour un élève
     *
     * @param string|null $schoolCode Le code de l'école
     * @param string|null $classCode Le code de la classe
     * @return string
     */
    public function generateRegistrationNumber(?string $schoolCode = null, ?string $classCode = null): string
    {
        $year = date('Y'); // Année complète
        $prefix = 'REG';

        if ($schoolCode) {
            $schoolCode = substr(strtoupper($schoolCode), 0, 2);
        } else {
            $schoolCode = 'TG'; // Togo par défaut
        }

        $sequence = $this->getNextRegistrationSequence($schoolCode, $year);

        // Format: REG-TG-2026-001, REG-LO-2026-002
        return "{$prefix}-{$schoolCode}-{$year}-" . str_pad($sequence, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Obtenir le numéro de séquence suivant pour un préfixe donné
     *
     * @param string $prefix Le préfixe (ADM, PROF, etc.)
     * @return int
     */
    protected function getNextSequence(string $prefix): int
    {
        $year = date('y');
        $pattern = "{$prefix}{$year}%";

        // Compter les matricules existants avec ce préfixe et cette année
        $count = User::where('natricule', 'like', $pattern)
            ->count();

        return $count + 1;
    }

    /**
     * Obtenir le numéro de séquence suivant pour un élève
     *
     * @param string $schoolCode Le code de l'école
     * @param string $year L'année (ex: 26)
     * @return int
     */
    protected function getNextStudentSequence(string $schoolCode, string $year): int
    {
        $schoolCode = strtoupper(substr($schoolCode, 0, 3));
        $pattern = "{$schoolCode}STU{$year}%";

        $count = Student::join('users', 'students.user_id', '=', 'users.id')
            ->where('users.natricule', 'like', $pattern)
            ->count();

        return $count + 1;
    }

    /**
     * Obtenir le numéro de séquence suivant pour un numéro d'enregistrement
     *
     * @param string $schoolCode Le code de l'école (2 lettres)
     * @param string $year L'année complète
     * @return int
     */
    protected function getNextRegistrationSequence(string $schoolCode, string $year): int
    {
        $schoolCode = strtoupper(substr($schoolCode, 0, 2));
        $pattern = "REG-{$schoolCode}-{$year}-%";

        $count = Student::where('registration_number', 'like', $pattern)
            ->count();

        return $count + 1;
    }

    /**
     * Vérifier si un matricule existe déjà
     *
     * @param string $matricule
     * @return bool
     */
    public function matriculeExists(string $matricule): bool
    {
        return User::where('natricule', $matricule)->exists();
    }

    /**
     * Vérifier si un numéro d'enregistrement existe déjà
     *
     * @param string $registrationNumber
     * @return bool
     */
    public function registrationNumberExists(string $registrationNumber): bool
    {
        return Student::where('registration_number', $registrationNumber)->exists();
    }

    /**
     * Obtenir des informations sur un matricule
     *
     * @param string $matricule
     * @return array|null
     */
    public function parseMatricule(string $matricule): ?array
    {
        // Pattern: ADM26001, PROF26001, etc.
        if (preg_match('/^([A-Z]{3,4})(\d{2})(\d{3})$/', $matricule, $matches)) {
            return [
                'prefix' => $matches[1],
                'year' => '20' . $matches[2],
                'sequence' => (int)$matches[3],
            ];
        }

        return null;
    }

    /**
     * Générer un matricule aléatoire sécurisé (pour les cas spéciaux)
     *
     * @param string $prefix Le préfixe
     * @return string
     */
    public function generateRandomMatricule(string $prefix = 'USR'): string
    {
        do {
            $random = Str::random(8);
            $matricule = "{$prefix}-" . time() . "-{$random}";
        } while ($this->matriculeExists($matricule));

        return $matricule;
    }

    /**
     * Obtenir le label du rôle basé sur le préfixe du matricule
     *
     * @param string $matricule
     * @return string|null
     */
    public function getRoleFromMatricule(string $matricule): ?string
    {
        $prefix = substr($matricule, 0, 3);

        foreach (self::ROLE_PREFIXES as $role => $rolePrefix) {
            if ($rolePrefix === $prefix) {
                return $role;
            }
        }

        return null;
    }

    /**
     * Obtenir tous les préfixes disponibles
     *
     * @return array
     */
    public static function getPrefixes(): array
    {
        return self::ROLE_PREFIXES;
    }
}
