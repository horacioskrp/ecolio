<?php

namespace App\Constants;

/**
 * Permissions disponibles dans l'application
 */
class Permissions
{
    // School Management
    public const VIEW_SCHOOLS = 'view_schools';
    public const CREATE_SCHOOLS = 'create_schools';
    public const EDIT_SCHOOLS = 'edit_schools';
    public const DELETE_SCHOOLS = 'delete_schools';

    // Academic Year Management
    public const VIEW_ACADEMIC_YEARS = 'view_academic_years';
    public const CREATE_ACADEMIC_YEARS = 'create_academic_years';
    public const EDIT_ACADEMIC_YEARS = 'edit_academic_years';
    public const DELETE_ACADEMIC_YEARS = 'delete_academic_years';

    // Class Management
    public const VIEW_CLASSES = 'view_classes';
    public const CREATE_CLASSES = 'create_classes';
    public const EDIT_CLASSES = 'edit_classes';
    public const DELETE_CLASSES = 'delete_classes';

    // Student Management
    public const VIEW_STUDENTS = 'view_students';
    public const CREATE_STUDENTS = 'create_students';
    public const EDIT_STUDENTS = 'edit_students';
    public const DELETE_STUDENTS = 'delete_students';
    public const VIEW_STUDENT_PARENTS_INFO = 'view_student_parents_info';

    // Subject Management
    public const VIEW_SUBJECTS = 'view_subjects';
    public const CREATE_SUBJECTS = 'create_subjects';
    public const EDIT_SUBJECTS = 'edit_subjects';
    public const DELETE_SUBJECTS = 'delete_subjects';

    // Grade Management
    public const VIEW_GRADES = 'view_grades';
    public const CREATE_GRADES = 'create_grades';
    public const EDIT_GRADES = 'edit_grades';
    public const DELETE_GRADES = 'delete_grades';

    // Attendance Management
    public const VIEW_ATTENDANCES = 'view_attendances';
    public const CREATE_ATTENDANCES = 'create_attendances';
    public const EDIT_ATTENDANCES = 'edit_attendances';
    public const DELETE_ATTENDANCES = 'delete_attendances';

    // User Management
    public const VIEW_USERS = 'view_users';
    public const CREATE_USERS = 'create_users';
    public const EDIT_USERS = 'edit_users';
    public const DELETE_USERS = 'delete_users';

    // Report Management
    public const VIEW_REPORTS = 'view_reports';
    public const EXPORT_REPORTS = 'export_reports';

    // Financial Management
    public const VIEW_FINANCES = 'view_finances';
    public const CREATE_FINANCES = 'create_finances';
    public const EDIT_FINANCES = 'edit_finances';
    public const DELETE_FINANCES = 'delete_finances';

    // Settings
    public const MANAGE_SETTINGS = 'manage_settings';
    public const MANAGE_ROLES_PERMISSIONS = 'manage_roles_permissions';

    /**
     * Get all available permissions
     */
    public static function all(): array
    {
        return [
            // Schools
            self::VIEW_SCHOOLS,
            self::CREATE_SCHOOLS,
            self::EDIT_SCHOOLS,
            self::DELETE_SCHOOLS,

            // Academic Years
            self::VIEW_ACADEMIC_YEARS,
            self::CREATE_ACADEMIC_YEARS,
            self::EDIT_ACADEMIC_YEARS,
            self::DELETE_ACADEMIC_YEARS,

            // Classes
            self::VIEW_CLASSES,
            self::CREATE_CLASSES,
            self::EDIT_CLASSES,
            self::DELETE_CLASSES,

            // Students
            self::VIEW_STUDENTS,
            self::CREATE_STUDENTS,
            self::EDIT_STUDENTS,
            self::DELETE_STUDENTS,
            self::VIEW_STUDENT_PARENTS_INFO,

            // Subjects
            self::VIEW_SUBJECTS,
            self::CREATE_SUBJECTS,
            self::EDIT_SUBJECTS,
            self::DELETE_SUBJECTS,

            // Grades
            self::VIEW_GRADES,
            self::CREATE_GRADES,
            self::EDIT_GRADES,
            self::DELETE_GRADES,

            // Attendances
            self::VIEW_ATTENDANCES,
            self::CREATE_ATTENDANCES,
            self::EDIT_ATTENDANCES,
            self::DELETE_ATTENDANCES,

            // Users
            self::VIEW_USERS,
            self::CREATE_USERS,
            self::EDIT_USERS,
            self::DELETE_USERS,

            // Reports
            self::VIEW_REPORTS,
            self::EXPORT_REPORTS,

            // Finances
            self::VIEW_FINANCES,
            self::CREATE_FINANCES,
            self::EDIT_FINANCES,
            self::DELETE_FINANCES,

            // Settings
            self::MANAGE_SETTINGS,
            self::MANAGE_ROLES_PERMISSIONS,
        ];
    }

