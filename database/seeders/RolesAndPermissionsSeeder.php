<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Constants\Roles;
use App\Constants\Permissions;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()['cache']->forget('spatie.permission.cache');

        // Create all permissions with descriptions
        $permissionsList = [
            // Schools
            [Permissions::VIEW_SCHOOLS, 'Voir les écoles'],
            [Permissions::CREATE_SCHOOLS, 'Créer une école'],
            [Permissions::EDIT_SCHOOLS, 'Modifier une école'],
            [Permissions::DELETE_SCHOOLS, 'Supprimer une école'],

            // Academic Years
            [Permissions::VIEW_ACADEMIC_YEARS, 'Voir les années scolaires'],
            [Permissions::CREATE_ACADEMIC_YEARS, 'Créer une année scolaire'],
            [Permissions::EDIT_ACADEMIC_YEARS, 'Modifier une année scolaire'],
            [Permissions::DELETE_ACADEMIC_YEARS, 'Supprimer une année scolaire'],

            // Classes
            [Permissions::VIEW_CLASSES, 'Voir les classes'],
            [Permissions::CREATE_CLASSES, 'Créer une classe'],
            [Permissions::EDIT_CLASSES, 'Modifier une classe'],
            [Permissions::DELETE_CLASSES, 'Supprimer une classe'],

            // Students
            [Permissions::VIEW_STUDENTS, 'Voir les élèves'],
            [Permissions::CREATE_STUDENTS, 'Créer un élève'],
            [Permissions::EDIT_STUDENTS, 'Modifier un élève'],
            [Permissions::DELETE_STUDENTS, 'Supprimer un élève'],
            [Permissions::VIEW_STUDENT_PARENTS_INFO, 'Voir les informations des parents'],

            // Subjects
            [Permissions::VIEW_SUBJECTS, 'Voir les matières'],
            [Permissions::CREATE_SUBJECTS, 'Créer une matière'],
            [Permissions::EDIT_SUBJECTS, 'Modifier une matière'],
            [Permissions::DELETE_SUBJECTS, 'Supprimer une matière'],

            // Grades
            [Permissions::VIEW_GRADES, 'Voir les notes'],
            [Permissions::CREATE_GRADES, 'Créer une note'],
            [Permissions::EDIT_GRADES, 'Modifier une note'],
            [Permissions::DELETE_GRADES, 'Supprimer une note'],

            // Attendance
            [Permissions::VIEW_ATTENDANCES, 'Voir les présences'],
            [Permissions::CREATE_ATTENDANCES, 'Créer une présence'],
            [Permissions::EDIT_ATTENDANCES, 'Modifier une présence'],
            [Permissions::DELETE_ATTENDANCES, 'Supprimer une présence'],

            // Users
            [Permissions::VIEW_USERS, 'Voir les utilisateurs'],
            [Permissions::CREATE_USERS, 'Créer un utilisateur'],
            [Permissions::EDIT_USERS, 'Modifier un utilisateur'],
            [Permissions::DELETE_USERS, 'Supprimer un utilisateur'],

            // Reports
            [Permissions::VIEW_REPORTS, 'Voir les rapports'],
            [Permissions::EXPORT_REPORTS, 'Exporter des rapports'],

            // Finances
            [Permissions::VIEW_FINANCES, 'Voir les finances'],
            [Permissions::CREATE_FINANCES, 'Créer une finance'],
            [Permissions::EDIT_FINANCES, 'Modifier une finance'],
            [Permissions::DELETE_FINANCES, 'Supprimer une finance'],

            // Settings
            [Permissions::MANAGE_SETTINGS, 'Gérer les paramètres'],
            [Permissions::MANAGE_ROLES_PERMISSIONS, 'Gérer les rôles et permissions'],
        ];

        foreach ($permissionsList as [$name, $description]) {
            Permission::firstOrCreate(['name' => $name], ['description' => $description]);
        }

        // Create Admin role and assign all permissions
        $adminRole = Role::firstOrCreate(['name' => Roles::ADMINISTRATOR]);
        $adminRole->syncPermissions(Permission::pluck('name'));

        // Create Director role
        $directorRole = Role::firstOrCreate(['name' => Roles::DIRECTOR]);
        $directorPermissions = [
            Permissions::VIEW_SCHOOLS,
            Permissions::VIEW_ACADEMIC_YEARS, Permissions::CREATE_ACADEMIC_YEARS, Permissions::EDIT_ACADEMIC_YEARS,
            Permissions::VIEW_CLASSES, Permissions::CREATE_CLASSES, Permissions::EDIT_CLASSES,
            Permissions::VIEW_STUDENTS, Permissions::CREATE_STUDENTS, Permissions::EDIT_STUDENTS,
            Permissions::VIEW_SUBJECTS, Permissions::CREATE_SUBJECTS, Permissions::EDIT_SUBJECTS,
            Permissions::VIEW_GRADES, Permissions::VIEW_ATTENDANCES,
            Permissions::VIEW_USERS, Permissions::CREATE_USERS, Permissions::EDIT_USERS,
            Permissions::VIEW_REPORTS, Permissions::EXPORT_REPORTS,
            Permissions::VIEW_FINANCES,
        ];
        $directorRole->syncPermissions($directorPermissions);

        // Create Teacher role
        $teacherRole = Role::firstOrCreate(['name' => Roles::TEACHER]);
        $teacherPermissions = [
            Permissions::VIEW_CLASSES,
            Permissions::VIEW_STUDENTS,
            Permissions::VIEW_STUDENT_PARENTS_INFO,
            Permissions::CREATE_GRADES, Permissions::EDIT_GRADES, Permissions::VIEW_GRADES,
            Permissions::CREATE_ATTENDANCES, Permissions::EDIT_ATTENDANCES, Permissions::VIEW_ATTENDANCES,
            Permissions::VIEW_SUBJECTS,
        ];
        $teacherRole->syncPermissions($teacherPermissions);

        // Create Accountant role
        $accountantRole = Role::firstOrCreate(['name' => Roles::ACCOUNTING]);
        $accountantPermissions = [
            Permissions::VIEW_SCHOOLS,
            Permissions::VIEW_STUDENTS,
            Permissions::VIEW_FINANCES, Permissions::CREATE_FINANCES, Permissions::EDIT_FINANCES,
            Permissions::VIEW_REPORTS, Permissions::EXPORT_REPORTS,
            Permissions::VIEW_USERS,
        ];
        $accountantRole->syncPermissions($accountantPermissions);

        // Create Secretary role
        $secretaryRole = Role::firstOrCreate(['name' => Roles::SECRETARIAT]);
        $secretaryPermissions = [
            Permissions::VIEW_SCHOOLS,
            Permissions::VIEW_CLASSES,
            Permissions::VIEW_STUDENTS, Permissions::CREATE_STUDENTS, Permissions::EDIT_STUDENTS,
            Permissions::VIEW_STUDENT_PARENTS_INFO,
            Permissions::VIEW_SUBJECTS,
            Permissions::VIEW_ATTENDANCES,
            Permissions::VIEW_USERS, Permissions::CREATE_USERS, Permissions::EDIT_USERS,
            Permissions::VIEW_REPORTS,
        ];
        $secretaryRole->syncPermissions($secretaryPermissions);

        $this->command->info('Rôles et permissions créés avec succès!');
    }
}
