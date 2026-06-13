<?php

return [
    'document_server_url' => env('ONLYOFFICE_DOCUMENT_SERVER_URL', ''),
    'jwt_secret' => env('ONLYOFFICE_JWT_SECRET', ''),
    'callback_download_timeout' => (int) env('ONLYOFFICE_CALLBACK_DOWNLOAD_TIMEOUT', 60),
];
