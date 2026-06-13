<?php

namespace Database\Seeders;

use App\Models\Correspondant;
use App\Models\Courrier;
use App\Models\Imputation;
use App\Models\User;
use Illuminate\Database\Seeder;

class CourrierSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::query()->get();
        $correspondants = Correspondant::query()->get();

        if ($users->isEmpty() || $correspondants->isEmpty()) {
            return;
        }

        $types = ['Entrant', 'Sortant'];
        $priorites = ['Normale', 'Urgente'];
        $statuts = ['Nouveau', 'En cours', 'Traité'];
        $confidentialites = ['Standard', 'Confidentiel', 'Personnel'];

        for ($index = 1; $index <= 12; $index++) {
            $courrier = Courrier::firstOrCreate(
                ['reference' => 'CR-' . now()->format('Y') . '-' . str_pad((string) $index, 4, '0', STR_PAD_LEFT)],
                [
                    'type' => $types[array_rand($types)],
                    'date_reception_envoi' => now()->subDays(rand(1, 60))->toDateString(),
                    'objet' => 'Objet de courrier de test n°' . $index,
                    'resume' => 'Résumé automatique de test pour valider le flux Filament (création, édition, consultation).',
                    'priorite' => $priorites[array_rand($priorites)],
                    'statut' => $statuts[array_rand($statuts)],
                    'niveau_confidentialite' => $confidentialites[array_rand($confidentialites)],
                    'correspondant_id' => $correspondants->random()->id,
                    'user_id' => $users->random()->id,
                ],
            );

            Imputation::firstOrCreate(
                [
                    'courrier_id' => $courrier->id,
                    'destinataire_id' => $users->random()->id,
                ],
                [
                    'expediteur_id' => $courrier->user_id,
                    'instructions' => 'Merci de traiter ce courrier et de faire un retour sous 48h.',
                    'statut_traitement' => 'En attente',
                    'date_imputation' => now(),
                ],
            );
        }
    }
}
