<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CAS OAuth Configuration
    |--------------------------------------------------------------------------
    |
    | Simple lien entre la configuration du fichier env et la méthode config().
    |
    */

    'provider' => env('OAUTH_PROVIDER'),
    'client_id' => env('OAUTH_CLIENT_ID'),
    'client_secret' => env('OAUTH_CLIENT_SECRET'),
    'scopes' => env('OAUTH_SCOPES', 'openid,profile,email'),
    'params' => env('OAUTH_PARAMS', ''),
    'cas_property' => env('CAS_PROPERTY', 'id'),
];