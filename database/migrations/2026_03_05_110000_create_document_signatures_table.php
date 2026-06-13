<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Colonnes sur documents ────────────────────────────────────────────
        Schema::table('documents', function (Blueprint $table) {
            $table->enum('parapheur_status', [
                'not_required',
                'pending',
                'completed',
                'rejected',
            ])->default('not_required')->after('etat_cycle_vie');

            $table->unsignedTinyInteger('current_signature_level')
                ->nullable()
                ->after('parapheur_status');
        });

        // ── Table parapheur ───────────────────────────────────────────────────
        Schema::create('document_signatures', function (Blueprint $table) {
            $table->id();

            $table->foreignId('document_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('level');

            $table->foreignId('signataire_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->enum('role_signature', ['Visa', 'Approbation', 'Signature'])
                ->default('Signature');

            $table->enum('status', ['pending', 'signed', 'rejected'])
                ->default('pending');

            $table->text('comment')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->string('ip_address', 45)->nullable();

            $table->timestamps();

            $table->index(['document_id', 'level', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_signatures');

        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['parapheur_status', 'current_signature_level']);
        });
    }
};
