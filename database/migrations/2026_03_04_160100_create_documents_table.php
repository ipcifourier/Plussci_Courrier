<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dossier_id')->nullable()->constrained('dossiers')->nullOnDelete();
            $table->foreignId('courrier_id')->nullable()->constrained('courriers')->nullOnDelete();
            $table->string('reference_doc')->unique();
            $table->string('titre');
            $table->string('type_document');
            $table->unsignedBigInteger('version_courante_id')->nullable();
            $table->enum('etat_cycle_vie', ['Brouillon', 'Valide', 'Archive'])->default('Brouillon');
            $table->foreignId('auteur_id')->constrained('users')->cascadeOnDelete();
            $table->enum('confidentiality_level', ['Standard', 'Confidentiel', 'Personnel'])->default('Standard');
            $table->json('tags_json')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->index(['dossier_id', 'etat_cycle_vie']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