    /**
     * Get permission labels
     */
    public static function labels(): array
    {
        return [
            // Schools
            self::VIEW_SCHOOLS => 'Voir les écoles',
            self::CREATE_SCHOOLS => 'Créer des écoles',
            self::EDIT_SCHOOLS => 'Éditer les écoles',
            self::DELETE_SCHOOLS => 'Supprimer les écoles',

            // Academic Years
            self::VIEW_ACADEMIC_YEARS => 'Voir les années académiques',
            self::CREATE_ACADEMIC_YEARS => 'Créer les années académiques',
            self::EDIT_ACADEMIC_YEARS => 'Éditer les années académiques',
            self::DELETE_ACADEMIC_YEARS => 'Supprimer les années académiques',

            // Classes
            self::VIEW_CLASSES => 'Voir les classes',
            self::CREATE_CLASSES => 'Créer des classes',
            self::EDIT_CLASSES => 'Éditer les classes',
            self::DELETE_CLASSES => 'Supprimer les classes',

            // Students
            self::VIEW_STUDENTS => 'Voir les étudiants',
            self::CREATE_STUDENTS => 'Créer des étudiants',
            self::EDIT_STUDENTS => 'Éditer les étudiants',
            self::DELETE_STUDENTS => 'Supprimer les étudiants',
            self::VIEW_STUDENT_PARENTS_INFO => 'Voir les infos des parents',

            // Subjects
            self::VIEW_SUBJECTS => 'Voir les matières',
            self::CREATE_SUBJECTS => 'Créer des matières',
            self::EDIT_SUBJECTS => 'Éditer les matières',
            self::DELETE_SUBJECTS => 'Supprimer les matières',

            // Grades
            self::VIEW_GRADES => 'Voir les notes',
            self::CREATE_GRADES => 'Créer des notes',
            self::EDIT_GRADES => 'Éditer les notes',
            self::DELETE_GRADES => 'Supprimer les notes',

            // Attendances
            self::VIEW_ATTENDANCES => 'Voir les présences',
            self::CREATE_ATTENDANCES => 'Créer des présences',
            self::EDIT_ATTENDANCES => 'Éditer les présences',
            self::DELETE_ATTENDANCES => 'Supprimer les présences',

            // Users
            self::VIEW_USERS => 'Voir les utilisateurs',
            self::CREATE_USERS => 'Créer des utilisateurs',
            self::EDIT_USERS => 'Éditer les utilisateurs',
            self::DELETE_USERS => 'Supprimer les utilisateurs',

            // Reports
            self::VIEW_REPORTS => 'Voir les rapports',
            self::EXPORT_REPORTS => 'Exporter les rapports',

            // Finances
            self::VIEW_FINANCES => 'Voir les finances',
            self::CREATE_FINANCES => 'Créer des finances',
            self::EDIT_FINANCES => 'Éditer les finances',
            self::DELETE_FINANCES => 'Supprimer les finances',

            // Settings
            self::MANAGE_SETTINGS => 'Gérer les paramètres',
            self::MANAGE_ROLES_PERMISSIONS => 'Gérer les rôles et permissions',
        ];
    }

    /**
     * Get permission label by key
     */
    public static function label(string $permission): string
    {
        return self::labels()[$permission] ?? $permission;
    }
}
