<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Imputations : délai de traitement & suivi relance ─────────────────
        Schema::table('imputations', function (Blueprint $table) {
            $table->date('delai_traitement')
                ->nullable()
                ->after('statut_traitement')
                ->comment('Échéance de traitement de l\'imputation');

            $table->timestamp('relance_envoyee_at')
                ->nullable()
                ->after('delai_traitement')
                ->comment('Date de la dernière relance envoyée au destinataire');
        });

        // ── Tasks : timestamp dernière alerte ─────────────────────────────────
        Schema::table('tasks', function (Blueprint $table) {
            $table->timestamp('alerte_envoyee_at')
                ->nullable()
                ->after('due_date')
                ->comment('Date de la dernière notification d\'échéance envoyée');
        });
    }

    public function down(): void
    {
        Schema::table('imputations', function (Blueprint $table) {
            $table->dropColumn(['delai_traitement', 'relance_envoyee_at']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('alerte_envoyee_at');
        });
    }
};
