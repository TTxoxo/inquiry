<?php

declare(strict_types=1);

return [
    'type' => 'file',
    'auto_start' => true,
    'prefix' => 'inquiry',
    'var_session_id' => '',
    'store' => '',
    'expire' => 1440,
    'use_trans_sid' => false,
    'cache_limiter' => '',
    'name' => 'inquiry_session',
    'path' => runtime_path('session'),
];
