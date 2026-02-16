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

        // Create all permissions
        $permissions = Permissions::all();
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create Admin role and assign all permissions
        $adminRole = Role::firstOrCreate(['name' => Roles::ADMINISTRATOR]);
        $adminRole->syncPermissions($permissions);

        // Create Director role
        $directorRole = Role::firstOrCreate(['name' => Roles::DIRECTOR]);
        $directorPermissions = [
            Permissions::VIEW_SCHOOLS, Permissions::EDIT_SCHOOLS,
            Permissions::VIEW_ACADEMIC_YEARS, Permissions::CREATE_ACADEMIC_YEARS, Permissions::EDIT_ACADEMIC_YEARS,
            Permissions::VIEW_CLASSES, Permissions::CREATE_CLASSES, Permissions::EDIT_CLASSES,
            Permissions::VIEW_STUDENTS, Permissions::CREATE_STUDENTS, Permissions::EDIT_STUDENTS,
            Permissions::VIEW_SUBJECTS, Permissions::CREATE_SUBJECTS, Permissions::EDIT_SUBJECTS,
            Permissions::VIEW_GRADES, Permissions::VIEW_ATTENDANCES,
            Permissions::VIEW_USERS, Permissions::CREATE_USERS, Permissions::EDIT_USERS,
            Permissions::VIEW_REPORTS, Permissions::EXPORT_REPORTS,
            Permissions::MANAGE_SETTINGS,
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
