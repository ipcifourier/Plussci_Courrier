<?php

namespace App\Jobs;

use App\Models\DocumentVersion;
use App\Services\OcrService;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ExtractDocumentTextJob implements ShouldQueue
{
    use Queueable;

    public int $tries   = 3;
    public int $timeout = 120;

    /**
     * Back-off delays between retries: 30 s, then 5 min.
     */
    public function backoff(): array
    {
        return [30, 300];
    }

    public function __construct(
        private readonly int $documentVersionId,
    ) {}

    public function handle(OcrService $ocr): void
    {
        $version = DocumentVersion::with(['document', 'media'])->find($this->documentVersionId);

        if (! $version) {
            return;
        }

        // Mark as processing
        $version->update(['ocr_status' => 'processing']);

        try {
            // Get the media file path via Spatie MediaLibrary
            $media = $version->media()->first();

            if (! $media) {
                $version->update(['ocr_status' => 'unavailable']);
                return;
            }

            $filePath = $media->getPath();
            $mimeType = $media->mime_type;

            $result = $ocr->extract($filePath, $mimeType);

            $version->update([
                'ocr_text'   => $result['text'],
                'ocr_status' => $result['status'],
            ]);

            // Trigger auto-classification when OCR succeeded
            if ($result['status'] === 'completed' && $version->document_id) {
                ClassifyDocumentJob::dispatch($version->document_id)
                    ->delay(now()->addSeconds(3));
            }
        } catch (Throwable $e) {
            $version->update([
                'ocr_status' => 'failed',
                'ocr_text'   => null,
            ]);
        }
    }

    public function failed(Throwable $e): void
    {
        $version = DocumentVersion::with('document.auteur')
            ->find($this->documentVersionId);

        DocumentVersion::where('id', $this->documentVersionId)
            ->update(['ocr_status' => 'failed']);

        // Notify the document author via Filament database notification.
        if ($version?->document?->auteur) {
            Notification::make()
                ->title('Échec OCR')
                ->body("Le traitement OCR du document « {$version->document->titre} » a échoué après {$this->tries} tentative(s).")
                ->danger()
                ->icon('heroicon-o-exclamation-circle')
                ->sendToDatabase($version->document->auteur);
        }
    }
}
