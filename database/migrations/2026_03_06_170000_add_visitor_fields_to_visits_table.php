<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->string('visitor_last_name')->nullable()->after('appointment_id');
            $table->string('visitor_first_name')->nullable()->after('visitor_last_name');
            $table->string('visitor_structure')->nullable()->after('visitor_first_name');
            $table->dateTime('ended_at')->nullable()->after('happened_at');
        });
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropColumn([
                'visitor_last_name',
                'visitor_first_name',
                'visitor_structure',
                'ended_at',
            ]);
        });
    }
};
