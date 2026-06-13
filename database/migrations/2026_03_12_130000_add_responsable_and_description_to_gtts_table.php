<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gtts', function (Blueprint $table) {
            $table->string('responsable')->nullable();
            $table->text('description')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('gtts', function (Blueprint $table) {
            $table->dropColumn('responsable');
            $table->dropColumn('description');
        });
    }
};
