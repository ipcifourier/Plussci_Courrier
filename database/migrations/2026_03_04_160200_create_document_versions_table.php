<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->string('numero_version');
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('checksum_sha256')->nullable();
            $table->text('commentaire_version')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['document_id', 'numero_version']);
        });

        Schema::table('documents', function (Blueprint $table): void {
            $table->foreign('version_courante_id')->references('id')->on('document_versions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->dropForeign(['version_courante_id']);
        });

        Schema::dropIfExists('document_versions');
    }
};
