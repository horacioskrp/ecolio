<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\User;
use App\Models\Student;
use App\Constants\Roles;
use Illuminate\Database\Seeder;

class SchoolDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create schools
        $primarySchool = School::updateOrCreate(
            ['code' => 'EPTOGO001'],
            [
                'name' => 'École Primaire Togo',
                'level' => 'primaire',
                'address' => 'Lomé, Togo',
                'phone' => '+228 22 123 456',
                'email' => 'primary@ecoliotogo.tg',
                'principal' => 'Jean Dupont',
                'description' => 'École primaire reconnue du Togo',
                'active' => true,
            ]
        );

        $collegSchool = School::updateOrCreate(
            ['code' => 'COLTOGO001'],
            [
                'name' => 'Collège Togo',
                'level' => 'college',
                'address' => 'Atakpamé, Togo',
                'phone' => '+228 22 789 012',
                'email' => 'college@ecoliotogo.tg',
                'principal' => 'Marie Assou',
                'description' => 'Collège d\'enseignement général',
                'active' => true,
            ]
        );

        $lyceeSchool = School::updateOrCreate(
            ['code' => 'LYTOGO001'],
            [
                'name' => 'Lycée Togo',
                'level' => 'lycee',
                'address' => 'Kara, Togo',
                'phone' => '+228 22 345 678',
                'email' => 'lycee@ecoliotogo.tg',
                'principal' => 'Pierre Kolani',
                'description' => 'Lycée d\'enseignement secondaire',
                'active' => true,
            ]
        );

        // Create academic years
        $year2024 = AcademicYear::updateOrCreate(
            ['school_id' => $primarySchool->id, 'year' => '2024-2025'],
            [
                'start_date' => '2024-09-01',
                'end_date' => '2025-07-31',
                'active' => true,
            ]
        );

        $year2024College = AcademicYear::updateOrCreate(
            ['school_id' => $collegSchool->id, 'year' => '2024-2025'],
            [
                'start_date' => '2024-09-01',
                'end_date' => '2025-07-31',
                'active' => true,
            ]
        );

        $year2024Lycee = AcademicYear::updateOrCreate(
            ['school_id' => $lyceeSchool->id, 'year' => '2024-2025'],
            [
                'start_date' => '2024-09-01',
                'end_date' => '2025-07-31',
                'active' => true,
            ]
        );

        // Create users (staff)
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@ecoliotogo.tg'],
            [
                'natricule' => 'ADM001',
                'firstname' => 'Admin',
                'lastname' => 'System',
                'gender' => 'male',
                'password' => bcrypt('password'),
            ]
        );
        if (!$adminUser->hasRole(Roles::ADMINISTRATOR)) {
            $adminUser->assignRole(Roles::ADMINISTRATOR);
        }

        $teacher1 = User::updateOrCreate(
            ['email' => 'sophie.martin@ecoliotogo.tg'],
            [
                'natricule' => 'PROF001',
                'firstname' => 'Sophie',
                'lastname' => 'Martin',
                'gender' => 'female',
                'password' => bcrypt('password'),
            ]
        );
        if (!$teacher1->hasRole(Roles::TEACHER)) {
            $teacher1->assignRole(Roles::TEACHER);
        }

        $teacher2 = User::updateOrCreate(
            ['email' => 'paul.durand@ecoliotogo.tg'],
            [
                'natricule' => 'PROF002',
                'firstname' => 'Paul',
                'lastname' => 'Durand',
                'gender' => 'male',
                'password' => bcrypt('password'),
            ]
        );
        if (!$teacher2->hasRole(Roles::TEACHER)) {
            $teacher2->assignRole(Roles::TEACHER);
        }

        $accountant = User::updateOrCreate(
            ['email' => 'claire@ecoliotogo.tg'],
            [
                'natricule' => 'COMPT001',
                'firstname' => 'Claire',
                'lastname' => 'Lemoine',
                'gender' => 'female',
                'password' => bcrypt('password'),
            ]
        );
        if (!$accountant->hasRole(Roles::ACCOUNTING)) {
            $accountant->assignRole(Roles::ACCOUNTING);
        }

        $secretary = User::updateOrCreate(
            ['email' => 'isabelle@ecoliotogo.tg'],
            [
                'natricule' => 'SEC001',
                'firstname' => 'Isabelle',
                'lastname' => 'Richard',
                'gender' => 'female',
                'password' => bcrypt('password'),
            ]
        );
        if (!$secretary->hasRole(Roles::SECRETARIAT)) {
            $secretary->assignRole(Roles::SECRETARIAT);
        }

        // Create classes
        $class1 = Classroom::updateOrCreate(
            ['code' => 'CM1A'],
            [
                'name' => 'CM1 A',
                'capacity' => 40,
            ]
        );

        $class2 = Classroom::updateOrCreate(
            ['code' => 'CM2A'],
            [
                'name' => 'CM2 A',
                'capacity' => 35,
            ]
        );

        // Create students
        for ($i = 1; $i <= 10; $i++) {
            $user = User::updateOrCreate(
                ['email' => 'etudiant' . $i . '@ecoliotogo.tg'],
                [
                    'natricule' => 'STU' . str_pad($i, 4, '0', STR_PAD_LEFT),
                    'firstname' => 'Etudiant',
                    'lastname' => 'Togo' . $i,
                    'gender' => $i % 2 == 0 ? 'female' : 'male',
                    'password' => bcrypt('password'),
                ]
            );

            Student::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'class_id' => $class1->id,
                    'registration_number' => 'REG' . str_pad($i, 5, '0', STR_PAD_LEFT),
                    'parent_name' => 'Parent Togo ' . $i,
                    'parent_phone' => '+228 ' . rand(20000000, 99999999),
                    'parent_email' => 'parent' . $i . '@example.tg',
                    'enrollment_date' => now(),
                    'active' => true,
                ]
            );
        }

        $this->command->info('Données de démonstration créées avec succès!');
    }
}
