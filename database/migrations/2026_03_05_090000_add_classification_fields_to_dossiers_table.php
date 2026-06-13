<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dossiers', function (Blueprint $table): void {
            $table->unsignedSmallInteger('annee_activite')->nullable()->after('parent_id')->index();
            $table->string('type_dossier', 50)->default('standard')->after('annee_activite')->index();
            $table->unsignedInteger('ordre_affichage')->default(0)->after('type_dossier');

            $table->index(['parent_id', 'annee_activite', 'ordre_affichage'], 'dossiers_parent_year_order_idx');
        });
    }

    public function down(): void
    {
        Schema::table('dossiers', function (Blueprint $table): void {
            $table->dropIndex('dossiers_parent_year_order_idx');
            $table->dropColumn(['annee_activite', 'type_dossier', 'ordre_affichage']);
        });
    }
};