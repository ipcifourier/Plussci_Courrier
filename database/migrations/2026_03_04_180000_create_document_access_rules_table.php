<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_access_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('can_view')->default(true);
            $table->boolean('can_download')->default(false);
            $table->boolean('can_edit')->default(false);
            $table->boolean('can_share')->default(false);
            $table->timestamps();

            $table->index(['document_id', 'user_id']);
            $table->index(['document_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_access_rules');
    }
};
