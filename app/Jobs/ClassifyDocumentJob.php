<?php

namespace App\Jobs;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Services\AutoClassificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Automatically classifies a document after OCR completes.
 * Dispatched by ExtractDocumentTextJob on success.
 */
class ClassifyDocumentJob implements ShouldQueue
{
    use Queueable;

    public int $tries   = 2;
    public int $timeout = 60;

    public function __construct(
        private readonly int $documentId,
    ) {}

    public function handle(AutoClassificationService $classifier): void
    {
        $document = Document::with(['currentVersion', 'versions'])->find($this->documentId);

        if (! $document) {
            return;
        }

        $classifier->classify($document);
    }

    public function failed(Throwable $e): void
    {
        // Classification failure is non-critical — just log
        report($e);
    }
}
