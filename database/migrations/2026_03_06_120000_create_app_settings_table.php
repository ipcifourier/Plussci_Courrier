<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        $this->seedDefaults();
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }

    private function seedDefaults(): void
    {
        DB::table('app_settings')->insert([
            [
                'key' => 'ged.document_types',
                'value' => json_encode(array_keys(config('acquisition.document_types', []))),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'ged.max_file_size_mb',
                'value' => json_encode((int) config('acquisition.max_file_size_mb', 50)),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'ged.upload_quota_mb',
                'value' => json_encode((int) config('courriers.upload_quota_mb', 500)),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'ged.lifecycle',
                'value' => json_encode([
                    'courrier_archive_after_days' => (int) config('courriers.lifecycle.courrier_archive_after_days', 90),
                    'document_archive_after_days' => (int) config('courriers.lifecycle.document_archive_after_days', 365),
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'ged.retention_by_type',
                'value' => json_encode([
                    'Contrat' => 10,
                    'Decision' => 10,
                    'Facture' => 10,
                    'Bon commande' => 10,
                    'Rapport activite' => 7,
                    'Rapport mission' => 7,
                    'PV reunion' => 7,
                    'Procedure' => 7,
                    'Note service' => 5,
                    'Note information' => 5,
                    'Compte-rendu' => 5,
                    'Courrier entrant' => 5,
                    'Courrier sortant' => 5,
                    'Autre' => 5,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
};
