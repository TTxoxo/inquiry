<?php

declare(strict_types=1);

return [
    'type' => 'file',
    'auto_start' => true,
    'prefix' => 'enterprise_inquiry_admin',
    'var_session_id' => '',
    'store' => '',
    'expire' => 7200,
    'use_trans_sid' => false,
    'cache_limiter' => '',
    'name' => 'enterprise_inquiry_admin',
    'path' => runtime_path('session'),
];
