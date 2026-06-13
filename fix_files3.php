<?php
function fix_php_file(string $path, ?int $maxLines = null, bool $addClosingBrace = false): void {
    $raw = file_get_contents($path);
    // Strip UTF-8 BOM if present
    if (substr($raw, 0, 3) === "\xef\xbb\xbf") {
        $raw = substr($raw, 3);
    }
    $lines = explode("\n", str_replace("\r\n", "\n", $raw));
    if ($maxLines !== null) {
        $lines = array_slice($lines, 0, $maxLines);
    }
    if ($addClosingBrace) {
        // Only append if last non-empty line is not already '}'
        $last = trim(end($lines));
        if ($last !== '}') {
            $lines[] = '}';
            $lines[] = '';
        }
    }
    file_put_contents($path, implode("\n", $lines));
    echo basename($path) . ': written ' . count($lines) . " lines\n";
}

$base = __DIR__;
fix_php_file($base . '/app/Services/SearchService.php', 182);
fix_php_file($base . '/app/Filament/Resources/Documents/Schemas/DocumentForm.php', 160, true);
