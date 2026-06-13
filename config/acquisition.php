<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Dossier de numérisation (scan folder)
    |--------------------------------------------------------------------------
    | Chemin absolu vers le dossier surveillé par la commande `scan:watch-folder`.
    | Les fichiers déposés ici seront automatiquement importés.
    |
    */
    'scan_folder' => env('ACQUISITION_SCAN_FOLDER') ?: storage_path('app/scan-inbox'),

    /*
    |--------------------------------------------------------------------------
    | Dossier "traité" (scan done)
    |--------------------------------------------------------------------------
    | Après import, les fichiers sont déplacés ici.
    |
    */
    'scan_done_folder' => env('ACQUISITION_SCAN_DONE_FOLDER') ?: storage_path('app/scan-done'),

    /*
    |--------------------------------------------------------------------------
    | OCR — Tesseract
    |--------------------------------------------------------------------------
    | Chemin vers l'exécutable Tesseract. Vide = détection automatique.
    | Téléchargement Windows : https://github.com/UB-Mannheim/tesseract/wiki
    |
    */
    'tesseract_path' => env('TESSERACT_PATH', ''),
    'tesseract_lang' => env('TESSERACT_LANG', 'fra+eng'),

    /*
    |--------------------------------------------------------------------------
    | Import e-mail
    |--------------------------------------------------------------------------
    | Configuration IMAP pour la capture automatique par e-mail.
    |
    */
    'imap' => [
        'host'          => env('IMAP_HOST', ''),
        'port'          => (int) env('IMAP_PORT', 993),
        'encryption'    => env('IMAP_ENCRYPTION', 'ssl'),
        'validate_cert' => (bool) env('IMAP_VALIDATE_CERT', false),
        'username'      => env('IMAP_USERNAME', ''),
        'password'      => env('IMAP_PASSWORD', ''),
        'folder'        => env('IMAP_FOLDER', 'INBOX'),
        'protocol'      => env('IMAP_PROTOCOL', 'imap'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Types de documents disponibles à l'acquisition
    |--------------------------------------------------------------------------
    */
    'document_types' => [
        'Courrier entrant' => 'Courrier entrant',
        'Courrier sortant' => 'Courrier sortant',
        'Contrat'          => 'Contrat',
        'Facture'          => 'Facture',
        'Rapport'          => 'Rapport',
        'Note interne'     => 'Note interne',
        'Formulaire'       => 'Formulaire',
        'Document'         => 'Document',
        'Autre'            => 'Autre',
    ],

    /*
    |--------------------------------------------------------------------------
    | Taille max de fichier (Mo) à l'acquisition
    |--------------------------------------------------------------------------
    */
    'max_file_size_mb' => (int) env('ACQUISITION_MAX_FILE_MB', 50),
];
