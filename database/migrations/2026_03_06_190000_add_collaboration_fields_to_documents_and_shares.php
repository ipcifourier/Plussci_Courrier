<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->boolean('collaboration_enabled')
                ->default(false)
                ->after('locked_at');

            $table->timestamp('finalized_read_only_at')
                ->nullable()
                ->after('collaboration_enabled');

            $table->foreignId('finalized_read_only_by')
                ->nullable()
                ->after('finalized_read_only_at')
                ->constrained('users')
                ->nullOnDelete();
        });

        Schema::table('document_shares', function (Blueprint $table): void {
            $table->boolean('can_edit')
                ->default(false)
                ->after('can_comment');
        });
    }

    public function down(): void
    {
        Schema::table('document_shares', function (Blueprint $table): void {
            $table->dropColumn('can_edit');
        });

        Schema::table('documents', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('finalized_read_only_by');
            $table->dropColumn('finalized_read_only_at');
            $table->dropColumn('collaboration_enabled');
        });
    }
};
