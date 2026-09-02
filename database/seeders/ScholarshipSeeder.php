<?php

namespace Database\Seeders;

use App\Models\Scholarship;
use Illuminate\Database\Seeder;

class ScholarshipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $scholarships = [
            [
                'name' => 'Bourse d\'Excellence Académique',
                'description' => 'Bourse attribuée aux élèves ayant obtenu d\'excellents résultats scolaires',
                'type' => 'fixed',
                'value' => 50000,
            ],
            [
                'name' => 'Bourse Sociale',
                'description' => 'Bourse destinée aux élèves issus de familles défavorisées',
                'type' => 'fixed',
                'value' => 75000,
            ],
            [
                'name' => 'Bourse Sportive',
                'description' => 'Bourse pour les élèves excellant dans les activités sportives',
                'type' => 'fixed',
                'value' => 30000,
            ],
            [
                'name' => 'Bourse Culturelle',
                'description' => 'Bourse pour les élèves participant activement aux activités culturelles',
                'type' => 'fixed',
                'value' => 25000,
            ],
            [
                'name' => 'Bourse de Mérite',
                'description' => 'Bourse générale pour les élèves méritants',
                'type' => 'fixed',
                'value' => 40000,
            ],
        ];

        foreach ($scholarships as $scholarship) {
            Scholarship::firstOrCreate(['name' => $scholarship['name']], $scholarship);
        }
    }
}
