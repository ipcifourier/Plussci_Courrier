<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dossiers', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('libelle');
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('dossiers')->nullOnDelete();
            $table->enum('confidentialite', ['Standard', 'Confidentiel', 'Personnel'])->default('Standard');
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->enum('statut', ['Actif', 'Clos', 'Archive'])->default('Actif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dossiers');
    }
};
