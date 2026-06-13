<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courriers', function (Blueprint $table) {
            $table->boolean('collaboration_enabled')->default(false)->after('accuse_reception');
            $table->json('cloud_links')->nullable()->after('collaboration_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('courriers', function (Blueprint $table) {
            $table->dropColumn('collaboration_enabled');
            $table->dropColumn('cloud_links');
        });
    }
};
