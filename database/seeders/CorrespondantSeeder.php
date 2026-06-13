<?php

namespace Database\Seeders;

use App\Models\Correspondant;
use Illuminate\Database\Seeder;

class CorrespondantSeeder extends Seeder
{
    public function run(): void
    {
        $correspondants = [
            [
                'nom_structure' => 'Ministère de l’Éducation Nationale',
                'nom_contact' => 'N. Kouassi',
                'email' => 'contact@education.gouv.ci',
                'telephone' => '+225 27 20 00 00 01',
                'adresse' => 'Plateau, Abidjan',
            ],
            [
                'nom_structure' => 'Mairie de Yopougon',
                'nom_contact' => 'A. Traoré',
                'email' => 'courrier@mairie-yop.ci',
                'telephone' => '+225 27 23 00 00 02',
                'adresse' => 'Yopougon, Abidjan',
            ],
            [
                'nom_structure' => 'SODECI',
                'nom_contact' => 'Service Relations Clients',
                'email' => 'relations@sodeci.ci',
                'telephone' => '+225 27 21 00 00 03',
                'adresse' => 'Treichville, Abidjan',
            ],
            [
                'nom_structure' => 'CIE',
                'nom_contact' => 'Cellule Institutionnelle',
                'email' => 'institutionnel@cie.ci',
                'telephone' => '+225 27 21 00 00 04',
                'adresse' => 'Plateau, Abidjan',
            ],
            [
                'nom_structure' => 'Université Félix Houphouët-Boigny',
                'nom_contact' => 'Secrétariat Central',
                'email' => 'secretariat@ufhb.edu.ci',
                'telephone' => '+225 27 22 00 00 05',
                'adresse' => 'Cocody, Abidjan',
            ],
        ];

        foreach ($correspondants as $correspondant) {
            Correspondant::firstOrCreate(
                ['nom_structure' => $correspondant['nom_structure']],
                $correspondant,
            );
        }
    }
}
