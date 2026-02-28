<?php

namespace Database\Seeders;

use App\Constants\Permissions;
use App\Constants\Roles;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()['cache']->forget('spatie.permission.cache');

        // Create all permissions
        $permissions = [
            // Schools
            ['name' => Permissions::VIEW_SCHOOLS, 'description' => 'Voir les écoles'],
            ['name' => Permissions::CREATE_SCHOOLS, 'description' => 'Créer une école'],
            ['name' => Permissions::EDIT_SCHOOLS, 'description' => 'Modifier une école'],
            ['name' => Permissions::DELETE_SCHOOLS, 'description' => 'Supprimer une école'],

            // Academic Years
            ['name' => Permissions::VIEW_ACADEMIC_YEARS, 'description' => 'Voir les années scolaires'],
            ['name' => Permissions::CREATE_ACADEMIC_YEARS, 'description' => 'Créer une année scolaire'],
            ['name' => Permissions::EDIT_ACADEMIC_YEARS, 'description' => 'Modifier une année scolaire'],
            ['name' => Permissions::DELETE_ACADEMIC_YEARS, 'description' => 'Supprimer une année scolaire'],

            // Classes
            ['name' => Permissions::VIEW_CLASSES, 'description' => 'Voir les classes'],
            ['name' => Permissions::CREATE_CLASSES, 'description' => 'Créer une classe'],
            ['name' => Permissions::EDIT_CLASSES, 'description' => 'Modifier une classe'],
            ['name' => Permissions::DELETE_CLASSES, 'description' => 'Supprimer une classe'],

            // Students
            ['name' => Permissions::VIEW_STUDENTS, 'description' => 'Voir les élèves'],
            ['name' => Permissions::CREATE_STUDENTS, 'description' => 'Créer un élève'],
            ['name' => Permissions::EDIT_STUDENTS, 'description' => 'Modifier un élève'],
            ['name' => Permissions::DELETE_STUDENTS, 'description' => 'Supprimer un élève'],
            ['name' => Permissions::VIEW_STUDENT_PARENTS_INFO, 'description' => 'Voir les informations des parents'],

            // Subjects
            ['name' => Permissions::VIEW_SUBJECTS, 'description' => 'Voir les matières'],
            ['name' => Permissions::CREATE_SUBJECTS, 'description' => 'Créer une matière'],
            ['name' => Permissions::EDIT_SUBJECTS, 'description' => 'Modifier une matière'],
            ['name' => Permissions::DELETE_SUBJECTS, 'description' => 'Supprimer une matière'],

            // Grades
            ['name' => Permissions::VIEW_GRADES, 'description' => 'Voir les notes'],
            ['name' => Permissions::CREATE_GRADES, 'description' => 'Créer une note'],
            ['name' => Permissions::EDIT_GRADES, 'description' => 'Modifier une note'],
            ['name' => Permissions::DELETE_GRADES, 'description' => 'Supprimer une note'],

            // Attendance
            ['name' => Permissions::VIEW_ATTENDANCES, 'description' => 'Voir les présences'],
            ['name' => Permissions::CREATE_ATTENDANCES, 'description' => 'Créer une présence'],
            ['name' => Permissions::EDIT_ATTENDANCES, 'description' => 'Modifier une présence'],
            ['name' => Permissions::DELETE_ATTENDANCES, 'description' => 'Supprimer une présence'],

            // Users
            ['name' => Permissions::VIEW_USERS, 'description' => 'Voir les utilisateurs'],
            ['name' => Permissions::CREATE_USERS, 'description' => 'Créer un utilisateur'],
            ['name' => Permissions::EDIT_USERS, 'description' => 'Modifier un utilisateur'],
            ['name' => Permissions::DELETE_USERS, 'description' => 'Supprimer un utilisateur'],

            // Reports
            ['name' => Permissions::VIEW_REPORTS, 'description' => 'Voir les rapports'],
            ['name' => Permissions::EXPORT_REPORTS, 'description' => 'Exporter des rapports'],

            // Finances
            ['name' => Permissions::VIEW_FINANCES, 'description' => 'Voir les finances'],
            ['name' => Permissions::CREATE_FINANCES, 'description' => 'Créer une finance'],
            ['name' => Permissions::EDIT_FINANCES, 'description' => 'Modifier une finance'],
            ['name' => Permissions::DELETE_FINANCES, 'description' => 'Supprimer une finance'],

            // Settings
            ['name' => Permissions::MANAGE_SETTINGS, 'description' => 'Gérer les paramètres'],
            ['name' => Permissions::MANAGE_ROLES_PERMISSIONS, 'description' => 'Gérer les rôles et permissions'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission['name']], $permission);
        }

        // Create roles and assign permissions
        // Administrator role - has all permissions
        $admin = Role::firstOrCreate(['name' => Roles::ADMINISTRATOR]);
        $admin->syncPermissions(Permission::pluck('name'));

        // Director role
        $director = Role::firstOrCreate(['name' => Roles::DIRECTOR]);
        $directorPermissions = [
            Permissions::VIEW_SCHOOLS,
            Permissions::VIEW_ACADEMIC_YEARS,
            Permissions::VIEW_CLASSES,
            Permissions::CREATE_CLASSES,
            Permissions::EDIT_CLASSES,
            Permissions::VIEW_STUDENTS,
            Permissions::CREATE_STUDENTS,
            Permissions::EDIT_STUDENTS,
            Permissions::VIEW_SUBJECTS,
            Permissions::VIEW_GRADES,
            Permissions::VIEW_ATTENDANCES,
            Permissions::VIEW_USERS,
            Permissions::VIEW_REPORTS,
            Permissions::VIEW_FINANCES,
        ];
        $director->syncPermissions($directorPermissions);

        // Teacher role
        $teacher = Role::firstOrCreate(['name' => Roles::TEACHER]);
        $teacherPermissions = [
            Permissions::VIEW_STUDENTS,
            Permissions::VIEW_STUDENT_PARENTS_INFO,
            Permissions::VIEW_SUBJECTS,
            Permissions::VIEW_GRADES,
            Permissions::CREATE_GRADES,
            Permissions::EDIT_GRADES,
            Permissions::VIEW_ATTENDANCES,
            Permissions::CREATE_ATTENDANCES,
            Permissions::EDIT_ATTENDANCES,
        ];
        $teacher->syncPermissions($teacherPermissions);

        // Accounting role
        $accounting = Role::firstOrCreate(['name' => Roles::ACCOUNTING]);
        $accountingPermissions = [
            Permissions::VIEW_STUDENTS,
            Permissions::VIEW_FINANCES,
            Permissions::CREATE_FINANCES,
            Permissions::EDIT_FINANCES,
            Permissions::VIEW_REPORTS,
            Permissions::EXPORT_REPORTS,
        ];
        $accounting->syncPermissions($accountingPermissions);

        // Secretariat role
        $secretariat = Role::firstOrCreate(['name' => Roles::SECRETARIAT]);
        $secretariatPermissions = [
            Permissions::VIEW_SCHOOLS,
            Permissions::VIEW_CLASSES,
            Permissions::VIEW_STUDENTS,
            Permissions::CREATE_STUDENTS,
            Permissions::EDIT_STUDENTS,
            Permissions::VIEW_USERS,
            Permissions::CREATE_USERS,
            Permissions::EDIT_USERS,
        ];
        $secretariat->syncPermissions($secretariatPermissions);
    }
}
