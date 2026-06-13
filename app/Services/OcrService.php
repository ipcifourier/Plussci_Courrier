<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use Smalot\PdfParser\Parser as PdfParser;
use Throwable;

class OcrService
{
    /**
     * Extract text from a file.
     * - PDF  : smalot/pdfparser (pure PHP, no binary needed)
     * - Image: Tesseract OCR binary (if installed)
     * - Other: null (unsupported)
     *
     * Returns ['status' => string, 'text' => string|null].
     */
    public function extract(string $filePath, string $mimeType): array
    {
        if (! file_exists($filePath)) {
            return ['status' => 'failed', 'text' => null, 'error' => 'File not found'];
        }

        // PDF via smalot/pdfparser
        if ($mimeType === 'application/pdf' || str_ends_with(strtolower($filePath), '.pdf')) {
            return $this->extractFromPdf($filePath);
        }

        // Image via Tesseract
        if (str_starts_with($mimeType, 'image/')) {
            return $this->extractFromImageWithTesseract($filePath);
        }

        // Plain text
        if ($mimeType === 'text/plain') {
            $content = @file_get_contents($filePath);

            return $content !== false
                ? ['status' => 'completed', 'text' => mb_convert_encoding($content, 'UTF-8', 'auto')]
                : ['status' => 'failed', 'text' => null, 'error' => 'Could not read file'];
        }

        // Word documents (.docx / .doc) via ZipArchive + XML parsing
        if (
            in_array($mimeType, [
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/msword',
            ], true)
            || str_ends_with(strtolower($filePath), '.docx')
            || str_ends_with(strtolower($filePath), '.doc')
        ) {
            return $this->extractFromWord($filePath);
        }

        // Spreadsheets (.xlsx / .xls / .ods) via PhpSpreadsheet
        if (
            in_array($mimeType, [
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-excel',
                'application/vnd.oasis.opendocument.spreadsheet',
            ], true)
            || str_ends_with(strtolower($filePath), '.xlsx')
            || str_ends_with(strtolower($filePath), '.xls')
            || str_ends_with(strtolower($filePath), '.ods')
        ) {
            return $this->extractFromSpreadsheet($filePath);
        }

        return ['status' => 'unavailable', 'text' => null, 'error' => 'Unsupported MIME type: ' . $mimeType];
    }

    // -------------------------------------------------------------------------
    // PDF extraction
    // -------------------------------------------------------------------------

    private function extractFromPdf(string $filePath): array
    {
        try {
            $parser = new PdfParser();
            $pdf    = $parser->parseFile($filePath);
            $text   = $pdf->getText();

            if (empty(trim($text))) {
                // PDF has no embedded text (scanned image PDF) → try Tesseract
                return $this->extractFromImageWithTesseract($filePath);
            }

            return ['status' => 'completed', 'text' => $text];
        } catch (Throwable $e) {
            return ['status' => 'failed', 'text' => null, 'error' => $e->getMessage()];
        }
    }

    // -------------------------------------------------------------------------
    // Tesseract OCR
    // -------------------------------------------------------------------------

    private function extractFromImageWithTesseract(string $filePath): array
    {
        $tesseract = $this->findTesseract();

        if ($tesseract === null) {
            return [
                'status' => 'unavailable',
                'text'   => null,
                'error'  => 'Tesseract OCR n\'est pas installé. Installez-le depuis https://github.com/UB-Mannheim/tesseract/wiki',
            ];
        }

        try {
            $outputBase = tempnam(sys_get_temp_dir(), 'ocr_');
            $lang       = $this->resolveTesseractLang($filePath);
            $cmd        = sprintf(
                '%s %s %s -l %s stdout 2>/dev/null',
                escapeshellcmd($tesseract),
                escapeshellarg($filePath),
                escapeshellarg($outputBase),
                escapeshellarg($lang)
            );

            $text     = shell_exec($cmd);
            @unlink($outputBase . '.txt');

            if (empty(trim((string) $text))) {
                return ['status' => 'completed', 'text' => null];
            }

            return ['status' => 'completed', 'text' => trim($text)];
        } catch (Throwable $e) {
            return ['status' => 'failed', 'text' => null, 'error' => $e->getMessage()];
        }
    }

    // -------------------------------------------------------------------------
    // Word (.docx) extraction — native PHP via ZipArchive
    // -------------------------------------------------------------------------

