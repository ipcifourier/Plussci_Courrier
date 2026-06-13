<?php

namespace Database\Seeders;

use App\Models\Departement;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            DepartementSeeder::class,
            RolesAndPermissionsSeeder::class,
            CorrespondantSeeder::class,
        ]);

        $secretariat = Departement::query()->where('nom', 'Secrétariat')->first();
        $informatique = Departement::query()->where('nom', 'Informatique')->first();

        $gestionnaire = User::firstOrCreate(
            ['email' => 'gestion.courrier@plussci.ci'],
            [
                'name' => 'Gestion Courrier',
                'password' => bcrypt('password'),
                'departement_id' => $secretariat?->id,
                'poste' => 'Gestionnaire Courrier',
            ],
        );

        $lecteur = User::firstOrCreate(
            ['email' => 'lecteur.courrier@plussci.ci'],
            [
                'name' => 'Lecteur Courrier',
                'password' => bcrypt('password'),
                'departement_id' => $informatique?->id,
                'poste' => 'Chef de Service',
            ],
        );

        $archiviste = User::firstOrCreate(
            ['email' => 'archiviste.ged@plussci.ci'],
            [
                'name' => 'Archiviste GED',
                'password' => bcrypt('password'),
                'departement_id' => $secretariat?->id,
                'poste' => 'Archiviste',
            ],
        );

        $gestionnaire->syncRoles(['Gestionnaire Courrier']);
        $lecteur->syncRoles(['Lecteur Courrier']);
        $archiviste->syncRoles(['Archiviste GED']);

        $this->call([
            CourrierSeeder::class,
        ]);
    }
}
