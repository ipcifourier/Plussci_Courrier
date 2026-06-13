<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->string('reference_mode', 20)
                ->default('saisir')
                ->after('reference_doc');

            $table->index('reference_mode');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->dropIndex(['reference_mode']);
            $table->dropColumn('reference_mode');
        });
    }
};
