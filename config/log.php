<?php

declare(strict_types=1);

return [
    'default' => 'file',
    'channels' => [
        'file' => [
            'type' => 'File',
            'path' => runtime_path('log'),
            'single' => false,
            'apart_level' => [],
            'max_files' => 0,
            'json' => false,
            'format' => '[%s][%s] %s',
            'realtime_write' => false,
            'close' => false,
            'processor' => null,
        ],
    ],
];
