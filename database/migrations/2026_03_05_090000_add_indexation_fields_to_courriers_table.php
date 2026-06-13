<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courriers', function (Blueprint $table) {
            // Canal de réception/envoi
            $table->enum('canal', ['Physique', 'Email', 'Portail', 'Fax'])
                ->default('Physique')
                ->after('type');

            // Nature du document
            $table->enum('nature_courrier', [
                'Lettre',
                'Note de service',
                'Circulaire',
                'Décision',
                'Rapport',
                'Facture',
                'Demande',
                'Autre',
            ])->nullable()->after('canal');

            // Mots-clés pour indexation (comma-separated)
            $table->text('mots_cles')->nullable()->after('resume');

            // Délai de réponse
            $table->date('delai_reponse')->nullable()->after('mots_cles');

            // Accusé de réception (entrant)
            $table->boolean('accuse_reception')->default(false)->after('delai_reponse');
            $table->timestamp('date_accuse')->nullable()->after('accuse_reception');

            // Numérisation (courriers physiques)
            $table->enum('scan_status', ['Non numérisé', 'En cours', 'Numérisé'])
                ->default('Non numérisé')
                ->after('date_accuse');
            $table->date('date_numerisation')->nullable()->after('scan_status');
            $table->foreignId('numerise_par')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->after('date_numerisation');
        });
    }

    public function down(): void
    {
        Schema::table('courriers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('numerise_par');
            $table->dropColumn([
                'canal',
                'nature_courrier',
                'mots_cles',
                'delai_reponse',
                'accuse_reception',
                'date_accuse',
                'scan_status',
                'date_numerisation',
            ]);
        });
    }
};
