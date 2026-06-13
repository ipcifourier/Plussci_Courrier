<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('departements', 'responsable')) {
            return;
        }

        Schema::table('departements', function (Blueprint $table) {
            $table->string('responsable')->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('departements', 'responsable')) {
            return;
        }

        Schema::table('departements', function (Blueprint $table) {
            $table->dropColumn('responsable');
        });
    }
};
