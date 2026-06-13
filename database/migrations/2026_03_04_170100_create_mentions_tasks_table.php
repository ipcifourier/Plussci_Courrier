<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mentions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('comment_id')->constrained('comments')->cascadeOnDelete();
            $table->foreignId('mentioned_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('notified_at')->nullable();

            $table->unique(['comment_id', 'mentioned_user_id']);
        });

        Schema::create('tasks', function (Blueprint $table): void {
            $table->id();
            $table->nullableMorphs('taskable');      // taskable_type + taskable_id (nullable → tâche standalone possible)
            $table->string('titre');
            $table->text('description')->nullable();
            $table->foreignId('assignee_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigner_id')->constrained('users')->cascadeOnDelete();
            $table->enum('priority', ['Basse', 'Normale', 'Haute', 'Urgente'])->default('Normale');
            $table->date('due_date')->nullable();
            $table->enum('status', ['todo', 'doing', 'done', 'cancelled'])->default('todo');
            $table->timestamps();

            $table->index(['assignee_id', 'status']);
        });

        Schema::create('task_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('changed_by')->constrained('users')->cascadeOnDelete();
            $table->string('from_status');
            $table->string('to_status');
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_histories');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('mentions');
    }
};
