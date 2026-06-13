<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workflow_template_steps', function (Blueprint $table): void {
            $table->unsignedSmallInteger('sla_hours')->default(24)->after('action');
            $table->foreignId('escalation_user_id')->nullable()->after('approver_user_id')->constrained('users')->nullOnDelete();
        });

        Schema::table('document_workflow_steps', function (Blueprint $table): void {
            $table->unsignedSmallInteger('sla_hours')->default(24)->after('action');
            $table->foreignId('escalation_user_id')->nullable()->after('approver_id')->constrained('users')->nullOnDelete();
            $table->timestamp('due_at')->nullable()->after('status');
            $table->timestamp('escalated_at')->nullable()->after('due_at');

            $table->index(['status', 'due_at'], 'document_workflow_steps_status_due_idx');
            $table->index(['status', 'escalated_at'], 'document_workflow_steps_status_escalated_idx');
        });
    }

    public function down(): void
    {
        Schema::table('document_workflow_steps', function (Blueprint $table): void {
            $table->dropIndex('document_workflow_steps_status_due_idx');
            $table->dropIndex('document_workflow_steps_status_escalated_idx');
            $table->dropConstrainedForeignId('escalation_user_id');
            $table->dropColumn(['sla_hours', 'due_at', 'escalated_at']);
        });

        Schema::table('workflow_template_steps', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('escalation_user_id');
            $table->dropColumn('sla_hours');
        });
    }
};
