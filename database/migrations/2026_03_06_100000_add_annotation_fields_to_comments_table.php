<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table): void {
            $table->string('kind')->default('comment')->after('body');
            $table->json('annotation_data')->nullable()->after('kind');
            $table->timestamp('resolved_at')->nullable()->after('annotation_data');
        });
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table): void {
            $table->dropColumn(['kind', 'annotation_data', 'resolved_at']);
        });
    }
};
