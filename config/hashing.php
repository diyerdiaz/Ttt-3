<?php

declare(strict_types=1);

use App\Infrastructure\Support\Env;

return [

    'driver' => env('HASH_DRIVER', 'bcrypt'),

    'bcrypt' => [
        'rounds' => Env::int('BCRYPT_ROUNDS', 12),
        'verify' => env('HASH_VERIFY', true),
        // bcrypt silently ignores input beyond 72 bytes; fail loudly instead.
        'limit' => Env::int('BCRYPT_LIMIT', 72),
    ],

    'argon' => [
        'memory' => Env::int('ARGON_MEMORY', 65536),
        'threads' => Env::int('ARGON_THREADS', 1),
        'time' => Env::int('ARGON_TIME', 4),
        'verify' => env('HASH_VERIFY', true),
    ],

    'rehash_on_login' => true,

];
