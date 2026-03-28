<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Demo Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, the application runs in demo mode with pre-seeded data,
    | quick-login buttons, and security restrictions to prevent abuse.
    |
    */

    'enabled' => (bool) env('APP_DEMO', false),

    'stale_minutes' => 60,

    'accounts' => [
        ['label' => 'Admin', 'username' => 'admin', 'password' => 'password'],
        ['label' => 'Helper', 'username' => 'helper', 'password' => 'password'],
    ],

];
