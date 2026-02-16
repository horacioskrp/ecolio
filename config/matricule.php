<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Matricule Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration pour le service de génération des matricules.
    |
    */

    'enabled' => env('MATRICULE_SERVICE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | User Matricule Settings
    |--------------------------------------------------------------------------
    */

    'user' => [
        'prefix_length' => 3, // ADM, DIR, PROF, SEC (4 max: COMPT)
        'year_length' => 2,   // 26 for 2026
        'sequence_length' => 3, // 001, 002, etc.
        'auto_generate' => env('MATRICULE_AUTO_GENERATE_USER', true),
        'separator' => '', // empty string for continuous format
    ],

    /*
    |--------------------------------------------------------------------------
    | Student Matricule Settings
    |--------------------------------------------------------------------------
    */

    'student' => [
        'prefix' => 'STU',
        'year_length' => 2,
        'sequence_length' => 3,
        'auto_generate' => env('MATRICULE_AUTO_GENERATE_STUDENT', true),
        'separator' => '', // empty string for continuous format
    ],

    /*
    |--------------------------------------------------------------------------
    | Registration Number Settings
    |--------------------------------------------------------------------------
    */

    'registration' => [
        'prefix' => 'REG',
        'country_code' => env('MATRICULE_COUNTRY_CODE', 'TG'), // Togo
        'year_length' => 4,
        'sequence_length' => 3,
        'auto_generate' => env('MATRICULE_AUTO_GENERATE_REGISTRATION', true),
        'separator' => '-',
    ],

    /*
    |--------------------------------------------------------------------------
    | Role Prefixes
    |--------------------------------------------------------------------------
    */

    'role_prefixes' => [
        'administrateur' => 'ADM',
        'directeur' => 'DIR',
        'enseignant' => 'PROF',
        'comptabilité' => 'COMPT',
        'secrétariat' => 'SEC',
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Settings
    |--------------------------------------------------------------------------
    */

    'database' => [
        'user_table' => 'users',
        'user_matricule_column' => 'natricule',
        'student_table' => 'students',
        'student_matricule_column' => 'registration_number',
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Settings
    |--------------------------------------------------------------------------
    */

    'validation' => [
        'require_unique' => true,
        'allow_custom_format' => env('MATRICULE_ALLOW_CUSTOM', false),
    ],

];
