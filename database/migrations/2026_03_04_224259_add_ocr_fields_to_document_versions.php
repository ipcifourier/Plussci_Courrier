<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('document_versions', function (Blueprint $table): void {
            $table->longText('ocr_text')->nullable()->after('commentaire_version');
            $table->enum('ocr_status', ['pending', 'processing', 'completed', 'failed', 'unavailable'])
                ->default('pending')
                ->after('ocr_text');
            $table->string('source', 50)->default('upload')->after('ocr_status')
                ->comment('upload | email | scan_folder');
            $table->string('source_meta')->nullable()->after('source')
                ->comment('Email sender / scan file path / etc.');
        });
    }

    public function down(): void
    {
        Schema::table('document_versions', function (Blueprint $table): void {
            $table->dropColumn(['ocr_text', 'ocr_status', 'source', 'source_meta']);
        });
    }
};