    private function extractFromWord(string $filePath): array
    {
        if (! class_exists(\ZipArchive::class)) {
            return ['status' => 'unavailable', 'text' => null, 'error' => 'ZipArchive extension not available'];
        }

        try {
            $zip = new \ZipArchive();

            if ($zip->open($filePath) !== true) {
                // Possibly a legacy .doc binary — not extractable without COM/antiword.
                return ['status' => 'unavailable', 'text' => null, 'error' => 'Cannot open file as ZIP (legacy .doc format)'];
            }

            $xmlContent = $zip->getFromName('word/document.xml');
            $zip->close();

            if ($xmlContent === false) {
                return ['status' => 'completed', 'text' => null];
            }

            // Strip XML tags and decode entities.
            $xml  = simplexml_load_string($xmlContent, 'SimpleXMLElement', LIBXML_NOERROR | LIBXML_NOWARNING);
            $text = $xml ? strip_tags($xml->asXML()) : strip_tags($xmlContent);
            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $text = preg_replace('/\s+/', ' ', $text) ?? $text;
            $text = trim($text);

            return empty($text)
                ? ['status' => 'completed', 'text' => null]
                : ['status' => 'completed', 'text' => $text];
        } catch (Throwable $e) {
            return ['status' => 'failed', 'text' => null, 'error' => $e->getMessage()];
        }
    }

    // -------------------------------------------------------------------------
    // Spreadsheet (.xlsx / .xls / .ods) extraction — PhpSpreadsheet
    // -------------------------------------------------------------------------

    private function extractFromSpreadsheet(string $filePath): array
    {
        if (! class_exists(SpreadsheetIOFactory::class)) {
            return ['status' => 'unavailable', 'text' => null, 'error' => 'PhpSpreadsheet not available'];
        }

        try {
            $spreadsheet = SpreadsheetIOFactory::load($filePath);
            $lines       = [];

            foreach ($spreadsheet->getAllSheets() as $sheet) {
                $lines[] = '=== ' . $sheet->getTitle() . ' ===';

                foreach ($sheet->toArray(null, true, true, false) as $row) {
                    $cells = array_filter(
                        array_map(fn ($v) => trim((string) $v), $row),
                        fn ($v) => $v !== ''
                    );

                    if ($cells) {
                        $lines[] = implode(' | ', $cells);
                    }
                }
            }

            $text = implode("\n", $lines);

            return empty(trim($text))
                ? ['status' => 'completed', 'text' => null]
                : ['status' => 'completed', 'text' => trim($text)];
        } catch (Throwable $e) {
            return ['status' => 'failed', 'text' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Find the Tesseract binary (checks common paths + PATH).
     */
    public function findTesseract(): ?string
    {
        $candidates = [
            config('acquisition.tesseract_path'),
            'tesseract',
            'C:\\Program Files\\Tesseract-OCR\\tesseract.exe',
            'C:\\Program Files (x86)\\Tesseract-OCR\\tesseract.exe',
            '/usr/bin/tesseract',
            '/usr/local/bin/tesseract',
        ];

        foreach (array_filter($candidates) as $path) {
            $test = @shell_exec(escapeshellcmd($path) . ' --version 2>&1');
            if ($test !== null && str_contains($test, 'tesseract')) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Returns true if any OCR backend is available for the given mime type.
     */
    public function isAvailableFor(string $mimeType): bool
    {
        if ($mimeType === 'application/pdf' || $mimeType === 'text/plain') {
            return true;
        }

        if (str_starts_with($mimeType, 'image/')) {
            return $this->findTesseract() !== null;
        }

        // Word
        if (in_array($mimeType, [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/msword',
        ], true)) {
            return class_exists(\ZipArchive::class);
        }

        // Spreadsheet
        if (in_array($mimeType, [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
            'application/vnd.oasis.opendocument.spreadsheet',
        ], true)) {
            return class_exists(SpreadsheetIOFactory::class);
        }

        return false;
    }

    // -------------------------------------------------------------------------
    // Language detection
    // -------------------------------------------------------------------------

    /**
     * Guess the best Tesseract language string from filename / path context.
     * Falls back to the configured default (acquisition.tesseract_lang).
     */
    private function resolveTesseractLang(string $filePath): string
    {
        $default = config('acquisition.tesseract_lang', 'fra+eng');

        // If the configured language is already a single language, honour it.
        if (! str_contains($default, '+')) {
            return $default;
        }

        // Score by French / English stopwords found in the filename.
        $tokens = mb_strtolower(
            pathinfo($filePath, PATHINFO_FILENAME) . ' ' . pathinfo($filePath, PATHINFO_DIRNAME)
        );

        $frScore = preg_match_all(
            '/\b(le|la|les|de|du|des|un|une|et|en|ou|pour|dans|avec|sur|par|que|qui|est|sont|nous|vous|leur|cette)\b/',
            $tokens
        );
        $enScore = preg_match_all(
            '/\b(the|of|and|to|in|is|it|that|for|with|are|this|was|be|as|at|by|from|or|an|not|have|been|report)\b/',
            $tokens
        );

        if ($frScore > $enScore) {
            return 'fra';
        }

        if ($enScore > $frScore) {
            return 'eng';
        }

        return $default;
    }
}
