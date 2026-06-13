<?php
$f = __DIR__ . '/app/Filament/Resources/Documents/Schemas/DocumentForm.php';
file_put_contents($f, file_get_contents($f) . "\n}\n");
echo "Done\n";
