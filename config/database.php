<?php

declare(strict_types=1);

return [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'type' => env('DATABASE_TYPE', 'mysql'),
            'hostname' => env('DATABASE_HOST', '127.0.0.1'),
            'database' => env('DATABASE_NAME', 'inquiry'),
            'username' => env('DATABASE_USER', 'root'),
            'password' => env('DATABASE_PASSWORD', ''),
            'hostport' => env('DATABASE_PORT', '3306'),
            'charset' => env('DATABASE_CHARSET', 'utf8mb4'),
            'prefix' => env('DATABASE_PREFIX', ''),
            'debug' => env('APP_DEBUG', false),
            'deploy' => 0,
            'rw_separate' => false,
            'master_num' => 1,
            'fields_strict' => true,
            'break_reconnect' => false,
            'trigger_sql' => env('APP_DEBUG', false),
            'params' => [],
        ],
    ],
];
