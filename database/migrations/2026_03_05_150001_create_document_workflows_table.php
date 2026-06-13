<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_workflows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workflow_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('template_name'); // snapshot of template name at start
            $table->foreignId('initiated_by')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->unsignedTinyInteger('current_step_order')->default(1);
            $table->text('final_comment')->nullable();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['document_id', 'status']);
            $table->index(['status', 'current_step_order']);
        });

        Schema::create('document_workflow_steps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_workflow_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('step_order');
            $table->string('label');
            $table->enum('action', ['review', 'approve', 'validate'])->default('approve');
            $table->foreignId('approver_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'approved', 'rejected', 'skipped'])->default('pending');
            $table->text('comment')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['document_workflow_id', 'step_order']);
            $table->index(['approver_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_workflow_steps');
        Schema::dropIfExists('document_workflows');
    }
};
