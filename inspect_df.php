<?php
$f = 'app/Filament/Resources/Documents/Schemas/DocumentForm.php';
$c = file_get_contents($f);
$hasBom = substr($c, 0, 3) === "\xef\xbb\xbf";
echo ($hasBom ? 'HAS BOM' : 'NO BOM') . "\n";
$lines = explode("\n", str_replace("\r\n", "\n", $c));
$total = count($lines);
echo "Total lines: $total\n";
$start = max(0, $total - 8);
for ($i = $start; $i < $total; $i++) {
    echo ($i + 1) . ': ' . var_export($lines[$i], true) . "\n";
}
