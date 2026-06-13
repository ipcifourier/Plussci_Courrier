<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archive_records', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('document_id')
                ->constrained('documents')
                ->cascadeOnDelete();

            $table->foreignId('archived_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamp('archived_at');

            // Why / legal mandate for archiving
            $table->string('reason')->nullable();
            $table->string('legal_basis')->nullable()
                ->comment('Référence légale ou réglementaire (ex: Code marchés art.55)');

            // Retention policy
            $table->unsignedTinyInteger('retention_years')->default(5);
            $table->date('retention_expires_at')->nullable()
                ->comment('Date calculée : archived_at + retention_years');

            // Integrity
            $table->string('integrity_checksum', 128)->nullable()
                ->comment('SHA-256 de tous les fichiers attachés au moment de l\'archivage');
            $table->enum('integrity_status', ['pending', 'verified', 'corrupted'])
                ->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Frozen metadata snapshot (document attributes at archival time)
            $table->json('manifest_json')->nullable()
                ->comment('Instantané des métadonnées du document au moment de l\'archivage');

            $table->timestamps();

            $table->unique('document_id'); // One archive record per document
            $table->index('archived_at');
            $table->index('retention_expires_at');
            $table->index('integrity_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archive_records');
    }
};
