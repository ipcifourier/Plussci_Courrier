<?php

declare(strict_types=1);

use Dompdf\Dompdf;
use Dompdf\Options;

require __DIR__ . '/../vendor/autoload.php';

$sourceName = $argv[1] ?? 'GUIDE_GESTIONNAIRE_FICHIERS_AVANCE.md';
$outputName = $argv[2] ?? 'GUIDE_GESTIONNAIRE_FICHIERS_AVANCE.pdf';
$documentTitle = $argv[3] ?? 'Guide Utilisateur';

$src = __DIR__ . '/' . ltrim($sourceName, '/\\');
$out = __DIR__ . '/' . ltrim($outputName, '/\\');

if (! is_file($src)) {
    fwrite(STDERR, "Source file not found: {$src}\n");
    exit(1);
}

$markdown = file_get_contents($src);
if ($markdown === false) {
    fwrite(STDERR, "Failed to read source markdown.\n");
    exit(1);
}

$lines = preg_split('/\R/', $markdown) ?: [];
$body = '';
$inUl = false;
$inOl = false;

$flushLists = static function () use (&$body, &$inUl, &$inOl): void {
    if ($inUl) {
        $body .= '</ul>';
        $inUl = false;
    }

    if ($inOl) {
        $body .= '</ol>';
        $inOl = false;
    }
};

$formatInline = static function (string $text): string {
    $escaped = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

    return (string) preg_replace('/`([^`]+)`/', '<code>$1</code>', $escaped);
};

foreach ($lines as $line) {
    $trimmed = trim($line);

    if ($trimmed === '---') {
        $flushLists();
        $body .= '<hr style="border:0;border-top:1px solid #dbe4ef;margin:14px 0;">';
        continue;
    }

    if (str_starts_with($trimmed, '# ')) {
        $flushLists();
        $body .= '<h1>' . $formatInline(substr($trimmed, 2)) . '</h1>';
        continue;
    }

    if (str_starts_with($trimmed, '## ')) {
        $flushLists();
        $body .= '<h2>' . $formatInline(substr($trimmed, 3)) . '</h2>';
        continue;
    }

    if (str_starts_with($trimmed, '### ')) {
        $flushLists();
        $body .= '<h3>' . $formatInline(substr($trimmed, 4)) . '</h3>';
        continue;
    }

    if (str_starts_with($trimmed, '- ')) {
        if ($inOl) {
            $body .= '</ol>';
            $inOl = false;
        }

        if (! $inUl) {
            $body .= '<ul>';
            $inUl = true;
        }

        $body .= '<li>' . $formatInline(substr($trimmed, 2)) . '</li>';
        continue;
    }

    if (preg_match('/^\d+\.\s+(.+)$/', $trimmed, $matches) === 1) {
        if ($inUl) {
            $body .= '</ul>';
            $inUl = false;
        }

        if (! $inOl) {
            $body .= '<ol>';
            $inOl = true;
        }

        $body .= '<li>' . $formatInline($matches[1]) . '</li>';
        continue;
    }

    if ($trimmed === '') {
        $flushLists();
        $body .= '<div class="spacer"></div>';
        continue;
    }

    $flushLists();
    $body .= '<p>' . $formatInline($line) . '</p>';
}

if ($inUl) {
    $body .= '</ul>';
}

if ($inOl) {
    $body .= '</ol>';
}

$titleEscaped = htmlspecialchars($documentTitle, ENT_QUOTES, 'UTF-8');
$sourceEscaped = htmlspecialchars(basename($sourceName), ENT_QUOTES, 'UTF-8');

$html = '<!doctype html><html lang="fr"><head><meta charset="utf-8"><style>
@page { margin: 88px 48px 62px 48px; }
body{font-family:DejaVu Sans,sans-serif;color:#0f172a;font-size:12px;line-height:1.5}
.header{position:fixed;top:-72px;left:0;right:0;height:56px;border-bottom:1px solid #dbe4ef;padding-bottom:8px}
.header-row{width:100%;display:table}
.header-cell{display:table-cell;vertical-align:middle}
.brand{font-size:11px;color:#0e7490;font-weight:700;text-transform:uppercase;letter-spacing:0.03em}
.doc-title{font-size:14px;color:#0b3a53;font-weight:700;text-align:right}
.sub{font-size:10px;color:#64748b;text-align:right}
h1{font-size:20px;margin:0 0 10px;color:#0b3a53;border-bottom:2px solid #99f6e4;padding-bottom:6px}
h2{font-size:15px;margin:16px 0 7px;color:#0e7490}
h3{font-size:13px;margin:12px 0 6px;color:#155e75}
p{margin:0 0 7px}
ul,ol{margin:0 0 8px 18px;padding:0}
li{margin:0 0 4px}
.spacer{height:6px}
code{background:#f1f5f9;padding:1px 4px;border-radius:4px;font-size:11px}
hr{border:0;border-top:1px solid #dbe4ef;margin:14px 0}
</style></head><body>
<div class="header"><div class="header-row"><div class="header-cell"><div class="brand">PLUSS.CI</div></div><div class="header-cell"><div class="doc-title">' . $titleEscaped . '</div><div class="sub">' . $sourceEscaped . '</div></div></div></div>
' . $body . '
</body></html>';

$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('defaultPaperSize', 'a4');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$canvas = $dompdf->getCanvas();
$pageCount = $canvas->get_page_count();
$font = $dompdf->getFontMetrics()->getFont('Helvetica', 'normal');
$canvas->page_text(48, 815, 'PLUSS.CI - Guide utilisateur', $font, 9, [0.39, 0.45, 0.54]);
$canvas->page_text(500, 815, 'Page {PAGE_NUM} / {PAGE_COUNT}', $font, 9, [0.39, 0.45, 0.54]);

$output = $dompdf->output();
if (file_put_contents($out, $output) === false) {
    fwrite(STDERR, "Failed to write output PDF.\n");
    exit(1);
}

echo $out . PHP_EOL;
