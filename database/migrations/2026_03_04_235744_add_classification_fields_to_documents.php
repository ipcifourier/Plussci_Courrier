<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            // Plain-text keywords extracted from OCR + filename (used for FULLTEXT)
            $table->text('keywords')->nullable()->after('metadata_json');

            // Classification confidence score (0.0 – 1.0)
            $table->unsignedTinyInteger('classification_confidence')->nullable()->after('keywords');

            // Timestamp of last automatic classification
            $table->timestamp('classified_at')->nullable()->after('classification_confidence');
        });

        // MySQL-only: FULLTEXT index on titre + keywords for fast MATCH..AGAINST
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE documents ADD FULLTEXT INDEX ft_documents_search (titre, keywords)');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE documents DROP INDEX ft_documents_search');
        }

        Schema::table('documents', function (Blueprint $table): void {
            $table->dropColumn(['keywords', 'classification_confidence', 'classified_at']);
        });
    }
};
