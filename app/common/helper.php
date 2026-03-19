<?php

declare(strict_types=1);

if (!function_exists('root_path')) {
    function root_path(string $path = ''): string
    {
        $root = __DIR__ . '/../../';

        return $path === '' ? $root : $root . ltrim($path, '/');
    }
}

if (!function_exists('runtime_path')) {
    function runtime_path(string $path = ''): string
    {
        $runtime = root_path('runtime/');

        return $path === '' ? $runtime : $runtime . ltrim($path, '/');
    }
}

if (!function_exists('public_path')) {
    function public_path(string $path = ''): string
    {
        $public = root_path('public/');

        return $path === '' ? $public : $public . ltrim($path, '/');
    }
}

if (!function_exists('env')) {
    function env(string $name, mixed $default = null): mixed
    {
        $value = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);

        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        return match (strtolower((string) $value)) {
            'true' => true,
            'false' => false,
            'null' => null,
            default => $value,
        };
    }
}
