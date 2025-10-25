<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CAS Services Configuration
    |--------------------------------------------------------------------------
    |
    | Liste des URLs autorisées à utiliser le serveur CAS.
    | Utilisez des regex pour matcher les URLs.
    | IMPORTANT : Échappez les points avec \.
    |
    */

    'cas' => [
        'https://app1\.example\.com/(.*)',
    ],
];