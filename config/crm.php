<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Administrador inicial
    |--------------------------------------------------------------------------
    |
    | Credenciales que lee AdminUserSeeder. No hay valores por defecto: si
    | faltan, el seeder falla en lugar de inventar credenciales.
    |
    */

    'admin' => [
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

];
