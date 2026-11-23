<?php

declare(strict_types=1);

return [

    'driver' => env('HASH_DRIVER', 'bcrypt'),

    'bcrypt' => [
        'rounds' => (int) (env('BCRYPT_ROUNDS') ?: 12),
        'verify' => env('HASH_VERIFY', true),
        // bcrypt silently ignores input beyond 72 bytes; fail loudly instead.
        'limit' => (int) (env('BCRYPT_LIMIT') ?: 72),
    ],

    'argon' => [
        'memory' => (int) (env('ARGON_MEMORY') ?: 65536),
        'threads' => (int) (env('ARGON_THREADS') ?: 1),
        'time' => (int) (env('ARGON_TIME') ?: 4),
        'verify' => env('HASH_VERIFY', true),
    ],

    'rehash_on_login' => true,

];
