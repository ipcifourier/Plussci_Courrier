<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workflow_templates', function (Blueprint $table): void {
            $table->boolean('auto_start')->default(false)->after('is_active');
            $table->json('trigger_confidentiality_levels')->nullable()->after('trigger_types');

            $table->index(['is_active', 'auto_start'], 'workflow_templates_active_auto_start_idx');
        });
    }

    public function down(): void
    {
        Schema::table('workflow_templates', function (Blueprint $table): void {
            $table->dropIndex('workflow_templates_active_auto_start_idx');
            $table->dropColumn(['auto_start', 'trigger_confidentiality_levels']);
        });
    }
};
