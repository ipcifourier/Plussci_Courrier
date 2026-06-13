<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * C6 — Ajoute le champ ocr_text aux courriers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courriers', function (Blueprint $table): void {
            $table->longText('ocr_text')->nullable()->after('scan_status');
        });
    }

    public function down(): void
    {
        Schema::table('courriers', function (Blueprint $table): void {
            $table->dropColumn('ocr_text');
        });
    }
};
