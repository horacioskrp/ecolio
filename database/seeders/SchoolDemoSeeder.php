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
        $primarySchool = School::create([
            'name' => 'École Primaire Togo',
            'level' => 'primaire',
            'code' => 'EPTOGO001',
            'address' => 'Lomé, Togo',
            'phone' => '+228 22 123 456',
            'email' => 'primary@ecoliotogo.tg',
            'principal' => 'Jean Dupont',
            'description' => 'École primaire reconnue du Togo',
            'active' => true,
        ]);

        $collegSchool = School::create([
            'name' => 'Collège Togo',
            'level' => 'college',
            'code' => 'COLTOGO001',
            'address' => 'Atakpamé, Togo',
            'phone' => '+228 22 789 012',
            'email' => 'college@ecoliotogo.tg',
            'principal' => 'Marie Assou',
            'description' => 'Collège d\'enseignement général',
            'active' => true,
        ]);

        $lyceeSchool = School::create([
            'name' => 'Lycée Togo',
            'level' => 'lycee',
            'code' => 'LYTOGO001',
            'address' => 'Kara, Togo',
            'phone' => '+228 22 345 678',
            'email' => 'lycee@ecoliotogo.tg',
            'principal' => 'Pierre Kolani',
            'description' => 'Lycée d\'enseignement secondaire',
            'active' => true,
        ]);

        // Create academic years
        $year2024 = AcademicYear::create([
            'school_id' => $primarySchool->id,
            'year' => '2024-2025',
            'start_date' => '2024-09-01',
            'end_date' => '2025-07-31',
            'active' => true,
        ]);

        $year2024College = AcademicYear::create([
            'school_id' => $collegSchool->id,
            'year' => '2024-2025',
            'start_date' => '2024-09-01',
            'end_date' => '2025-07-31',
            'active' => true,
        ]);

        $year2024Lycee = AcademicYear::create([
            'school_id' => $lyceeSchool->id,
            'year' => '2024-2025',
            'start_date' => '2024-09-01',
            'end_date' => '2025-07-31',
            'active' => true,
        ]);

        // Create users (staff)
        $adminUser = User::create([
            'natricule' => 'ADM001',
            'firstname' => 'Admin',
            'lastname' => 'System',
            'name' => 'Admin System',
            'gender' => 'male',
            'email' => 'admin@ecoliotogo.tg',
            'password' => bcrypt('password'),
            'school_id' => $primarySchool->id,
        ]);
        $adminUser->assignRole(Roles::ADMINISTRATOR);

        $teacher1 = User::create([
            'natricule' => 'PROF001',
            'firstname' => 'Sophie',
            'lastname' => 'Martin',
            'name' => 'Sophie Martin',
            'gender' => 'female',
            'email' => 'sophie.martin@ecoliotogo.tg',
            'password' => bcrypt('password'),
            'school_id' => $primarySchool->id,
        ]);
        $teacher1->assignRole(Roles::TEACHER);

        $teacher2 = User::create([
            'natricule' => 'PROF002',
            'firstname' => 'Paul',
            'lastname' => 'Durand',
            'name' => 'Paul Durand',
            'gender' => 'male',
            'email' => 'paul.durand@ecoliotogo.tg',
            'password' => bcrypt('password'),
            'school_id' => $primarySchool->id,
        ]);
        $teacher2->assignRole(Roles::TEACHER);

        $accountant = User::create([
            'natricule' => 'COMPT001',
            'firstname' => 'Claire',
            'lastname' => 'Lemoine',
            'name' => 'Claire Lemoine',
            'gender' => 'female',
            'email' => 'claire@ecoliotogo.tg',
            'password' => bcrypt('password'),
            'school_id' => $primarySchool->id,
        ]);
        $accountant->assignRole(Roles::ACCOUNTING);

        $secretary = User::create([
            'natricule' => 'SEC001',
            'firstname' => 'Isabelle',
            'lastname' => 'Richard',
            'name' => 'Isabelle Richard',
            'gender' => 'female',
            'email' => 'isabelle@ecoliotogo.tg',
            'password' => bcrypt('password'),
            'school_id' => $primarySchool->id,
        ]);
        $secretary->assignRole(Roles::SECRETARIAT);

        // Create classes
        $class1 = Classroom::create([
            'school_id' => $primarySchool->id,
            'academic_year_id' => $year2024->id,
            'name' => 'CM1 A',
            'code' => 'CM1A',
            'capacity' => 40,
            'teacher_id' => $teacher1->id,
        ]);

        $class2 = Classroom::create([
            'school_id' => $primarySchool->id,
            'academic_year_id' => $year2024->id,
            'name' => 'CM2 A',
            'code' => 'CM2A',
            'capacity' => 35,
            'teacher_id' => $teacher2->id,
        ]);

        // Create students
        for ($i = 1; $i <= 10; $i++) {
            $user = User::create([
                'natricule' => 'STU' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'firstname' => 'Etudiant',
                'lastname' => 'Togo' . $i,
                'name' => 'Etudiant Togo ' . $i,
                'gender' => $i % 2 == 0 ? 'female' : 'male',
                'email' => 'etudiant' . $i . '@ecoliotogo.tg',
                'password' => bcrypt('password'),
                'school_id' => $primarySchool->id,
            ]);

            Student::create([
                'user_id' => $user->id,
                'class_id' => $class1->id,
                'registration_number' => 'REG' . str_pad($i, 5, '0', STR_PAD_LEFT),
                'parent_name' => 'Parent Togo ' . $i,
                'parent_phone' => '+228 ' . rand(20000000, 99999999),
                'parent_email' => 'parent' . $i . '@example.tg',
                'enrollment_date' => now(),
                'active' => true,
            ]);
        }

        $this->command->info('Données de démonstration créées avec succès!');
    }
}
