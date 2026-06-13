<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        $now = now();
        $defaults = [
            'Rapport activite',
            'Rapport mission',
            'Compte-rendu',
            'PV reunion',
            'Note service',
            'Note information',
            'Courrier entrant',
            'Courrier sortant',
            'Contrat',
            'Facture',
            'Bon commande',
            'Decision',
            'Procedure',
            'Document',
            'Autre',
        ];

        $rows = array_map(static fn (string $name): array => [
            'name' => $name,
            'created_at' => $now,
            'updated_at' => $now,
        ], $defaults);

        DB::table('document_types')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('document_types');
    }
};
