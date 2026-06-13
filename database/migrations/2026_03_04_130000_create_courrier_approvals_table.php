<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courriers', function (Blueprint $table): void {
            $table->boolean('requires_approval')->default(false)->after('niveau_confidentialite');
            $table->enum('approval_status', ['not_required', 'pending', 'approved', 'rejected'])->default('not_required')->after('requires_approval');
            $table->unsignedTinyInteger('current_approval_level')->nullable()->after('approval_status');
        });

        Schema::create('courrier_approvals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('courrier_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('level');
            $table->foreignId('approver_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('comment')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['courrier_id', 'level', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courrier_approvals');

        Schema::table('courriers', function (Blueprint $table): void {
            $table->dropColumn(['requires_approval', 'approval_status', 'current_approval_level']);
        });
    }
};
