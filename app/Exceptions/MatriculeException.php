<?php

namespace App\Exceptions;

use Exception;

class MatriculeException extends Exception
{
    /**
     * Exception thrown when matricule generation fails.
     */
    public static function generationFailed(string $role): self
    {
        return new self("Impossible de générer un matricule pour le rôle '{$role}'.");
    }

    /**
     * Exception thrown when matricule format is invalid.
     */
    public static function invalidFormat(string $matricule): self
    {
        return new self("Le format du matricule '{$matricule}' est invalide.");
    }

    /**
     * Exception thrown when matricule already exists.
     */
    public static function alreadyExists(string $matricule): self
    {
        return new self("Le matricule '{$matricule}' existe déjà.");
    }

    /**
     * Exception thrown when role is not found.
     */
    public static function roleNotFound(string $role): self
    {
        return new self("Le rôle '{$role}' n'existe pas.");
    }

    /**
     * Exception thrown when parsing matricule fails.
     */
    public static function parsingFailed(string $matricule): self
    {
        return new self("Impossible de parser le matricule '{$matricule}'.");
    }

    /**
     * Exception thrown when user or student is not found.
     */
    public static function modelNotFound(string $type, string $id): self
    {
        return new self("Le {$type} avec l'ID '{$id}' n'a pas été trouvé.");
    }
}
