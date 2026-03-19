<?php

declare(strict_types=1);

return [
    'default' => 'local',
    'disks' => [
        'local' => [
            'type' => 'local',
            'root' => public_path(),
            'url' => '/',
            'visibility' => 'public',
        ],
    ],
];
