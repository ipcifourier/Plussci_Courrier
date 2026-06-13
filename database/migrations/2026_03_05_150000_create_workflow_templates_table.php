<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            // JSON array of document types this template is suggested for (e.g. ["Facture","Contrat"])
            $table->json('trigger_types')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('workflow_template_steps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workflow_template_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('step_order');
            $table->string('label');
            $table->foreignId('approver_user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('action', ['review', 'approve', 'validate'])->default('approve');
            $table->timestamps();

            $table->index(['workflow_template_id', 'step_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_template_steps');
        Schema::dropIfExists('workflow_templates');
    }
};
