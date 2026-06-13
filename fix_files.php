<?php
// Truncate AutoClassificationServiceTest to remove duplicate class body
$path = __DIR__ . '/tests/Feature/AutoClassificationServiceTest.php';
$lines = explode("\n", str_replace("\r\n", "\n", file_get_contents($path)));
// Find the closing brace of the first (and only) class - stop searching at line 310
$end = 0;
for ($i = 0; $i < min(310, count($lines)); $i++) {
    if ($lines[$i] === '}') { $end = $i; }
}
echo "Class end at line " . ($end + 1) . " of " . count($lines) . "\n";
$clean = implode("\n", array_slice($lines, 0, $end + 1)) . "\n";
file_put_contents($path, $clean);
echo "Done: " . substr_count($clean, "\n") . " lines written\n";

// Fix DocumentForm - truncate at first standalone '}' after line 155
$raw2 = file_get_contents(__DIR__ . '/app/Filament/Resources/Documents/Schemas/DocumentForm.php');
$lines2 = explode("\n", str_replace("\r\n", "\n", $raw2));
$end = 159;
for ($i = 154; $i < count($lines2); $i++) {
    if (trim($lines2[$i]) === '}') { $end = $i; break; }
}
echo "DocumentForm class end at line " . ($end + 1) . "\n";
$clean2 = implode("\n", array_slice($lines2, 0, $end + 1));
file_put_contents(__DIR__ . '/app/Filament/Resources/Documents/Schemas/DocumentForm.php', $clean2);
echo "DocumentForm: trimmed to line " . ($end + 1) . "\n";
