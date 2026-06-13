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
       Schema::create('courriers', function (Blueprint $table) {
        $table->id();
        $table->enum('type', ['Entrant', 'Sortant']);
        $table->string('reference')->unique();
        $table->date('date_reception_envoi');
        $table->string('objet');
        $table->text('resume')->nullable();
        $table->enum('priorite', ['Normale', 'Urgente'])->default('Normale');
        $table->enum('statut', ['Nouveau', 'En cours', 'Traité', 'Archivé'])->default('Nouveau');
        $table->enum('niveau_confidentialite', ['Standard', 'Confidentiel', 'Personnel'])->default('Standard');
        
        $table->foreignId('correspondant_id')->constrained()->restrictOnDelete();
        $table->foreignId('user_id')->constrained('users')->comment('Agent ayant enregistré ou initié le courrier');
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courriers');
    }
};
