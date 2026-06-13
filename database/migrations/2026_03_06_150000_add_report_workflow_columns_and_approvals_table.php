<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table): void {
            $table->boolean('requires_approval')->default(false)->after('status');
            $table->string('approval_status')->default('not_required')->after('requires_approval');
            $table->unsignedInteger('current_approval_level')->nullable()->after('approval_status');
            $table->timestamp('submitted_at')->nullable()->after('current_approval_level');
            $table->timestamp('approved_at')->nullable()->after('submitted_at');
            $table->timestamp('rejected_at')->nullable()->after('approved_at');

            $table->index(['approval_status', 'current_approval_level']);
        });

        Schema::create('report_approvals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('report_id')->constrained('reports')->cascadeOnDelete();
            $table->unsignedInteger('level');
            $table->foreignId('approver_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->text('comment')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['report_id', 'level']);
            $table->index(['approver_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_approvals');

        Schema::table('reports', function (Blueprint $table): void {
            $table->dropIndex(['approval_status', 'current_approval_level']);
            $table->dropColumn([
                'requires_approval',
                'approval_status',
                'current_approval_level',
                'submitted_at',
                'approved_at',
                'rejected_at',
            ]);
        });
    }
};
