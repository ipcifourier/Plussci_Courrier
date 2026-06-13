<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_workflow_steps', function (Blueprint $table): void {
            $table->string('sla_source', 40)
                ->default('template_default')
                ->after('sla_hours');

            $table->index(['status', 'sla_source'], 'document_workflow_steps_status_sla_source_idx');
        });
    }

    public function down(): void
    {
        Schema::table('document_workflow_steps', function (Blueprint $table): void {
            $table->dropIndex('document_workflow_steps_status_sla_source_idx');
            $table->dropColumn('sla_source');
        });
    }
};
