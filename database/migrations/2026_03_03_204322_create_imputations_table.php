<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('imputations', function (Blueprint $table) {
        $table->id();
        $table->foreignId('courrier_id')->constrained()->cascadeOnDelete();
        $table->foreignId('expediteur_id')->constrained('users')->comment('Celui qui impute');
        $table->foreignId('destinataire_id')->constrained('users')->comment('Celui qui doit traiter');
        $table->text('instructions')->nullable();
        $table->enum('statut_traitement', ['En attente', 'En cours', 'Traité'])->default('En attente');
        $table->timestamp('date_imputation')->useCurrent();
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imputations');
    }
};
