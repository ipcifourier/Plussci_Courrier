<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->enum('committee_type', [
                'comite_veille',
                'comite_technique',
                'secretariat_technique',
                'gtt',
                'other',
            ])->nullable()->after('status');

            $table->enum('jee_status', [
                'not_done',   // rouge
                'launched',   // orange  (TDR OK / Validé)
                'in_progress', // jaune  (Démarré / En cours)
                'completed',  // vert   (Réalisé)
            ])->default('not_done')->after('committee_type');

            $table->unsignedSmallInteger('planning_year')->nullable()->after('jee_status');

            // S1/S2 for CV; T1-T4 for CT; 1-12 (month) for STM/GTT
            $table->string('planning_period', 10)->nullable()->after('planning_year');

            $table->foreignId('gtt_id')
                ->nullable()
                ->constrained('gtts')
                ->nullOnDelete()
                ->after('planning_period');
        });
    }

    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropForeign(['gtt_id']);
            $table->dropColumn([
                'committee_type',
                'jee_status',
                'planning_year',
                'planning_period',
                'gtt_id',
            ]);
        });
    }
};
