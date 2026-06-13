<?php
$path = __DIR__ . '/app/Filament/Resources/Documents/Schemas/DocumentForm.php';
$content = file_get_contents($path);
$content = rtrim($content) . "\n}\n";
file_put_contents($path, $content);
echo "Done. Lines: " . substr_count($content, "\n") . "\n";
