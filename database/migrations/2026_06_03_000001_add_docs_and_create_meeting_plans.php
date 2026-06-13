<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add TDR and rapport paths to meetings
        Schema::table('meetings', function (Blueprint $table) {
            $table->string('tdr_path', 500)->nullable()->after('jee_status');
            $table->string('rapport_path', 500)->nullable()->after('tdr_path');
        });

        // Annual planning targets and comments per committee row
        Schema::create('meeting_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('planning_year');
            $table->string('committee_type', 100);
            $table->foreignId('gtt_id')->nullable()->constrained('gtts')->nullOnDelete();
            $table->unsignedTinyInteger('target_count')->default(1)->comment('Nombre de réunions prévues dans l\'indicateur annuel');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['planning_year', 'committee_type', 'gtt_id'], 'mp_year_type_gtt_unique');
        });
    }

    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn(['tdr_path', 'rapport_path']);
        });

        Schema::dropIfExists('meeting_plans');
    }
};
