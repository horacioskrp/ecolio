<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\School;
use App\Constants\Roles;
use Illuminate\Database\Seeder;

class CreateAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create a default school
        $school = School::first();
        
        if (!$school) {
            $school = School::create([
                'name' => 'École Centrale',
                'level' => 'primaire',
                'code' => 'ECOLE001',
                'address' => 'Lomé, Togo',
                'phone' => '+228 22 123 456',
                'email' => 'contact@ecoliotogo.tg',
                'principal' => 'Directeur École',
                'description' => 'École de gestion centrale',
                'active' => true,
            ]);
            
            $this->command->info('Nouvelle école créée : ' . $school->name);
        }

        // Check if admin already exists
        $adminExists = User::where('email', 'admin@ecoliotogo.tg')->exists();
        
        if ($adminExists) {
            $this->command->warn('Un administrateur avec l\'email admin@ecoliotogo.tg existe déjà.');
            return;
        }

        // Create admin user
        $admin = User::create([
            'firstname' => 'Admin',
            'lastname' => 'Système',
            'name' => 'Admin Système',
            'email' => 'admin@ecoliotogo.tg',
            'password' => bcrypt('admin@123'),  // À changer lors de la première connexion
            'school_id' => $school->id,
            'gender' => 'male',
            'telephone' => '+228 22 123 456',
            'address' => 'Siège administratif',
        ]);

        // Assign the administrator role
        $admin->assignRole(Roles::ADMINISTRATOR);

        // Generate matricule if not already set
        if (!$admin->natricule) {
            $admin->generateMatricule();
            $admin->save();
        }

        $this->command->info('✅ Administrateur créé avec succès !');
        $this->command->info('Email: ' . $admin->email);
        $this->command->info('Matricule: ' . $admin->natricule);
        $this->command->info('Mot de passe: admin@123 (À changer!)');
    }
}
