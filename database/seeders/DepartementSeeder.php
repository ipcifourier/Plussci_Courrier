<?php

namespace Database\Seeders;

use App\Models\Departement;
use Illuminate\Database\Seeder;

class DepartementSeeder extends Seeder
{
    public function run(): void
    {
        $departements = [
            ['nom' => 'Direction Générale', 'description' => 'Pilotage et décisions stratégiques.'],
            ['nom' => 'Secrétariat', 'description' => 'Gestion administrative et suivi des dossiers.'],
            ['nom' => 'Finances', 'description' => 'Comptabilité, budget et engagements.'],
            ['nom' => 'Ressources Humaines', 'description' => 'Gestion du personnel et carrière.'],
            ['nom' => 'Informatique', 'description' => 'Systèmes, réseau et support utilisateur.'],
        ];

        foreach ($departements as $departement) {
            Departement::firstOrCreate(['nom' => $departement['nom']], $departement);
        }
    }
}
