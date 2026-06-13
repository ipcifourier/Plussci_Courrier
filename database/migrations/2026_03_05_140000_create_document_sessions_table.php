<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('mode', ['view', 'edit'])->default('view');
            $table->timestamp('last_seen_at')->useCurrent();
            $table->timestamp('joined_at')->useCurrent();

            // Un seul enregistrement par (document, utilisateur) à la fois
            $table->unique(['document_id', 'user_id']);
            $table->index(['document_id', 'last_seen_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_sessions');
    }
};
