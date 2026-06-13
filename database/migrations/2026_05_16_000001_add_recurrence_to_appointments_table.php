<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A4 — Ajoute les champs de récurrence aux rendez-vous.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->enum('recurrence_rule', ['none', 'daily', 'weekly', 'monthly'])->default('none')->after('status');
            $table->date('recurrence_ends_at')->nullable()->after('recurrence_rule');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropColumn(['recurrence_rule', 'recurrence_ends_at']);
        });
    }
};
